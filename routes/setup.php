<?php

use App\Http\Controllers\SetupController;
use Illuminate\Support\Facades\Route;

Route::prefix('setup')->name('setup.')->group(function () {
    Route::get('/', [SetupController::class, 'show'])->name('show');
    Route::get('/{step}', [SetupController::class, 'step'])->name('step');
    Route::post('/finish', [SetupController::class, 'finish'])->name('finish');
    Route::post('/{step}', [SetupController::class, 'store'])->name('store');
});
