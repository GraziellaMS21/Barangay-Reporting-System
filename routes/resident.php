<?php
// Resident routes
use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReportController::class, 'create'])->name('reports.create');
Route::post('/report', [ReportController::class, 'store'])->middleware('throttle:10,60')->name('reports.store');
Route::get('/report/success/{trackingCode}', [ReportController::class, 'success'])->name('reports.success');
Route::get('/track', [TrackController::class, 'index'])->name('track.index');
Route::post('/track', [TrackController::class, 'search'])->name('track.search');
Route::get('/track/{trackingCode}', [TrackController::class, 'show'])->name('track.show');

// Phase 9 tracking additions
Route::post('/track/{trackingCode}/info-response', [TrackController::class, 'respondToInfoRequest'])->name('track.info-response');
Route::post('/track/{trackingCode}/confirm', [TrackController::class, 'confirmResolution'])->name('track.confirm');
Route::post('/track/{trackingCode}/reopen', [TrackController::class, 'reopen'])->name('track.reopen');
Route::post('/track/{trackingCode}/feedback', [TrackController::class, 'feedback'])->name('track.feedback');

Route::get('/map', [MapController::class, 'index'])->name('map.index');
