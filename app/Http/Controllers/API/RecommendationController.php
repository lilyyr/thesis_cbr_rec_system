<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RecommendationController extends Controller
{
    /**
     * Get CBR recommendation for new case
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecommendation(Request $request)
    {
        // Validate input
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date|before:today',
            'occupation' => 'required|string|max:255',
            'income' => 'required|numeric|min:0',
            'num_dependents' => 'required|integer|min:0|max:20',
            'financial_goals' => 'required|array|min:1',
            'insurance_period' => 'required|integer|min:1|max:50',
            'premium_payment_period' => 'required|integer|min:1|max:40',
            'overseas_plans' => 'nullable|boolean',
            'has_existing_health_insurance' => 'nullable|boolean',
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

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $validated = $validator->validated();

        try {
            // Create or find customer
            $customer = Customer::firstOrCreate(
                [
                    'name' => $validated['name'],
                    'dob' => $validated['dob'],
                ],
                [
                    'gender' => $validated['gender'],
                    'occupation' => $validated['occupation'],
                    'income' => $validated['income'],
                    'num_dependents' => $validated['num_dependents'],
                ]
            );

            // Prepare data for Python CBR
            $cbrInput = [
                'name' => $customer->name,
                'gender' => $customer->gender,
                'dob' => $customer->dob->format('Y-m-d'),
                'occupation' => $customer->occupation,
                'income' => (float) $customer->income,  // CAST TO FLOAT
                'num_dependents' => (int) $customer->num_dependents,  // CAST TO INT
                'financial_goals' => $validated['financial_goals'],
                'insurance_period' => (int) $validated['insurance_period'],  // CAST TO INT
                'premium_payment_period' => (int) $validated['premium_payment_period'],  // CAST TO INT
                'overseas_plans' => (bool) ($validated['overseas_plans'] ?? false),  // CAST TO BOOL
                'has_existing_health_insurance' => (bool) ($validated['has_existing_health_insurance'] ?? false),  // CAST TO BOOL
                'height' => (float) $validated['height'],  // CAST TO FLOAT
                'weight' => (float) $validated['weight'],  // CAST TO FLOAT
                'weight_change_last_year' => (bool) ($validated['weight_change_last_year'] ?? false),
                'smoked_last_year' => (bool) ($validated['smoked_last_year'] ?? false),
                'hospitalization_last_5_years' => (bool) ($validated['hospitalization_last_5_years'] ?? false),
                'lab_tests_last_5_years' => (bool) ($validated['lab_tests_last_5_years'] ?? false),
                'accident_poisoning_last_5_years' => (bool) ($validated['accident_poisoning_last_5_years'] ?? false),
                'has_disability' => (bool) ($validated['has_disability'] ?? false),
                'has_serious_illness' => (bool) ($validated['has_serious_illness'] ?? false),
                'receiving_treatment' => (bool) ($validated['receiving_treatment'] ?? false),
                'family_medical_history' => (bool) ($validated['family_medical_history'] ?? false),
                'is_pregnant' => $customer->gender === 'female' ? (bool) ($validated['is_pregnant'] ?? false) : null,
                'health_details' => $validated['health_details'] ?? null,
            ];

            // Call Python CBR system
            $cbrResult = $this->executePythonCBR($cbrInput);

            if (!$cbrResult['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'CBR processing failed',
                    'error' => $cbrResult['error'] ?? 'Unknown error'
                ], 500);
            }

            // Get top recommendation
            $topRecommendation = $cbrResult['recommendations'][0];

            // Calculate BMI
            $bmi = $validated['weight'] / (($validated['height'] / 100) ** 2);

            // Save consultation case
            $case = CaseModel::create([
                'customer_id' => $customer->id,
                'product_id' => $topRecommendation['product_id'],
                'agent_id' => Auth::id(),
                'financial_goals' => $validated['financial_goals'],
                'insurance_period' => $validated['insurance_period'],
                'premium_payment_period' => $validated['premium_payment_period'],
                'overseas_plans' => $validated['overseas_plans'] ?? false,
                'has_existing_health_insurance' => $validated['has_existing_health_insurance'] ?? false,
                'height' => $validated['height'],
                'weight' => $validated['weight'],
                'bmi' => round($bmi, 2),
                'weight_change_last_year' => $validated['weight_change_last_year'] ?? false,
                'smoked_last_year' => $validated['smoked_last_year'] ?? false,
                'hospitalization_last_5_years' => $validated['hospitalization_last_5_years'] ?? false,
                'lab_tests_last_5_years' => $validated['lab_tests_last_5_years'] ?? false,
                'accident_poisoning_last_5_years' => $validated['accident_poisoning_last_5_years'] ?? false,
                'has_disability' => $validated['has_disability'] ?? false,
                'has_serious_illness' => $validated['has_serious_illness'] ?? false,
                'receiving_treatment' => $validated['receiving_treatment'] ?? false,
                'family_medical_history' => $validated['family_medical_history'] ?? false,
                'is_pregnant' => $cbrInput['is_pregnant'],
                'health_details' => $validated['health_details'],
                'health_risk_score' => $cbrResult['preprocessed']['health_risk_score'],
                'feature_vector' => $cbrResult['feature_vector'],
                'euclidean_score' => $topRecommendation['euclidean_score'],
                'weighted_euclidean_score' => $topRecommendation['weighted_euclidean_score'],
                'random_forest_score' => $topRecommendation['random_forest_score'],
                'algorithm_details' => $cbrResult['algorithm_details'],
            ]);

            // Return API response
            return response()->json([
                'success' => true,
                'message' => 'Recommendation generated successfully',
                'data' => [
                    'consultation_id' => $case->id,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
                        'age' => $customer->age,
                        'bmi' => round($bmi, 2),
                        'health_risk_score' => $cbrResult['preprocessed']['health_risk_score']
                    ],
                    'top_recommendation' => [
                        'product_id' => $topRecommendation['product_id'],
                        'product_name' => $topRecommendation['product_name'],
                        'match_percentage' => $topRecommendation['match_percentage'],
                        'euclidean_score' => $topRecommendation['euclidean_score'],
                        'weighted_euclidean_score' => $topRecommendation['weighted_euclidean_score'],
                        'random_forest_score' => $topRecommendation['random_forest_score'],
                        'aggregate_score' => $topRecommendation['aggregate_score']
                    ],
                    'all_recommendations' => $cbrResult['recommendations'],
                    'execution_time' => $cbrResult['execution_time'],
                    'feature_vector' => $cbrResult['feature_vector']
                ],
                'links' => [
                    'view_details' => route('consultations.show', $case->id),
                    'view_process' => route('consultations.process', $case->id)
                ]
            ], 201);

        } catch (\Exception $e) {
            Log::error('API Recommendation error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while processing the recommendation',
                'error' => $e->getMessage()
            ], 500);
        }
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

        // Build command
        $pythonPath = env('PYTHON_PATH', 'python');
        $scriptPath = base_path('python/cbr_system.py');

        $command = sprintf(
            '%s %s %s %s 2>&1',
            $pythonPath,
            escapeshellarg($scriptPath),
            escapeshellarg($inputFile),
            escapeshellarg($outputFile)
        );

        // Execute
        exec($command, $output, $returnCode);

        // Check if output file exists
        if (!file_exists($outputFile)) {
            Log::error('Python execution failed', [
                'command' => $command,
                'output' => $output,
                'return_code' => $returnCode
            ]);

            return [
                'success' => false,
                'error' => 'Python script failed: ' . implode("\n", $output)
            ];
        }

        // Read result
        $result = json_decode(file_get_contents($outputFile), true);

        // Cleanup
        @unlink($inputFile);
        @unlink($outputFile);

        return $result;
    }

    /**
     * Get consultation history
     */
    public function getHistory(Request $request)
    {
        $query = CaseModel::with(['customer', 'product', 'agent']);

        // Filter by role
        if (Auth::user()->role === 'agent') {
            $query->where('agent_id', Auth::id());
        } elseif (Auth::user()->role === 'client') {
            // Get customer IDs associated with this client
            $customer = Customer::where('name', Auth::user()->name)->first();
            if ($customer) {
                $query->where('customer_id', $customer->id);
            }
        }

        // Pagination
        $perPage = $request->get('per_page', 15);
        $consultations = $query->latest()->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $consultations->items(),
            'pagination' => [
                'total' => $consultations->total(),
                'per_page' => $consultations->perPage(),
                'current_page' => $consultations->currentPage(),
                'last_page' => $consultations->lastPage(),
                'from' => $consultations->firstItem(),
                'to' => $consultations->lastItem()
            ]
        ]);
    }

    /**
     * Get specific consultation
     */
    public function getConsultation($id)
    {
        $consultation = CaseModel::with(['customer', 'product', 'agent'])->find($id);

        if (!$consultation) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found'
            ], 404);
        }

        // Check permissions
        if (Auth::user()->role === 'agent' && $consultation->agent_id !== Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized access'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $consultation
        ]);
    }

    /**
     * Get statistics
     */
    public function getStatistics()
    {
        $query = CaseModel::query();

        if (Auth::user()->role === 'agent') {
            $query->where('agent_id', Auth::id());
        }

        $stats = [
            'total_consultations' => $query->count(),
            'this_month' => (clone $query)->whereMonth('created_at', now()->month)->count(),
            'avg_match_score' => $query->avg(DB::raw('(euclidean_score + weighted_euclidean_score + random_forest_score) / 3')) * 100,
            'top_products' => DB::table('cases')
                ->join('products', 'cases.product_id', '=', 'products.id')
                ->select('products.name', DB::raw('COUNT(*) as count'))
                ->when(Auth::user()->role === 'agent', function($q) {
                    return $q->where('cases.agent_id', Auth::id());
                })
                ->groupBy('products.id', 'products.name')
                ->orderByDesc('count')
                ->limit(5)
                ->get()
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}
