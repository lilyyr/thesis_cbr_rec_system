<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
use App\Http\Controllers\API\RecommendationController;
use App\Http\Controllers\API\TreeController;
use App\Http\Controllers\API\ModelController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| These routes use Sanctum authentication and return JSON responses
*/

// Public test endpoint
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'Insurance CBR API is running',
        'version' => '1.0.0',
        'timestamp' => now()->toIso8601String()
    ]);
});

Route::post('/login', function (Request $request) {
    $request->validate([
        'email' => 'required|email',
        'password' => 'required'
    ]);

    $user = \App\Models\User::where('email', $request->email)->first();

    if (!$user || !Hash::check($request->password, $user->password)) {
        return response()->json([
            'success' => false,
            'message' => 'Invalid credentials'
        ], 401);
    }

    // Delete old tokens
    $user->tokens()->delete();

    // Create new token
    $token = $user->createToken('api-token')->plainTextToken;

    return response()->json([
        'success' => true,
        'message' => 'Login successful',
        'data' => [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ],
            'token' => $token,
            'token_type' => 'Bearer'
        ]
    ]);
});

// Protected API routes
Route::middleware('auth:sanctum')->group(function () {

    // Get authenticated user
    Route::get('/user', function (Request $request) {
        return response()->json([
            'success' => true,
            'data' => $request->user()
        ]);
    });

    // CBR Recommendation API
    Route::prefix('recommendations')->group(function () {
        // Get recommendation for new case
        Route::post('/', [RecommendationController::class, 'getRecommendation'])
            ->middleware('role:admin,agent');

        // Get consultation history
        Route::get('/', [RecommendationController::class, 'getHistory'])
            ->middleware('role:admin,agent,client');

        // Get specific consultation
        Route::get('/{id}', [RecommendationController::class, 'getConsultation'])
            ->middleware('role:admin,agent,client');
    });

    // Tree Visualization API
    Route::prefix('visualizations')->group(function () {
        // Generate tree visualizations
        Route::post('/trees/{caseId}', [TreeController::class, 'generateTrees'])
            ->middleware('role:admin,agent');

        // Get visualization status
        Route::get('/trees/{caseId}', [TreeController::class, 'getTreeStatus'])
            ->middleware('role:admin,agent');
    });

    // Model Management API (Admin only)
    Route::prefix('model')->middleware('role:admin')->group(function () {
        // Train Random Forest model
        Route::post('/train', [ModelController::class, 'train']);

        // Get model status
        Route::get('/status', [ModelController::class, 'status']);

        // Get model metrics
        Route::get('/metrics', [ModelController::class, 'metrics']);
    });

    // Statistics API
    Route::get('/statistics', [RecommendationController::class, 'getStatistics'])
        ->middleware('role:admin,agent');
});
