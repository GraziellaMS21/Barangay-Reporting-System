<?php

// Admin routes
use App\Http\Controllers\Admin\AuditLogController;
use App\Http\Controllers\Admin\CategorySettingsController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EmergencyContactController;
use App\Http\Controllers\Admin\OfficeSettingsController;
use App\Http\Controllers\Admin\RoutingRuleSettingsController;
use App\Http\Controllers\Admin\SensitivePlaceSettingsController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserSettingsController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin,staff', 'permission:dashboard.view'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', fn () => redirect()->route('admin.dashboard'))->name('dashboard.legacy');
});

Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:admin'])->group(function () {
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::post('/settings/priority-weights', [SettingsController::class, 'updatePriority'])->name('settings.priority');
    Route::get('/settings/priority-weights', [SettingsController::class, 'priorityWeights'])->name('settings.priority-weights.index');
    Route::post('/settings/priority-weights/reset', [SettingsController::class, 'resetPriority'])->name('settings.priority-weights.reset');
    Route::get('/settings/emergency-alert-emails', [SettingsController::class, 'emergencyAlertEmails'])->name('settings.emergency-alert-emails.index');
    Route::put('/settings/emergency-alert-emails', [SettingsController::class, 'updateEmergencyAlertEmails'])->name('settings.emergency-alert-emails.update');
    Route::get('/settings/ai', [SettingsController::class, 'aiConfig'])->name('settings.ai.edit');
    Route::put('/settings/ai', [SettingsController::class, 'updateAiConfig'])->name('settings.ai.update');
    Route::resource('settings/categories', CategorySettingsController::class)->except(['show', 'destroy'])->names('settings.categories');
    Route::patch('settings/offices/{office}/toggle-active', [OfficeSettingsController::class, 'toggleActive'])->name('settings.offices.toggle-active');
    Route::resource('settings/offices', OfficeSettingsController::class)->except('show')->names('settings.offices');
    Route::resource('settings/routing-rules', RoutingRuleSettingsController::class)->except('show')->parameters(['routing-rules' => 'routingRule'])->names('settings.routing-rules');
    Route::resource('settings/sensitive-places', SensitivePlaceSettingsController::class)->except('show')->parameters(['sensitive-places' => 'sensitivePlace'])->names('settings.sensitive-places');
    Route::resource('settings/users', UserSettingsController::class)->except(['show', 'destroy'])->names('settings.users');
    Route::post('/audit-log/filter', [AuditLogController::class, 'filter'])->name('audit-log.filter');
    Route::get('/audit-log', [AuditLogController::class, 'index'])->name('audit-log.index');
    Route::get('/settings/emergency-contacts', [EmergencyContactController::class, 'index'])->name('settings.emergency-contacts.index');
    Route::post('/settings/emergency-contacts', [EmergencyContactController::class, 'store'])->name('settings.emergency-contacts.store');
    Route::get('/settings/emergency-contacts/{emergencyContact}/edit', [EmergencyContactController::class, 'edit'])->name('settings.emergency-contacts.edit');
    Route::put('/settings/emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'update'])->name('settings.emergency-contacts.update');
    Route::delete('/settings/emergency-contacts/{emergencyContact}', [EmergencyContactController::class, 'destroy'])->name('settings.emergency-contacts.destroy');
});
