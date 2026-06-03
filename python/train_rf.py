import json
import numpy as np
from sklearn.ensemble import RandomForestClassifier
import joblib
import os
from cbr_system import connect_db, load_historical_cases

def load_training_data():
    cases = load_historical_cases()
    print(f"Loaded {len(cases)} training cases")

    # Parse JSON feature vectors
    X = []
    y = []
    case_ids = []

    for case in cases:
        X.append(case['feature_vector'])
        y.append(case['product_id'])
        case_ids.append(case['id'])

    return np.array(X), np.array(y), case_ids

def train_model(X, y):
    print("\nTraining Random Forest...")
    print(f"  Features: {X.shape[1]} dimensions")
    print(f"  Samples: {X.shape[0]}")
    print(f"  Classes: {len(np.unique(y))} products")

    # Train model
    rf = RandomForestClassifier(
        n_estimators=100,
        max_depth=10,
        random_state=42,
        n_jobs=-1 #this determines speed, -1 means uses all my computer's cores so rf commands runs faster, can tweak this to compare speeds
    )

    rf.fit(X, y)

    print("Training complete!")
    return rf

def generate_leaf_cache(rf, X, case_ids):
    print("\nGenerating leaf cache...")
    leaf_assignments = rf.apply(X)

    print(f"Generated leaf assignments: {leaf_assignments.shape}")

    # CREATE DICTIONARY KEYED BY CASE_ID
    leaf_dict = {}
    for i, case_id in enumerate(case_ids):
        leaf_dict[str(case_id)] = leaf_assignments[i].tolist()  # Convert to string for JSON

    return leaf_dict

def save_model(rf, leaf_cache):
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
        'leaf_assignments': leaf_cache,
        'n_trees': rf.n_estimators,
        'n_cases': len(leaf_cache_dict)
    }

    with open(cache_path, 'w') as f:
        json.dump(cache_data, f)

    print(f"Leaf cache saved to: {cache_path}")

def main():
    print("=" * 60)
    print("Random Forest Training Script")
    print("=" * 60)

    X, y, case_ids = load_training_data()
    rf = train_model(X, y)
    leaf_cache = generate_leaf_cache(rf, X, case_ids)
    save_model(rf, leaf_cache)

    print("\n" + "=" * 60)
    print("Training Complete!")
    print("=" * 60)

if __name__ == '__main__':
    main()
