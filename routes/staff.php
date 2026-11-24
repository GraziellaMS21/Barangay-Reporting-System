<?php

// Staff routes
use App\Http\Controllers\Admin\MapController as AdminMapController;
use App\Http\Controllers\Admin\PhoneReportController;
use App\Http\Controllers\Admin\ReferralController;
use App\Http\Controllers\Admin\ReportAudioController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,staff'])->group(function () {
    Route::get('/reports', [AdminReportController::class, 'index'])->name('reports.index');
    Route::get('/reports/phone', [PhoneReportController::class, 'create'])->middleware('permission:reports.log_phone')->name('reports.phone.create');
    Route::post('/reports/phone', [PhoneReportController::class, 'store'])->middleware('permission:reports.log_phone')->name('reports.phone.store');
    Route::post('/reports/bulk-confirm', [AdminReportController::class, 'bulkConfirm'])->name('reports.bulk-confirm');
    Route::post('/reports/bulk-action', [AdminReportController::class, 'bulkAction'])->name('reports.bulk-action');
    Route::get('/reports/export', [AdminReportController::class, 'export'])->name('reports.export');
    Route::get('/my-cases', [AdminReportController::class, 'myCases'])->name('reports.my-cases');
    Route::get('/reports/{report}/photo', [AdminReportController::class, 'photo'])->name('reports.photo');
    Route::get('/reports/{report}/photos/{photo}', [AdminReportController::class, 'showPhoto'])->name('reports.photos.show');
    Route::get('/reports/{report}/proofs/{proof}', [AdminReportController::class, 'showProofPhoto'])->name('reports.proofs.show');
    Route::get('/reports/{report}/review-ai', [AdminReportController::class, 'reviewAiStatus'])->name('reports.review-ai');
    // Review form also carries "Prepare referral" (POST from card 3, Mode B),
    // so accept both verbs through the same endpoint.
    Route::match(['post', 'patch'], '/reports/{report}/review', [AdminReportController::class, 'saveReview'])->name('reports.review');
    Route::post('/reports/{report}/proof-photos', [AdminReportController::class, 'storeProofPhotos'])->name('reports.proof-photos.store');
    Route::get('/reports/{report}/proof-photos/{proofPhoto}', [AdminReportController::class, 'showProofPhotoImage'])->name('reports.proof-photos.show');
    Route::delete('/reports/{report}/proof-photos/{proofPhoto}', [AdminReportController::class, 'deleteProofPhoto'])->name('reports.proof-photos.destroy');
    Route::patch('/reports/{report}/emergency', [AdminReportController::class, 'resolveEmergencyFlag'])->name('reports.emergency');
    Route::patch('/reports/{report}/photo-mismatch', [AdminReportController::class, 'resolvePhotoMismatch'])->name('reports.photo-mismatch');
    Route::patch('/reports/{report}/refer', [AdminReportController::class, 'referReport'])->name('reports.refer');
    Route::patch('/reports/{report}/duplicate', [AdminReportController::class, 'markDuplicate'])->name('reports.duplicate');
    Route::patch('/reports/{report}/unmerge', [AdminReportController::class, 'unmerge'])->name('reports.unmerge');
    Route::patch('/reports/{report}/accept-review', [AdminReportController::class, 'acceptReview'])->name('reports.accept-review');
    Route::patch('/reports/{report}/route', [AdminReportController::class, 'routeReport'])->name('reports.route');
    Route::patch('/reports/{report}/accept-and-route', [AdminReportController::class, 'acceptAndRoute'])->name('reports.accept-and-route');
    Route::post('/reports/{report}/retry-ai', [AdminReportController::class, 'retryAiAnalysis'])->name('reports.retry-ai');
    Route::get('/reports/{report}', [AdminReportController::class, 'show'])->name('reports.show');
    Route::get('/reports/{report}/audio', [ReportAudioController::class, 'stream'])->middleware('permission:reports.view')->name('reports.audio');
    Route::post('/reports/{report}/audio/verify', [ReportAudioController::class, 'verify'])->middleware('permission:reports.view')->name('reports.audio.verify');
    Route::patch('/reports/{report}/status', [AdminReportController::class, 'updateStatus'])->name('reports.status');
    Route::patch('/reports/{report}/assign', [AdminReportController::class, 'assign'])->name('reports.assign');
    Route::post('/reports/{report}/info-requests', [AdminReportController::class, 'storeInfoRequest'])->name('reports.info-requests.store');
    // JSON thread for the chat modal (open + light polling while it is open).
    Route::get('/reports/{report}/info-requests', [AdminReportController::class, 'infoThread'])->name('reports.info-requests.index');
    Route::post('/reports/{report}/internal-notes', [AdminReportController::class, 'storeInternalNote'])->name('reports.internal-notes.store');
    Route::post('/reports/{report}/updates', [AdminReportController::class, 'storeUpdate'])->name('reports.updates.store');
    Route::post('/reports/{report}/reopen', [AdminReportController::class, 'reopenReport'])->name('reports.reopen');
    Route::post('/reports/{report}/resolve', [AdminReportController::class, 'proposeResolution'])->name('reports.resolve');
    Route::post('/reports/{report}/close', [AdminReportController::class, 'closeManually'])->name('reports.close');
    Route::post('/reports/{report}/escalate', [AdminReportController::class, 'escalate'])->name('reports.escalate');
    Route::get('/map', [AdminMapController::class, 'index'])->name('map');
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/{referral}/letter', [ReferralController::class, 'letter'])->name('referrals.letter');
    Route::get('/referrals/{referral}/updates/{update}/photo', [ReferralController::class, 'updatePhoto'])->name('referrals.updates.photo');
    Route::post('/referrals/{referral}/mark-sent', [ReferralController::class, 'markSent'])->name('referrals.mark-sent');
    Route::post('/referrals/{referral}/updates', [ReferralController::class, 'storeUpdate'])->name('referrals.updates.store');
    Route::post('/referrals/{referral}/close', [ReferralController::class, 'close'])->name('referrals.close');
});
