"""
Complete CBR System - Single Script
All preprocessing, algorithms, and aggregation in one place
"""

import json
import sys
import numpy as np
from datetime import datetime
from sklearn.ensemble import RandomForestClassifier
import joblib
import mysql.connector
from typing import List, Dict, Tuple
import os

# DATABASE CONNECTION
def connect_db():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='rec_ins_cbr'
    )

# DATA PREPROCESSING
def calculate_age(dob: str) -> int:
    """Calculate age from date of birth"""
    birth_year = int(dob.split('-')[0])
    current_year = datetime.now().year
    return current_year - birth_year

def calculate_bmi(height: float, weight: float) -> float:
    """Calculate BMI"""
    height_m = height / 100
    return weight / (height_m ** 2)

def calculate_health_risk(data: Dict) -> float:
    """Calculate health risk score (0-25)"""
    risk = 0.0

    # Health factors with weights
    risk_factors = {
        'weight_change_last_year': 1.0,
        'smoked_last_year': 2.0,
        'hospitalization_last_5_years': 3.0,
        'lab_tests_last_5_years': 1.0,
        'accident_poisoning_last_5_years': 2.5,
        'has_disability': 4.0,
        'has_serious_illness': 5.0,
        'receiving_treatment': 3.0,
        'family_medical_history': 1.5,
        'is_pregnant': 0.5
    }

    for factor, weight in risk_factors.items():
        if data.get(factor, False):
            risk += weight

    # BMI risk
    bmi = data['bmi']
    if bmi < 18.5:
        risk += 2.0
    elif bmi >= 30:
        risk += 3.0
    elif bmi >= 25:
        risk += 1.0

    return risk

def normalize_value(value: float, min_val: float, max_val: float) -> float:
    """Normalize value to 0-1 range"""
    if max_val == min_val:
        return 0.0
    return (value - min_val) / (max_val - min_val)

def preprocess_case(data: Dict) -> Dict:
    # Calculate derived fields
    age = calculate_age(data['dob'])
    bmi = calculate_bmi(data['height'], data['weight'])

    data['age'] = age
    data['bmi'] = bmi

    # Calculate health risk
    health_risk = calculate_health_risk(data)

    conn = connect_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT risk_score FROM occupations WHERE id = %s", (data['occupation_id'],))
    occupation = cursor.fetchone()
    occupation_risk = float(occupation['risk_score'])
    cursor.close()
    conn.close()

    # Normalize features
    age_norm = normalize_value(age, 1, 70)
    gender_encoded = 1 if data['gender'] == 'male' else 0
    marital_encoded = 1 if data['marital_status'] == 'married' else 0
    income_norm = normalize_value(data['income'], 0, 1500000000)
    occupation_risk_norm = normalize_value(occupation_risk, 1,3)
    dependents_norm = normalize_value(data['num_dependents'], 0, 10)
    bmi_norm = normalize_value(bmi, 15, 40)
    ins_period_norm = normalize_value(data['insurance_period'], 1, 50)
    prem_period_norm = normalize_value(data['premium_payment_period'], 1, 40)
    health_risk_norm = normalize_value(health_risk, 0, 25)
    overseas_encoded = 1 if data['overseas_plans'] else 0
    health_ins_encoded = 1 if data['has_existing_health_insurance'] else 0
    high_risk_hobby_encoded = 1 if data['high_risk_hobby'] else 0

    premium_budget_norm = normalize_value(data['premium_budget'], 0, 1000000000)

    relationship_map = {
        'orang tua kandung': 1.0,
        'suami/istri': 0.9,
        'anak kandung': 0.8,
        'adik/kakak kandung': 0.7,
        'nenek/kakek kandung': 0.6,
        'cucu/cicit': 0.5,
        'lainnya': 0.3
    }

    beneficiary_encoded = relationship_map.get(data['beneficiary_relationship'], 0.3)

    goals = data['financial_goals']
    goal_family = 1 if 'family_protection' in goals else 0
    goal_health = 1 if 'health' in goals else 0
    goal_retirement = 1 if 'retirement' in goals else 0
    goal_education = 1 if 'education' in goals else 0
    goal_critical = 1 if 'critical_illness' in goals else 0
    goal_income = 1 if 'income_protection' in goals else 0
    goal_savings = 1 if 'savings' in goals else 0
    goal_wealth = 1 if 'wealth_protection' in goals else 0

    feature_vector = [
        age_norm, gender_encoded, marital_encoded, income_norm, occupation_risk_norm,
        dependents_norm, bmi_norm, ins_period_norm, prem_period_norm, health_risk_norm,
        overseas_encoded, health_ins_encoded, high_risk_hobby_encoded,
        premium_budget_norm, beneficiary_encoded,
        goal_family, goal_health, goal_retirement, goal_education,
        goal_critical, goal_income, goal_savings, goal_wealth
    ]

    return {
        'feature_vector': feature_vector,
        'health_risk_score': health_risk
    }

