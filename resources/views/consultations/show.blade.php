@extends('layouts.app')

@section('title', 'Consultation Results')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <a href="{{ route('consultations.index') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
            ← Back to Consultations
        </a>
        <h1 class="text-3xl font-bold text-gray-900">Consultation Results</h1>
        <p class="text-gray-600">AI-powered insurance recommendation for {{ $consultation->customer->name }}</p>
    </div>

    <!-- Top Recommendation -->
    <div class="bg-gradient-to-r from-green-600 to-green-800 rounded-lg shadow-xl p-8 text-white mb-8">
        <div class="flex items-center justify-between">
            <div>
                <div class="text-sm uppercase tracking-wide text-green-200 mb-2">Top Recommendation</div>
                <h2 class="text-4xl font-bold mb-4">{{ $consultation->product->name }}</h2>
                <p class="text-green-100 text-lg mb-4">{{ $consultation->product->description }}</p>

                <div class="flex items-center space-x-6">
                    <div>
                        <div class="text-green-200 text-sm">Base Premium</div>
                        <div class="text-2xl font-bold">Rp {{ number_format($consultation->product->base_premium, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="text-green-200 text-sm">Insurance Period</div>
                        <div class="text-2xl font-bold">{{ $consultation->insurance_period }} years</div>
                    </div>
                </div>
            </div>

            <div class="text-center">
                @php
                    $avgScore = ($consultation->euclidean_score + $consultation->weighted_euclidean_score + $consultation->random_forest_score) / 3 * 100;
                @endphp
                <div class="bg-white text-green-600 rounded-full w-32 h-32 flex items-center justify-center">
                    <div>
                        <div class="text-5xl font-bold">{{ round($avgScore, 1) }}</div>
                        <div class="text-sm">% Match</div>
                    </div>
                </div>
                <div class="mt-3 text-green-200 text-sm">Average Score</div>
            </div>
        </div>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        <!-- Left Column: Customer & Consultation Info -->
        <div class="lg:col-span-1">
            <!-- Customer Info -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Customer Information</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-gray-500">Name</div>
                        <div class="font-semibold">{{ $consultation->customer->name }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Email</div>
                        <div class="font-semibold">{{ $consultation->customer->email }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Gender</div>
                        <div class="font-semibold capitalize">{{ $consultation->customer->gender }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Age</div>
                        <div class="font-semibold">{{ $consultation->customer->age }} years</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Income</div>
                        <div class="font-semibold">Rp {{ number_format($consultation->customer->income, 0, ',', '.') }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Dependents</div>
                        <div class="font-semibold">{{ $consultation->customer->num_dependents }}</div>
                    </div>
                </div>
            </div>

            <!-- Health Metrics -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Health Metrics</h3>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-gray-600">BMI</span>
                        <span class="font-bold text-lg">{{ $consultation->bmi }}</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        @php
                            $bmiPercent = min(($consultation->bmi / 40) * 100, 100);
                            $bmiColor = $consultation->bmi < 18.5 ? 'bg-yellow-500' :
                                       ($consultation->bmi < 25 ? 'bg-green-500' :
                                       ($consultation->bmi < 30 ? 'bg-orange-500' : 'bg-red-500'));
                        @endphp
                        <div class="{{ $bmiColor }} h-2 rounded-full" style="width: {{ $bmiPercent }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500">
                        @if($consultation->bmi < 18.5)
                            Underweight
                        @elseif($consultation->bmi < 25)
                            Normal
                        @elseif($consultation->bmi < 30)
                            Overweight
                        @else
                            Obese
                        @endif
                    </div>

                    <div class="flex justify-between items-center mt-4">
                        <span class="text-gray-600">Health Risk Score</span>
                        <span class="font-bold text-lg">{{ $consultation->health_risk_score }} / 25</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        @php
                            $riskPercent = ($consultation->health_risk_score / 25) * 100;
                            $riskColor = $consultation->health_risk_score < 5 ? 'bg-green-500' :
                                        ($consultation->health_risk_score < 10 ? 'bg-yellow-500' :
                                        ($consultation->health_risk_score < 15 ? 'bg-orange-500' : 'bg-red-500'));
                        @endphp
                        <div class="{{ $riskColor }} h-2 rounded-full" style="width: {{ $riskPercent }}%"></div>
                    </div>
                    <div class="text-xs text-gray-500">
                        @if($consultation->health_risk_score < 5)
                            Low Risk
                        @elseif($consultation->health_risk_score < 10)
                            Medium Risk
                        @elseif($consultation->health_risk_score < 15)
                            High Risk
                        @else
                            Very High Risk
                        @endif
                    </div>
                </div>
            </div>

            <!-- Consultation Details -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Consultation Details</h3>
                <div class="space-y-3 text-sm">
                    <div>
                        <div class="text-gray-500">Agent</div>
                        <div class="font-semibold">{{ $consultation->agent->name }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Date</div>
                        <div class="font-semibold">{{ $consultation->created_at->format('M d, Y h:i A') }}</div>
                    </div>
                    <div>
                        <div class="text-gray-500">Premium Period</div>
                        <div class="font-semibold">{{ $consultation->premium_payment_period }} years</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Algorithm Results & Goals -->
        <div class="lg:col-span-2">
            <!-- Algorithm Scores -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-6">Algorithm Scores</h3>

                <div class="grid md:grid-cols-3 gap-6 mb-6">
                    <!-- Euclidean -->
                    <div class="text-center p-6 bg-blue-50 rounded-lg">
                        <div class="text-4xl font-bold text-blue-600 mb-2">
                            {{ round($consultation->euclidean_score * 100, 1) }}%
                        </div>
                        <div class="text-sm font-semibold text-gray-700 mb-2">Euclidean Distance</div>
                        <div class="text-xs text-gray-500">Standard similarity measure</div>
                    </div>

                    <!-- Weighted -->
                    <div class="text-center p-6 bg-green-50 rounded-lg">
                        <div class="text-4xl font-bold text-green-600 mb-2">
                            {{ round($consultation->weighted_euclidean_score * 100, 1) }}%
                        </div>
                        <div class="text-sm font-semibold text-gray-700 mb-2">Weighted Euclidean</div>
                        <div class="text-xs text-gray-500">Feature-importance weighted</div>
                    </div>

                    <!-- Random Forest -->
                    <div class="text-center p-6 bg-purple-50 rounded-lg">
                        <div class="text-4xl font-bold text-purple-600 mb-2">
                            {{ round($consultation->random_forest_score * 100, 1) }}%
                        </div>
                        <div class="text-sm font-semibold text-gray-700 mb-2">Random Forest</div>
                        <div class="text-xs text-gray-500">Machine learning proximity</div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-lg p-4">
                    <div class="text-sm text-gray-600 mb-2">
                        <strong>How it works:</strong> Our AI system analyzed {{ \App\Models\CaseModel::count() }} historical cases using three different algorithms to find the best match.
                    </div>
                    <div class="text-xs text-gray-500">
                        The final recommendation combines all three scores to provide the most accurate result.
                    </div>
                </div>
            </div>

            <!-- Financial Goals -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Financial Goals</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach($consultation->financial_goals as $goal)
                        <span class="bg-blue-100 text-blue-800 px-4 py-2 rounded-full text-sm font-semibold">
                            {{ ucwords(str_replace('_', ' ', $goal)) }}
                        </span>
                    @endforeach
                </div>
            </div>

            <!-- Health Conditions -->
            @php
                $hasHealthConditions = $consultation->weight_change_last_year ||
                                      $consultation->smoked_last_year ||
                                      $consultation->hospitalization_last_5_years ||
                                      $consultation->lab_tests_last_5_years ||
                                      $consultation->accident_poisoning_last_5_years ||
                                      $consultation->has_disability ||
                                      $consultation->has_serious_illness ||
                                      $consultation->receiving_treatment ||
                                      $consultation->family_medical_history ||
                                      $consultation->is_pregnant;
            @endphp

            @if($hasHealthConditions)
                <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                    <h3 class="text-lg font-bold text-gray-900 mb-4">Health Conditions Reported</h3>
                    <div class="space-y-2">
                        @if($consultation->weight_change_last_year)
                            <div class="flex items-center text-sm">
                                <span class="text-orange-500 mr-2">⚠️</span>
                                <span>Significant weight change in last year</span>
                            </div>
                        @endif
                        @if($consultation->smoked_last_year)
                            <div class="flex items-center text-sm">
                                <span class="text-orange-500 mr-2">⚠️</span>
                                <span>Smoked in the last year</span>
                            </div>
                        @endif
                        @if($consultation->hospitalization_last_5_years)
                            <div class="flex items-center text-sm">
                                <span class="text-red-500 mr-2">⚠️</span>
                                <span>Hospitalized in last 5 years</span>
                            </div>
                        @endif
                        @if($consultation->has_serious_illness)
                            <div class="flex items-center text-sm">
                                <span class="text-red-500 mr-2">⚠️</span>
                                <span>Has serious illness</span>
                            </div>
                        @endif
                        @if($consultation->has_disability)
                            <div class="flex items-center text-sm">
                                <span class="text-red-500 mr-2">⚠️</span>
                                <span>Has disability</span>
                            </div>
                        @endif
                        @if($consultation->receiving_treatment)
                            <div class="flex items-center text-sm">
                                <span class="text-orange-500 mr-2">⚠️</span>
                                <span>Currently receiving medical treatment</span>
                            </div>
                        @endif
                        @if($consultation->family_medical_history)
                            <div class="flex items-center text-sm">
                                <span class="text-yellow-500 mr-2">⚠️</span>
                                <span>Family medical history</span>
                            </div>
                        @endif
                    </div>

                    @if($consultation->health_details)
                        <div class="mt-4 p-4 bg-gray-50 rounded">
                            <div class="text-sm font-semibold text-gray-700 mb-1">Additional Details:</div>
                            <div class="text-sm text-gray-600">{{ $consultation->health_details }}</div>
                        </div>
                    @endif
                </div>
            @endif

            <!-- Alternative Products -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h3 class="text-lg font-bold text-gray-900 mb-4">Alternative Products</h3>
                <p class="text-sm text-gray-600 mb-4">Other products that may also suit this customer</p>

                <div class="space-y-3">
                    @foreach($allProducts->where('id', '!=', $consultation->product_id)->take(3) as $product)
                        <div class="p-4 border border-gray-200 rounded-lg hover:border-blue-300 transition">
                            <div class="flex justify-between items-start">
                                <div>
                                    <div class="font-semibold text-gray-900">{{ $product->name }}</div>
                                    <div class="text-sm text-gray-600">{{ $product->description }}</div>
                                </div>
                                <div class="text-right">
                                    <div class="font-bold text-blue-600">Rp {{ number_format($product->base_premium, 0, ',', '.') }}</div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
