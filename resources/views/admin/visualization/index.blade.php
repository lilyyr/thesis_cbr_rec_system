@extends('layouts.app')

@section('title', 'Visualization Comparison')

@section('content')
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Model Stability: F1-Score vs n_estimators</h2>
        <div id="stability-chart" style="width:100%;height:400px;"></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow mt-6">
        <h2 class="text-xl font-bold mb-4">Tree Depth: max_depth vs F1-Score</h2>
        <div id="depth-linechart" style="width:100%;height:400px;"></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Feature Strategy: max_features vs F1-Score</h2>
        <div id="feature-boxplot" style="width:100%;height:450px;"></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow">
        <h2 class="text-xl font-bold mb-4">Leaf Size: min_samples_leaf vs F1-Score</h2>
        <div id="leaf-boxplot" style="width:100%;height:450px;"></div>
    </div>
    <div class="bg-white p-6 rounded-lg shadow mt-6 border-2">
        <h2 class="text-xl font-bold mb-4">Algorithm Performance Comparison</h2>
        <div id="comparison-barchart" style="width:100%;height:500px;"></div>
    </div>

@endsection

@push('scripts')
    <script src="https://cdn.plot.ly/plotly-2.24.1.min.js"></script>


    <script>
        // ==========================================
        // 1. N ESTIMATOR STABILITY CHART 
        // ==========================================
        const rawData = {!! $nEstimatorsData !!};

        const xValues = rawData.map(row => row.n_estimators);
        const yValues = rawData.map(row => row.f1_score);

        const trace = {
            x: xValues,
            y: yValues,
            type: 'scatter',
            mode: 'lines+markers',
            line: { shape: 'spline', width: 3 },
            marker: { size: 8 },
            name: 'F1-Score'
        };

        const layout = {
            xaxis: { title: 'Number of Estimators (n_estimators)' },
            yaxis: { title: 'F1-Score', range: [0, 1] },
            margin: { l: 50, r: 20, t: 20, b: 50 }
        };

        Plotly.newPlot('stability-chart', [trace], layout);


        // ==========================================
        // 2. MAX DEPTH LINE VISUALIZATION
        // ==========================================
        const depthData = {!! $maxDepthData !!};

        // FIX: Sort the array so that null (Unlimited) is pushed to the very end
        depthData.sort((a, b) => {
            if (a.max_depth === null) return 1;  // Move 'a' to the end
            if (b.max_depth === null) return -1; // Move 'b' to the end
            return a.max_depth - b.max_depth;    // Otherwise sort numbers normally
        });

        const xValuesDepth = depthData.map(row => row.max_depth === null ? 'Unlimited' : row.max_depth);
        const yValuesDepth = depthData.map(row => row.f1_score);

        const traceDepth = {
            x: xValuesDepth,
            y: yValuesDepth,
            type: 'scatter',
            mode: 'lines+markers',
            line: { shape: 'spline', width: 3, color: '#f59e0b' },
            marker: { size: 8 },
            name: 'F1-Score'
        };

        const layoutDepth = {
            xaxis: { title: 'Maximum Depth (max_depth)', type: 'category' },
            yaxis: { title: 'F1-Score', range: [0, 1.05] },
            margin: { l: 50, r: 20, t: 20, b: 50 }
        };

        Plotly.newPlot('depth-linechart', [traceDepth], layoutDepth);


        // ==========================================
        // 3. MAX FEATURES VISUALIZATION
        // ==========================================
        const featureData = {!! $maxFeaturesData !!};

        // Sort to group the categories together so the line doesn't tangle
        featureData.sort((a, b) => {
            if (a.max_features === null) return 1;
            if (b.max_features === null) return -1;
            return a.max_features > b.max_features ? 1 : -1;
        });

        const xCategories = featureData.map(row => row.max_features === null ? 'All Features (None)' : row.max_features);
        const yValuesMaxFeature = featureData.map(row => row.f1_score);

        const traceFeature = {
            x: xCategories,
            y: yValuesMaxFeature,
            type: 'scatter',           // CHANGED FROM 'box'
            mode: 'lines+markers',     // ADDED FOR LINE CHART
            line: { shape: 'spline', width: 3, color: '#3b82f6' },
            marker: { size: 8 },
            name: 'F1-Score'
        };

        const layoutMaxFeature = {
            xaxis: { title: 'Max Features Strategy', type: 'category' },
            yaxis: { title: 'F1-Score', range: [0, 1.05] },
            margin: { l: 50, r: 20, t: 20, b: 50 }
        };

        Plotly.newPlot('feature-boxplot', [traceFeature], layoutMaxFeature);


        // ==========================================
        // 4. MINIMUM SAMPLES LEAF VISUALIZATION
        // ==========================================
        const mssData = {!! $mssData !!};

        // Sort ascending so the line draws cleanly from left to right
        mssData.sort((a, b) => a.min_samples_leaf - b.min_samples_leaf);

        const xCategoriesMss = mssData.map(row => `Leaf Size ${row.min_samples_leaf}`);
        const yValuesMss = mssData.map(row => row.f1_score);

        const traceMss = {
            x: xCategoriesMss,
            y: yValuesMss,
            type: 'scatter',           // CHANGED FROM 'box'
            mode: 'lines+markers',     // ADDED FOR LINE CHART
            line: { shape: 'spline', width: 3, color: '#10b981' },
            marker: { size: 8 },
            name: 'F1-Score'
        };

        const layoutMss = {
            xaxis: { title: 'Minimum Samples Per Leaf', type: 'category' },
            yaxis: { title: 'F1-Score', range: [0, 1.05] },
            margin: { l: 50, r: 20, t: 20, b: 50 }
        };

        Plotly.newPlot('leaf-boxplot', [traceMss], layoutMss);

        // ==========================================
        // 5. FINAL ALGORITHM COMPARISON (GROUPED BAR)
        // ==========================================
        const compData = {!! $comparisonData !!};

        // The metrics we want to compare on the X-axis
        const metrics = ['F1-Score', 'MRR', 'HR@3', 'HR@5'];

        // Helper function to extract the 4 metrics for a specific algorithm safely
        const getMetricsForAlgo = (algoName) => {
            const row = compData.find(r => r.algorithm_name === algoName);
            if (!row) return [0, 0, 0, 0]; // Fallback if data is missing
            return [row.f1_score, row.mrr, row.hr_at_3, row.hr_at_5];
        };

        // Trace 1: Standard Euclidean (Gray)
        const traceEuclidean = {
            x: metrics,
            y: getMetricsForAlgo('euclidean'),
            name: 'Euclidean Baseline',
            type: 'bar',
            marker: { color: '#9ca3af' }
        };

        // Trace 2: Weighted Euclidean (Blue)
        const traceWeighted = {
            x: metrics,
            y: getMetricsForAlgo('weighted_euclidean'),
            name: 'Weighted Euclidean',
            type: 'bar',
            marker: { color: '#3b82f6' }
        };

        // Trace 3: Tuned Random Forest (Emerald Green - to highlight the winner)
        const traceRF = {
            x: metrics,
            y: getMetricsForAlgo('random_forest'),
            name: 'Random Forest',
            type: 'bar',
            marker: { color: '#10b981' }
        };

        const layoutComparison = {
            barmode: 'group', // This clusters the bars side-by-side
            xaxis: { title: 'Evaluation Metrics' },
            yaxis: { title: 'Score', range: [0, 1.05] },
            margin: { l: 50, r: 20, t: 20, b: 50 },
            legend: { orientation: 'h', y: 1.1 } // Puts the legend neatly at the top
        };

        Plotly.newPlot('comparison-barchart', [traceEuclidean, traceWeighted, traceRF], layoutComparison);
    </script>
@endpush