# SIMILARITY ALGORITHMS
def euclidean_distance(new_case: np.ndarray, historical_cases: np.ndarray, case_info, k: int = 5) -> List[Dict]:
    similarities = []
    results = []

    for i, hist_case in enumerate(historical_cases):
        # Calculate squared differences for each feature
        diff_squared = (new_case - hist_case) ** 2
        sum_squared = np.sum(diff_squared)
        distance = np.sqrt(sum_squared)
        similarity = 1 / (1 + distance)

        similarities.append({
            'case_id': case_info[i]['id'],
            'product_id': case_info[i]['product_id'],
            'product_name': case_info[i]['product_name'],
            'similarity': float(similarity),
            'distance': float(distance),
            'sum_squared_diff': float(sum_squared),
            'feature_differences': [float(d) for d in diff_squared],
            'historical_vector': case_info[i]['feature_vector']
        })

    similarities.sort(key=lambda x: x['similarity'], reverse=True)

    results = []
    for case in similarities:
        product_id = case['product_id']
        if product_id not in [r['product_id'] for r in results]:
            results.append(case)

    return results[:k]

def weighted_euclidean(new_case: np.ndarray, historical_cases: np.ndarray, case_info, k: int = 5) -> List[Dict]:
    conn = connect_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT feature_name, weight FROM weights ORDER BY id")
    weight_rows = cursor.fetchall()
    conn.close()

    weights = np.array([float(row['weight']) for row in weight_rows])

    similarities = []

    for i, hist_case in enumerate(historical_cases):
        diff = new_case - hist_case
        weighted_diff_squared = weights * (diff ** 2)
        sum_weighted_squared = np.sum(weighted_diff_squared)
        distance = np.sqrt(sum_weighted_squared)
        similarity = 1 / (1 + distance)

        similarities.append({
            'case_id': case_info[i]['id'],
            'product_id': case_info[i]['product_id'],
            'product_name': case_info[i]['product_name'],
            'similarity': float(similarity),
            'distance': float(distance),
            'sum_weighted_squared': float(sum_weighted_squared),
            'weighted_differences': [float(d) for d in weighted_diff_squared],
            'historical_vector': case_info[i]['feature_vector'],
    })

    similarities.sort(key=lambda x: x['similarity'], reverse=True)

    results = []
    for case in similarities:
        product_id = case['product_id']
        if product_id not in [r['product_id'] for r in results]:
            results.append(case)

    cursor.close()
    return results[:k]

