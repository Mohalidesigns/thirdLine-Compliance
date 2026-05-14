<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Policy\Http\Controllers\PoliciesController;

Route::middleware(['auth', 'verified', 'audit'])->group(function (): void {
    Route::resource('policies', PoliciesController::class);
    Route::post('policies/{policy}/transition', [PoliciesController::class, 'transition'])->name('policies.transition');
    Route::post('policies/{policy}/acknowledge', [PoliciesController::class, 'acknowledge'])->name('policies.acknowledge');
    Route::get('policies/{policy}/download', [PoliciesController::class, 'download'])->name('policies.download');
});
