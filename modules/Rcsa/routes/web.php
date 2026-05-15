<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Rcsa\Http\Controllers\RiskAssessmentsController;
use Modules\Rcsa\Http\Controllers\RisksController;

Route::middleware(['auth', 'verified', 'audit'])->group(function (): void {
    Route::resource('risk-assessments', RiskAssessmentsController::class);
    Route::post('risk-assessments/{risk_assessment}/transition', [RiskAssessmentsController::class, 'transition'])
        ->name('risk-assessments.transition');

    Route::resource('risks', RisksController::class);
    Route::post('risks/{risk}/score', [RisksController::class, 'score'])->name('risks.score');
});
