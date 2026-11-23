<?php

use App\Http\Controllers\Admin\ReportsCenterController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:staff', 'password.changed'])->prefix('admin/reports-center')->name('admin.reports-center.')->group(function () {
    Route::middleware(['permission:reports_center.view'])->group(function () {
        Route::get('/', [ReportsCenterController::class, 'index'])->name('index');
        Route::get('/history', [ReportsCenterController::class, 'history'])->name('history');
        Route::get('/generated/{generatedReport}', [ReportsCenterController::class, 'download'])->name('download');
        Route::get('/{key}', [ReportsCenterController::class, 'show'])->name('show');
    });

    Route::middleware(['permission:reports_center.export'])->group(function () {
        Route::post('/{key}/export', [ReportsCenterController::class, 'export'])->name('export');
    });
});
