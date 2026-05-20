<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ConsultationController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\AdminController;

// Public routes
Route::get('/', function () {
    return view('home');
})->name('home');

Route::get('/demo', function () {
    return view('demo');
})->name('demo');

// Authentication routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    // Agent management
    Route::get('/agents', [AdminController::class, 'agentsIndex'])->name('agents.index');
    Route::get('/agents/create', [AdminController::class, 'agentsCreate'])->name('agents.create');
    Route::post('/agents', [AdminController::class, 'agentsStore'])->name('agents.store');
    Route::get('/agents/{id}/edit', [AdminController::class, 'agentsEdit'])->name('agents.edit');
    Route::put('/agents/{id}', [AdminController::class, 'agentsUpdate'])->name('agents.update');
    Route::delete('/agents/{id}', [AdminController::class, 'agentsDestroy'])->name('agents.destroy');

    // Product management
    Route::get('/products', [AdminController::class, 'productsIndex'])->name('products.index');
    Route::get('/products/create', [AdminController::class, 'productsCreate'])->name('products.create');
    Route::post('/products', [AdminController::class, 'productsStore'])->name('products.store');
    Route::get('/products/{id}/edit', [AdminController::class, 'productsEdit'])->name('products.edit');
    Route::put('/products/{id}', [AdminController::class, 'productsUpdate'])->name('products.update');
    Route::delete('/products/{id}', [AdminController::class, 'productsDestroy'])->name('products.destroy');

    // Weight management
    Route::get('/weights', [AdminController::class, 'weightsIndex'])->name('weights.index');
    Route::post('/weights', [AdminController::class, 'weightsUpdate'])->name('weights.update');

    // Model training
    Route::post('/train-model', [AdminController::class, 'trainModel'])->name('train-model');
});

// Agent & Admin routes (can use CBR system)
Route::middleware(['auth', 'role:admin,agent'])->group(function () {
    // Route::get('/consultations', [ConsultationController::class, 'index'])->name('consultations.index');
    // Route::get('/consultations/create', [ConsultationController::class, 'create'])->name('consultations.create');
    // // Route::post('/consultations', [ConsultationController::class, 'store'])->name('consultations.store');
    // Route::get('/consultations/{id}', [ConsultationController::class, 'show'])->name('consultations.show');
    // Route::get('/consultations/{id}/process', [ConsultationController::class, 'process'])->name('consultations.process');
    // Route::post('consultations/{id}/generate-trees', [ConsultationController::class, 'generateTreeVisualizations'])->name('consultations.generate-trees');

    Route::get('/consultations', function() {return view('consultations.index');})->name('consultations.index');
    Route::get('/consultations/create', function() {
        $occupations = \App\Models\Occupation::orderBy('name')->get();
        return view('consultations.create', compact('occupations'));})->name('consultations.create');
    Route::get('/consultations/{id}', function($id) {return view('consultations.show', ['consultationId' => $id]);})->name('consultations.show');
    Route::get('/consultations/{id}/process', function($id) {return view('consultations.process', ['consultationId' => $id]);})->name('consultations.process');

    // Client management
    Route::resource('clients', ClientController::class);
});

// Client routes (view only)
Route::middleware(['auth', 'role:client'])->prefix('client')->group(function () {
    Route::get('/consultations', [ClientController::class, 'myConsultations'])->name('client.consultations');
    Route::get('/consultations/{id}', [ClientController::class, 'showConsultation'])->name('client.consultations.show');
});
