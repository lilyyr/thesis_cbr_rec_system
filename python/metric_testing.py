import sys
import json
import time
import mysql.connector
from datetime import datetime
import numpy as np
from cbr_system import (
    preprocess_case,
    euclidean_distance,
    weighted_euclidean,
    random_forest_proximity,
    aggregate_results
)

def get_db_connection():
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='rec_ins_cbr'
    )

def load_all_cases(cursor):
    cursor.execute("""
        SELECT c.id, c.customer_id, c.product_id, c.feature_vector, p.name as product_name
        FROM cases c
        JOIN products p ON c.product_id = p.id
        ORDER BY c.id
    """)

    cases = cursor.fetchall()

    result = []
    for case in cases:
        result.append({
            'id': case[0],
            'customer_id': case[1],
            'product_id': case[2],
            'product_name': case[4],
            'feature_vector': json.loads(case[3])
        })

    return result

# this is wrong, i need the input to be like a normal case
def load_test_cases(cursor):
    cursor.execute("""
        SELECT tc.*, p.name as correct_product_name
        FROM test_case tc
        JOIN products p ON tc.correct_product_id = p.id
    """)

    test_cases = []
    for case in cursor.fetchall():
        incomeMap = {
            'below_50m': 25000000,
            '50m_100m': 75000000,
            '100m_300m': 200000000,
            '300m_500m': 400000000,
            '500m_1b': 750000000,
            'above_1b': 1500000000
        }

        income = incomeMap.get(case['income_range'], 0)

        financial_goals = json.loads(case['financlail_goals'])
        coverage_regions = json.loads(case['coverage_regions'])

        test_cases.append({
            'id': row[0],
            'customer_id': row[1],
            'correct_product_id': row[2],
            'feature_vector': json.loads(row[3]),
            'customer_name': row[4],
            'gender': row[5],
            'dob': str(row[6]),
            'marital_status': row[7],
            'occupation_id': row[8],
            'income_range': row[9],
            'num_dependents': row[10],
            'correct_product_name': row[11]
        })

    return test_cases

def calculate_confusion_matrix(predictions):
    tp = 0  # Correct product in top-1
    fp = 0  # Wrong product in top-1
    tn = 0  # Irrelevant products correctly excluded (not in top 1)
    fn = 0  # Correct product not in top-1

    for pred in predictions:
        correct_id = pred['correct_product_id']
        top_k_ids = [p[0] for p in pred['top_k_predictions']]
        all_products = pred['all_products']

        # Check top-1 prediction
        if top_k_ids[0] == correct_id:
            tp += 1
        else:
            fp += 1

        incorrect_products = [p for p in all_products if p != correct_id]
        for product_id in incorrect_products:
            if product_id != top_k_ids[0]:
                tn += 1
            else:
                fn += 1

    return tp, fp, tn, fn

# def calculate_precision_at_k(predictions, k=5):
#     """
#     Calculate Precision@K: What proportion of top-K recommendations are relevant?
#     """
#     precisions = []

#     for pred in predictions:
#         correct_id = pred['correct_product_id']
#         top_k_ids = [p[0] for p in pred['top_k_predictions'][:k]]

#         # In our case, only 1 product is "relevant" (the correct one)
#         relevant_in_k = 1 if correct_id in top_k_ids else 0
#         precision_at_k = relevant_in_k / k
#         precisions.append(precision_at_k)

#     return np.mean(precisions)

def calculate_mrr(predictions):
    # MRR = Average of (1 / rank of first correct result)
    reciprocal_ranks = []

    for pred in prediction:
        correct_id = pred['correct_product_id']
        top_k_ids = [p[0] for p in pred['top_k_predictions']]

        for product_id in top_k_ids:
            if product_id == correct_id:
                rank = top_k_ids.index(product_id) + 1
                reciprocal_ranks.append(1.0 / rank)

        if correct_id not in top_k_ids:
            reciprocal_ranks.append(0.0)

    return np.mean(reciprocal_ranks)

