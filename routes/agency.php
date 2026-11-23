<?php

use App\Http\Controllers\Admin\AgencyManagementController;
use App\Http\Controllers\Admin\ReferralActionController;
use App\Http\Controllers\Agency\InvitationController;
use App\Http\Controllers\Agency\ReferralController as AgencyReferralController;
use App\Http\Controllers\Agency\RegistrationController;
use App\Http\Controllers\Agency\TeamController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public agency registration and invitations
Route::prefix('agency')->name('agency.')->group(function () {
    Route::middleware('throttle:5,1')->group(function () {
        Route::get('/register', [RegistrationController::class, 'show'])->name('register.show');
        Route::post('/register', [RegistrationController::class, 'store'])->name('register.store');
        Route::get('/invite/{token}', [InvitationController::class, 'show'])->name('invite.show');
        Route::post('/invite/{token}', [InvitationController::class, 'accept'])->name('invite.accept');
    });

    Route::get('/pending', [RegistrationController::class, 'pending'])->name('pending');
});

// Laravel email verification routes
Route::middleware(['auth'])->group(function () {
    Route::get('/email/verify', function () {
        return view('stub', ['title' => 'Verify Your Email Address', 'owner' => 'WS5']);
    })->name('verification.notice');

    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('agency.referrals.index');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('message', 'Verification link sent.');
    })->middleware(['throttle:6,1'])->name('verification.send');
});

// Agency portal
Route::middleware(['auth', 'role:agency', 'agency.active'])->prefix('agency')->name('agency.')->group(function () {
    Route::get('/', fn () => redirect()->route('agency.referrals.index'));
    Route::get('/referrals', [AgencyReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/{referral}', [AgencyReferralController::class, 'show'])->name('referrals.show');
    Route::post('/referrals/{referral}', [AgencyReferralController::class, 'update'])->name('referrals.update');
    Route::put('/referrals/{referral}', [AgencyReferralController::class, 'update'])->name('referrals.put');
    Route::post('/referrals/{referral}/message', [AgencyReferralController::class, 'message'])->name('referrals.message');
    Route::get('/referrals/{referral}/attachments/{attachment}', [AgencyReferralController::class, 'downloadAttachment'])->name('referrals.attachments.download');
    Route::post('/referrals/{referral}/attachments', [AgencyReferralController::class, 'uploadAttachment'])->name('referrals.attachments.upload');

    Route::prefix('team')->name('team.')->group(function () {
        Route::get('/', [TeamController::class, 'index'])->name('index');
        Route::post('/invite', [TeamController::class, 'invite'])->name('invite');
        Route::post('/revoke', [TeamController::class, 'revoke'])->name('revoke');
        Route::post('/deactivate', [TeamController::class, 'deactivate'])->name('deactivate');
    });
});

// Admin side agency management
Route::middleware(['auth', 'role:staff', 'permission:agencies.manage'])->prefix('admin/agencies')->name('admin.agencies.')->group(function () {
    Route::get('/', [AgencyManagementController::class, 'index'])->name('index');
    Route::get('/{registration}', [AgencyManagementController::class, 'show'])->name('show');
    Route::post('/{registration}/approve', [AgencyManagementController::class, 'approve'])->name('approve');
    Route::post('/{registration}/reject', [AgencyManagementController::class, 'reject'])->name('reject');
    Route::post('/{registration}/suspend', [AgencyManagementController::class, 'suspend'])->name('suspend');
    Route::post('/{registration}/reinstate', [AgencyManagementController::class, 'reinstate'])->name('reinstate');
    Route::get('/{registration}/document', [AgencyManagementController::class, 'document'])->name('document');
});

// Staff side referral dispatch & messaging
Route::middleware(['auth', 'role:staff', 'permission:referrals.manage'])->prefix('admin/referrals')->name('admin.referrals.')->group(function () {
    Route::post('/{referral}/send', [ReferralActionController::class, 'send'])->name('send');
    Route::post('/{referral}/messages', [ReferralActionController::class, 'storeMessage'])->name('messages.store');
    Route::get('/{referral}/attachments/{attachment}', [ReferralActionController::class, 'downloadAttachment'])->name('attachments.download');
    Route::post('/{referral}/attachments', [ReferralActionController::class, 'uploadAttachment'])->name('attachments.upload');
});
