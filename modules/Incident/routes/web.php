<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Incident\Http\Controllers\IncidentsController;

Route::middleware(['auth', 'verified', 'audit'])->group(function (): void {
    Route::resource('incidents', IncidentsController::class);

    Route::post('incidents/{incident}/transition', [IncidentsController::class, 'transition'])
        ->name('incidents.transition');

    Route::post('incidents/{incident}/evidence', [IncidentsController::class, 'attachEvidence'])
        ->name('incidents.attach_evidence');

    Route::post('incidents/{incident}/actions', [IncidentsController::class, 'storeAction'])
        ->name('incidents.actions.store');

    Route::post('incidents/{incident}/actions/{action}/complete', [IncidentsController::class, 'completeAction'])
        ->name('incidents.actions.complete');

    Route::post('incidents/{incident}/notify/{notification}', [IncidentsController::class, 'recordNotification'])
        ->name('incidents.record_notification');

    Route::post('incidents/{incident}/loss', [IncidentsController::class, 'recordLoss'])
        ->name('incidents.record_loss');
});
