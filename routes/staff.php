<?php

// Staff routes
use App\Http\Controllers\Admin\MapController as AdminMapController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::post('/reports/bulk-confirm', [AdminReportController::class, 'bulkConfirm'])->name('reports.bulk-confirm');
    Route::get('/my-cases', [AdminReportController::class, 'myCases'])->name('reports.my-cases');
    Route::get('/reports/{report}/photo', [AdminReportController::class, 'photo'])->name('reports.photo');
    Route::get('/reports/{report}/photos/{photo}', [AdminReportController::class, 'showPhoto'])->name('reports.photos.show');
    Route::get('/reports/{report}/proofs/{proof}', [AdminReportController::class, 'showProofPhoto'])->name('reports.proofs.show');
    Route::patch('/reports/{report}/review', [AdminReportController::class, 'saveReview'])->name('reports.review');
    Route::post('/reports/{report}/retry-ai', [AdminReportController::class, 'retryAiAnalysis'])->name('reports.retry-ai');
    Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
    Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.status');
    Route::patch('/reports/{report}/assign', [AdminReportController::class, 'assign'])->name('reports.assign');
    Route::post('/reports/{report}/info-requests', [AdminReportController::class, 'storeInfoRequest'])->name('reports.info-requests.store');
    Route::post('/reports/{report}/internal-notes', [AdminReportController::class, 'storeInternalNote'])->name('reports.internal-notes.store');
    Route::post('/reports/{report}/resolve', [AdminReportController::class, 'proposeResolution'])->name('reports.resolve');
    Route::post('/reports/{report}/close', [AdminReportController::class, 'closeManually'])->name('reports.close');
    Route::post('/reports/{report}/escalate', [AdminReportController::class, 'escalate'])->name('reports.escalate');
    Route::get('/map', [AdminMapController::class, 'index'])->name('map');
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
});
