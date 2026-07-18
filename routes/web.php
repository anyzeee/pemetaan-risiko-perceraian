<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\DataManagementController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\RiskMappingController;
use App\Http\Controllers\ClusteringController;
use App\Http\Controllers\RecommendationController;
use App\Http\Controllers\ReportsController;
use App\Http\Controllers\ProfileController;

Route::get('/', function () {
    return redirect('/login');
});

Route::get('/login', [LoginController::class, 'index'])->name('login');
Route::post('/login', [LoginController::class, 'login'])->name('login.post');

Route::middleware('auth')->group(function () {

    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Data Management
    Route::get('/data-management', [DataManagementController::class, 'index'])->name('data-management.index');
    Route::post('/data-management', [DataManagementController::class, 'store'])->name('data-management.store');
    Route::put('/data-management/{tab}/{id}', [DataManagementController::class, 'update'])->name('data-management.update');
    Route::delete('/data-management/{tab}/{id}', [DataManagementController::class, 'destroy'])->name('data-management.destroy');

    // Import CSV
    Route::post('/data-management/import/cases', [ImportController::class, 'importDivorceRecaps'])->name('data-management.import.cases');
    Route::post('/data-management/import/marriage', [ImportController::class, 'importMarriages'])->name('data-management.import.marriage');
    Route::post('/data-management/import/population', [ImportController::class, 'importPopulations'])->name('data-management.import.population');
    Route::post('/data-management/import/factors', [ImportController::class, 'importDivorceFactors'])->name('data-management.import.factors');

    Route::get('/risk-mapping', [RiskMappingController::class, 'index'])->name('risk-mapping.index');

    Route::get('/clustering-analysis', [ClusteringController::class, 'index'])->name('clustering-analysis.index');

    Route::middleware('admin.only')->group(function () {
        Route::get('/clustering-analysis/results', [ClusteringController::class, 'showResults'])->name('clustering-analysis.results');
        Route::post('/clustering-analysis/run', [ClusteringController::class, 'runClustering'])->name('clustering-analysis.run');
        Route::get('/recommendation', [RecommendationController::class, 'index'])->name('recommendation.index');
        Route::patch('/recommendation/{id}/toggle', [RecommendationController::class, 'toggleComplete'])->name('recommendation.toggle');
    });

    Route::get('/reports', [ReportsController::class, 'index'])->name('reports.index');
    Route::get('/reports/export-csv', [ReportsController::class, 'exportCsv'])->name('reports.export-csv');

    Route::get('/profile', [ProfileController::class, 'index'])->name('profile.index');
    Route::put('/profile/update', [ProfileController::class, 'update'])->name('profile.update');
    Route::post('/profile/password', [ProfileController::class, 'password'])->name('profile.password');

});