def random_forest_proximity(new_case: np.ndarray, historical_cases: np.ndarray, case_info, k: int = 5) -> List[Dict]:
    script_dir = os.path.dirname(__file__)
    model_path = os.path.join(script_dir, 'models', 'rf_model.pkl')
    cache_path = os.path.join(script_dir, 'models', 'leaf_cache.json')

    if not os.path.exists(model_path):
        print("Warning: RF model not trained")
        return []

    # Load model and cache
    rf_model = joblib.load(model_path)
    with open(cache_path, 'r') as f:
        leaf_cache = json.load(f)

    # Get leaf nodes for new case
    new_leaves = rf_model.apply(new_case.reshape(1, -1))[0]

    # NOW IT'S A DICTIONARY!
    leaf_assignments_dict = leaf_cache['leaf_assignments']
    n_trees = rf_model.n_estimators

    similarities = []

    for i, hist in enumerate(historical_cases):
        case_id_str = str(case_info[i]['id'])

        if case_id_str in leaf_assignments_dict:
            hist_leaves = np.array(leaf_assignments_dict[case_id_str])
        else:
            hist_leaves = rf_model.apply(hist.reshape(1, -1))[0]

        matches = np.sum(new_leaves == hist_leaves)
        proximity = matches / n_trees


        tree_matches = [
            {
                'tree_id': j + 1,
                'new_leaf': int(new_leaves[j]),
                'hist_leaf': int(hist_leaves[j]),
                'match': bool(new_leaves[j] == hist_leaves[j])
            }
            for j in range(len(new_leaves[:10]))
        ]

        similarities.append({
            'case_id': case_info[i]['id'],
            'product_id': case_info[i]['product_id'],
            'product_name': case_info[i]['product_name'],
            'similarity': float(proximity),
            'matches': int(matches),
            'total_trees': n_trees,
            'new_case_leaves': [int(l) for l in new_leaves[:10]],
            'historical_case_leaves': [int(l) for l in hist_leaves[:10]],
            'tree_by_tree_matches': tree_matches
        })

    similarities.sort(key=lambda x: x['similarity'], reverse=True)

    results = []
    for case in similarities:
        product_id = case['product_id']
        if product_id not in [r['product_id'] for r in results]:
            results.append(case)

    return results[:k]

# RESULT AGGREGATION
def aggregate_results(euclidean: List[Dict], weighted: List[Dict], rf: List[Dict]) -> List[Dict]:
    product_scores = {}

    # Add scores from each algorithm
    for result in euclidean:
        pid = result['product_id']
        if pid not in product_scores:
            product_scores[pid] = {
                'product_id': pid,
                'product_name': result['product_name'],
                'euclidean_scores': [],
                'weighted_scores': [],
                'rf_scores': []
            }
        product_scores[pid]['euclidean_scores'].append(result['similarity'])

    for result in weighted:
        pid = result['product_id']
        if pid not in product_scores:
            product_scores[pid] = {
                'product_id': pid,
                'product_name': result['product_name'],
                'euclidean_scores': [],
                'weighted_scores': [],
                'rf_scores': []
            }
        product_scores[pid]['weighted_scores'].append(result['similarity'])

    for result in rf:
        pid = result['product_id']
        if pid not in product_scores:
            product_scores[pid] = {
                'product_id': pid,
                'product_name': result['product_name'],
                'euclidean_scores': [],
                'weighted_scores': [],
                'rf_scores': []
            }
        product_scores[pid]['rf_scores'].append(result['similarity'])

    # Calculate aggregates
    recommendations = []

    for pid, scores in product_scores.items():
        euc_avg = np.mean(scores['euclidean_scores']) if scores['euclidean_scores'] else 0
        weighted_avg = np.mean(scores['weighted_scores']) if scores['weighted_scores'] else 0
        rf_avg = np.mean(scores['rf_scores']) if scores['rf_scores'] else 0

        total = euc_avg + weighted_avg + rf_avg
        aggregate = total / 3

        recommendations.append({
            'product_id': pid,
            'product_name': scores['product_name'],
            'euclidean_score': float(euc_avg),
            'weighted_euclidean_score': float(weighted_avg),
            'random_forest_score': float(rf_avg),
            'aggregate_score': float(aggregate),
            'match_percentage': round(aggregate * 100, 2)
        })

    recommendations.sort(key=lambda x: x['aggregate_score'], reverse=True)

    for i, rec in enumerate(recommendations, 1):
        rec['rank'] = i

    return recommendations