def test_algorithm(algorithm_name, test_cases, cursor):
    """
    Test a single algorithm on all test cases
    """
    print(f"\n{'='*60}")
    print(f"Testing {algorithm_name.upper()}")
    print(f"{'='*60}")

    # Load training cases
    training_info = load_all_cases(cursor)
    training_vectors = np.array([case['feature_vector'] for case in training_info])
    print(f"Loaded {len(training_info)} training cases")

    # Get all product IDs
    cursor.execute("SELECT id FROM products")
    all_product_ids = [row[0] for row in cursor.fetchall()]

    predictions = []
    execution_times = []

    for i, test_case in enumerate(test_cases, 1):
        print(f"Testing case {i}/{len(test_cases)}: {test_case['customer_name']}")

        # Preprocess test case
        new_vector = np.array(test_case['feature_vector'])

        # Run algorithm and measure time
        start_time = time.time()

        if algorithm_name == 'euclidean':
            similarities = euclidean_distance(new_vector, training_vectors, training_info)
        elif algorithm_name == 'weighted_euclidean':
            similarities = weighted_euclidean(new_vector, training_vectors, training_info)
        elif algorithm_name == 'random_forest':
            similarities = random_forest_proximity(new_vector, training_vectors, training_info)
        else:
            raise ValueError(f"Unknown algorithm: {algorithm_name}")

        execution_time = (time.time() - start_time) * 1000  # Convert to ms
        execution_times.append(execution_time)

        #find out if we even need this as compared to using similarities[0]['product_id']
        results = [(s['product_id'], s['product_name'], s['similarity']) for s in similarities]

        predictions.append({
            'test_case_id': test_case['id'],
            'customer_id': test_case['customer_id'],
            'customer_name': test_case['customer_name'],
            'correct_product_id': test_case['correct_product_id'],
            'correct_product_name': test_case['correct_product_name'],
            'top_k_predictions': results,
            'all_products': all_product_ids,
            'top_1_product_id': results[0][0] if results else None,
            'algorithm_details': similarities['algorithm_details'] if 'algorithm_details' in similarities else None,
            'execution_time_ms': execution_time
        })

        top_1_correct = results[0][0] == test_case['correct_product_id'] if results else False
        print(f"  → Predicted: Product #{results[0][0] if results else 'Null'} {results[0][1] if results else 'None'}")
        print(f"  → Actual: Product #{test_case['correct_product_id']} {test_case['correct_product_name']}")
        print(f"  → Result: {'✓ CORRECT' if top_1_correct else '✗ WRONG'}")
        print(f"  → Execution time: {execution_time:.2f}ms\n")

    # Calculate confusion matrix
    print("\nCalculating metrics...")
    tp, fp, tn, fn = calculate_confusion_matrix(predictions)

    print(f"\nConfusion Matrix:")
    print(f"  True Positives (TP):  {tp}")
    print(f"  False Positives (FP): {fp}")
    print(f"  True Negatives (TN):  {tn}")
    print(f"  False Negatives (FN): {fn}")

    # Calculate metrics
    precision = tp / (tp + fp)
    recall = tp / (tp + fn)
    f1_score = 2 * (precision * recall) / (precision + recall)
    accuracy = (tp + tn) / (tp + tn + fp + fn)

    # precision_at_5 = calculate_precision_at_k(predictions, k=5)
    mrr = calculate_mrr(predictions)

    avg_execution_time = np.mean(execution_times)
    total_execution_time = np.sum(execution_times)

    print(f"\nMetrics:")
    print(f"  Precision:     {precision:.4f} ({precision*100:.2f}%)")
    print(f"  Recall:        {recall:.4f} ({recall*100:.2f}%)")
    print(f"  F1-Score:      {f1_score:.4f} ({f1_score*100:.2f}%)")
    print(f"  Accuracy:      {accuracy:.4f} ({accuracy*100:.2f}%)")
    print(f"  Precision@5:   {precision_at_5:.4f} ({precision_at_5*100:.2f}%)")
    print(f"  MRR:           {mrr:.4f} ({mrr*100:.2f}%)")
    print(f"\nPerformance:")
    print(f"  Avg Time:      {avg_execution_time:.2f}ms")
    print(f"  Total Time:    {total_execution_time:.2f}ms")

    return {
        'algorithm_name': algorithm_name,
        'test_run_date': datetime.now(),
        'total_test_cases': len(test_cases),
        'true_positives': tp,
        'false_positives': fp,
        'true_negatives': tn,
        'false_negatives': fn,
        'precision_score': precision,
        'recall': recall,
        'f1_score': f1_score,
        'accuracy': accuracy,
        'precision_at_5': precision_at_5,
        'mrr': mrr,
        'avg_execution_time_ms': avg_execution_time,
        'total_execution_time_ms': total_execution_time,
        'detailed_results': predictions
    }

