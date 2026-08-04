<?php

declare(strict_types=1);

use App\Extensions\TitanMapsIntelligence\Http\Controllers\CandidateController;
use App\Extensions\TitanMapsIntelligence\Http\Controllers\ExportController;
use App\Extensions\TitanMapsIntelligence\Http\Controllers\SearchController;
use App\Extensions\TitanMapsIntelligence\Http\Controllers\TerritoryController;
use App\Extensions\TitanMapsIntelligence\Http\Controllers\UsageController;
use App\Titan\Maps\MapsExportController;
use Illuminate\Support\Facades\Route;

Route::prefix('api/titan/maps-intelligence')
    ->middleware(['auth:sanctum', 'titan.company'])
    ->name('titan-maps-intelligence.')
    ->group(static function (): void {
        Route::post('/searches', [SearchController::class, 'store'])->name('searches.store');
        Route::get('/searches/{search}', [SearchController::class, 'show'])->name('searches.show');
        Route::post('/searches/{search}/cancel', [SearchController::class, 'cancel'])->name('searches.cancel');
        Route::post('/searches/{search}/export', [ExportController::class, 'store'])->name('searches.export');

        Route::get('/candidates', [CandidateController::class, 'index'])->name('candidates.index');
        Route::get('/candidates/{candidate}', [CandidateController::class, 'show'])->name('candidates.show');
        Route::post('/candidates/{candidate}/classify', [CandidateController::class, 'classify'])->name('candidates.classify');
        Route::post('/candidates/{candidate}/match', [CandidateController::class, 'match'])->name('candidates.match');
        Route::post('/candidates/{candidate}/approve', [CandidateController::class, 'approve'])->name('candidates.approve');
        Route::post('/candidates/{candidate}/reject', [CandidateController::class, 'reject'])->name('candidates.reject');
        Route::post('/candidates/{candidate}/promote', [CandidateController::class, 'promote'])->name('candidates.promote');

        Route::post('/territories/analyse', [TerritoryController::class, 'analyse'])->name('territories.analyse');
        Route::get('/territories/{analysis}', [TerritoryController::class, 'show'])->name('territories.show');
        Route::get('/usage', [UsageController::class, 'index'])->name('usage.index');

        Route::get('/exports/{reference}', MapsExportController::class)
            ->middleware('signed')
            ->name('exports.download');
    });
