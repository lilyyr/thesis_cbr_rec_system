@extends('layouts.app')

@section('title', 'Algorithm Testing')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">

    {{-- ── Page header ──────────────────────────────────────────── --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Algorithm Testing</h1>
            @if($lastRunAt)
                <p class="text-sm text-gray-500 mt-1">
                    Last run: {{ \Carbon\Carbon::parse($lastRunAt)->format('d M Y, H:i') }}
                </p>
            @endif
        </div>

        {{-- Run Tests button --}}
        <button id="run-btn"
                onclick="runTests()"
                class="bg-gray-900 text-white px-5 py-2.5 rounded text-sm font-semibold
                       hover:bg-gray-700 transition disabled:opacity-50">
            ▶ Run Tests
        </button>
    </div>

    {{-- ── Status bar (hidden until test runs) ─────────────────── --}}
    <div id="status-bar" class="hidden mb-6 p-4 rounded border text-sm"></div>

    {{-- ── Summary cards ────────────────────────────────────────── --}}
    @if($totalRuns > 0)
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
        <div class="bg-white border border-gray-200 p-4 rounded-lg">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Total Runs</p>
            <p class="text-3xl font-bold text-gray-900">{{ $totalRuns }}</p>
        </div>

        @if($bestByF1)
        <div class="bg-white border border-gray-200 p-4 rounded-lg">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Best F1-Score</p>
            <p class="text-3xl font-bold text-green-700">{{ $bestByF1->f1_score }}%</p>
            <p class="text-xs text-gray-500 mt-1">
                {{ $bestByF1->alg_label }}
                ({{ str_replace('_', '/', $bestByF1->split_ratio) }})
            </p>
        </div>
        @endif

        @if($bestByMRR)
        <div class="bg-white border border-gray-200 p-4 rounded-lg">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Best MRR</p>
            <p class="text-3xl font-bold text-blue-700">{{ $bestByMRR->mrr }}%</p>
            <p class="text-xs text-gray-500 mt-1">
                {{ $bestByMRR->alg_label }}
                ({{ str_replace('_', '/', $bestByMRR->split_ratio) }})
            </p>
        </div>
        @endif

        <div class="bg-white border border-gray-200 p-4 rounded-lg">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">Splits Tested</p>
            <p class="text-3xl font-bold text-gray-900">
                {{ $results->pluck('split_ratio')->unique()->count() }}
            </p>
            <p class="text-xs text-gray-500 mt-1">
                {{ $results->pluck('split_ratio')->unique()->map(fn($s) => str_replace('_','/',$s))->join(' · ') }}
            </p>
        </div>
    </div>

    {{-- ── Filter bar ───────────────────────────────────────────── --}}
    <div class="flex flex-wrap gap-3 mb-4 items-center">
        <span class="text-sm font-medium text-gray-700">Filter:</span>

        <select id="filter-algo"
                onchange="applyFilters()"
                class="text-sm border border-gray-300 rounded px-3 py-1.5
                       focus:border-gray-500 focus:ring-0">
            <option value="">All Algorithms</option>
            <option value="euclidean">Euclidean</option>
            <option value="weighted_euclidean">Weighted Euclidean</option>
            <option value="random_forest">Random Forest</option>
        </select>

        <select id="filter-split"
                onchange="applyFilters()"
                class="text-sm border border-gray-300 rounded px-3 py-1.5
                       focus:border-gray-500 focus:ring-0">
            <option value="">All Splits</option>
            <option value="80_20">80 / 20</option>
            <option value="70_30">70 / 30</option>
        </select>

        <button onclick="clearFilters()"
                class="text-sm text-gray-500 hover:text-gray-800 underline ml-1">
            Clear
        </button>

        <span id="row-count" class="text-xs text-gray-400 ml-auto"></span>
    </div>

    {{-- ── Results table ────────────────────────────────────────── --}}
    <div class="overflow-x-auto rounded-lg border border-gray-200">
        <table class="min-w-full text-sm" id="results-table">
            <thead class="bg-gray-900 text-white text-xs uppercase tracking-wide">
                <tr>
                    <th class="px-4 py-3 text-left">Algorithm</th>
                    <th class="px-4 py-3 text-center">Split</th>
                    <th class="px-4 py-3 text-left">RF Params</th>
                    <th class="px-4 py-3 text-center">Train</th>
                    <th class="px-4 py-3 text-center">Test</th>
                    <th class="px-4 py-3 text-center metric-col">Precision</th>
                    <th class="px-4 py-3 text-center metric-col">Recall</th>
                    <th class="px-4 py-3 text-center metric-col">F1</th>
                    <th class="px-4 py-3 text-center metric-col">Accuracy</th>
                    <th class="px-4 py-3 text-center metric-col">MRR</th>
                    <th class="px-4 py-3 text-center">Time (ms)</th>
                    <th class="px-4 py-3 text-center">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 bg-white">
                @forelse($results as $r)
                <tr class="hover:bg-gray-50 transition result-row"
                    data-algo="{{ $r->algorithm_name }}"
                    data-split="{{ $r->split_ratio }}">

                    {{-- Algorithm --}}
                    <td class="px-4 py-3 font-medium text-gray-900 whitespace-nowrap">
                        {{ $r->alg_label }}
                    </td>

                    {{-- Split --}}
                    <td class="px-4 py-3 text-center">
                        <span class="inline-block px-2 py-0.5 rounded text-xs font-semibold
                            {{ $r->split_ratio === '80_20'
                                ? 'bg-blue-100 text-blue-800'
                                : 'bg-orange-100 text-orange-800' }}">
                            {{ str_replace('_', ' / ', $r->split_ratio) }}
                        </span>
                    </td>

                    {{-- RF Params --}}
                    <td class="px-4 py-3 font-mono text-xs text-gray-600 whitespace-nowrap">
                        {{ $r->rf_label }}
                    </td>

                    {{-- Train / Test sizes --}}
                    <td class="px-4 py-3 text-center text-gray-600">{{ $r->train_size ?? '—' }}</td>
                    <td class="px-4 py-3 text-center text-gray-600">{{ $r->test_size ?? '—' }}</td>

                    {{-- Metrics (formatted as %) --}}
                    @foreach([
                        'precision_score', 'recall', 'f1_score', 'accuracy', 'mrr'
                    ] as $metric)
                    <td class="px-4 py-3 text-center font-mono metric-cell"
                        data-value="{{ $r->$metric }}">
                        <span class="metric-badge {{ $r->$metric >= 80
                            ? 'text-green-700 font-bold'
                            : ($r->$metric >= 60 ? 'text-yellow-700' : 'text-gray-500') }}">
                            {{ $r->$metric }}%
                        </span>
                    </td>
                    @endforeach

                    {{-- Time --}}
                    <td class="px-4 py-3 text-center text-gray-600 font-mono">
                        {{ $r->avg_execution_time_ms }}
                    </td>

                    {{-- Details link --}}
                    <td class="px-4 py-3 text-center">
                        <a href="{{ route('admin.algorithm-testing.results', $r->id) }}"
                           class="text-xs text-yellow-600 hover:text-yellow-800 underline">
                            View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="12" class="px-4 py-10 text-center text-gray-400 italic">
                        No test results yet. Click <strong>Run Tests</strong> to start.
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- ── Legend ───────────────────────────────────────────────── --}}
    @if($totalRuns > 0)
    <div class="mt-3 flex gap-5 text-xs text-gray-500">
        <span class="flex items-center gap-1">
            <span class="font-bold text-green-700">value</span> ≥ 80%
        </span>
        <span class="flex items-center gap-1">
            <span class="text-yellow-700">value</span> 60 – 79%
        </span>
        <span class="flex items-center gap-1">
            <span class="text-gray-500">value</span> &lt; 60%
        </span>
        <span class="ml-auto">
            ★ = column best (highlighted after filter)
        </span>
    </div>
    @endif

    @else
    {{-- No results yet --}}
    <div class="bg-white border border-gray-200 rounded-lg p-16 text-center">
        <p class="text-gray-500 text-lg mb-2">No test results found.</p>
        <p class="text-sm text-gray-400">
            Click <strong>Run Tests</strong> to execute
            <code class="bg-gray-100 px-1 rounded">python/metric_testing.py</code>.
        </p>
    </div>
    @endif

