<?php

use App\Http\Controllers\Admin\Imports\ImportController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:staff', 'password.changed', 'permission:imports.manage'])
    ->prefix('admin/imports')
    ->name('admin.imports.')
    ->group(function () {
        Route::get('/', [ImportController::class, 'index'])->name('index');
        Route::get('/create', [ImportController::class, 'create'])->name('create');
        Route::get('/template', [ImportController::class, 'downloadTemplate'])->name('template');
        Route::post('/', [ImportController::class, 'store'])->name('store');
        Route::get('/{batch}/map', [ImportController::class, 'map'])->name('map');
        Route::post('/{batch}/map', [ImportController::class, 'saveMap'])->name('save-map');
        Route::get('/{batch}/preview', [ImportController::class, 'preview'])->name('preview');
        Route::post('/{batch}/commit', [ImportController::class, 'commit'])->name('commit');
        Route::post('/{batch}/rollback', [ImportController::class, 'rollback'])->name('rollback');
    });
