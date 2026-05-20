import json
import sys
import os
import numpy as np
import joblib
from sklearn.tree import plot_tree, export_text
import matplotlib.pyplot as plt
import matplotlib
matplotlib.use('Agg')  # Non-GUI backend

def visualize_trees(case_id, num_trees=3):
    try:
        # Paths
        model_path = os.path.join(os.path.dirname(__file__), 'models', 'rf_model.pkl')
        output_dir = os.path.join(os.path.dirname(__file__), '..', 'public', 'tree_visualizations')

        # Create output directory
        os.makedirs(output_dir, exist_ok=True)

        # Load model
        if not os.path.exists(model_path):
            raise Exception("Random Forest model not found. Train the model first.")

        rf_model = joblib.load(model_path)

        feature_names = [
            'age_norm', 'gender_enc', 'marital_enc', 'income_norm', 'occupation_risk_norm',
            'dependents_norm', 'bmi_norm', 'ins_period_norm', 'prem_period_norm',
            'health_risk_norm', 'overseas_enc', 'has_health_ins_enc',
            'high_risk_hobby_enc', 'premium_budget_enc', 'beneficiary_enc',
            'goal_family', 'goal_health', 'goal_retire', 'goal_edu',
            'goal_critical', 'goal_income', 'goal_savings', 'goal_wealth'
        ]
        # Load case data if case_id provided
        case_vector = None

        if case_id:
            import mysql.connector
            from mysql.connector import Error

            try:
                conn = mysql.connector.connect(
                    host='localhost',
                    database='rec_ins_cbr',
                    user='root',
                    password=''
                )

                cursor = conn.cursor(dictionary=True)
                cursor.execute("SELECT feature_vector FROM cases WHERE id = %s", (case_id,))
                result = cursor.fetchone()

                if result:
                    case_vector = np.array(json.loads(result['feature_vector'])).reshape(1, -1)

                cursor.close()
                conn.close()

            except Error as e:
                print(f"Database error: {e}")

        # Visualize specified number of trees
        tree_info = []

        for i in range(min(num_trees, len(rf_model.estimators_))):
            tree = rf_model.estimators_[i]
            tree_structure = tree.tree_

            max_depth = tree.get_depth()
            num_nodes = tree_structure.node_count

            # width = max(40, 8 * (max_depth / 1.5))
            # height = max(25, 5 * (max_depth / 1.5))

            width = max(40, 8 * max_depth)
            height = max(25, 5 * max_depth)

            fig, ax = plt.subplots(figsize=(width, height))

            # Adjust subplot to give more room
            plt.subplots_adjust(left=0.05, right=0.95, top=0.95, bottom=0.05)

            plot_tree(
                tree,
                feature_names=feature_names,
                filled=True,
                rounded=True,
                fontsize=9,
                ax=ax,
                proportion=False,
                precision=3,
                node_ids=True,
                impurity=True,
                class_names=None,
                label='all',
                max_depth=None
            )

            plt.title(f'Decision Tree #{i+1} from Random Forest', fontsize=16, fontweight='bold', pad=20)

            #dpi controls image resolution (i tried 100 and it seemed a bit blurry to me, 200 looks better)
            #bbok_inches so no useless white space around the tree
            #facecolor is just canvas bg color, white is clearer
            tree_filename = f'tree_{case_id}_{i+1}.png'
            tree_path = os.path.join(output_dir, tree_filename)
            plt.savefig(tree_path, dpi=200, bbox_inches='tight', facecolor='white')
            plt.close()

            # Get the path this case takes through the tree
            if case_vector is not None:
                node_indicator = tree.decision_path(case_vector)
                leaf_id = tree.apply(case_vector)[0]

                # Get the path (node indices)
                node_index = node_indicator.indices[node_indicator.indptr[0]:node_indicator.indptr[1]]

                # Extract decision rules along the path
                path_rules = []
                for idx, node_id in enumerate(node_index):
                    if tree_structure.feature[node_id] != -2:  # Not a leaf
                        feature = feature_names[tree_structure.feature[node_id]]
                        threshold = tree_structure.threshold[node_id]

                        feature_idx = tree_structure.feature[node_id]
                        feature_value = float(case_vector[0][feature_idx])

                        # Determine which branch was taken
                        if idx < len(node_index) - 1:
                            next_node = node_index[idx + 1]
                            if next_node == tree_structure.children_left[node_id]:
                                decision = "≤"
                            else:
                                decision = ">"

                            path_rules.append({
                                'node': int(node_id),
                                'feature': feature,
                                'threshold': float(threshold),
                                'decision': decision,
                                'rule': f"{feature} = {feature_value:.4f} {decision} {threshold:.4f}"
                            })

                tree_info.append({
                    'tree_number': i + 1,
                    'image_filename': tree_filename,
                    'leaf_node': int(leaf_id),
                    'path_rules': path_rules,
                    'total_nodes': int(tree_structure.node_count),
                    'max_depth': int(tree.get_depth())
                })
            else:
                tree_info.append({
                    'tree_number': i + 1,
                    'image_filename': tree_filename,
                    'total_nodes': int(tree_structure.node_count),
                    'max_depth': int(tree.get_depth())
                })

        result = {
            'success': True,
            'trees': tree_info,
            'num_trees_visualized': len(tree_info),
            'output_directory': output_dir
        }

        return result

    except Exception as e:
        return {
            'success': False,
            'error': str(e),
            'traceback': traceback.format_exc()
        }

if __name__ == '__main__':
    if len(sys.argv) < 2:
        print(json.dumps({'success': False, 'error': 'Case ID required'}))
        sys.exit(1)

    case_id = int(sys.argv[1])
    num_trees = int(sys.argv[2]) if len(sys.argv) > 2 else 3

    result = visualize_trees(case_id, num_trees)
    print(json.dumps(result, indent=2))
