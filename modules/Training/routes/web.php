<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Training\Http\Controllers\MyAttestationsController;
use Modules\Training\Http\Controllers\MyTrainingController;

Route::middleware(['auth'])->group(function (): void {
    Route::get('my/training', [MyTrainingController::class, 'index'])->name('my.training.index');
    Route::get('my/training/{enrollment}', [MyTrainingController::class, 'show'])->name('my.training.show');
    Route::post('my/training/{enrollment}/start', [MyTrainingController::class, 'start'])->name('my.training.start');
    Route::post('my/training/{enrollment}/complete', [MyTrainingController::class, 'complete'])->name('my.training.complete');

    Route::get('my/attestations', [MyAttestationsController::class, 'index'])->name('my.attestations.index');
    Route::get('my/attestations/{campaign}', [MyAttestationsController::class, 'show'])->name('my.attestations.show');
    Route::post('my/attestations/{campaign}/sign', [MyAttestationsController::class, 'sign'])->name('my.attestations.sign');
});
