<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ModelController extends Controller
{
    /**
     * Train Random Forest model
     */
    public function train()
    {
        try {
            $pythonPath = env('PYTHON_PATH', 'python');
            $scriptPath = base_path('python/train_rf.py');

            $command = sprintf('%s %s 2>&1', $pythonPath, escapeshellarg($scriptPath));

            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                throw new \Exception('Training failed: ' . implode("\n", $output));
            }

            return response()->json([
                'success' => true,
                'message' => 'Model trained successfully',
                'data' => [
                    'output' => $output,
                    'timestamp' => now()->toIso8601String()
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Model training failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get model status
     */
    public function status()
    {
        $modelPath = base_path('python/models/rf_model.pkl');
        $cachePath = base_path('python/models/leaf_cache.json');

        return response()->json([
            'success' => true,
            'data' => [
                'model_exists' => file_exists($modelPath),
                'cache_exists' => file_exists($cachePath),
                'last_trained' => file_exists($modelPath)
                    ? date('Y-m-d H:i:s', filemtime($modelPath))
                    : null,
                'model_size' => file_exists($modelPath)
                    ? filesize($modelPath)
                    : 0
            ]
        ]);
    }

    /**
     * Get model metrics
     */
    public function metrics()
    {
        $totalCases = \App\Models\CaseModel::count();
        $avgAccuracy = \App\Models\CaseModel::avg(DB::raw('(euclidean_score + weighted_euclidean_score + random_forest_score) / 3'));

        return response()->json([
            'success' => true,
            'data' => [
                'total_training_cases' => $totalCases,
                'average_match_score' => round($avgAccuracy * 100, 2),
                'algorithms' => [
                    'euclidean' => [
                        'name' => 'Euclidean Distance',
                        'avg_score' => round(\App\Models\CaseModel::avg('euclidean_score') * 100, 2)
                    ],
                    'weighted_euclidean' => [
                        'name' => 'Weighted Euclidean',
                        'avg_score' => round(\App\Models\CaseModel::avg('weighted_euclidean_score') * 100, 2)
                    ],
                    'random_forest' => [
                        'name' => 'Random Forest Proximity',
                        'avg_score' => round(\App\Models\CaseModel::avg('random_forest_score') * 100, 2)
                    ]
                ]
            ]
        ]);
    }
}