def get_feature_weights():
    conn = connect_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT feature_name, weight FROM weights ORDER BY id")
    weights = cursor.fetchall()
    cursor.close()
    conn.close()
    return [{'feature': w['feature_name'], 'weight': float(w['weight'])} for w in weights]

# MAIN
def main(input_file: str, output_file: str):
    """Main CBR system"""
    print("=" * 60)
    print("CBR System - Processing")
    print("=" * 60)

    # Load input
    with open(input_file, 'r') as f:
        input_data = json.load(f)

    preprocessed = preprocess_case(input_data)

    # Load historical cases
    conn = connect_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("""
        SELECT c.*, p.name as product_name
        FROM cases c
        JOIN products p ON c.product_id = p.id
    """)
    historical_cases = cursor.fetchall()

    # Extract feature vectors
    historical_vectors = []
    case_info = []
    for case in historical_cases:
        vector = json.loads(case['feature_vector'])
        historical_vectors.append(vector)
        case_info.append({
            'id': case['id'],
            'product_id': case['product_id'],
            'product_name': case['product_name'],
            'feature_vector': vector
        })

    historical_vectors = np.array(historical_vectors)
    new_case_vector = np.array(preprocessed['feature_vector'])

    start = datetime.now()
    euclidean_results = euclidean_distance(new_case_vector, historical_vectors, case_info, k=5)
    euc_time = (datetime.now() - start).total_seconds() * 1000

    start = datetime.now()
    weighted_results = weighted_euclidean(new_case_vector, historical_vectors, case_info, k=5)
    weighted_time = (datetime.now() - start).total_seconds() * 1000

    start = datetime.now()
    rf_results = random_forest_proximity(new_case_vector, historical_vectors, case_info, k=5)
    rf_time = (datetime.now() - start).total_seconds() * 1000

    print("[6/6] Aggregating...")
    recommendations = aggregate_results(euclidean_results, weighted_results, rf_results)

    total_time = euc_time + weighted_time + rf_time

    output = {
        'success': True,
        'input_data': input_data,
        'feature_vector': preprocessed['feature_vector'],
        'health_risk_score': preprocessed['health_risk_score'],
        'recommendations': recommendations,
        'execution_time': {
            'euclidean': round(euc_time, 2),
            'weighted': round(weighted_time, 2),
            'random_forest': round(rf_time, 2),
            'total': round(total_time, 2)
        },
        'algorithm_details': {
            'euclidean': {
                'top_5_matches': euclidean_results,
                # 'formula': 'd(x,y) = sqrt(sum((xi - yi)^2))',
                # 'similarity_formula': 'similarity = 1 / (1 + distance)',
            },
            'weighted_euclidean': {
                'top_5_matches': weighted_results,
                # 'formula': 'dw(x,y) = sqrt(sum(wi * (xi - yi)^2))',
                # 'similarity_formula': 'similarity = 1 / (1 + weighted_distance)',
                'weights_used': get_feature_weights()
            },
            'random_forest': {
                'top_5_matches': rf_results,
                # 'proximity_formula': 'proximity = matching_leaves / total_trees',
                'model_info': {
                    'n_estimators': 100,
                    'max_depth': 10,
                    'random_state': 42
                }
            }
        }
    }

    with open(output_file, 'w') as f:
        json.dump(output, f, indent=2)

    print(f"\n✓ Complete! Top: {recommendations[0]['product_name']} ({recommendations[0]['match_percentage']}%)")
    print("=" * 60)

if __name__ == '__main__':
    if len(sys.argv) != 3:
        print("Usage: python cbr_system.py <input.json> <output.json>")
        sys.exit(1)

    main(sys.argv[1], sys.argv[2])
