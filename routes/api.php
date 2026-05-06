<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\RecommendationController;

// Public test route
// Route::get('/test', function () {
//     return response()->json(['message' => 'API is working!']);
// });

// // Protected API routes (require authentication)
// Route::middleware('auth:sanctum')->group(function () {

// //     // Get authenticated user
//     Route::get('/user', function (Request $request) {
//         return $request->user();
//     });

//     // CBR Recommendation endpoint
//     Route::post('/v1/recommendations/get', [RecommendationController::class, 'getRecommendations'])
//         ->middleware('role:admin,agent');

// });
// // use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\API\RecommendationController;

// Route::prefix('v1')->group(function () {

//     // CBR Recommendation endpoint
//     Route::post('/recommendations/get', [RecommendationController::class, 'getRecommendations'])
//         ->name('api.recommendations.get');

// use Illuminate\Support\Facades\Route;
// use App\Http\Controllers\API\RecommendationController;

// // Without prefix - for testing
// Route::post('/test-cbr', [RecommendationController::class, 'getRecommendations']);
