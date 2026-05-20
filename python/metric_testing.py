#!/usr/bin/env python3
"""
Algorithm Testing Script
Tests CBR algorithms and calculates performance metrics
"""

import sys
import json
import time
import mysql.connector
from datetime import datetime
import numpy as np
from cbr_system import (
    load_all_cases,
    preprocess_case,
    euclidean_similarity,
    weighted_euclidean_similarity,
    random_forest_proximity,
    aggregate_results
)

def get_db_connection():
    """Create database connection"""
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='rec_ins_cbr'
    )

def load_test_cases(cursor):
    """Load test cases with ground truth"""
    cursor.execute("""
        SELECT
            tc.id,
            tc.customer_id,
            tc.correct_product_id,
            tc.feature_vector,
            c.name as customer_name,
            c.gender,
            c.dob,
            c.marital_status,
            c.occupation_id,
            c.income_range,
            c.num_dependents,
            p.name as correct_product_name
        FROM test_cases tc
        JOIN customers c ON tc.customer_id = c.customer_id
        JOIN products p ON tc.correct_product_id = p.id
    """)

    test_cases = []
    for row in cursor.fetchall():
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

def calculate_confusion_matrix(predictions, k=5):
    """
    Calculate TP, FP, TN, FN for all predictions

    predictions: list of dicts with keys:
        - correct_product_id: ground truth
        - top_k_predictions: list of (product_id, score) tuples
        - all_products: list of all possible product IDs
    """
    tp = 0  # Correct product in top-1
    fp = 0  # Wrong product in top-1
    tn = 0  # Irrelevant products correctly excluded
    fn = 0  # Correct product not in top-K

    for pred in predictions:
        correct_id = pred['correct_product_id']
        top_k_ids = [p[0] for p in pred['top_k_predictions'][:k]]
        all_products = pred['all_products']

        # Check top-1 prediction
        if top_k_ids[0] == correct_id:
            tp += 1
        else:
            fp += 1

        # Check if correct product is in top-K
        if correct_id not in top_k_ids:
            fn += 1

        # Count true negatives (irrelevant products not in top-K)
        irrelevant_products = [p for p in all_products if p != correct_id]
        for product_id in irrelevant_products:
            if product_id not in top_k_ids:
                tn += 1

    return tp, fp, tn, fn

def calculate_precision_at_k(predictions, k=5):
    """
    Calculate Precision@K: What proportion of top-K recommendations are relevant?
    """
    precisions = []

    for pred in predictions:
        correct_id = pred['correct_product_id']
        top_k_ids = [p[0] for p in pred['top_k_predictions'][:k]]

        # In our case, only 1 product is "relevant" (the correct one)
        relevant_in_k = 1 if correct_id in top_k_ids else 0
        precision_at_k = relevant_in_k / k
        precisions.append(precision_at_k)

    return np.mean(precisions)

