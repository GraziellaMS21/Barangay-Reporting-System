<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmergencyContactController;
use App\Http\Controllers\Admin\MapController as AdminMapController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\MapController;
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
Route::get('/map', [MapController::class, 'index'])->name('map.index');

// Phase 2: Staff Authentication
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

// Phase 2-7: Staff Area
Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin,staff'])
    ->group(function () {
        Route::get('/', function () {
            return redirect()->route('admin.dashboard');
        });

        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/{report}/photo', [AdminReportController::class, 'photo'])->name('reports.photo');
        Route::get('/reports/{report}/photos/{photo}', [AdminReportController::class, 'showPhoto'])
            ->name('reports.photos.show');
        Route::patch('/reports/{report}/ai-result', [AdminReportController::class, 'confirmAiResult'])
            ->name('reports.ai-result');
        Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
        Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.status');
        Route::get('/map', [AdminMapController::class, 'index'])->name('map');
        Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
        Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/priority-weights', [SettingsController::class, 'updatePriority'])->name('settings.priority');
    });

Route::prefix('admin')
    ->name('admin.')
    ->middleware(['auth', 'role:admin'])
    ->group(function () {
        Route::get('/settings/emergency-contacts', [EmergencyContactController::class, 'index'])
            ->name('settings.emergency-contacts.index');
        Route::post('/settings/emergency-contacts', [EmergencyContactController::class, 'store'])
            ->name('settings.emergency-contacts.store');
        Route::get('/settings/emergency-contacts/{emergencyContact}/edit', [EmergencyContactController::class, 'edit'])
            ->name('settings.emergency-contacts.edit');
        Route::put('/settings/emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'update'])
            ->name('settings.emergency-contacts.update');
        Route::delete('/settings/emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'destroy'])
            ->name('settings.emergency-contacts.destroy');
    });
