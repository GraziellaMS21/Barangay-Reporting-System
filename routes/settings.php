<?php

use App\Http\Controllers\Admin\BackupController;
use App\Http\Controllers\Admin\CalendarController;
use App\Http\Controllers\Admin\FiscalYearController;
use App\Http\Controllers\Admin\GeneralSettingsController;
use App\Http\Controllers\Admin\GlobalSearchController;
use App\Http\Controllers\Admin\NumberingController;
use App\Http\Controllers\Admin\PendingConfirmationController;
use App\Http\Controllers\Admin\TemplateController;
use App\Http\Controllers\FiscalYearSwitchController;
use Illuminate\Support\Facades\Route;

// Authenticated utilities
Route::middleware(['auth'])->group(function () {
    Route::post('/year/switch', [FiscalYearSwitchController::class, 'switch'])->name('year.switch');
});

Route::middleware(['auth', 'role:staff'])->group(function () {
    Route::get('/admin/_ui', fn () => view('admin.ui-guide'))->name('admin.ui');

    Route::get('/admin/search', [GlobalSearchController::class, 'search'])
        ->middleware('throttle:30,1')
        ->name('admin.search');

    // General settings
    Route::middleware(['permission:settings.manage'])->prefix('admin/settings')->name('admin.settings.')->group(function () {
        Route::get('/general', [GeneralSettingsController::class, 'edit'])->name('general.edit');
        Route::post('/general', [GeneralSettingsController::class, 'update'])->name('general.update');
        Route::put('/general', [GeneralSettingsController::class, 'update'])->name('general.put');
    });

    // Fiscal years
    Route::middleware(['permission:fiscal_year.manage'])->prefix('admin/settings/fiscal-years')->name('admin.fiscal-years.')->group(function () {
        Route::get('/', [FiscalYearController::class, 'index'])->name('index');
        Route::post('/', [FiscalYearController::class, 'store'])->name('store');
        Route::post('/{fiscal_year}/activate', [FiscalYearController::class, 'activate'])->name('activate');
        Route::post('/{fiscal_year}/close', [FiscalYearController::class, 'close'])->name('close');
        Route::post('/{fiscal_year}/reopen', [FiscalYearController::class, 'reopen'])->name('reopen');
    });

    // Numbering schemes
    Route::middleware(['permission:settings.manage'])->prefix('admin/settings/numbering')->name('admin.numbering.')->group(function () {
        Route::get('/', [NumberingController::class, 'edit'])->name('edit');
        Route::post('/', [NumberingController::class, 'update'])->name('update');
        Route::put('/', [NumberingController::class, 'update'])->name('put');
    });

    // Calendar Holidays
    Route::middleware(['permission:settings.manage'])->prefix('admin/settings/calendar')->name('admin.calendar.')->group(function () {
        Route::get('/', [CalendarController::class, 'index'])->name('index');
        Route::post('/', [CalendarController::class, 'store'])->name('store');
        Route::delete('/{date}', [CalendarController::class, 'destroy'])->name('destroy');
    });

    // Pending confirmations
    Route::middleware(['permission:settings.manage'])->prefix('admin/settings/pending')->name('admin.pending.')->group(function () {
        Route::get('/', [PendingConfirmationController::class, 'index'])->name('index');
        Route::post('/confirm', [PendingConfirmationController::class, 'confirm'])->name('confirm');
        Route::post('/confirm-group', [PendingConfirmationController::class, 'confirmGroup'])->name('confirm-group');
        Route::get('/export', [PendingConfirmationController::class, 'exportCsv'])->name('export');
    });

    // Response templates
    Route::middleware(['permission:templates.manage'])->prefix('admin/settings/templates')->name('admin.templates.')->group(function () {
        Route::get('/', [TemplateController::class, 'index'])->name('index');
        Route::get('/create', [TemplateController::class, 'create'])->name('create');
        Route::post('/', [TemplateController::class, 'store'])->name('store');
        Route::get('/json', [TemplateController::class, 'json'])->name('json');
        Route::get('/{template}/edit', [TemplateController::class, 'edit'])->name('edit');
        Route::put('/{template}', [TemplateController::class, 'update'])->name('update');
        Route::patch('/{template}', [TemplateController::class, 'update'])->name('patch');
        Route::delete('/{template}', [TemplateController::class, 'destroy'])->name('destroy');
    });

    // Backups
    Route::middleware(['permission:backups.manage'])->prefix('admin/settings/backups')->name('admin.backups.')->group(function () {
        Route::get('/', [BackupController::class, 'index'])->name('index');
        Route::post('/run', [BackupController::class, 'run'])->name('run');
        Route::get('/{file}', [BackupController::class, 'download'])->name('download');
    });
});
