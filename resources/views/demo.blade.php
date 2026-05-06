<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>CBR Process Visualization - Thesis Demo</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .step-box {
            border-left: 4px solid #3b82f6;
            background: #f8fafc;
            margin-bottom: 2rem;
        }
        .code-block {
            background: #1e293b;
            color: #e2e8f0;
            padding: 1rem;
            border-radius: 0.5rem;
            overflow-x: auto;
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto px-4 py-8 max-w-6xl">
        <h1 class="text-3xl font-bold text-gray-800 mb-2">CBR System Process Visualization</h1>
        <p class="text-gray-600 mb-8">Complete step-by-step demonstration for thesis defense</p>

        <!-- Quick Test Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Quick Test</h2>
            <p class="text-sm text-gray-600 mb-4">Use preset data for demonstration</p>

            <button onclick="runDemo()" class="bg-blue-600 text-white px-6 py-3 rounded-lg hover:bg-blue-700 transition">
                Run CBR Process Visualization
            </button>

            <div class="mt-4 text-sm text-gray-500">
                <strong>Test Case:</strong> John Doe, 35 years old, Software Engineer, Rp 15M income, 2 dependents
            </div>
        </div>

        <!-- Results Container -->
        <div id="results" class="hidden">
            <!-- Step 1: Input Data -->
            <div class="step-box bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">STEP 1: INPUT DATA</h2>
                <div id="input-data" class="grid grid-cols-2 gap-4"></div>
            </div>

            <!-- Step 2: Preprocessing -->
            <div class="step-box bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">STEP 2: PREPROCESSING</h2>
                <div id="preprocessing"></div>
            </div>

            <!-- Step 3: Euclidean -->
            <div class="step-box bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">STEP 3: EUCLIDEAN DISTANCE</h2>
                <div id="euclidean"></div>
            </div>

            <!-- Step 4: Weighted Euclidean -->
            <div class="step-box bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">STEP 4: WEIGHTED EUCLIDEAN</h2>
                <div id="weighted"></div>
            </div>

            <!-- Step 5: Random Forest -->
            <div class="step-box bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">⭐ STEP 5: RANDOM FOREST PROXIMITY</h2>
                <div id="random-forest"></div>
            </div>

            <!-- Step 6: Aggregation -->
            <div class="step-box bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-blue-600 mb-4">STEP 6: AGGREGATION</h2>
                <div id="aggregation"></div>
            </div>

            <!-- Final Results -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-bold mb-4">FINAL RECOMMENDATIONS</h2>
                <div id="final-results"></div>
            </div>
        </div>

        <!-- Loading Indicator -->
        <div id="loading" class="hidden text-center py-12">
            <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-blue-600"></div>
            <p class="mt-4 text-gray-600">Processing CBR algorithms...</p>
        </div>

        <!-- Error Display -->
        <div id="error" class="hidden bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded mb-4"></div>
    </div>

    <script>
        // Test data
        const testData = {
            name: "John Doe",
            gender: "male",
            dob: "1990-05-15",
            occupation: "Software Engineer",
            income: 15000000,
            num_dependents: 2,
            financial_goals: ["family_protection", "education"],
            insurance_period: 20,
            premium_payment_period: 15,
            overseas_plans: false,
            has_existing_health_insurance: false,
            height: 175,
            weight: 75,
            weight_change_last_year: false,
            smoked_last_year: false,
            hospitalization_last_5_years: false,
            lab_tests_last_5_years: false,
            accident_poisoning_last_5_years: false,
            has_disability: false,
            has_serious_illness: false,
            receiving_treatment: false,
            family_medical_history: false,
            is_pregnant: null,
            health_details: null
        };

        // async function runDemo() {
        //     // Hide error, results
        //     document.getElementById('error').classList.add('hidden');
        //     document.getElementById('results').classList.add('hidden');
        //     document.getElementById('loading').classList.remove('hidden');

        //     try {
        //         // Call API
        //         const response = await fetch('/api/v1/recommendations/get', {
        //             method: 'POST',
        //             headers: {
        //                 'Content-Type': 'application/json',
        //                 'Accept': 'application/json',
        //                 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        //             },
        //             body: JSON.stringify(testData)
        //         });

        //         const data = await response.json();

        //         if (!data.success) {
        //             throw new Error(data.error || 'Unknown error');
        //         }

        //         // Display results
        //         displayResults(data);

        //     } catch (error) {
        //         console.error('Error:', error);
        //         const errorDiv = document.getElementById('error');
        //         errorDiv.textContent = 'Error: ' + error.message;
        //         errorDiv.classList.remove('hidden');
        //     } finally {
        //         document.getElementById('loading').classList.add('hidden');
        //     }
        // }

async function runDemo() {
    // Hide error, results
    document.getElementById('error').classList.add('hidden');
    document.getElementById('results').classList.add('hidden');
    document.getElementById('loading').classList.remove('hidden');

    let data = null; // Initialize data variable

    try {
        // Call API
        const response = await fetch('/api/v1/recommendations/get', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(testData)
        });

        // Check if response is ok
        if (!response.ok) {
            const errorText = await response.text();
            console.error('HTTP Error:', response.status, errorText);
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }

        // Parse JSON
        data = await response.json();
        console.log('Response data:', data);

        if (!data.success) {
            throw new Error(data.error || 'Unknown error');
        }

        // Display results
        displayResults(data);

    } catch (error) {
        console.error('Full error:', error);
        console.error('Response data:', data);

        const errorDiv = document.getElementById('error');

        // Show detailed error info
        let errorMessage = 'Error: ' + error.message;

        if (data && data.details) {
            errorMessage += '\n\nDetails:\n' + JSON.stringify(data.details, null, 2);
        }

        if (data && data.message) {
            errorMessage += '\n\nMessage: ' + data.message;
        }

        errorDiv.innerHTML = '<pre class="whitespace-pre-wrap text-sm">' + errorMessage + '</pre>';
        errorDiv.classList.remove('hidden');
    } finally {
        document.getElementById('loading').classList.add('hidden');
    }
}

        function displayResults(data) {
            // Show results
            document.getElementById('results').classList.remove('hidden');

            // Step 1: Input
            displayInputData(data.input_data);

            // Step 2: Preprocessing
            displayPreprocessing(data.preprocessed, data.feature_vector);

            // Step 3-5: Algorithms
            displayAlgorithm('euclidean', data.algorithm_results.euclidean);
            displayAlgorithm('weighted', data.algorithm_results.weighted);
            displayAlgorithm('random-forest', data.algorithm_results.random_forest);

            // Step 6: Aggregation
            displayAggregation(data.recommendations);

            // Final
            displayFinalResults(data.recommendations, data.execution_time);

            // Scroll
            document.getElementById('results').scrollIntoView({ behavior: 'smooth' });
        }

        function displayInputData(input) {
            const html = `
                <div class="space-y-2">
                    <div><strong>Name:</strong> ${input.name}</div>
                    <div><strong>Gender:</strong> ${input.gender}</div>
                    <div><strong>DOB:</strong> ${input.dob}</div>
                    <div><strong>Occupation:</strong> ${input.occupation}</div>
                    <div><strong>Income:</strong> Rp ${input.income.toLocaleString()}</div>
                    <div><strong>Dependents:</strong> ${input.num_dependents}</div>
                    <div><strong>Goals:</strong> ${input.financial_goals.join(', ')}</div>
                    <div><strong>Height:</strong> ${input.height} cm</div>
                    <div><strong>Weight:</strong> ${input.weight} kg</div>
                </div>
            `;
            document.getElementById('input-data').innerHTML = html;
        }

        function displayPreprocessing(prep, vector) {
            const bmiCategory = prep.bmi < 18.5 ? 'Underweight' :
                               prep.bmi < 25 ? 'Normal' :
                               prep.bmi < 30 ? 'Overweight' : 'Obese';

            const html = `
                <div class="space-y-4">
                    <div class="bg-green-50 border-l-4 border-green-500 p-4">
                        <p class="font-semibold text-green-800">✓ Calculated Metrics:</p>
                        <ul class="mt-2 space-y-1 text-sm">
                            <li><strong>Age:</strong> ${prep.age} years</li>
                            <li><strong>BMI:</strong> ${prep.bmi.toFixed(2)} (${bmiCategory})</li>
                            <li><strong>Health Risk:</strong> ${prep.health_risk_score} / 25</li>
                        </ul>
                    </div>
                    <div>
                        <p class="font-semibold mb-2">18D Feature Vector:</p>
                        <div class="code-block">[${vector.map(v => v.toFixed(3)).join(', ')}]</div>
                    </div>
                </div>
            `;
            document.getElementById('preprocessing').innerHTML = html;
        }

        function displayAlgorithm(id, results) {
            let html = `
                <div>
                    <p class="font-semibold mb-2">Top 5 Results:</p>
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="p-2 text-left">Rank</th>
                                <th class="p-2 text-left">Case ID</th>
                                <th class="p-2 text-left">Product</th>
                                <th class="p-2 text-right">Similarity</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            results.forEach((r, i) => {
                html += `
                    <tr class="border-b">
                        <td class="p-2">${i + 1}</td>
                        <td class="p-2">#${r.case_id}</td>
                        <td class="p-2">${r.product_name}</td>
                        <td class="p-2 text-right font-mono">${r.similarity.toFixed(4)}</td>
                    </tr>
                `;
            });

            html += `</tbody></table></div>`;
            document.getElementById(id).innerHTML = html;
        }

        function displayAggregation(recommendations) {
            let html = '<div class="space-y-4">';

            recommendations.slice(0, 3).forEach(rec => {
                html += `
                    <div class="bg-gray-50 p-4 rounded border-l-4 border-blue-500">
                        <p class="font-semibold text-lg mb-2">${rec.product_name}</p>
                        <div class="grid grid-cols-3 gap-4 text-sm">
                            <div>Euclidean: ${rec.euclidean_score.toFixed(4)}</div>
                            <div>Weighted: ${rec.weighted_euclidean_score.toFixed(4)}</div>
                            <div>RF: ${rec.random_forest_score.toFixed(4)}</div>
                        </div>
                        <div class="mt-2 text-xl font-bold text-blue-600">${rec.match_percentage}% Match</div>
                    </div>
                `;
            });

            html += '</div>';
            document.getElementById('aggregation').innerHTML = html;
        }

        function displayFinalResults(recommendations, time) {
            let html = '<div class="space-y-4">';

            recommendations.forEach((rec, i) => {
                const bgColor = i === 0 ? 'bg-yellow-400 text-yellow-900' : 'bg-white/20';
                html += `
                    <div class="${bgColor} p-4 rounded-lg">
                        <div class="flex justify-between">
                            <div>
                                <div class="text-2xl font-bold">Rank ${rec.rank}: ${rec.product_name}</div>
                                <div class="mt-2 text-sm opacity-90">
                                    E: ${rec.euclidean_score.toFixed(3)} |
                                    W: ${rec.weighted_euclidean_score.toFixed(3)} |
                                    RF: ${rec.random_forest_score.toFixed(3)}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="text-4xl font-bold">${rec.match_percentage}%</div>
                            </div>
                        </div>
                    </div>
                `;
            });

            html += `
                <div class="mt-6 pt-6 border-t border-white/30">
                    <p class="font-semibold mb-2">Execution Time:</p>
                    <div class="text-lg">Total: <span class="font-mono">${time.total} ms</span></div>
                </div>
            </div>`;

            document.getElementById('final-results').innerHTML = html;
        }
    </script>
</body>
</html>