def calculate_mrr(predictions):
    """
    Calculate Mean Reciprocal Rank
    MRR = Average of (1 / rank of first correct result)
    """
    reciprocal_ranks = []

    for pred in predictions:
        correct_id = pred['correct_product_id']
        top_k_ids = [p[0] for p in pred['top_k_predictions']]

        # Find rank of correct product (1-indexed)
        try:
            rank = top_k_ids.index(correct_id) + 1
            reciprocal_ranks.append(1.0 / rank)
        except ValueError:
            # Correct product not in recommendations
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
    training_cases = load_all_cases(cursor)
    print(f"Loaded {len(training_cases)} training cases")

    # Get all product IDs
    cursor.execute("SELECT id FROM products")
    all_product_ids = [row[0] for row in cursor.fetchall()]

    predictions = []
    execution_times = []

    for i, test_case in enumerate(test_cases, 1):
        print(f"Testing case {i}/{len(test_cases)}: {test_case['customer_name']}")

        # Preprocess test case
        new_vector = test_case['feature_vector']

        # Run algorithm and measure time
        start_time = time.time()

        if algorithm_name == 'euclidean':
            similarities = euclidean_similarity(new_vector, training_cases)
        elif algorithm_name == 'weighted_euclidean':
            similarities = weighted_euclidean_similarity(new_vector, training_cases, cursor)
        elif algorithm_name == 'random_forest':
            similarities = random_forest_proximity(new_vector, training_cases)
        else:
            raise ValueError(f"Unknown algorithm: {algorithm_name}")

        execution_time = (time.time() - start_time) * 1000  # Convert to ms
        execution_times.append(execution_time)

        # Get top recommendations
        top_recommendations = aggregate_results(
            similarities['euclidean'] if algorithm_name == 'euclidean' else [],
            similarities['weighted'] if algorithm_name == 'weighted_euclidean' else [],
            similarities['random_forest'] if algorithm_name == 'random_forest' else [],
            cursor
        )

        # Actually, we need to aggregate based on which algorithm we're testing
        # Let me fix this - we should use the specific algorithm's results
        if algorithm_name == 'euclidean':
            sorted_cases = sorted(similarities['euclidean'], key=lambda x: x[1], reverse=True)
        elif algorithm_name == 'weighted_euclidean':
            sorted_cases = sorted(similarities['weighted'], key=lambda x: x[1], reverse=True)
        else:  # random_forest
            sorted_cases = sorted(similarities['random_forest'], key=lambda x: x[1], reverse=True)

        # Get product recommendations
        product_scores = {}
        for case_id, similarity in sorted_cases[:20]:  # Top 20 similar cases
            # Get product for this case
            cursor.execute("SELECT product_id FROM cases WHERE id = %s", (case_id,))
            product_id = cursor.fetchone()[0]

            if product_id not in product_scores:
                product_scores[product_id] = []
            product_scores[product_id].append(similarity)

        # Average scores per product
        product_avg_scores = {
            pid: np.mean(scores) for pid, scores in product_scores.items()
        }

        # Sort products by average score
        sorted_products = sorted(
            product_avg_scores.items(),
            key=lambda x: x[1],
            reverse=True
        )

        predictions.append({
            'test_case_id': test_case['id'],
            'customer_name': test_case['customer_name'],
            'correct_product_id': test_case['correct_product_id'],
            'correct_product_name': test_case['correct_product_name'],
            'top_k_predictions': sorted_products,
            'all_products': all_product_ids,
            'top_1_product_id': sorted_products[0][0] if sorted_products else None,
            'execution_time_ms': execution_time
        })

        # Print immediate result
        top_1_correct = sorted_products[0][0] == test_case['correct_product_id'] if sorted_products else False
        print(f"  → Predicted: Product #{sorted_products[0][0] if sorted_products else 'None'}")
        print(f"  → Actual: Product #{test_case['correct_product_id']} ({test_case['correct_product_name']})")
        print(f"  → Result: {'✓ CORRECT' if top_1_correct else '✗ WRONG'}")
        print(f"  → Execution time: {execution_time:.2f}ms\n")

    # Calculate confusion matrix
    print("\nCalculating metrics...")
    tp, fp, tn, fn = calculate_confusion_matrix(predictions, k=5)

    print(f"\nConfusion Matrix:")
    print(f"  True Positives (TP):  {tp}")
    print(f"  False Positives (FP): {fp}")
    print(f"  True Negatives (TN):  {tn}")
    print(f"  False Negatives (FN): {fn}")

    # Calculate metrics
    precision = tp / (tp + fp) if (tp + fp) > 0 else 0
    recall = tp / (tp + fn) if (tp + fn) > 0 else 0
    f1_score = 2 * (precision * recall) / (precision + recall) if (precision + recall) > 0 else 0
    accuracy = (tp + tn) / (tp + tn + fp + fn) if (tp + tn + fp + fn) > 0 else 0

    precision_at_5 = calculate_precision_at_k(predictions, k=5)
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
        'precision': precision,
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
            precision, recall, f1_score, accuracy, precision_at_5, mrr,
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
        results['precision'],
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
