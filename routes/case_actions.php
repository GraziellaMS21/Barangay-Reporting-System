<?php

use App\Http\Controllers\Admin\CaseEscalationController;
use App\Http\Controllers\Admin\CasePrintableController;
use App\Http\Controllers\Admin\CaseSettlementController;
use App\Http\Controllers\Admin\EscalationDestinationController;
use App\Http\Controllers\Admin\HearingController;
use App\Http\Controllers\Admin\SecretaryDashboardController;
use App\Http\Controllers\Admin\SummonsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'role:staff'])->prefix('admin')->name('admin.')->group(function () {
    // Secretary dashboard
    Route::middleware(['permission:cases.view'])->group(function () {
        Route::get('/secretary', [SecretaryDashboardController::class, 'index'])->name('secretary');
    });

    // Escalation destinations
    Route::middleware(['permission:settings.manage'])->group(function () {
        Route::resource('settings/escalation-destinations', EscalationDestinationController::class)
            ->names('escalation-destinations');
    });

    // Hearings list
    Route::middleware(['permission:cases.summons'])->group(function () {
        Route::get('/hearings', [HearingController::class, 'index'])->name('hearings.index');
    });

    // Summons, hearings, escalation, settlement under case
    Route::prefix('cases/{case}')->name('cases.')->group(function () {
        Route::middleware(['permission:cases.summons'])->group(function () {
            Route::get('/summons/create', [SummonsController::class, 'create'])->name('summons.create');
            Route::post('/summons', [SummonsController::class, 'store'])->name('summons.store');
            Route::post('/summons/{summons}/service', [SummonsController::class, 'service'])->name('summons.service');
            Route::post('/summons/{summons}/attendance', [SummonsController::class, 'attendance'])->name('summons.attendance');
            Route::post('/summons/{summons}/void', [SummonsController::class, 'void'])->name('summons.void');
            Route::get('/summons/{summons}/print', [SummonsController::class, 'print'])->name('summons.print');

            Route::post('/hearings', [HearingController::class, 'store'])->name('hearings.store');
            Route::put('/hearings/{hearing}', [HearingController::class, 'update'])->name('hearings.update');
            Route::patch('/hearings/{hearing}', [HearingController::class, 'update'])->name('hearings.patch');
            Route::post('/hearings/{hearing}/outcome', [HearingController::class, 'outcome'])->name('hearings.outcome');
        });

        Route::middleware(['permission:cases.settlement'])->group(function () {
            Route::post('/settlement', [CaseSettlementController::class, 'store'])->name('settlement.store');
            Route::get('/settlement/agreement', [CaseSettlementController::class, 'agreement'])->name('settlement.agreement');
        });

        Route::middleware(['permission:cases.escalate'])->group(function () {
            Route::get('/escalate', [CaseEscalationController::class, 'create'])->name('escalations.create');
            Route::post('/escalate', [CaseEscalationController::class, 'store'])->name('escalations.store');
            Route::get('/escalate/letter', [CaseEscalationController::class, 'letter'])->name('escalations.letter');
            Route::get('/escalate/cfa', [CaseEscalationController::class, 'cfa'])->name('escalations.cfa');
        });

        // Official Katarungang Pambarangay Printables (Task 11)
        Route::middleware(['permission:cases.view'])->prefix('printables')->name('printables.')->group(function () {
            Route::get('/blotter-extract', [CasePrintableController::class, 'blotterExtract'])->name('blotter-extract');
            Route::get('/complaint', [CasePrintableController::class, 'complaint'])->name('complaint');
            Route::get('/summons/{summons?}', [CasePrintableController::class, 'summons'])->name('summons');
            Route::get('/conciliation-notice/{hearing?}', [CasePrintableController::class, 'conciliationNotice'])->name('conciliation-notice');
            Route::get('/settlement', [CasePrintableController::class, 'settlement'])->name('settlement');
            Route::get('/cfa', [CasePrintableController::class, 'cfa'])->name('cfa');
        });
    });
});
