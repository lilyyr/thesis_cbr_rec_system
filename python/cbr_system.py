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

# ============================================
# DATABASE CONNECTION
# ============================================

def connect_db():
    """Connect to MySQL database"""
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='rec_ins_cbr'
    )

def load_historical_cases() -> List[Dict]:
    """Load all historical cases from database"""
    conn = connect_db()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("""
        SELECT
            c.id as case_id,
            c.product_id,
            c.feature_vector,
            p.name as product_name
        FROM cases c
        JOIN products p ON c.product_id = p.id
    """)

    cases = cursor.fetchall()
    conn.close()

    # Parse JSON feature vectors
    for case in cases:
        case['feature_vector'] = json.loads(case['feature_vector'])

    return cases

# ============================================
# DATA PREPROCESSING
# ============================================

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
    """
    Preprocess customer data into normalized features
    """
    # Calculate derived fields
    age = calculate_age(data['dob'])
    bmi = calculate_bmi(data['height'], data['weight'])

    data['age'] = age
    data['bmi'] = bmi

    # Calculate health risk
    health_risk = calculate_health_risk(data)

    # Normalize features
    age_norm = normalize_value(age, 18, 70)
    gender_encoded = 1 if data['gender'] == 'male' else 0
    income_norm = normalize_value(data['income'], 0, 100000000)
    dependents_norm = normalize_value(data['num_dependents'], 0, 10)
    bmi_norm = normalize_value(bmi, 15, 40)
    ins_period_norm = normalize_value(data['insurance_period'], 1, 50)
    prem_period_norm = normalize_value(data['premium_payment_period'], 1, 40)
    overseas_encoded = 1 if data['overseas_plans'] else 0
    health_ins_encoded = 1 if data['has_existing_health_insurance'] else 0
    health_risk_norm = normalize_value(health_risk, 0, 25)

    # Financial goals (8 binary features)
    goals = data['financial_goals']
    goal_family = 1 if 'family_protection' in goals else 0
    goal_health = 1 if 'health' in goals else 0
    goal_retirement = 1 if 'retirement' in goals else 0
    goal_education = 1 if 'education' in goals else 0
    goal_critical = 1 if 'critical_illness' in goals else 0
    goal_income = 1 if 'income_protection' in goals else 0
    goal_savings = 1 if 'savings' in goals else 0
    goal_wealth = 1 if 'wealth_protection' in goals else 0

    # Create 18D feature vector
    feature_vector = [
        age_norm, gender_encoded, income_norm, dependents_norm, bmi_norm,
        ins_period_norm, prem_period_norm, overseas_encoded, health_ins_encoded,
        health_risk_norm,
        goal_family, goal_health, goal_retirement, goal_education,
        goal_critical, goal_income, goal_savings, goal_wealth
    ]

    return {
        'feature_vector': feature_vector,
        'age': age,
        'bmi': bmi,
        'health_risk_score': health_risk,
        'preprocessed': {
            'age_normalized': age_norm,
            'income_normalized': income_norm,
            'bmi_normalized': bmi_norm,
            'health_risk_normalized': health_risk_norm
        }
    }

# ============================================
# SIMILARITY ALGORITHMS
# ============================================

def euclidean_distance(new_case: List[float], historical: List[Dict], k: int = 5) -> List[Dict]:
    """Calculate Euclidean Distance similarity"""
    results = []
    new_array = np.array(new_case)

    for hist in historical:
        hist_array = np.array(hist['feature_vector'])
        distance = np.sqrt(np.sum((new_array - hist_array) ** 2))
        similarity = 1 / (1 + distance)

        results.append({
            'case_id': hist['case_id'],
            'product_id': hist['product_id'],
            'product_name': hist['product_name'],
            'similarity': float(similarity),
            'distance': float(distance)
        })

    results.sort(key=lambda x: x['similarity'], reverse=True)
    return results[:k]

def weighted_euclidean(new_case: List[float], historical: List[Dict], k: int = 5) -> List[Dict]:
    """Calculate Weighted Euclidean Distance similarity"""
    # Load weights
    conn = connect_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT weight FROM weights ORDER BY id")
    weight_rows = cursor.fetchall()
    conn.close()

    weights = np.array([float(row['weight']) for row in weight_rows])

    results = []
    new_array = np.array(new_case)

    for hist in historical:
        hist_array = np.array(hist['feature_vector'])
        distance = np.sqrt(np.sum(weights * (new_array - hist_array) ** 2))
        similarity = 1 / (1 + distance)

        results.append({
            'case_id': hist['case_id'],
            'product_id': hist['product_id'],
            'product_name': hist['product_name'],
            'similarity': float(similarity),
            'distance': float(distance)
        })

    results.sort(key=lambda x: x['similarity'], reverse=True)
    return results[:k]

