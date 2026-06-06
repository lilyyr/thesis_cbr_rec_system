@extends('layouts.app')

@section('title', 'Test Run Detail')

@section('content')
<div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">

    <div class="flex items-center justify-between mb-8">
        <div>
            <a href="{{ route('admin.algorithm-testing') }}"
               class="text-sm text-yellow-600 hover:text-yellow-700 mb-2 inline-block">
                ← Back to Testing
            </a>
            <h1 class="text-3xl font-bold text-gray-900">Run #{{ $run->id }} — Per-Case Breakdown</h1>
            <p class="text-sm text-gray-500 mt-1">
                {{ match($run->algorithm_name) {
                    'euclidean'          => 'Euclidean Distance',
                    'weighted_euclidean' => 'Weighted Euclidean Distance',
                    'random_forest'      => 'Random Forest Proximity',
                    default              => $run->algorithm_name
                } }}
                &nbsp;·&nbsp;
                Split {{ str_replace('_', ' / ', $run->split_ratio) }}
                @if($run->algorithm_name === 'random_forest')
                    &nbsp;·&nbsp;
                    n={{ $run->n_estimators ?? '?' }}
                    d={{ $run->max_depth ?? '∞' }}
                    mss={{ $run->min_samples_split ?? '?' }}
                    msl={{ $run->min_samples_leaf ?? '?' }}
                @endif
            </p>
        </div>
    </div>

    {{-- ── Summary metrics ──────────────────────────────────────── --}}
    @php
        $pct = fn($v) => round((float)($v ?? 0) * 100, 2);
        $metrics = [
            'Precision'  => $pct($run->precision_score),
            'Recall'     => $pct($run->recall),
            'F1-Score'   => $pct($run->f1_score),
            'Accuracy'   => $pct($run->accuracy),
            'MRR'        => $pct($run->mrr),
        ];
    @endphp

    <div class="grid grid-cols-5 gap-3 mb-8">
        @foreach($metrics as $label => $val)
        <div class="bg-white border border-gray-200 p-4 rounded-lg text-center">
            <p class="text-xs text-gray-500 uppercase tracking-wide mb-1">{{ $label }}</p>
            <p class="text-2xl font-bold
                {{ $val >= 80 ? 'text-green-700' : ($val >= 60 ? 'text-yellow-600' : 'text-gray-700') }}">
                {{ $val }}%
            </p>
        </div>
        @endforeach
    </div>

    {{-- ── Confusion matrix ─────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-lg p-6 mb-8">
        <h2 class="text-base font-semibold text-gray-900 mb-4">Confusion Matrix</h2>
        <div class="grid grid-cols-4 gap-3 max-w-sm">
            @foreach([
                ['TP', $run->true_positives,  'green'],
                ['FP', $run->false_positives, 'red'],
                ['FN', $run->false_negatives, 'red'],
                ['TN', $run->true_negatives,  'green'],
            ] as [$label, $val, $color])
            <div class="text-center border border-gray-200 rounded p-3
                {{ $color === 'green' ? 'bg-green-50' : 'bg-red-50' }}">
                <p class="text-xs font-semibold text-gray-600 mb-1">{{ $label }}</p>
                <p class="text-xl font-bold {{ $color === 'green' ? 'text-green-700' : 'text-red-700' }}">
                    {{ $val ?? 0 }}
                </p>
            </div>
            @endforeach
        </div>
        <p class="text-xs text-gray-400 mt-3">
            TP + FP + TN + FN = {{ ($run->true_positives + $run->false_positives + $run->true_negatives + $run->false_negatives) }}
        </p>
    </div>

    {{-- ── Per-case table ───────────────────────────────────────── --}}
    <div class="bg-white border border-gray-200 rounded-lg overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="text-base font-semibold text-gray-900">
                Per-Case Predictions
                <span class="text-gray-400 font-normal text-sm ml-2">
                    ({{ count($detailed) }} cases)
                </span>
            </h2>

            {{-- Quick stats --}}
            @php
                $correct = collect($detailed)->filter(fn($d) =>
                    !empty($d['ranked_product_ids']) &&
                    $d['ranked_product_ids'][0] == $d['correct_product_id']
                )->count();
            @endphp
            <span class="text-sm text-gray-500">
                <span class="font-semibold text-green-700">{{ $correct }}</span> correct
                at rank #1 out of {{ count($detailed) }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm divide-y divide-gray-100">
                <thead class="bg-gray-50 text-xs text-gray-600 uppercase tracking-wide">
                    <tr>
                        <th class="px-4 py-3 text-left">Case ID</th>
                        <th class="px-4 py-3 text-left">Correct Product</th>
                        <th class="px-4 py-3 text-left">Top-1 Predicted</th>
                        <th class="px-4 py-3 text-center">Result</th>
                        <th class="px-4 py-3 text-left">Full Ranking (top-5)</th>
                        <th class="px-4 py-3 text-center">Rank of Correct</th>
                        <th class="px-4 py-3 text-center">Time (ms)</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($detailed as $case)
                    @php
                        $ranked   = $case['ranked_product_ids'] ?? [];
                        $correct  = $case['correct_product_id'] ?? null;
                        $top1     = $ranked[0] ?? null;
                        $isRight  = $top1 == $correct;
                        $rankPos  = array_search($correct, $ranked);
                        $rankPos  = $rankPos !== false ? $rankPos + 1 : '—';
                    @endphp
                    <tr class="{{ $isRight ? 'bg-green-50/40' : 'bg-red-50/30' }} hover:bg-gray-50 transition">
                        <td class="px-4 py-3 font-mono text-gray-600">#{{ $case['test_case_id'] ?? '?' }}</td>
                        <td class="px-4 py-3 text-gray-800">{{ $case['correct_product'] ?? $correct }}</td>
                        <td class="px-4 py-3 {{ $isRight ? 'text-green-700 font-semibold' : 'text-red-700' }}">
                            Product #{{ $top1 ?? '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($isRight)
                                <span class="text-green-700 font-bold">✓</span>
                            @else
                                <span class="text-red-600 font-bold">✗</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-gray-500">
                            {{ implode(' › ', array_slice($ranked, 0, 5)) }}
                        </td>
                        <td class="px-4 py-3 text-center font-semibold
                            {{ is_numeric($rankPos) && $rankPos == 1 ? 'text-green-700'
                             : (is_numeric($rankPos) ? 'text-yellow-600' : 'text-gray-400') }}">
                            {{ $rankPos }}
                        </td>
                        <td class="px-4 py-3 text-center font-mono text-gray-500">
                            {{ round($case['execution_time_ms'] ?? 0, 2) }}
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-4 py-8 text-center text-gray-400 italic">
                            No per-case data stored for this run.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
