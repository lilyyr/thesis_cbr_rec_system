@extends('layouts.app')

@section('title', 'Algorithm Testing')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Algorithm Testing & Comparison</h1>
        <p class="text-gray-600 mt-2">Compare the accuracy and efficiency of CBR algorithms</p>
    </div>

    @if(session('success'))
    <div class="bg-green-50 border border-green-200 text-green-800 p-4 rounded mb-6">
        {{ session('success') }}
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-50 border border-red-200 text-red-800 p-4 rounded mb-6">
        {{ session('error') }}
    </div>
    @endif

    <!-- Test Cases Info -->
    <div class="bg-white border border-gray-200 p-6 rounded-lg mb-6">
        <div class="flex justify-between items-center">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Test Dataset</h2>
                <p class="text-gray-600 mt-2">
                    <span class="text-3xl font-bold text-yellow-600">{{ $testCaseCount }}</span> test cases available
                </p>
            </div>
            <form action="{{ route('admin.algorithm-testing.run') }}" method="POST">
                @csrf
                <button type="submit"
                        class="bg-gradient-to-r from-yellow-600 to-yellow-500 text-white px-8 py-4 rounded-lg hover:from-yellow-700 hover:to-yellow-600 font-semibold text-lg">
                    🧪 Run Algorithm Tests
                </button>
            </form>
        </div>
    </div>

    <!-- Comparison Table -->
    @if($comparisonData->count() > 0)
    <div class="bg-white border border-gray-200 p-6 rounded-lg mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Algorithm Comparison (All Time Average)</h2>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-50 border-b-2 border-gray-200">
                    <tr>
                        <th class="px-4 py-3 text-left font-semibold text-gray-900">Algorithm</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900">Precision</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900">Recall</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900">F1-Score</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900">Accuracy</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900">Precision@5</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900">MRR</th>
                        <th class="px-4 py-3 text-center font-semibold text-gray-900">Avg Time (ms)</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($comparisonData as $data)
                    <tr class="border-b border-gray-200 hover:bg-gray-50">
                        <td class="px-4 py-3 font-semibold text-gray-900">
                            {{ ucfirst(str_replace('_', ' ', $data->algorithm_name)) }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-blue-100 text-blue-800 px-3 py-1 rounded font-semibold">
                                {{ number_format($data->avg_precision * 100, 2) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded font-semibold">
                                {{ number_format($data->avg_recall * 100, 2) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-purple-100 text-purple-800 px-3 py-1 rounded font-semibold">
                                {{ number_format($data->avg_f1 * 100, 2) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-yellow-100 text-yellow-800 px-3 py-1 rounded font-semibold">
                                {{ number_format($data->avg_accuracy * 100, 2) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-indigo-100 text-indigo-800 px-3 py-1 rounded font-semibold">
                                {{ number_format($data->avg_p_at_5 * 100, 2) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <span class="bg-pink-100 text-pink-800 px-3 py-1 rounded font-semibold">
                                {{ number_format($data->avg_mrr * 100, 2) }}%
                            </span>
                        </td>
                        <td class="px-4 py-3 text-center text-gray-700 font-mono">
                            {{ number_format($data->avg_time, 2) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- Recent Test Results -->
    @if($latestResults->count() > 0)
    <div class="bg-white border border-gray-200 p-6 rounded-lg">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">Recent Test Runs</h2>

        <div class="space-y-3">
            @foreach($latestResults as $result)
            <div class="border border-gray-300 p-4 rounded hover:border-yellow-600 transition">
                <div class="flex justify-between items-start">
                    <div>
                        <h3 class="font-bold text-lg text-gray-900">
                            {{ ucfirst(str_replace('_', ' ', $result->algorithm_name)) }}
                        </h3>
                        <p class="text-sm text-gray-600">
                            {{ \Carbon\Carbon::parse($result->test_run_date)->format('M d, Y H:i') }}
                            • {{ $result->total_test_cases }} test cases
                        </p>
                    </div>
                    <div class="text-right">
                        <div class="text-2xl font-bold text-yellow-600">
                            {{ number_format($result->accuracy * 100, 1) }}%
                        </div>
                        <div class="text-sm text-gray-600">Accuracy</div>
                    </div>
                </div>

                <div class="mt-3 grid grid-cols-6 gap-2 text-sm">
                    <div>
                        <span class="text-gray-600">Precision:</span>
                        <span class="font-semibold">{{ number_format($result->precision * 100, 1) }}%</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Recall:</span>
                        <span class="font-semibold">{{ number_format($result->recall * 100, 1) }}%</span>
                    </div>
                    <div>
                        <span class="text-gray-600">F1:</span>
                        <span class="font-semibold">{{ number_format($result->f1_score * 100, 1) }}%</span>
                    </div>
                    <div>
                        <span class="text-gray-600">P@5:</span>
                        <span class="font-semibold">{{ number_format($result->precision_at_5 * 100, 1) }}%</span>
                    </div>
                    <div>
                        <span class="text-gray-600">MRR:</span>
                        <span class="font-semibold">{{ number_format($result->mrr * 100, 1) }}%</span>
                    </div>
                    <div>
                        <span class="text-gray-600">Time:</span>
                        <span class="font-semibold">{{ number_format($result->avg_execution_time_ms, 1) }}ms</span>
                    </div>
                </div>

                <div class="mt-3">
                    <a href="{{ route('admin.algorithm-testing.results', $result->id) }}"
                       class="text-yellow-600 hover:text-yellow-700 text-sm font-semibold">
                        View Detailed Results →
                    </a>
                </div>
            </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
