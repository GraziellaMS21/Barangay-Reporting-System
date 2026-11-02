<?php

use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

// Phase 1: Resident routes
Route::get('/', [ReportController::class, 'create'])->name('reports.create');
Route::post('/report', [ReportController::class, 'store'])
    ->middleware('throttle:10,60')->name('reports.store');
Route::get('/report/success/{trackingCode}', [ReportController::class, 'success'])->name('reports.success');

Route::get('/track', [TrackController::class, 'index'])->name('track.index');
Route::post('/track', [TrackController::class, 'search'])->name('track.search');
Route::get('/track/{trackingCode}', [TrackController::class, 'show'])->name('track.show');

// Phase 2: Staff Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Phase 2: Staff Area
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin,staff'])
    ->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.reports.index');
        });
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.status');
    });
