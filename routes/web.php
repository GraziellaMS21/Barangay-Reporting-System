<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->middleware('throttle:10,1');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

require __DIR__.'/resident.php';
require __DIR__.'/staff.php';
require __DIR__.'/engine.php';
require __DIR__.'/admin.php';

require __DIR__.'/setup.php';
require __DIR__.'/settings.php';
require __DIR__.'/people.php';
require __DIR__.'/reports_center.php';
require __DIR__.'/cases.php';
require __DIR__.'/case_actions.php';
require __DIR__.'/agency.php';
