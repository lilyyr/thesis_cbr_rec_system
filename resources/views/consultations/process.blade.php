@extends('layouts.app')

@section('title', 'CBR Process Visualization')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    @php
        $details = $consultation->algorithm_details;
        $euclideanTop = $details['euclidean']['top_5_matches'] ?? [];
        $weightedTop = $details['weighted_euclidean']['top_5_matches'] ?? [];
        $rfTop = $details['random_forest']['top_5_matches'] ?? [];

        // Get best match (first in euclidean results)
        $bestMatch = !empty($euclideanTop) ? $euclideanTop[0] : null;
    @endphp

    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('consultations.show', $consultation->id) }}" class="text-gray-600 hover:text-gold-500 mb-2 inline-block transition">
            ← Back to Results
        </a>
        <h1 class="text-4xl font-bold text-gray-900">CBR Process Visualization</h1>
        <p class="text-gray-600 mt-2">Detailed algorithmic breakdown for {{ $consultation->customer->name }}</p>
    </div>

    <!-- Process Overview -->
    <div class="bg-black text-white p-8 mb-8">
        <h2 class="text-2xl font-bold mb-6 flex items-center">
            <span class="text-gold-500 mr-3">🔬</span> CBR Process Overview
        </h2>
        <div class="grid md:grid-cols-3 gap-6">
            <div class="border border-gray-700 p-6">
                <div class="text-gold-500 text-3xl font-bold mb-2">{{ round($consultation->euclidean_score * 100, 2) }}%</div>
                <div class="text-sm text-gray-400">Euclidean Distance</div>
                <div class="text-xs text-gray-500 mt-2">Best match: {{ $euclideanTop[0]['customer_name'] ?? 'N/A' }}</div>
            </div>
            <div class="border border-gray-700 p-6">
                <div class="text-gold-500 text-3xl font-bold mb-2">{{ round($consultation->weighted_euclidean_score * 100, 2) }}%</div>
                <div class="text-sm text-gray-400">Weighted Euclidean</div>
                <div class="text-xs text-gray-500 mt-2">Best match: {{ $weightedTop[0]['customer_name'] ?? 'N/A' }}</div>
            </div>
            <div class="border border-gray-700 p-6">
                <div class="text-gold-500 text-3xl font-bold mb-2">{{ round($consultation->random_forest_score * 100, 2) }}%</div>
                <div class="text-sm text-gray-400">Random Forest Proximity</div>
                <div class="text-xs text-gray-500 mt-2">Best match: {{ $rfTop[0]['customer_name'] ?? 'N/A' }}</div>
            </div>
        </div>
    </div>

    <!-- Step 1: Input Data & Feature Vector -->
    <div class="bg-white border border-gray-200 p-8 mb-6">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center font-bold text-xl mr-4">1</div>
            <h2 class="text-2xl font-bold text-gray-900">Input Data & Feature Extraction</h2>
        </div>

        <!-- Customer Raw Data -->
        <div class="mb-6">
            <h3 class="font-bold text-gray-900 mb-4">Customer Information:</h3>
            <div class="grid md:grid-cols-3 gap-4 bg-gray-50 p-6 border border-gray-200">
                <div>
                    <div class="text-xs text-gray-500 uppercase">Name</div>
                    <div class="font-semibold">{{ $consultation->customer->name }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Age</div>
                    <div class="font-semibold">{{ $consultation->customer->age }} years</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Gender</div>
                    <div class="font-semibold capitalize">{{ $consultation->customer->gender }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Income</div>
                    <div class="font-semibold">Rp {{ number_format($consultation->customer->income, 0, ',', '.') }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">BMI</div>
                    <div class="font-semibold">{{ $consultation->bmi }}</div>
                </div>
                <div>
                    <div class="text-xs text-gray-500 uppercase">Health Risk</div>
                    <div class="font-semibold">{{ $consultation->health_risk_score }} / 25</div>
                </div>
            </div>
        </div>

        <!-- 18D Feature Vector -->
        <div>
            <h3 class="font-bold text-gray-900 mb-4">18-Dimensional Feature Vector (Normalized 0-1):</h3>
            <div class="bg-gray-50 border border-gray-200 p-6">
                <div class="grid md:grid-cols-3 gap-4 text-sm">
                    @php
                        $featureVector = $consultation->feature_vector;
                        $featureNames = [
                            'age_normalized', 'gender_encoded', 'income_normalized', 'dependents_normalized',
                            'bmi_normalized', 'health_risk_normalized',
                            'insurance_period_normalized', 'premium_period_normalized', 'overseas_plans', 'has_health_insurance',
                            'goal_family', 'goal_health', 'goal_retirement', 'goal_education',
                            'goal_critical', 'goal_income', 'goal_savings', 'goal_wealth'
                        ];
                    @endphp

                    @foreach($featureNames as $index => $name)
                        <div class="flex justify-between items-center p-3 bg-white border border-gray-200">
                            <span class="text-gray-700">{{ ucwords(str_replace('_', ' ', $name)) }}:</span>
                            <span class="font-mono font-bold text-gray-900">{{ number_format($featureVector[$index], 4) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- Step 2: Euclidean Distance Algorithm -->
    <div class="bg-white border border-gray-200 p-8 mb-6">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center font-bold text-xl mr-4">2</div>
            <h2 class="text-2xl font-bold text-gray-900">Euclidean Distance Algorithm</h2>
        </div>

        <!-- Formula -->
        <div class="mb-6 p-6 bg-gray-50 border-l-4 border-gold-500">
            <div class="text-sm text-gray-600 mb-2">Formula:</div>
            <div class="font-mono text-lg text-gray-900 mb-4">
                {{ $details['euclidean']['formula'] ?? 'Not here'}}
            </div>
            <div class="font-mono text-lg text-gray-900">
                {{ $details['euclidean']['similarity_formula'] ?? 'Error'}}
            </div>
        </div>

        <!-- Top 5 Matches -->
        <div class="mb-6">
            <h3 class="font-bold text-gray-900 mb-4">Top 5 Similar Cases:</h3>
            <div class="space-y-3">
                @foreach($euclideanTop as $index => $match)
                    <div class="p-4 border border-gray-200 hover:border-gold-500 transition">
                        <div class="flex justify-between items-center">
                            <div>

                                <div class="text-sm text-gray-600">Case ID: {{ $match['case_id'] }} | Product: {{ $match['product_name'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gold-600">{{ number_format($match['similarity'] * 100, 2) }}%</div>
                                <div class="text-xs text-gray-500">Distance: {{ number_format($match['distance'], 4) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Detailed Calculation for Best Match -->
        @if($bestMatch)
            <div class="mb-6">


                <div class="bg-gray-50 border border-gray-200 p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 text-left">Feature</th>
                                    <th class="px-4 py-2 text-center">New Case (x<sub>i</sub>)</th>
                                    <th class="px-4 py-2 text-center">Historical (y<sub>i</sub>)</th>
                                    <th class="px-4 py-2 text-center">(x<sub>i</sub> - y<sub>i</sub>)<sup>2</sup></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($featureNames as $index => $name)
                                    <tr class="border-b border-gray-200">
                                        <td class="px-4 py-2">{{ ucwords(str_replace('_', ' ', $name)) }}</td>
                                        <td class="px-4 py-2 text-center font-mono">{{ number_format($featureVector[$index], 4) }}</td>
                                        <td class="px-4 py-2 text-center font-mono">{{ number_format($bestMatch['historical_vector'][$index], 4) }}</td>
                                        <td class="px-4 py-2 text-center font-mono text-gold-600">{{ number_format($bestMatch['feature_differences'][$index], 6) }}</td>
                                    </tr>
                                @endforeach

                                <tr class="bg-gold-100 font-bold">
                                    <td class="px-4 py-2" colspan="3">Sum of Squares (Σ):</td>
                                    <td class="px-4 py-2 text-center font-mono text-gold-600">{{ number_format($bestMatch['sum_squared_diff'], 6) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 p-4 bg-black text-white">
                        <div class="font-mono">
                            <div class="mb-2">Distance = √{{ number_format($bestMatch['sum_squared_diff'], 6) }} = <span class="text-gold-500 font-bold">{{ number_format($bestMatch['distance'], 6) }}</span></div>
                            <div>Similarity = 1 / (1 + {{ number_format($bestMatch['distance'], 6) }}) = <span class="text-gold-500 font-bold text-2xl">{{ number_format($bestMatch['similarity'], 4) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Final Result -->
        <div class="bg-gradient-to-r from-gray-900 to-black text-white p-6">
            <div class="text-center">
                <div class="text-sm text-gray-400 mb-2">Final Euclidean Similarity Score (Best Match)</div>
                <div class="text-5xl font-bold text-gold-500">{{ number_format($consultation->euclidean_score * 100, 2) }}%</div>
            </div>
        </div>
    </div>

    <!-- Step 3: Weighted Euclidean Distance -->
    <div class="bg-white border border-gray-200 p-8 mb-6">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center font-bold text-xl mr-4">3</div>
            <h2 class="text-2xl font-bold text-gray-900">Weighted Euclidean Distance Algorithm</h2>
        </div>

        <!-- Formula -->
        <div class="mb-6 p-6 bg-gray-50 border-l-4 border-gold-500">
            <div class="text-sm text-gray-600 mb-2">Formula:</div>
            <div class="font-mono text-lg text-gray-900 mb-4">
                {{ $details['weighted_euclidean']['formula'] ?? 'dw(x,y) = sqrt(sum(wi * (xi - yi)^2))' }}
            </div>
            <div class="font-mono text-lg text-gray-900">
                {{ $details['weighted_euclidean']['similarity_formula'] ?? 'similarity = 1 / (1 + weighted_distance)' }}
            </div>
        </div>

        <!-- Feature Weights -->
        <div class="mb-6">
            <h3 class="font-bold text-gray-900 mb-4">Feature Weights (w<sub>i</sub>):</h3>

            @php
                $weights = $details['weighted_euclidean']['weights_used'] ?? [];
            @endphp

            <div class="grid md:grid-cols-3 gap-4">
                @foreach($weights as $weight)
                    <div class="bg-gray-50 border border-gray-200 p-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-700">{{ $weight['feature'] }}</span>
                            <span class="font-mono font-bold text-gold-600">{{ number_format($weight['weight'], 4) }}</span>
                        </div>
                        <div class="mt-2 bg-gray-200 rounded-full h-2">
                            <div class="bg-gold-500 h-2 rounded-full" style="width: {{ $weight['weight'] * 100 }}%"></div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Top 5 Weighted Matches -->
        <div class="mb-6">
            <h3 class="font-bold text-gray-900 mb-4">Top 5 Weighted Similar Cases:</h3>
            <div class="space-y-3">
                @foreach($weightedTop as $index => $match)
                    <div class="p-4 border border-gray-200 hover:border-gold-500 transition">
                        <div class="flex justify-between items-center">
                            <div>

                                <div class="text-sm text-gray-600">Case ID: {{ $match['case_id'] }} | Product: {{ $match['product_name'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gold-600">{{ number_format($match['similarity'] * 100, 2) }}%</div>
                                <div class="text-xs text-gray-500">Weighted Distance: {{ number_format($match['distance'], 4) }}</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Detailed Weighted Calculation -->
        @if(!empty($weightedTop))
            @php
                $weightedBest = $weightedTop[0];
            @endphp
            <div class="mb-6">


                <div class="bg-gray-50 border border-gray-200 p-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-200">
                                <tr>
                                    <th class="px-4 py-2 text-left">Feature</th>
                                    <th class="px-4 py-2 text-center">Weight (w<sub>i</sub>)</th>
                                    <th class="px-4 py-2 text-center">(x<sub>i</sub> - y<sub>i</sub>)<sup>2</sup></th>
                                    <th class="px-4 py-2 text-center">w<sub>i</sub> × (diff)<sup>2</sup></th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($featureNames as $index => $name)
                                    @php
                                        $diff = $featureVector[$index] - $weightedBest['historical_vector'][$index];
                                        $squared = $diff * $diff;
                                    @endphp
                                    <tr class="border-b border-gray-200">
                                        <td class="px-4 py-2">{{ ucwords(str_replace('_', ' ', $name)) }}</td>
                                        <td class="px-4 py-2 text-center font-mono text-gold-600">{{ number_format($weights[$index]['weight'], 4) }}</td>
                                        <td class="px-4 py-2 text-center font-mono">{{ number_format($squared, 6) }}</td>
                                        <td class="px-4 py-2 text-center font-mono text-gold-600">{{ number_format($weightedBest['weighted_differences'][$index], 6) }}</td>
                                    </tr>
                                @endforeach

                                <tr class="bg-gold-100 font-bold">
                                    <td class="px-4 py-2" colspan="3">Weighted Sum (Σ):</td>
                                    <td class="px-4 py-2 text-center font-mono text-gold-600">{{ number_format($weightedBest['sum_weighted_squared'], 6) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-6 p-4 bg-black text-white">
                        <div class="font-mono">
                            <div class="mb-2">Weighted Distance = √{{ number_format($weightedBest['sum_weighted_squared'], 6) }} = <span class="text-gold-500 font-bold">{{ number_format($weightedBest['distance'], 6) }}</span></div>
                            <div>Similarity = 1 / (1 + {{ number_format($weightedBest['distance'], 6) }}) = <span class="text-gold-500 font-bold text-2xl">{{ number_format($weightedBest['similarity'], 4) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Final Result -->
        <div class="bg-gradient-to-r from-gray-900 to-black text-white p-6">
            <div class="text-center">
                <div class="text-sm text-gray-400 mb-2">Final Weighted Euclidean Similarity Score (Best Match)</div>
                <div class="text-5xl font-bold text-gold-500">{{ number_format($consultation->weighted_euclidean_score * 100, 2) }}%</div>
            </div>
        </div>
    </div>

    <!-- Step 4: Random Forest Proximity (MOST IMPORTANT) -->
    <div class="bg-white border border-gray-200 p-8 mb-6">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center font-bold text-xl mr-4">4</div>
            <h2 class="text-2xl font-bold text-gray-900">Random Forest Proximity Algorithm</h2>
        </div>

        <!-- How it Works -->
        <div class="mb-6 p-6 bg-gray-50 border-l-4 border-gold-500">
            <h3 class="font-bold text-gray-900 mb-3">{{$details['random_forest']['formula'] ?? 'Error'}}</h3>
            {{-- <ol class="list-decimal list-inside space-y-2 text-gray-700">
                <li>The Random Forest model contains <strong>{{ $details['random_forest']['total_trees'] ?? 100 }} decision trees</strong></li>
                <li>Each case is passed through all {{ $details['random_forest']['total_trees'] ?? 100 }} trees</li>
                <li>Each tree assigns the case to a specific <strong>leaf node</strong> (terminal node)</li>
                <li>We store the leaf node ID for each tree (creating a {{ $details['random_forest']['total_trees'] ?? 100 }}D "leaf vector")</li>
                <li>To calculate proximity: Compare leaf vectors between two cases</li>
                <li><strong>{{ $details['random_forest']['formula'] ?? 'Proximity = matching_leaves / total_trees' }}</strong></li>
            </ol> --}}
        </div>

        <!-- Top 5 RF Matches -->
        <div class="mb-6">
            <h3 class="font-bold text-gray-900 mb-4">Top 5 Random Forest Similar Cases:</h3>
            <div class="space-y-3">
                @foreach($rfTop as $index => $match)
                    <div class="p-4 border border-gray-200 hover:border-gold-500 transition">
                        <div class="flex justify-between items-center">
                            <div>

                                <div class="text-sm text-gray-600">Case ID: {{ $match['case_id'] }} | Product: {{ $match['product_name'] }}</div>
                            </div>
                            <div class="text-right">
                                <div class="text-2xl font-bold text-gold-600">{{ number_format($match['similarity'] * 100, 2) }}%</div>
                                <div class="text-xs text-gray-500">{{ $match['matches'] }} / {{ $match['total_trees'] }} trees match</div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Leaf Node Visualization for Best Match -->
        @if(!empty($rfTop))
            @php
                $rfBest = $rfTop[0];
                $treeMatches = $rfBest['tree_by_tree_matches'] ?? [];
            @endphp
            <div class="mb-6">


                <div class="bg-gray-50 border border-gray-200 p-6">
                    <div class="mb-4 text-sm text-gray-600">
                        Comparing leaf nodes from all {{ $rfBest['total_trees'] }} trees. ✓ = Match (same leaf), ✗ = Different leaf
                    </div>

                    <!-- Visual Grid -->
                    <div class="grid grid-cols-10 gap-2 mb-6">
                        @foreach($treeMatches as $tree)
                            <div class="relative group">
                                <div class="w-full aspect-square {{ $tree['match'] ? 'bg-green-500' : 'bg-red-500' }} rounded flex items-center justify-center text-white text-xs font-bold cursor-pointer hover:scale-110 transition">
                                    {{ $tree['match'] ? '✓' : '✗' }}
                                </div>
                                <!-- Tooltip -->
                                <div class="absolute hidden group-hover:block bg-black text-white text-xs p-2 rounded shadow-lg z-10 w-32 -left-12 top-full mt-1">
                                    <div class="font-bold mb-1">Tree {{ $tree['tree_id'] }}</div>
                                    <div>New: Leaf {{ $tree['new_leaf'] }}</div>
                                    <div>Hist: Leaf {{ $tree['hist_leaf'] }}</div>
                                    <div class="mt-1 {{ $tree['match'] ? 'text-green-400' : 'text-red-400' }}">
                                        {{ $tree['match'] ? 'MATCH ✓' : 'DIFFERENT ✗' }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Legend -->
                    <div class="flex items-center justify-center space-x-6 mb-6">
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-green-500 rounded mr-2"></div>
                            <span class="text-sm text-gray-700">Matching Leaf (✓): {{ $rfBest['matches'] }} trees</span>
                        </div>
                        <div class="flex items-center">
                            <div class="w-6 h-6 bg-red-500 rounded mr-2"></div>
                            <span class="text-sm text-gray-700">Different Leaf (✗): {{ $rfBest['total_trees'] - $rfBest['matches'] }} trees</span>
                        </div>
                    </div>

                    <!-- Sample Tree Comparisons -->
                    <div class="bg-white border border-gray-200 p-6">
                        <h4 class="font-bold text-gray-900 mb-4">Sample Tree Comparisons (First 10 Trees):</h4>

                        <div class="space-y-3">
                            @foreach(array_slice($treeMatches, 0, 10) as $tree)
                                <div class="flex items-center justify-between p-4 border {{ $tree['match'] ? 'border-green-500 bg-green-50' : 'border-red-500 bg-red-50' }}">
                                    <div class="flex-1">
                                        <div class="font-semibold text-gray-900">Tree {{ $tree['tree_id'] }}</div>
                                    </div>
                                    <div class="flex items-center space-x-4">
                                        <div class="text-center">
                                            <div class="text-xs text-gray-500">New Case</div>
                                            <div class="font-mono font-bold">Leaf {{ $tree['new_leaf'] }}</div>
                                        </div>
                                        <div class="text-2xl {{ $tree['match'] ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $tree['match'] ? '=' : '≠' }}
                                        </div>
                                        <div class="text-center">
                                            <div class="text-xs text-gray-500">Historical</div>
                                            <div class="font-mono font-bold">Leaf {{ $tree['hist_leaf'] }}</div>
                                        </div>
                                        <div class="text-2xl {{ $tree['match'] ? 'text-green-600' : 'text-red-600' }}">
                                            {{ $tree['match'] ? '✓' : '✗' }}
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Calculation -->
                    <div class="mt-6 p-6 bg-black text-white">
                        <div class="font-mono">
                            <div class="text-xl mb-4">Proximity Calculation:</div>
                            <div class="text-3xl mb-2">
                                Proximity = <span class="text-gold-500">{{ $rfBest['matches'] }}</span> / {{ $rfBest['total_trees'] }}
                            </div>
                            <div class="text-5xl font-bold text-gold-500">
                                = {{ number_format($rfBest['similarity'], 4) }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        <!-- Final Result -->
        <div class="bg-gradient-to-r from-gray-900 to-black text-white p-6">
            <div class="text-center">
                <div class="text-sm text-gray-400 mb-2">Final Random Forest Proximity Score (Best Match)</div>
                <div class="text-5xl font-bold text-gold-500">{{ number_format($consultation->random_forest_score * 100, 2) }}%</div>
                <div class="text-sm text-gray-400 mt-4">
                    Based on {{ round($consultation->random_forest_score * 100) }} matching leaf nodes out of 100 trees
                </div>
            </div>
        </div>
    </div>

    <!-- Step 4.5: Decision Tree Visualizations -->
<div class="bg-white border border-gray-200 p-8 mb-6">
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center">
            <div class="w-12 h-12 bg-black text-white rounded-full flex items-center justify-center font-bold text-xl mr-4">🌳</div>
            <div>
                <h2 class="text-2xl font-bold text-gray-900">Decision Tree Visualizations</h2>
                <p class="text-gray-600">See the actual tree structure and decision paths</p>
            </div>
        </div>

        <button
            onclick="generateTrees()"
            id="generateTreeBtn"
            class="gold-gradient text-black px-6 py-3 hover:opacity-90 transition font-semibold"
        >
            Generate Tree Visualizations
        </button>
    </div>

    <!-- Loading State -->
    <div id="treeLoading" class="hidden text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-gold-500"></div>
        <p class="mt-4 text-gray-600">Generating tree visualizations... This may take 10-20 seconds.</p>
    </div>

    <!-- Trees Container -->
    <div id="treesContainer" class="hidden">
        <!-- Trees will be loaded here -->
    </div>

    <!-- Info Box -->
    <div class="bg-gray-50 border border-gray-200 p-6">
        <h3 class="font-bold text-gray-900 mb-3">Understanding Decision Trees:</h3>
        <ul class="space-y-2 text-sm text-gray-700">
            <li><strong>Root Node (Top):</strong> The first decision point based on the most important feature</li>
            <li><strong>Internal Nodes:</strong> Decision points that split the data based on feature thresholds</li>
            <li><strong>Branches:</strong> Represent "yes/no" or "true/false" decisions (≤ threshold or > threshold)</li>
            <li><strong>Leaf Nodes (Bottom):</strong> Final predictions where the case ends up</li>
            <li><strong>Path:</strong> The highlighted route your case takes from root to leaf</li>
        </ul>
    </div>
</div>

@push('scripts')
<script>
let treesGenerated = false;

async function generateTrees() {
    if (treesGenerated) {
        alert('Trees already generated. Refresh the page to regenerate.');
        return;
    }

    const btn = document.getElementById('generateTreeBtn');
    const loading = document.getElementById('treeLoading');
    const container = document.getElementById('treesContainer');

    // Show loading
    btn.disabled = true;
    btn.classList.add('opacity-50');
    loading.classList.remove('hidden');
    container.classList.add('hidden');

    try {
        const response = await fetch('{{ route("consultations.generate-trees", $consultation->id) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (!data.success) {
            throw new Error(data.error || 'Failed to generate trees');
        }

        // Display trees
        displayTrees(data.trees);
        treesGenerated = true;

    } catch (error) {
        alert('Error generating trees: ' + error.message);
        btn.disabled = false;
        btn.classList.remove('opacity-50');
    } finally {
        loading.classList.add('hidden');
    }
}

function displayTrees(trees) {
    const container = document.getElementById('treesContainer');
    container.innerHTML = '';

    trees.forEach((tree, index) => {
        const treeSection = document.createElement('div');
        treeSection.className = 'mb-8 border-b border-gray-200 pb-8';

        let pathRulesHtml = '';
        if (tree.path_rules && tree.path_rules.length > 0) {
            pathRulesHtml = `
                <div class="mb-6">
                    <h4 class="font-bold text-gray-900 mb-3">Decision Path for This Case:</h4>
                    <div class="bg-gold-50 border-l-4 border-gold-500 p-6">
                        <div class="space-y-3">
                            ${tree.path_rules.map((rule, idx) => `
                                <div class="flex items-center space-x-3">
                                    <div class="w-8 h-8 bg-gold-500 text-white rounded-full flex items-center justify-center font-bold text-sm">
                                        ${idx + 1}
                                    </div>
                                    <div class="flex-1">
                                        <div class="font-mono text-sm bg-white px-4 py-2 border border-gold-300 rounded">
                                            ${rule.rule}
                                        </div>
                                    </div>
                                    <div class="text-2xl">${idx < tree.path_rules.length - 1 ? '↓' : '✓'}</div>
                                </div>
                            `).join('')}
                            <div class="mt-4 p-4 bg-green-100 border border-green-500 rounded">
                                <div class="font-semibold text-green-900">
                                    Final Leaf Node: <span class="font-mono">#${tree.leaf_node}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        }

        treeSection.innerHTML = `
            <div class="mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xl font-bold text-gray-900">Tree #${tree.tree_number}</h3>
                    <div class="text-sm text-gray-600">
                        Depth: ${tree.max_depth} | Total Nodes: ${tree.total_nodes}
                    </div>
                </div>

                ${pathRulesHtml}

                <div class="bg-gray-50 border border-gray-200 p-4">
                    <h4 class="font-bold text-gray-900 mb-3">Tree Structure:</h4>
                    <div class="overflow-x-auto">
                        <img
                            src="/tree_visualizations/${tree.image_filename}"
                            alt="Decision Tree ${tree.tree_number}"
                            class="w-full h-auto border border-gray-300"
                        />
                    </div>
                </div>

                ${tree.tree_text ? `
                    <div class="mt-4">
                        <details class="bg-gray-50 border border-gray-200 p-4">
                            <summary class="font-bold text-gray-900 cursor-pointer">View Text Representation</summary>
                            <pre class="mt-4 text-xs overflow-x-auto bg-black text-green-400 p-4 rounded font-mono">${tree.tree_text}</pre>
                        </details>
                    </div>
                ` : ''}
            </div>
        `;

        container.appendChild(treeSection);
    });

    container.classList.remove('hidden');
}
</script>
@endpush

    <!-- Step 5: Final Aggregation -->
    <div class="bg-black text-white p-8">
        <div class="flex items-center mb-6">
            <div class="w-12 h-12 bg-gold-500 text-black rounded-full flex items-center justify-center font-bold text-xl mr-4">5</div>
            <h2 class="text-2xl font-bold">Final Score Aggregation</h2>
        </div>

        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-dark-700 p-6 border border-gray-700">
                <div class="text-sm text-gray-400 mb-2">Euclidean</div>
                <div class="text-4xl font-bold text-gold-500">{{ number_format($consultation->euclidean_score * 100, 2) }}%</div>
            </div>
            <div class="bg-dark-700 p-6 border border-gray-700">
                <div class="text-sm text-gray-400 mb-2">Weighted Euclidean</div>
                <div class="text-4xl font-bold text-gold-500">{{ number_format($consultation->weighted_euclidean_score * 100, 2) }}%</div>
            </div>
            <div class="bg-dark-700 p-6 border border-gray-700">
                <div class="text-sm text-gray-400 mb-2">Random Forest</div>
                <div class="text-4xl font-bold text-gold-500">{{ number_format($consultation->random_forest_score * 100, 2) }}%</div>
            </div>
        </div>

        <div class="bg-gold-500 text-black p-8 rounded-lg">
            <div class="text-center">
                <div class="text-sm font-semibold mb-2 uppercase tracking-wide">Final Aggregated Score</div>
                <div class="font-mono text-2xl mb-4">
                    ({{ number_format($consultation->euclidean_score * 100, 2) }} + {{ number_format($consultation->weighted_euclidean_score * 100, 2) }} + {{ number_format($consultation->random_forest_score * 100, 2) }}) / 3
                </div>
                <div class="text-7xl font-bold">
                    {{ number_format((($consultation->euclidean_score + $consultation->weighted_euclidean_score + $consultation->random_forest_score) / 3) * 100, 2) }}%
                </div>
                <div class="text-sm mt-4 font-semibold">
                    Recommended Product: {{ $consultation->product->name }}
                </div>
            </div>
        </div>
    </div>

    <!-- Back Button -->
    <div class="mt-8 text-center">
        <a href="{{ route('consultations.show', $consultation->id) }}"
           class="inline-block bg-white border-2 border-black text-black px-8 py-4 hover:bg-black hover:text-white transition font-semibold">
            ← Back to Consultation Results
        </a>
    </div>
</div>
@endsection
