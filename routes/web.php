<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PsgcController;
use App\Http\Controllers\ResponseController;
use App\Http\Controllers\ResultsController;
use App\Http\Controllers\SurveyController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

// PSGC location API (cascading dropdowns)
Route::get('/api/psgc/regions', [PsgcController::class, 'regions'])->name('psgc.regions');
Route::get('/api/psgc/regions/{regionCode}/provinces', [PsgcController::class, 'provinces'])->name('psgc.provinces');
Route::get('/api/psgc/provinces/{provinceCode}/cities', [PsgcController::class, 'cities'])->name('psgc.cities');
Route::get('/api/psgc/cities/{cityCode}/barangays', [PsgcController::class, 'barangays'])->name('psgc.barangays');

// Survey code lookup (from landing page)
Route::post('/survey/lookup', [ResponseController::class, 'lookupCode'])->name('survey.lookup');

// Public survey routes (no auth)
Route::get('/s/{slug}', [ResponseController::class, 'showBySlug'])->name('survey.respond');
Route::post('/s/{slug}/submit', [ResponseController::class, 'store'])->name('survey.submit');
Route::get('/s/{slug}/thankyou', [ResponseController::class, 'thankyou'])->name('survey.thankyou');
Route::get('/survey/code/{code}', [ResponseController::class, 'showByCode'])->name('survey.code');

// Authenticated routes
Route::middleware('auth')->group(function () {
    // Dashboard (survey list)
    Route::get('/dashboard', [SurveyController::class, 'index'])->name('dashboard');

    // Survey CRUD
    Route::get('/surveys/create', [SurveyController::class, 'create'])->name('surveys.create');
    Route::post('/surveys', [SurveyController::class, 'store'])->name('surveys.store');
    Route::get('/surveys/{survey}/edit', [SurveyController::class, 'edit'])->name('surveys.edit');
    Route::put('/surveys/{survey}', [SurveyController::class, 'update'])->name('surveys.update');
    Route::delete('/surveys/{survey}', [SurveyController::class, 'destroy'])->name('surveys.destroy');

    // Survey actions
    Route::post('/surveys/{survey}/publish', [SurveyController::class, 'publish'])->name('surveys.publish');
    Route::post('/surveys/{survey}/close', [SurveyController::class, 'close'])->name('surveys.close');
    Route::post('/surveys/{survey}/reopen', [SurveyController::class, 'reopen'])->name('surveys.reopen');
    Route::post('/surveys/{survey}/duplicate', [SurveyController::class, 'duplicate'])->name('surveys.duplicate');

    // Results
    Route::get('/surveys/{survey}/results', [ResultsController::class, 'show'])->name('surveys.results');
    Route::get('/surveys/{survey}/results/export', [ResultsController::class, 'export'])->name('surveys.export');

    // Profile (from Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
