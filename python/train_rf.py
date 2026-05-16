"""
Train Random Forest Model on Historical Cases
"""

import json
import numpy as np
from sklearn.ensemble import RandomForestClassifier
import joblib
import mysql.connector
import os

def connect_db():
    """Connect to MySQL database"""
    return mysql.connector.connect(
        host='localhost',
        user='root',
        password='',
        database='rec_ins_cbr'
    )

def load_training_data():
    """Load all cases from database"""
    conn = connect_db()
    cursor = conn.cursor(dictionary=True)

    cursor.execute("""
        SELECT
            id,
            product_id,
            feature_vector
        FROM cases
    """)

    cases = cursor.fetchall()
    conn.close()

    print(f"Loaded {len(cases)} training cases")

    # Parse JSON feature vectors
    X = []
    y = []
    case_ids = []

    for case in cases:
        X.append(json.loads(case['feature_vector']))
        y.append(case['product_id'])
        case_ids.append(case['id'])

    return np.array(X), np.array(y), case_ids

def train_model(X, y):
    """Train Random Forest model"""
    print("\nTraining Random Forest...")
    print(f"  Features: {X.shape[1]} dimensions")
    print(f"  Samples: {X.shape[0]}")
    print(f"  Classes: {len(np.unique(y))} products")

    # Train model
    rf = RandomForestClassifier(
        n_estimators=100,
        max_depth=10,
        random_state=42,
        n_jobs=-1
    )

    rf.fit(X, y)

    print("Training complete!")
    return rf

def generate_leaf_cache(rf, X, case_ids):
    """Generate leaf node assignments for all training cases"""
    print("\nGenerating leaf cache...")

    # Get leaf assignments for all cases
    leaf_assignments = rf.apply(X)

    print(f"Generated leaf assignments: {leaf_assignments.shape}")

    # CREATE DICTIONARY KEYED BY CASE_ID
    leaf_dict = {}
    for i, case_id in enumerate(case_ids):
        leaf_dict[str(case_id)] = leaf_assignments[i].tolist()  # Convert to string for JSON

    return leaf_dict

    # return leaf_assignments.tolist()

def save_model(rf, leaf_cache_dict):
    """Save model and leaf cache"""
    script_dir = os.path.dirname(os.path.abspath(__file__))
    models_dir = os.path.join(script_dir, 'models')

    # Create models directory if not exists
    if not os.path.exists(models_dir):
        os.makedirs(models_dir)

    # Save model
    model_path = os.path.join(models_dir, 'rf_model.pkl')
    joblib.dump(rf, model_path)
    print(f"Model saved to: {model_path}")

    # Save leaf cache
    cache_path = os.path.join(models_dir, 'leaf_cache.json')
    cache_data = {
        'leaf_assignments': leaf_cache_dict, # Now a dictionary!
        'n_trees': rf.n_estimators,
        'n_cases': len(leaf_cache_dict)
    }

    with open(cache_path, 'w') as f:
        json.dump(cache_data, f)

    print(f"Leaf cache saved to: {cache_path}")

def main():
    """Main training function"""
    print("=" * 60)
    print("Random Forest Training Script")
    print("=" * 60)

    # Load data
    X, y, case_ids = load_training_data()

    # Train model
    rf = train_model(X, y)

    # # Generate leaf cache
    # leaf_assignments = generate_leaf_cache(rf, X)

    # Generate leaf cache - PASS case_ids!
    leaf_cache_dict = generate_leaf_cache(rf, X, case_ids)

    # Save
    save_model(rf, leaf_cache_dict)

    print("\n" + "=" * 60)
    print("Training Complete!")
    print("=" * 60)

if __name__ == '__main__':
    main()
