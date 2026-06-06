import os
import numpy as np
import matplotlib.pyplot as plt
import mysql.connector

os.makedirs('charts', exist_ok=True)

def get_db():
    return mysql.connector.connect(
        host='localhost', user='root', password='', database='rec_ins_cbr'
    )

def load_results():
    conn   = get_db()
    cursor = conn.cursor(dictionary=True)
    cursor.execute("SELECT * FROM algorithm_test_results ORDER BY created_at DESC")
    rows   = cursor.fetchall()
    cursor.close()
    conn.close()
    for row in rows:
        for col in ['precision_score','recall','f1_score','accuracy','mrr','avg_time_taken']:
            row[col] = float(row[col] or 0)
    return rows

rows    = load_results()
splits  = ['80_20', '70_30']
metrics = ['precision_score', 'recall', 'f1_score', 'accuracy', 'mrr', 'hr_at_3', 'hr_at_5']
mlabels = ['Precision', 'Recall', 'F1', 'Accuracy', 'MRR', 'HR@3', 'HR@5',]

def rf_rows(split=None):
    """All RF rows, optionally filtered by split."""
    return [r for r in rows
            if r['algorithm_name'] == 'random_forest'
            and (split is None or r['split_ratio'] == split)]

def best_rf(split):
    candidates = rf_rows(split)
    return max(candidates, key=lambda r: r['f1_score']) if candidates else {}

def get_dist(alg, split):
    return next((r for r in rows
                 if r['algorithm_name'] == alg and r['split_ratio'] == split), None)

def avg_metric(subset, metric):
    """Average a metric over a list of rows, as a percentage."""
    if not subset:
        return 0
    return sum(r[metric] for r in subset) / len(subset) * 100

# ── Chart 1: Algorithm comparison ────────────────────────────────────────────
# Bar chart: Euclidean / Weighted / RF-best  for each metric, grouped by split.

fig, axes = plt.subplots(1, 5, figsize=(18, 4))
fig.suptitle('Algorithm Comparison (RF = best configuration)')

algs      = ['euclidean', 'weighted_euclidean', 'random_forest']
alg_names = ['Euclidean', 'Weighted', 'RF best']
x         = np.arange(3)
w         = 0.3

for i, (metric, label) in enumerate(zip(metrics, mlabels)):
    ax = axes[i]
    for j, split in enumerate(splits):
        vals = []
        for alg in algs:
            r = best_rf(split) if alg == 'random_forest' else get_dist(alg, split)
            vals.append(float(r[metric]) * 100 if r else 0)
        ax.bar(x + j * w, vals, w, label=split.replace('_', '/'))
        for xi, v in zip(x + j * w, vals):
            if v > 0:
                ax.text(xi, v + 0.5, f'{v:.1f}', ha='center', fontsize=7)
    ax.set_title(label)
    ax.set_xticks(x + w / 2)
    ax.set_xticklabels(alg_names, fontsize=8)
    ax.set_ylim(0, 110)
    ax.set_ylabel('%')
    if i == 0:
        ax.legend(fontsize=8)

plt.tight_layout()
plt.savefig('charts/chart1_comparison.png')
plt.close()
print('Saved chart1_comparison.png')

# ── Chart 2: Isolated effect of each RF parameter ────────────────────────────
# For each parameter, average F1 over ALL other parameter combinations.
# This shows the general trend of each parameter in isolation.
# 4 subplots (one per parameter), 2 bars per x-tick (one per split).

params = {
    'n_estimators':    ([100, 200, 300],           ['100', '200', '300']),
    'max_depth':       ([5, 10, None],              ['5', '10', 'None']),
    'max_features':    (['sqrt', 'log2', None],     ['sqrt', 'log2', 'None']),
    'min_samples_leaf':([1, 2, 3],                  ['1', '2', '3']),
}

fig, axes = plt.subplots(1, 4, figsize=(16, 4))
fig.suptitle('RF: Average F1 by Parameter Value (averaged over all other combinations)')

for ax, (param_name, (values, xlabels)) in zip(axes, params.items()):
    x = np.arange(len(values))
    w = 0.3
    for j, split in enumerate(splits):
        avgs = []
        for val in values:
            subset = [r for r in rf_rows(split) if r.get(param_name) == val]
            avgs.append(avg_metric(subset, 'f1_score'))
        ax.bar(x + j * w, avgs, w, label=split.replace('_', '/'))
        for xi, v in zip(x + j * w, avgs):
            if v > 0:
                ax.text(xi, v + 0.5, f'{v:.1f}', ha='center', fontsize=7)
    ax.set_title(param_name.replace('_', ' ').title())
    ax.set_xticks(x + w / 2)
    ax.set_xticklabels(xlabels)
    ax.set_ylim(0, 110)
    ax.set_ylabel('Avg F1 (%)')
    ax.legend(fontsize=7)

plt.tight_layout()
plt.savefig('charts/chart2_param_effects.png')
plt.close()
print('Saved chart2_param_effects.png')

