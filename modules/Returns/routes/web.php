<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Returns\Http\Controllers\ReturnRunsController;
use Modules\Returns\Http\Controllers\ReturnsDashboardController;

Route::middleware(['auth', 'verified'])->group(function (): void {
    Route::get('returns', [ReturnsDashboardController::class, 'index'])->name('returns.dashboard');
    Route::get('returns/runs', [ReturnRunsController::class, 'index'])->name('returns.runs.index');
    Route::get('returns/runs/{run}', [ReturnRunsController::class, 'show'])->name('returns.runs.show');
    Route::post('returns/runs/{run}/submit', [ReturnRunsController::class, 'submitForReview'])->name('returns.runs.submit');
    Route::post('returns/runs/{run}/approve', [ReturnRunsController::class, 'approveAsChecker'])->name('returns.runs.approve');
    Route::post('returns/runs/{run}/sign-off', [ReturnRunsController::class, 'signOff'])->name('returns.runs.sign_off');
    Route::post('returns/runs/{run}/acknowledge', [ReturnRunsController::class, 'recordAcknowledgement'])->name('returns.runs.acknowledge');
});