def random_forest_proximity(new_case: List[float], historical: List[Dict], k: int = 5) -> List[Dict]:
    """Calculate Random Forest Proximity"""
    script_dir = os.path.dirname(os.path.abspath(__file__))
    model_path = os.path.join(script_dir, 'models', 'rf_model.pkl')
    cache_path = os.path.join(script_dir, 'models', 'leaf_cache.json')

    if not os.path.exists(model_path):
        print("Warning: RF model not trained")
        return []

    # Load model and cache
    rf_model = joblib.load(model_path)
    with open(cache_path, 'r') as f:
        leaf_cache = json.load(f)

    # Get new case leaves
    new_case_array = np.array([new_case])
    new_leaves = rf_model.apply(new_case_array)[0]

    # NOW IT'S A DICTIONARY!
    leaf_assignments_dict = leaf_cache['leaf_assignments']
    n_trees = rf_model.n_estimators

    # historical_leaves = np.array(leaf_cache['leaf_assignments'])
    # n_trees = rf_model.n_estimators

    results = []

    # for i, hist in enumerate(historical):
    #     hist_leaves = historical_leaves[i]
    #     matches = np.sum(new_leaves == hist_leaves)
    #     proximity = matches / n_trees

    for hist in historical:  # No enumerate needed!
        case_id_str = str(hist['case_id'])

        # Check if this case is in the cache
        if case_id_str not in leaf_assignments_dict:
            continue  # Skip cases not in training set

        hist_leaves = np.array(leaf_assignments_dict[case_id_str])
        matches = np.sum(new_leaves == hist_leaves)
        proximity = matches / n_trees

        results.append({
            'case_id': hist['case_id'],
            'product_id': hist['product_id'],
            'product_name': hist['product_name'],
            'similarity': float(proximity),
            'matches': int(matches),
            'total_trees': n_trees
        })

    results.sort(key=lambda x: x['similarity'], reverse=True)
    return results[:k]

# ============================================
# RESULT AGGREGATION
# ============================================

def aggregate_results(euclidean: List[Dict], weighted: List[Dict], rf: List[Dict]) -> List[Dict]:
    """Aggregate results from all three algorithms"""
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

        methods_used = sum([
            len(scores['euclidean_scores']) > 0,
            len(scores['weighted_scores']) > 0,
            len(scores['rf_scores']) > 0
        ])

        total = euc_avg + weighted_avg + rf_avg
        aggregate = total / 3

        recommendations.append({
            'product_id': pid,
            'product_name': scores['product_name'],
            'euclidean_score': float(euc_avg),
            'weighted_euclidean_score': float(weighted_avg),
            'random_forest_score': float(rf_avg),
            'aggregate_score': float(aggregate),
            'match_percentage': round(aggregate * 100, 2),
            'methods_used': methods_used
        })

    recommendations.sort(key=lambda x: x['aggregate_score'], reverse=True)

    for i, rec in enumerate(recommendations, 1):
        rec['rank'] = i

    return recommendations

# ============================================
# MAIN ENTRY POINT
# ============================================

def main(input_file: str, output_file: str):
    """Main CBR system"""
    print("=" * 60)
    print("CBR System - Processing")
    print("=" * 60)

    # Load input
    with open(input_file, 'r') as f:
        input_data = json.load(f)

    # Preprocess
    print("\n[1/6] Preprocessing...")
    start = datetime.now()
    preprocessed = preprocess_case(input_data)
    preprocess_time = (datetime.now() - start).total_seconds() * 1000

    new_case_vector = preprocessed['feature_vector']

    # Load historical
    print("[2/6] Loading cases...")
    historical = load_historical_cases()
    print(f"      Loaded {len(historical)} cases")

    # Run algorithms
    print("[3/6] Euclidean...")
    start = datetime.now()
    euclidean_results = euclidean_distance(new_case_vector, historical, k=5)
    euc_time = (datetime.now() - start).total_seconds() * 1000

    print("[4/6] Weighted...")
    start = datetime.now()
    weighted_results = weighted_euclidean(new_case_vector, historical, k=5)
    weighted_time = (datetime.now() - start).total_seconds() * 1000

    print("[5/6] Random Forest...")
    start = datetime.now()
    rf_results = random_forest_proximity(new_case_vector, historical, k=5)
    rf_time = (datetime.now() - start).total_seconds() * 1000

    # Aggregate
    print("[6/6] Aggregating...")
    recommendations = aggregate_results(euclidean_results, weighted_results, rf_results)

    total_time = preprocess_time + euc_time + weighted_time + rf_time

    # Output
    output = {
        'success': True,
        'recommendations': recommendations,
        'execution_time': {
            'preprocessing': round(preprocess_time, 2),
            'euclidean': round(euc_time, 2),
            'weighted': round(weighted_time, 2),
            'random_forest': round(rf_time, 2),
            'total': round(total_time, 2)
        },
        'input_data': input_data,
        'feature_vector': new_case_vector,
        'preprocessed': {
            **preprocessed['preprocessed'],
            'age': preprocessed['age'],
            'bmi': preprocessed['bmi'],
            'health_risk_score': preprocessed['health_risk_score']
        },
        'algorithm_results': {
            'euclidean': euclidean_results,
            'weighted': weighted_results,
            'random_forest': rf_results
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
