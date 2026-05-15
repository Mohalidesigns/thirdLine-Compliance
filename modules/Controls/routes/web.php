<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Modules\Controls\Http\Controllers\ControlsController;
use Modules\Controls\Http\Controllers\IssuesController;
use Modules\Controls\Http\Controllers\TestsController;

Route::middleware(['auth', 'verified', 'audit'])->group(function (): void {
    Route::resource('controls', ControlsController::class);
    Route::get('controls/{control}/test', [ControlsController::class, 'test'])->name('controls.test');
    Route::post('controls/{control}/tests', [ControlsController::class, 'recordTest'])->name('controls.recordTest');

    Route::resource('tests', TestsController::class)->only(['index', 'show']);
    Route::resource('issues', IssuesController::class)->only(['index', 'show', 'update']);
});
