@extends('layouts.app')

@section('title', 'CBR Process Visualization')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <!-- Loading state -->
    <div id="loading" class="text-center py-12">
        <div class="inline-block animate-spin rounded-full h-12 w-12 border-b-2 border-yellow-600"></div>
        <p class="mt-4 text-gray-600">Loading CBR process details...</p>
    </div>

    <!-- Process visualization (populated by JavaScript) -->
    <div id="process-content" class="hidden space-y-6"></div>
</div>

<script>
const apiToken = document.querySelector('meta[name="api-token"]').content;
const consultationId = {{ $consultationId }};

async function loadProcess() {
    try {
        const response = await fetch(`/api/recommendations/${consultationId}`, {
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${apiToken}`
            }
        });

        if (!response.ok) {
            throw new Error('Failed to load process details');
        }

        const result = await response.json();
        displayProcess(result.data);

    } catch (error) {
        console.error('Error:', error);
        document.getElementById('loading').innerHTML = `
            <div class="text-red-600">
                <p class="text-lg font-semibold">Error loading process</p>
                <p class="text-sm">${error.message}</p>
            </div>
        `;
    }
}

function displayProcess(data) {
    document.getElementById('loading').classList.add('hidden');

    console.log(data);

    // const consultation = data.consultation;
    const algorithmDetails = data.algorithm_details;

    if (!algorithmDetails) {
        document.getElementById('process-content').innerHTML = `
            <div class="text-center py-12 bg-yellow-50 border border-yellow-200 rounded">
                <p class="text-yellow-800 text-lg">No algorithm details available for this consultation.</p>
                <a href="/consultations/${data.id}" class="text-yellow-600 hover:text-yellow-700 mt-4 inline-block">
                    ← Back to Consultation
                </a>
            </div>
        `;
        document.getElementById('process-content').classList.remove('hidden');
        return;
    }

    // Build the visualization
    let html = `
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">CBR Process Visualization</h1>
                <p class="text-gray-600 mt-2">Customer: ${data.customer.name}</p>
            </div>
            <a href="/consultations/${data.id}" class="text-yellow-600 hover:text-yellow-700 font-semibold">
                ← Back to Results
            </a>
        </div>

        <!-- Feature Vector -->
        <div class="bg-white border border-gray-200 p-6 rounded-lg">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">Feature Vector (25 Dimensions)</h2>
            <div class="bg-gray-50 border border-gray-200 p-4 rounded">
                <p class="font-mono text-sm break-all">${data.feature_vector}</p>
            </div>
            <p class="text-sm text-gray-600 mt-2">
                This vector represents the customer's profile across demographics, health, insurance needs, and financial goals.
            </p>
        </div>
    `;

    // Algorithm Results
    if (algorithmDetails.euclidean) {
        html += `
        <div class="bg-white border border-gray-200 p-6 rounded-lg">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">1. Euclidean Distance Algorithm</h2>
            <p class="text-gray-700 mb-4">
                Calculates the straight-line distance between the new case and historical cases in 25-dimensional space.
                Lower distance = higher similarity.
            </p>

            <div class="bg-gray-50 border border-gray-200 p-4 rounded">
                <p class="font-semibold mb-2">Top 5 Similar Cases:</p>
                <div class="space-y-2">
                    ${algorithmDetails.euclidean.top_5_matches.map((c, i) => `
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <div>
                                <span class="font-semibold">${i + 1}. Case #${c.case_id}</span>
                                <span class="text-gray-600 ml-2">→ ${c.product_name}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm text-gray-600">Distance: ${c.distance.toFixed(4)}</span>
                                <span class="ml-4 font-semibold text-yellow-600">${(c.similarity * 100).toFixed(1)}%</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
        `;
    }

    if (algorithmDetails.weighted_euclidean) {
        html += `
        <div class="bg-white border border-gray-200 p-6 rounded-lg">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">2. Weighted Euclidean Distance Algorithm</h2>
            <p class="text-gray-700 mb-4">
                Similar to Euclidean but applies feature weights to prioritize more important attributes.
                Features like financial goals and health factors have higher weights.
            </p>

            <div class="bg-gray-50 border border-gray-200 p-4 rounded mb-4">
                <p class="font-semibold mb-2">Feature Weights Applied:</p>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                    ${(algorithmDetails.weighted_euclidean.weights_used || []).map(item => `
                        <div class="flex justify-between">
                            <span class="text-gray-700">${item.feature}:</span>
                            <span class="font-semibold">${item.weight.toFixed(2)}</span>
                        </div>
                    `).join('')}
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-200 p-4 rounded">
                <p class="font-semibold mb-2">Top 5 Weighted Similar Cases:</p>
                <div class="space-y-2">
                    ${algorithmDetails.weighted_euclidean.top_5_matches.map((c, i) => `
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <div>
                                <span class="font-semibold">${i + 1}. Case #${c.case_id}</span>
                                <span class="text-gray-600 ml-2">→ ${c.product_name}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm text-gray-600">Weighted Distance: ${c.distance.toFixed(4)}</span>
                                <span class="ml-4 font-semibold text-yellow-600">${(c.similarity * 100).toFixed(1)}%</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        </div>
        `;
    }

    // console.log(algorithmDetails.random_forest.top_5_matches);
    // console.log(algorithmDetails.random_forest.top_5_matches[0].total_trees);

    if (algorithmDetails.random_forest) {
        html += `
        <div class="bg-white border border-gray-200 p-6 rounded-lg">
            <h2 class="text-2xl font-bold text-gray-900 mb-4">3. Random Forest Proximity Algorithm</h2>
            <p class="text-gray-700 mb-4">
                Uses a trained Random Forest model to find cases that end up in the same leaf nodes across decision trees.
                Cases in the same leaves are considered similar.
            </p>

            <div class="bg-gray-50 border border-gray-200 p-4 rounded mb-4">
                <p class="font-semibold mb-2">Model Information:</p>
                <div class="grid grid-cols-3 gap-4 text-sm">
                    <div>
                        <p class="text-gray-600">Number of Trees</p>
                        <p class="font-semibold">${algorithmDetails.random_forest.top_5_matches[0].total_trees || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Training Cases</p>
                        <p class="font-semibold">${algorithmDetails.random_forest.training_size || 'N/A'}</p>
                    </div>
                    <div>
                        <p class="text-gray-600">Max Depth</p>
                        <p class="font-semibold">${algorithmDetails.random_forest.max_depth || 'N/A'}</p>
                    </div>
                </div>
            </div>

            <div class="bg-gray-50 border border-gray-200 p-4 rounded">
                <p class="font-semibold mb-2">Top 5 Proximate Cases:</p>
                <div class="space-y-2">
                    ${algorithmDetails.random_forest.top_5_matches.map((c, i) => `
                        <div class="flex justify-between items-center py-2 border-b border-gray-200">
                            <div>
                                <span class="font-semibold">${i + 1}. Case #${c.case_id}</span>
                                <span class="text-gray-600 ml-2">→ ${c.product_name}</span>
                            </div>
                            <div class="text-right">
                                <span class="text-sm text-gray-600">Proximity: ${c.matches.toFixed(4)}</span>
                                <span class="ml-4 font-semibold text-yellow-600">${(c.similarity * 100).toFixed(1)}%</span>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>

            <!-- Tree Visualization Button -->
            <div class="mt-6">
                <button onclick="generateTrees()" id="generate-trees-btn"
                        class="bg-gradient-to-r from-yellow-600 to-yellow-500 text-white px-6 py-3 rounded hover:from-yellow-700 hover:to-yellow-600">
                    Generate Decision Tree Visualizations
                </button>
                <div id="tree-status" class="mt-4 hidden"></div>
                <div id="tree-images" class="mt-4 grid grid-cols-1 gap-4"></div>
            </div>
        </div>
        `;
    }

    // Aggregate Results
    html += `
    <div class="bg-gradient-to-r from-yellow-600 to-yellow-500 text-white p-6 rounded-lg">
        <h2 class="text-2xl font-bold mb-4">Final Aggregated Result</h2>
        <div class="grid grid-cols-3 gap-6">
            <div>
                <p class="text-yellow-100 text-sm">Euclidean Score</p>
                <p class="text-3xl font-bold">${(data.euclidean_score * 100).toFixed(1)}%</p>
            </div>
            <div>
                <p class="text-yellow-100 text-sm">Weighted Euclidean Score</p>
                <p class="text-3xl font-bold">${(data.weighted_euclidean_score * 100).toFixed(1)}%</p>
            </div>
            <div>
                <p class="text-yellow-100 text-sm">Random Forest Score</p>
                <p class="text-3xl font-bold">${(data.random_forest_score * 100).toFixed(1)}%</p>
            </div>
        </div>
        <div class="mt-6 pt-6 border-t border-yellow-400 text-center">
            <p class="text-yellow-100 text-sm">Overall Match (Average of 3 Algorithms)</p>
            <p class="text-5xl font-bold">${Math.round((data.euclidean_score + data.weighted_euclidean_score + data.random_forest_score) / 3 * 100)}%</p>
        </div>
    </div>
    `;

    document.getElementById('process-content').innerHTML = html;
    document.getElementById('process-content').classList.remove('hidden');
}

async function generateTrees() {
    const btn = document.getElementById('generate-trees-btn');
    const statusDiv = document.getElementById('tree-status');
    const imagesDiv = document.getElementById('tree-images');

    btn.disabled = true;
    btn.innerHTML = '<span class="inline-block animate-spin mr-2">⟳</span> Generating...';

    statusDiv.classList.remove('hidden');
    statusDiv.innerHTML = '<p class="text-gray-600">Generating decision tree visualizations...</p>';

    try {
        const response = await fetch(`/api/visualizations/trees/${consultationId}`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Authorization': `Bearer ${apiToken}`
            }
        });

        const result = await response.json();

        if (!response.ok) {
            throw new Error(result.message || 'Failed to generate trees');
        }

        statusDiv.innerHTML = '<p class="text-green-600 font-semibold">✓ Trees generated successfully!</p>';

        // Display tree images
        imagesDiv.innerHTML = result.data.trees.map((tree, i) => `
            <div class="bg-white border border-gray-200 p-4 rounded">
                <h3 class="font-bold text-lg mb-2">Decision Tree ${i + 1}</h3>
                <img src="/tree_visualizations/${tree.image_filename}" alt="Tree ${i + 1}" class="w-full border border-gray-300 rounded">
            </div>
        `).join('');

        btn.textContent = 'Regenerate Trees';
        btn.disabled = false;

    } catch (error) {
        console.error('Error:', error);
        statusDiv.innerHTML = `<p class="text-red-600">✗ Error: ${error.message}</p>`;
        btn.textContent = 'Try Again';
        btn.disabled = false;
    }
}

loadProcess();
</script>
@endsection
