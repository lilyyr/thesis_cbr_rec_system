<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CaseModel;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class ConsultationController extends Controller
{
    /**
     * Display consultation list
     */
    public function index()
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $query = CaseModel::with(['customer', 'product', 'agent']);

        // If agent, only show their own consultations
        if ($currentUser->isAgent()) {
            $query->where('agent_id', Auth::id());
        }

        $consultations = $query->latest()->paginate(15);

        return view('consultations.index', compact('consultations'));
    }

    /**
     * Show create consultation form
     */
    public function create()
    {
        return view('consultations.create');
    }

    /**
     * Store consultation and get CBR recommendation
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'gender' => 'required|in:male,female',
            'dob' => 'required|date|before:today',
            'occupation' => 'required|string|max:255',
            'income' => 'required|numeric|min:0',
            'num_dependents' => 'required|integer|min:0',

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
        ]);

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

Log::info('CBR Input prepared', ['data' => $cbrInput]);

        // Call Python CBR system
        try {
            $cbrResult = $this->executePythonCBR($cbrInput);

            Log::info('CBR Result received', [
                'full_result' => $cbrResult,
                'recommendations' => $cbrResult['recommendations'] ?? 'MISSING',
                'top_rec' => $cbrResult['recommendations'][0] ?? 'MISSING'
            ]);

            if (!$cbrResult['success']) {
                Log::error('CBR system error', ['result' => $cbrResult]);
                return back()->with('error', 'CBR system error: ' . ($cbrResult['error'] ?? 'Unknown error'))->withInput();
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
                'algorithm_details'=> $cbrResult['algorithm_details'],
            ]);

            return redirect()->route('consultations.show', $case->id)
                ->with('success', 'Consultation completed! Recommendations generated.');

        } catch (\Exception $e) {
            Log::error('Consultation error: ' . $e->getMessage());
            return back()->with('error', 'Error processing consultation: ' . $e->getMessage());
        }
    }

    public function process($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();
        $consultation = CaseModel::with(['customer', 'product', 'agent'])->findOrFail($id);

        // Check permissions
        if ($currentUser->isAgent() && $consultation->agent_id !== Auth::id()) {
            abort(403);
        }

        return view('consultations.process', compact('consultation'));
    }

    /**
     * Show consultation details
     */
    public function show($id)
    {
        /** @var User $currentUser */
        $currentUser = Auth::user();

        $consultation = CaseModel::with(['customer', 'product', 'agent'])->findOrFail($id);

        // Check permissions
        if ($currentUser->isAgent() && $consultation->agent_id !== Auth::id()) {
            abort(403);
        }

        // Get all products for comparison
        $allProducts = Product::where('active', true)->get();

        return view('consultations.show', compact('consultation', 'allProducts'));
    }

    /**
     * Execute Python CBR system
     */
    protected function executePythonCBR(array $data): array
    {
        try {
            // Create temp files
            $inputFile = storage_path('app/temp/cbr_input_' . uniqid() . '.json');
            $outputFile = storage_path('app/temp/cbr_output_' . uniqid() . '.json');

            // Ensure temp directory exists
            $tempDir = storage_path('app/temp');
            if (!is_dir($tempDir)) {
                mkdir($tempDir, 0755, true);
            }

            // Write input with numeric encoding
            file_put_contents($inputFile, json_encode($data, JSON_PRETTY_PRINT | JSON_NUMERIC_CHECK));

            // Add this to see what was written:
            Log::info('Input file content', ['content' => file_get_contents($inputFile)]);

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

            Log::info('Executing Python CBR', ['command' => $command]);

            // Execute
            exec($command, $output, $returnCode);

            Log::info('Python execution result', [
                'return_code' => $returnCode,
                'output' => $output
            ]);

            // Check if output file exists
            if (!file_exists($outputFile)) {
                Log::error('Python execution failed - no output file', [
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
            $resultJson = file_get_contents($outputFile);
            $result = json_decode($resultJson, true);

            if (!$result) {
                Log::error('Failed to parse Python output', ['json' => $resultJson]);
                return [
                    'success' => false,
                    'error' => 'Failed to parse CBR results'
                ];
            }

            // Cleanup
            // @unlink($inputFile);
            // @unlink($outputFile);

            Log::info('Output file location', [
                'path' => $outputFile,
                'exists' => file_exists($outputFile)
            ]);

            $result['success'] = true;
            return $result;

        } catch (\Exception $e) {
            Log::error('CBR execution exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
 * Generate tree visualizations for a case
 */
public function generateTreeVisualizations($id)
{
    /** @var User $currentUser */
    $currentUser = Auth::user();

    $consultation = CaseModel::findOrFail($id);

    // Check permissions
    if ($currentUser->isAgent() && $consultation->agent_id !== Auth::id()) {
        abort(403);
    }

    try {
        // Build command
        $pythonPath = env('PYTHON_PATH', 'python');
        $scriptPath = base_path('python/visualize_trees.py');

        $command = sprintf(
            '%s %s %d 3 2>&1',
            $pythonPath,
            escapeshellarg($scriptPath),
            $id
        );

        // Execute
        exec($command, $output, $returnCode);

        // Parse output
        $result = json_decode(implode("\n", $output), true);

        if (!$result || !$result['success']) {
            throw new \Exception($result['error'] ?? 'Failed to generate tree visualizations');
        }

        // Store tree info in session for display
        session(['tree_visualizations_' . $id => $result['trees']]);

        return response()->json([
            'success' => true,
            'message' => 'Tree visualizations generated successfully',
            'trees' => $result['trees']
        ]);

    } catch (\Exception $e) {
        Log::error('Tree visualization error: ' . $e->getMessage());
        return response()->json([
            'success' => false,
            'error' => $e->getMessage()
        ], 500);
    }
}
}
