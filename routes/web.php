<?php

declare(strict_types=1);

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Modules\Calendar\Http\Controllers\CalendarController;
use Modules\Dashboard\Http\Controllers\DashboardController;
use Modules\Library\Http\Controllers\InstrumentsController;
use Modules\Library\Http\Controllers\ObligationsController;
use Modules\Sanctkb\Http\Controllers\SanctionsController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'       => Route::has('login'),
        'canRegister'    => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion'     => PHP_VERSION,
    ]);
});

Route::middleware(['auth', 'verified', 'audit'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/instruments', [InstrumentsController::class, 'index'])->name('instruments.index');
    Route::get('/instruments/create', [InstrumentsController::class, 'create'])->name('instruments.create');
    Route::post('/instruments', [InstrumentsController::class, 'store'])->name('instruments.store');
    Route::post('/instruments/bulk-import', [InstrumentsController::class, 'bulkImport'])->name('instruments.bulkImport');
    Route::get('/instruments/{id}', [InstrumentsController::class, 'show'])->name('instruments.show');
    Route::get('/instruments/{id}/edit', [InstrumentsController::class, 'edit'])->name('instruments.edit');
    Route::put('/instruments/{id}', [InstrumentsController::class, 'update'])->name('instruments.update');
    Route::delete('/instruments/{id}', [InstrumentsController::class, 'destroy'])->name('instruments.destroy');

    Route::get('/obligations', [ObligationsController::class, 'index'])->name('obligations.index');
    Route::get('/obligations/create', [ObligationsController::class, 'create'])->name('obligations.create');
    Route::post('/obligations', [ObligationsController::class, 'store'])->name('obligations.store');
    Route::get('/obligations/{id}', [ObligationsController::class, 'show'])->name('obligations.show');
    Route::get('/obligations/{id}/edit', [ObligationsController::class, 'edit'])->name('obligations.edit');
    Route::put('/obligations/{id}', [ObligationsController::class, 'update'])->name('obligations.update');
    Route::delete('/obligations/{id}', [ObligationsController::class, 'destroy'])->name('obligations.destroy');

    Route::get('/sanctions', [SanctionsController::class, 'index'])->name('sanctions.index');
    Route::get('/sanctions/create', [SanctionsController::class, 'create'])->name('sanctions.create');
    Route::post('/sanctions', [SanctionsController::class, 'store'])->name('sanctions.store');
    Route::get('/sanctions/{id}', [SanctionsController::class, 'show'])->name('sanctions.show');
    Route::get('/sanctions/{id}/edit', [SanctionsController::class, 'edit'])->name('sanctions.edit');
    Route::put('/sanctions/{id}', [SanctionsController::class, 'update'])->name('sanctions.update');
    Route::delete('/sanctions/{id}', [SanctionsController::class, 'destroy'])->name('sanctions.destroy');

    Route::get('/calendar', [CalendarController::class, 'index'])->name('calendar.index');
    Route::get('/calendar/ics', [CalendarController::class, 'ics'])->name('calendar.ics')->middleware('signed');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';

