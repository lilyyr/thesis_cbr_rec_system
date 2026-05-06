<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class RecommendationController extends Controller
{
    /**
     * Get product recommendations using CBR
     */
    public function getRecommendations(Request $request): JsonResponse
    {
        try {
            // Validate input
            $validated = $this->validateInput($request);

            // Call Python CBR system
            $result = $this->executePythonCBR($validated);

            return response()->json($result);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'error' => 'Validation failed',
                'details' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            Log::error('CBR Error: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Validate input data
     */
    protected function validateInput(Request $request): array
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date',
            'occupation' => 'required|string',
            'income' => 'required|numeric|min:0',
            'num_dependents' => 'required|integer|min:0',
            'financial_goals' => 'required|array|min:1',
            'insurance_period' => 'required|integer|min:1|max:50',
            'premium_payment_period' => 'required|integer|min:1|max:40',
            'overseas_plans' => 'required|boolean',
            'has_existing_health_insurance' => 'required|boolean',
            'height' => 'required|numeric|min:100|max:250',
            'weight' => 'required|numeric|min:30|max:200',
            'weight_change_last_year' => 'nullable|boolean',
            'smoked_last_year' => 'nullable|boolean',
            'hospitalization_last_5_years' => 'nullable|boolean',
            'lab_tests_last_5_years' => 'nullable|boolean',
            'accident_poisoning_last_5_years' => 'nullable|boolean',
            'has_disability' => 'nullable|boolean',
            'has_serious_illness' => 'nullable|boolean',
            'receiving_treatment' => 'nullable|boolean',
            'family_medical_history' => 'nullable|boolean',
            'is_pregnant' => 'nullable|boolean',
            'health_details' => 'nullable|string|max:1000',
        ]);

        // Set defaults for unchecked health fields
        $healthFields = [
            'weight_change_last_year', 'smoked_last_year',
            'hospitalization_last_5_years', 'lab_tests_last_5_years',
            'accident_poisoning_last_5_years', 'has_disability',
            'has_serious_illness', 'receiving_treatment',
            'family_medical_history', 'is_pregnant',
        ];

        foreach ($healthFields as $field) {
            if (!isset($validated[$field])) {
                $validated[$field] = false;
            }
        }

        // Pregnancy only for females
        if ($validated['gender'] === 'male') {
            $validated['is_pregnant'] = null;
        }

        return $validated;
    }

    /**
     * Execute Python CBR system
     */
    protected function executePythonCBR(array $data): array
{
    // Create temp files
    $inputFile = storage_path('app/temp/cbr_input_' . uniqid() . '.json');
    $outputFile = storage_path('app/temp/cbr_output_' . uniqid() . '.json');

    // Ensure temp directory exists
    $tempDir = storage_path('app/temp');
    if (!is_dir($tempDir)) {
        mkdir($tempDir, 0755, true);
    }

    // Write input
    file_put_contents($inputFile, json_encode($data, JSON_PRETTY_PRINT));

    Log::info('CBR Input file created', ['path' => $inputFile]);

    // Build command
    $pythonPath = env('PYTHON_PATH', 'python');
    $scriptPath = base_path('python/cbr_system.py');

    Log::info('Python configuration', [
        'python_path' => $pythonPath,
        'script_path' => $scriptPath,
        'script_exists' => file_exists($scriptPath)
    ]);

    $command = sprintf(
        '%s %s %s %s 2>&1',
        $pythonPath,
        escapeshellarg($scriptPath),
        escapeshellarg($inputFile),
        escapeshellarg($outputFile)
    );

    Log::info('Executing command', ['command' => $command]);

    // Execute
    $startTime = microtime(true);
    exec($command, $output, $returnCode);
    $executionTime = (microtime(true) - $startTime) * 1000;

    Log::info('Command executed', [
        'return_code' => $returnCode,
        'output' => $output,
        'execution_time' => $executionTime
    ]);

    // Check if output file exists
    if (!file_exists($outputFile)) {
        Log::error('Python execution failed', [
            'command' => $command,
            'output' => $output,
            'return_code' => $returnCode,
            'input_file' => $inputFile,
            'output_file' => $outputFile,
            'input_exists' => file_exists($inputFile),
            'output_exists' => file_exists($outputFile)
        ]);

        throw new \Exception('Python script failed. Output: ' . implode("\n", $output));
    }

    // Read result
    $resultContent = file_get_contents($outputFile);
    Log::info('Output file content', ['content' => $resultContent]);

    $result = json_decode($resultContent, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        Log::error('JSON decode error', [
            'error' => json_last_error_msg(),
            'content' => $resultContent
        ]);
        throw new \Exception('Failed to parse Python output: ' . json_last_error_msg());
    }

    // Cleanup
    @unlink($inputFile);
    @unlink($outputFile);

    // Add Laravel execution time
    $result['laravel_execution_time'] = round($executionTime, 2);

    return $result;
}
}