# ── Chart 3: Top 10 RF combinations by F1 ────────────────────────────────────
# Horizontal bar chart, one subplot per split.
# Makes it easy to see which specific combination performs best overall.

fig, axes = plt.subplots(1, 2, figsize=(16, 6), sharey=False)
fig.suptitle('Top 10 RF Combinations by F1-Score')

for ax, split in zip(axes, splits):
    candidates = sorted(rf_rows(split), key=lambda r: r['f1_score'], reverse=True)[:10]

    labels = [
        f"n={r['n_estimators']}  d={r['max_depth'] or 'None'}  "
        f"f={r['max_features'] or 'None'}  msl={r['min_samples_leaf']}"
        for r in candidates
    ]
    f1_vals  = [r['f1_score']       * 100 for r in candidates]
    acc_vals = [r['accuracy']       * 100 for r in candidates]
    mrr_vals = [r['mrr']            * 100 for r in candidates]

    y = np.arange(len(labels))
    h = 0.25

    ax.barh(y + h,   f1_vals,  h, label='F1')
    ax.barh(y,       acc_vals, h, label='Accuracy')
    ax.barh(y - h,   mrr_vals, h, label='MRR')

    ax.set_yticks(y)
    ax.set_yticklabels(labels, fontsize=8)
    ax.invert_yaxis()
    ax.set_xlabel('Score (%)')
    ax.set_title(f'Split {split.replace("_", "/")}')
    ax.set_xlim(0, 105)
    ax.legend(fontsize=8)

    # value labels
    for i, (f1, acc, mrr) in enumerate(zip(f1_vals, acc_vals, mrr_vals)):
        ax.text(f1  + 0.5, i + h,   f'{f1:.1f}',  va='center', fontsize=7)
        ax.text(acc + 0.5, i,        f'{acc:.1f}', va='center', fontsize=7)
        ax.text(mrr + 0.5, i - h,   f'{mrr:.1f}', va='center', fontsize=7)

plt.tight_layout()
plt.savefig('charts/chart3_top10.png')
plt.close()
print('Saved chart3_top10.png')

# ── Chart 4: Retrieval time ───────────────────────────────────────────────────
# Compare Euclidean, Weighted Euclidean, and top 5 RF combinations by time.

fig, axes = plt.subplots(1, 2, figsize=(14, 5))
fig.suptitle('Average Retrieval Time (ms per case)')

for ax, split in zip(axes, splits):
    entries   = []
    time_vals = []

    # Distance algorithms
    for alg, name in [('euclidean', 'Euclidean'), ('weighted_euclidean', 'Weighted')]:
        r = get_dist(alg, split)
        entries.append(name)
        time_vals.append(r['avg_execution_time_ms'] if r else 0)

    # Top 5 RF by F1
    top5 = sorted(rf_rows(split), key=lambda r: r['f1_score'], reverse=True)[:5]
    for r in top5:
        label = (f"n={r['n_estimators']} d={r['max_depth'] or 'N'} "
                 f"f={r['max_features'] or 'N'} msl={r['min_samples_leaf']}")
        entries.append(label)
        time_vals.append(r['avg_execution_time_ms'])

    y = np.arange(len(entries))
    ax.barh(y, time_vals)
    ax.set_yticks(y)
    ax.set_yticklabels(entries, fontsize=8)
    ax.invert_yaxis()
    ax.set_xlabel('ms')
    ax.set_title(f'Split {split.replace("_", "/")}')

    for i, v in enumerate(time_vals):
        ax.text(v + 0.01, i, f'{v:.2f}ms', va='center', fontsize=7)

plt.tight_layout()
plt.savefig('charts/chart4_time.png')
plt.close()
print('Saved chart4_time.png')

# ── Extra chart: Mean Rank ─────────────────────────────────────────────────
# Lower bar = better (correct product found closer to rank #1 on average).
# Shown separately because all other metrics are "higher = better".

fig, axes = plt.subplots(1, 2, figsize=(12, 4))
fig.suptitle('Mean Rank (lower = better)')

algs      = ['euclidean', 'weighted_euclidean', 'random_forest']
alg_names = ['Euclidean', 'Weighted', 'RF best']
x         = np.arange(3)
w         = 0.3

for ax, split in zip(axes, splits):
    vals = []
    for alg in algs:
        r = best_rf(split) if alg == 'random_forest' else get_dist(alg, split)
        vals.append(float(r['mean_rank']) if r and r.get('mean_rank') else 0)

    bars = ax.bar(x, vals, w * 2)
    ax.set_xticks(x)
    ax.set_xticklabels(alg_names)
    ax.set_ylabel('Average rank position')
    ax.set_title(f'Split {split.replace("_","/")}')

    for bar, v in zip(bars, vals):
        if v > 0:
            ax.text(bar.get_x() + bar.get_width() / 2,
                    v + 0.05, f'{v:.2f}', ha='center', fontsize=8)

plt.tight_layout()
plt.savefig('charts/chart5_mean_rank.png')
plt.close()
print('Saved chart5_mean_rank.png')

print('\nDone. Charts saved to charts/')