def save_results(results, cursor, conn):
    """Save test results to database"""
    cursor.execute("""
        INSERT INTO algorithm_test_results (
            algorithm_name, test_run_date, total_test_cases,
            true_positives, false_positives, true_negatives, false_negatives,
            precision_score, recall, f1_score, accuracy, precision_at_5, mrr,
            avg_execution_time_ms, total_execution_time_ms, detailed_results,
            created_at, updated_at
        ) VALUES (
            %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s, %s
        )
    """, (
        results['algorithm_name'],
        results['test_run_date'],
        results['total_test_cases'],
        results['true_positives'],
        results['false_positives'],
        results['true_negatives'],
        results['false_negatives'],
        results['precision_score'],
        results['recall'],
        results['f1_score'],
        results['accuracy'],
        results['precision_at_5'],
        results['mrr'],
        results['avg_execution_time_ms'],
        results['total_execution_time_ms'],
        json.dumps(results['detailed_results']),
        datetime.now(),
        datetime.now()
    ))
    conn.commit()

def main():
    """Main testing function"""
    print("="*60)
    print("CBR ALGORITHM TESTING SUITE")
    print("="*60)

    # Connect to database
    conn = get_db_connection()
    cursor = conn.cursor()

    # Load test cases
    print("\nLoading test cases...")
    test_cases = load_test_cases(cursor)
    print(f"Loaded {len(test_cases)} test cases")

    if len(test_cases) == 0:
        print("ERROR: No test cases found. Please create test cases first.")
        sys.exit(1)

    # Test each algorithm
    algorithms = ['euclidean', 'weighted_euclidean', 'random_forest']
    all_results = []

    for algorithm in algorithms:
        try:
            results = test_algorithm(algorithm, test_cases, cursor)
            all_results.append(results)
            save_results(results, cursor, conn)
            print(f"\n✓ Results saved for {algorithm}")
        except Exception as e:
            print(f"\n✗ Error testing {algorithm}: {str(e)}")
            import traceback
            traceback.print_exc()

    # Print comparison
    print("\n" + "="*60)
    print("COMPARISON SUMMARY")
    print("="*60)
    print(f"\n{'Metric':<20} {'Euclidean':<15} {'Weighted':<15} {'Random Forest':<15}")
    print("-" * 65)

    metrics = ['precision', 'recall', 'f1_score', 'accuracy', 'precision_at_5', 'mrr']
    for metric in metrics:
        values = [f"{r[metric]*100:.2f}%" for r in all_results]
        print(f"{metric.replace('_', ' ').title():<20} {values[0]:<15} {values[1]:<15} {values[2]:<15}")

    print("\nPerformance:")
    for i, r in enumerate(all_results):
        print(f"{algorithms[i]:<20} Avg: {r['avg_execution_time_ms']:.2f}ms  Total: {r['total_execution_time_ms']:.2f}ms")

    # Determine best algorithm
    print("\n" + "="*60)
    print("BEST ALGORITHM BY METRIC:")
    print("="*60)
    for metric in metrics:
        best_idx = max(range(len(all_results)), key=lambda i: all_results[i][metric])
        print(f"{metric.replace('_', ' ').title():<20} → {algorithms[best_idx].upper()} ({all_results[best_idx][metric]*100:.2f}%)")

    cursor.close()
    conn.close()

    print("\n✓ Testing complete!")

if __name__ == '__main__':
    main()
