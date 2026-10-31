<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackController;

Route::get('/', [ReportController::class, 'create'])->name('reports.create');
Route::post('/report', [ReportController::class, 'store'])
    ->middleware('throttle:10,60')->name('reports.store');
Route::get('/report/success/{trackingCode}', [ReportController::class, 'success'])->name('reports.success');

Route::get('/track', [TrackController::class, 'index'])->name('track.index');
Route::post('/track', [TrackController::class, 'search'])->name('track.search');
Route::get('/track/{trackingCode}', [TrackController::class, 'show'])->name('track.show');