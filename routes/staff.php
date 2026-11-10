<?php
// Staff routes
use App\Http\Controllers\Admin\MapController as AdminMapController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth','role:admin,staff'])->group(function () {
    Route::get('/reports', [AdminReportController::class,'index'])->name('reports.index');
    Route::get('/reports/{report}/photo', [AdminReportController::class,'photo'])->name('reports.photo');
    Route::get('/reports/{report}/photos/{photo}', [AdminReportController::class,'showPhoto'])->name('reports.photos.show');
    Route::patch('/reports/{report}/review', [AdminReportController::class,'saveReview'])->name('reports.review');
    Route::post('/reports/{report}/retry-ai', [AdminReportController::class,'retryAiAnalysis'])->name('reports.retry-ai');
    Route::get('/reports/{report}', [AdminReportController::class,'show'])->name('reports.show');
    Route::patch('/reports/{report}/status', [AdminReportController::class,'updateStatus'])->name('reports.status');
    Route::get('/map', [AdminMapController::class,'index'])->name('map');
    Route::get('/referrals', [ReferralController::class,'index'])->name('referrals.index');
});
