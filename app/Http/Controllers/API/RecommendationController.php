<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\CaseModel;
use App\Models\Customer;
use App\Models\PolicyHolder;
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
            'marital_status' => 'required|in:single,married',
            'income_range' => 'required|in:below_50m,50m_100m,100m_300m,300m_500m,500m_1b,above_1b',
            'occupation_id' => 'required|exists:occupations,id',
            'num_dependents' => 'required|integer|min:0',

            'holder_is_insured' => 'required|boolean',
            'holder_name' => 'required|string|max:255',
            'holder_dob' => 'required|date',
            'holder_gender' => 'required|in:male,female',
            'holder_income_range' => 'required|in:below_50m,50m_100m,100m_300m,300m_500m,500m_1b,above_1b',
            'holder_relationship_to_insured' => 'required|in:diri sendiri,adik/kakak kandung,anak kandung,cucu/cicit,nenek/kakek kandung,orang tua kandung,suami/istri,lainnya',

            'beneficiary_name' => 'required|string|max:255',
            'beneficiary_dob' => 'required|date|before:today',
            'beneficiary_gender' => 'required|in:male,female',
            'beneficiary_relationship' => 'required|in:adik/kakak kandung,anak kandung,cucu/cicit,nenek/kakek kandung,orang tua kandung,suami/istri,lainnya',

            'financial_goals' => 'required|array|min:1',
            'insurance_period' => 'required|integer|min:1|max:100',
            'nominal_received' => 'required|numeric|min:0',
            'overseas_medical_plans' => 'nullable|boolean',
            'coverage_regions' => 'nullable|array',
            'has_existing_health_insurance' => 'nullable|boolean',
            'high_risk_hobby' => 'nullable|boolean',

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
            'health_details' => 'nullable|string|max:1000'
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
            DB::beginTransaction();

            // Create or find
            $customer = Customer::firstOrCreate([
                'name' => $validated['name'],
                'dob' => $validated['dob'],
                'gender' => $validated['gender'],
                'marital_status' => $validated['marital_status'],
                'occupation_id' => $validated['occupation_id'],
                'income_range' => $validated['income_range'],
                'num_dependents' => $validated['num_dependents']
            ]);

            $policyHolder = PolicyHolder::firstOrCreate([
                'name' => $validated['holder_name'],
                'dob' => $validated['holder_dob'],
                'gender' => $validated['holder_gender'],
                'income_range' => $validated['holder_income_range'],
            ]);

            // data input for cbr system
            $cbrInput = [
                'gender' => $customer->gender,
                'dob' => $customer->dob->format('Y-m-d'),
                // 'marital_status' => $validated['marital_status'],
                // 'occupation_id' => (int) $validated['occupation_id'],
                'marital_status' => $customer->marital_status,
                'occupation_id' => (int) $customer->occupation_id,
                // 'income' => (float) $income,
                'num_dependents' => (int) $customer->num_dependents,

                // 'holder_dob' => $validated['holder_dob'],
                // 'holder_income_range' => $validated['holder_income_range'],
                // 'holder_relationship_to_insured' => $validated['holder_relationship_to_insured'],

                // 'holder_dob' => $policyHolder->dob->format('Y-m-d'),
                'holder_income_range' => $policyHolder->income_range,
                'holder_relationship_to_insured' => $validated['holder_relationship_to_insured'],

                'beneficiary_relationship' => $validated['beneficiary_relationship'],
                'financial_goals' => $validated['financial_goals'],
                'insurance_period' => (int) $validated['insurance_period'],
                'nominal_received' => (float) $validated['nominal_received'],
                'overseas_medical_plans' => (bool) ($validated['overseas_medical_plans'] ?? false),
                'coverage_regions' => $validated['coverage_regions'] ?? [],
                'has_existing_health_insurance' => (bool) ($validated['has_existing_health_insurance'] ?? false),
                'high_risk_hobby' => (bool) ($validated['high_risk_hobby'] ?? false),
                'height' => (float) $validated['height'],
                'weight' => (float) $validated['weight'],
                'weight_change_last_year' => (bool) ($validated['weight_change_last_year'] ?? false),
                'smoked_last_year' => (bool) ($validated['smoked_last_year'] ?? false),
                'hospitalization_last_5_years' => (bool) ($validated['hospitalization_last_5_years'] ?? false),
                'lab_tests_last_5_years' => (bool) ($validated['lab_tests_last_5_years'] ?? false),
                'accident_poisoning_lazst_5_years' => (bool) ($validated['accident_poisoning_last_5_years'] ?? false),
                'has_disability' => (bool) ($validated['has_disability'] ?? false),
                'has_serious_illness' => (bool) ($validated['has_serious_illness'] ?? false),
                'receiving_treatment' => (bool) ($validated['receiving_treatment'] ?? false),
                'family_medical_history' => (bool) ($validated['family_medical_history'] ?? false),
                'is_pregnant' => $customer->gender === 'female' ? (bool) ($validated['is_pregnant'] ?? false) : null,
                'health_details' => $validated['health_details'] ?? null
            ];

            // Call CBR system
            $cbrResult = $this->executePythonCBR($cbrInput);

            if (!$cbrResult['success']) {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'CBR processing failed',
                    'error' => $cbrResult['error'] ?? 'Unknown error'
                ], 500);
            }

            $topRecommendation = $cbrResult['recommendations'][0];

            $bmi = $validated['weight'] / (($validated['height'] / 100) ** 2);

            $case = CaseModel::create([
                'customer_id' => $customer->id,
                'policy_holder_id' => $policyHolder->id,
                'holder_is_insured' => $validated['holder_is_insured'],
                'holder_relationship_to_insured' => $validated['holder_relationship_to_insured'],
                'product_id' => $topRecommendation['product_id'],
                'agent_id' => Auth::id(),
                'financial_goals' => $validated['financial_goals'],
                'insurance_period' => $validated['insurance_period'],
                'nominal_received' => $validated['nominal_received'],
                'overseas_medical_plans' => $validated['overseas_medical_plans'] ?? false,
                'coverage_regions' => $validated['coverage_regions'] ?? [], //json_encode($validated['coverage_regions'] ?? []),
                'has_existing_health_insurance' => $validated['has_existing_health_insurance'] ?? false,
                'high_risk_hobby' => $validated['high_risk_hobby'] ?? false,
                'beneficiary_name' => $validated['beneficiary_name'],
                'beneficiary_dob' => $validated['beneficiary_dob'],
                'beneficiary_gender' => $validated['beneficiary_gender'],
                'beneficiary_relationship' => $validated['beneficiary_relationship'],
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
                'is_pregnant' => $validated['is_pregnant'] ?? false,
                'health_details' => $validated['health_details'],
                'health_risk_score' => $cbrResult['health_risk_score'],
                'feature_vector' => $cbrResult['feature_vector'],
                'euclidean_score' => $topRecommendation['euclidean_score'],
                'weighted_euclidean_score' => $topRecommendation['weighted_euclidean_score'],
                'random_forest_score' => $topRecommendation['random_forest_score'],
                'algorithm_details' => $cbrResult['algorithm_details'],
                'all_recommendations' => $cbrResult['recommendations']
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Recommendation generated successfully',
                'data' => [
                    'consultation_id' => $case->id,
                    'customer' => [
                        'id' => $customer->id,
                        'name' => $customer->name,
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
        // temp files
        $inputFile = storage_path('app/temp/cbr_input_' . uniqid() . '.json');
        $outputFile = storage_path('app/temp/cbr_output_' . uniqid() . '.json');

        // temp directory exists
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
        try {
            $user = Auth::user();
            $perPage = $request->input('per_page', 15);
            $search  = $request->input('search', '');
            $sort    = $request->input('sort', 'latest'); // 'latest' or 'oldest'

            $query = CaseModel::with(['customer', 'product', 'agent'])
                ->orderBy('created_at', $sort === 'oldest' ? 'asc' : 'desc');

            // need to change to policy holder for client
            if ($user->role === 'agent') {
                $query->where('agent_id', $user->id);
            } elseif ($user->role === 'client') {
                $query->whereHas('customer', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                });
            }

            // Search by customer name OR product name
            if ($search !== '') {
                $query->where(function ($q) use ($search) {
                    $q->whereHas('customer', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    })->orWhereHas('product', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%{$search}%");
                    });
                });
            }

            $cases = $query->paginate($perPage);

            return response()->json([
                'success'    => true,
                'data'       => $cases->items(),
                'pagination' => [
                    'current_page' => $cases->currentPage(),
                    'per_page'     => $cases->perPage(),
                    'total'        => $cases->total(),
                    'last_page'    => $cases->lastPage(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Get history error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve consultation history',
            ], 500);
        }
    }

    /**
     * Get specific consultation
     */
    public function getConsultation($id)
    {
        $consultation = CaseModel::with(['customer', 'customer.occupation', 'product', 'agent', 'policyHolder'])->find($id);

        if (!$consultation) {
            return response()->json([
                'success' => false,
                'message' => 'Consultation not found'
            ], 404);
        }

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
     * Get CBR process details
     */
    // public function getProcess($id)
    // {
    //     $consultation = CaseModel::with(['customer', 'customer.occupation', 'product', 'agent'])->find($id);

    //     if (!$consultation) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Consultation not found'
    //         ], 404);
    //     }

    //     // Check permissions
    //     if (Auth::user()->role === 'agent' && $consultation->agent_id !== Auth::id()) {
    //         return response()->json([
    //             'success' => false,
    //             'message' => 'Unauthorized access'
    //         ], 403);
    //     }

    //     $algorithmDetails = $consultation->algorithm_details
    //         ? json_decode($consultation->algorithm_details, true)
    //         : null;

    //     return response()->json([
    //         'success' => true,
    //         'data' => [
    //             'consultation' => $consultation,
    //             'algorithm_details' => $algorithmDetails
    //         ]
    //     ]);
    // }

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
