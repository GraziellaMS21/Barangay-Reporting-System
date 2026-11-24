<?php

use App\Http\Controllers\Admin\CaseController;
use App\Http\Controllers\Admin\CaseFieldConfigController;
use App\Http\Controllers\Admin\CaseIntakeController;
use App\Http\Controllers\Admin\CaseTypeController;
use App\Http\Controllers\Admin\PartyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:staff'])->prefix('admin')->name('admin.')->group(function () {
    // Case classification & field configs
    Route::middleware(['permission:settings.manage'])->group(function () {
        Route::resource('settings/case-types', CaseTypeController::class)->names('case-types');
        Route::get('settings/case-fields', [CaseFieldConfigController::class, 'index'])->name('case-fields.index');
        Route::post('settings/case-fields', [CaseFieldConfigController::class, 'update'])->name('case-fields.update');
        Route::put('settings/case-fields', [CaseFieldConfigController::class, 'update'])->name('case-fields.put');
    });

    // Case convert
    Route::middleware(['permission:cases.create'])->group(function () {
        Route::post('reports/{report}/convert-to-case', [CaseController::class, 'convertFromReport'])->name('reports.convert-to-case');
    });

    // Parties
    Route::prefix('parties')->name('parties.')->group(function () {
        Route::middleware(['permission:cases.view'])->group(function () {
            Route::get('/', [PartyController::class, 'index'])->name('index');
            Route::get('/search', [PartyController::class, 'search'])->name('search');
            Route::get('/{party}', [PartyController::class, 'show'])->name('show');
        });

        Route::middleware(['permission:cases.edit'])->group(function () {
            Route::get('/{party}/edit', [PartyController::class, 'edit'])->name('edit');
            Route::put('/{party}', [PartyController::class, 'update'])->name('update');
            Route::patch('/{party}', [PartyController::class, 'update'])->name('patch');
            Route::post('/{party}/verify-residency', [PartyController::class, 'verifyResidency'])->name('verify-residency');
            Route::get('/{party}/merge', [PartyController::class, 'mergeCreate'])->name('merge.create');
            Route::post('/{party}/merge', [PartyController::class, 'mergeStore'])->name('merge.store');
        });
    });

    // Cases
    Route::prefix('cases')->name('cases.')->group(function () {
        Route::middleware(['permission:cases.create'])->group(function () {
            Route::get('/create', [CaseController::class, 'create'])->name('create');
            Route::post('/', [CaseController::class, 'store'])->name('store');
        });

        Route::middleware(['permission:cases.view'])->group(function () {
            Route::get('/', [CaseController::class, 'index'])->name('index');
            Route::get('/export/csv', [CaseController::class, 'exportCsv'])->name('export-csv');
            Route::get('/{case}', [CaseController::class, 'show'])->name('show');
            Route::get('/{case}/print', [CaseController::class, 'print'])->name('print');
        });

        Route::middleware(['permission:cases.edit'])->group(function () {
            Route::get('/{case}/edit', [CaseController::class, 'edit'])->name('edit');
            Route::put('/{case}', [CaseController::class, 'update'])->name('update');
            Route::patch('/{case}', [CaseController::class, 'update'])->name('patch');
            Route::post('/{case}/status', [CaseController::class, 'status'])->name('status');
            Route::post('/{case}/assign', [CaseController::class, 'assign'])->name('assign');
            Route::post('/{case}/notes', [CaseController::class, 'storeNote'])->name('notes.store');
            Route::post('/{case}/eligibility', [CaseController::class, 'updateEligibility'])->name('eligibility');
            Route::post('/{case}/first-meeting', [CaseController::class, 'updateFirstMeeting'])->name('first-meeting');
        });

        // Citizen Pending Intakes
        Route::middleware(['permission:cases.view'])->group(function () {
            Route::get('/intakes/{intake}', [CaseIntakeController::class, 'show'])->name('intakes.show');
        });
        Route::middleware(['permission:cases.edit'])->group(function () {
            Route::post('/intakes/{intake}/reject', [CaseIntakeController::class, 'reject'])->name('intakes.reject');
            Route::post('/intakes/{intake}/retry-ai', [CaseIntakeController::class, 'retryAi'])->name('intakes.retry-ai');
            Route::post('/intakes/{intake}/docket', [CaseIntakeController::class, 'docket'])->name('intakes.docket');
        });
    });
});
