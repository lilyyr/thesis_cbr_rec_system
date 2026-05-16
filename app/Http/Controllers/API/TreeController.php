<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

class TreeController extends Controller
{
    /**
     * Generate tree visualizations
     */
    public function generateTrees($caseId)
    {
        $consultation = CaseModel::findOrFail($caseId);

        if (Auth::user()->role === 'agent' && $consultation->agent_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        try {
            $pythonPath = env('PYTHON_PATH', 'python');
            $scriptPath = base_path('python/visualize_trees.py');

            $command = sprintf(
                '%s %s %d 3 2>&1',
                $pythonPath,
                escapeshellarg($scriptPath),
                $caseId
            );

            exec($command, $output, $returnCode);

            // Find JSON output
            // $jsonOutput = '';
            // foreach ($output as $line) {
            //     if (strpos($line, '{') !== false) {
            //         $jsonOutput = $line;
            //         break;
            //     }
            // }

            // if (empty($jsonOutput)) {
            //     $jsonOutput = implode("\n", $output);
            // }

            $jsonOutput = implode("\n", $output);

            $result = json_decode($jsonOutput, true);

            if (!$result || !$result['success']) {
                throw new \Exception($result['error'] ?? 'Failed to generate visualizations');
            }

            return response()->json([
                'success' => true,
                'message' => 'Tree visualizations generated successfully',
                'data' => [
                    'case_id' => $caseId,
                    'trees' => $result['trees'],
                    'num_trees' => $result['num_trees_visualized']
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Tree visualization error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate tree visualizations',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get tree visualization status
     */
    public function getTreeStatus($caseId)
    {
        $treePath = public_path("tree_visualizations/tree_{$caseId}_1.png");

        return response()->json([
            'success' => true,
            'data' => [
                'case_id' => $caseId,
                'trees_generated' => file_exists($treePath),
                'tree_urls' => file_exists($treePath) ? [
                    asset("tree_visualizations/tree_{$caseId}_1.png"),
                    asset("tree_visualizations/tree_{$caseId}_2.png"),
                    asset("tree_visualizations/tree_{$caseId}_3.png"),
                ] : []
            ]
        ]);
    }
}
