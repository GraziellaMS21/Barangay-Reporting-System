<?php

use App\Http\Controllers\AccountPasswordController;
use App\Http\Controllers\Admin\PersonnelController;
use App\Http\Controllers\Admin\PositionController;
use App\Http\Controllers\Admin\SignatoryController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

// Account password change (any authenticated user)
Route::middleware(['auth'])->prefix('account/password')->name('account.password.')->group(function () {
    Route::get('/', [AccountPasswordController::class, 'edit'])->name('edit');
    Route::post('/', [AccountPasswordController::class, 'update'])->name('update');
    Route::put('/', [AccountPasswordController::class, 'update'])->name('put');
});

Route::middleware(['auth', 'role:staff'])->prefix('admin')->name('admin.')->group(function () {
    // Personnel
    Route::middleware(['permission:personnel.manage'])->group(function () {
        Route::resource('personnel', PersonnelController::class);
        Route::resource('positions', PositionController::class);

        Route::get('/signatories', [SignatoryController::class, 'edit'])->name('signatories.edit');
        Route::post('/signatories', [SignatoryController::class, 'update'])->name('signatories.update');
        Route::put('/signatories', [SignatoryController::class, 'update'])->name('signatories.put');
    });

    // Users
    Route::middleware(['permission:users.manage'])->group(function () {
        Route::resource('users', UserController::class);
        Route::post('/users/{user}/deactivate', [UserController::class, 'deactivate'])->name('users.deactivate');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
    });
});