</div>

{{-- ── Loading overlay ──────────────────────────────────────────── --}}
<div id="loading-overlay"
     class="hidden fixed inset-0 bg-black bg-opacity-40 z-50
            flex flex-col items-center justify-center">
    <div class="bg-white rounded-lg p-8 max-w-sm w-full mx-4 text-center shadow-xl">
        <div class="inline-block animate-spin rounded-full h-10 w-10
                    border-4 border-gray-200 border-t-gray-900 mb-4"></div>
        <p class="font-semibold text-gray-900 mb-1">Running tests…</p>
        <p class="text-sm text-gray-500">
            This may take several minutes.<br>
            Please keep this tab open.
        </p>
    </div>
</div>

<script>
// ── Run Tests ─────────────────────────────────────────────────────────────────

async function runTests() {
    const btn     = document.getElementById('run-btn');
    const overlay = document.getElementById('loading-overlay');
    const bar     = document.getElementById('status-bar');

    btn.disabled = true;
    overlay.classList.remove('hidden');
    bar.classList.add('hidden');

    try {
        const res  = await fetch('{{ route("admin.algorithm-testing.run") }}', {
            method: 'POST',
            headers: {
                'Accept':       'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await res.json();

        overlay.classList.add('hidden');

        if (data.success) {
            bar.className = 'mb-6 p-4 rounded border text-sm bg-green-50 border-green-200 text-green-800';
            bar.textContent = '✓ ' + data.message + ' Reloading results…';
            bar.classList.remove('hidden');
            setTimeout(() => location.reload(), 1500);
        } else {
            bar.className = 'mb-6 p-4 rounded border text-sm bg-red-50 border-red-200 text-red-800';
            bar.innerHTML = '<strong>✗ ' + (data.message || 'Tests failed.') + '</strong>'
                + (data.output ? '<pre class="mt-2 text-xs overflow-x-auto whitespace-pre-wrap">'
                    + escHtml(data.output) + '</pre>' : '');
            bar.classList.remove('hidden');
            btn.disabled = false;
        }

    } catch (err) {
        overlay.classList.add('hidden');
        bar.className = 'mb-6 p-4 rounded border text-sm bg-red-50 border-red-200 text-red-800';
        bar.textContent = '✗ Network error: ' + err.message;
        bar.classList.remove('hidden');
        btn.disabled = false;
    }
}

// ── Filtering ─────────────────────────────────────────────────────────────────

function applyFilters() {
    const algo  = document.getElementById('filter-algo').value;
    const split = document.getElementById('filter-split').value;
    let visible = 0;

    document.querySelectorAll('.result-row').forEach(row => {
        const matchAlgo  = !algo  || row.dataset.algo  === algo;
        const matchSplit = !split || row.dataset.split === split;
        row.style.display = (matchAlgo && matchSplit) ? '' : 'none';
        if (matchAlgo && matchSplit) visible++;
    });

    const countEl = document.getElementById('row-count');
    if (countEl) countEl.textContent = visible + ' row' + (visible !== 1 ? 's' : '') + ' shown';

    highlightBest();
}

function clearFilters() {
    document.getElementById('filter-algo').value  = '';
    document.getElementById('filter-split').value = '';
    applyFilters();
}

// ── Highlight best value per metric column ────────────────────────────────────

function highlightBest() {
    const metricCols = 5;   // precision, recall, f1, accuracy, mrr
    const colStart   = 5;   // 0-indexed: first metric column index in the row

    // Reset old highlights
    document.querySelectorAll('.metric-cell').forEach(cell => {
        cell.classList.remove('bg-yellow-50', 'ring-1', 'ring-yellow-400');
    });

    // For each metric column, find the max value among visible rows
    for (let col = 0; col < metricCols; col++) {
        let maxVal = -1;
        let maxCells = [];

        document.querySelectorAll('.result-row').forEach(row => {
            if (row.style.display === 'none') return;
            const cells = row.querySelectorAll('.metric-cell');
            if (!cells[col]) return;
            const val = parseFloat(cells[col].dataset.value);
            if (val > maxVal) {
                maxVal  = val;
                maxCells = [cells[col]];
            } else if (val === maxVal) {
                maxCells.push(cells[col]);
            }
        });

        maxCells.forEach(cell => {
            cell.classList.add('bg-yellow-50', 'ring-1', 'ring-yellow-400');
        });
    }
}

function escHtml(str) {
    return str.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
}

// Run highlight on page load
document.addEventListener('DOMContentLoaded', () => {
    const count = document.querySelectorAll('.result-row').length;
    const countEl = document.getElementById('row-count');
    if (countEl) countEl.textContent = count + ' row' + (count !== 1 ? 's' : '') + ' shown';
    highlightBest();
});
</script>
@endsection
