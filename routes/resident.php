<?php

// Resident routes
use App\Http\Controllers\ComplaintController;
use App\Http\Controllers\MapController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TrackController;
use App\Http\Controllers\VoiceAnalyzeController;
use App\Http\Controllers\VoiceTranscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/', [ReportController::class, 'index'])->name('home');
Route::get('/report', [ReportController::class, 'create'])->name('reports.create');
Route::get('/reports/create', [ReportController::class, 'create']);
Route::post('/report', [ReportController::class, 'store'])->middleware('throttle:10,60')->name('reports.store');
Route::post('/report/voice/transcribe', [VoiceTranscriptionController::class, 'transcribe'])->middleware('throttle:5,1')->name('reports.voice.transcribe');
Route::post('/report/voice/analyze', [VoiceAnalyzeController::class, 'analyzeResident'])->middleware('throttle:3,10')->name('reports.voice.analyze');
Route::get('/report/success/{trackingCode}', [ReportController::class, 'success'])->name('reports.success');
Route::get('/track', [TrackController::class, 'index'])->name('track.index');
Route::post('/track', [TrackController::class, 'search'])->name('track.search');
Route::get('/track/{trackingCode}', [TrackController::class, 'show'])->name('track.show');
Route::get('/track/{trackingCode}/proof-photos/{proofPhoto}', [TrackController::class, 'proofPhoto'])->name('track.proof-photo');
Route::get('/track/{trackingCode}/legacy-proof/{proof}', [TrackController::class, 'legacyProofPhoto'])->name('track.legacy-proof');

// Phase 9 tracking additions
Route::post('/track/{trackingCode}/info-response', [TrackController::class, 'respondToInfoRequest'])->name('track.info-response');
Route::post('/track/{trackingCode}/confirm', [TrackController::class, 'confirmResolution'])->name('track.confirm');
Route::post('/track/{trackingCode}/reopen', [TrackController::class, 'reopen'])->name('track.reopen');
Route::post('/track/{trackingCode}/feedback', [TrackController::class, 'feedback'])->name('track.feedback');

Route::get('/map', [MapController::class, 'index'])->name('map.index');

// Resident Dispute Complaint Intake (Katarungang Pambarangay)
Route::get('/complaint', [ComplaintController::class, 'create'])->name('complaint.create');
Route::post('/complaint', [ComplaintController::class, 'store'])->middleware('throttle:3,10')->name('complaint.store');
Route::get('/complaint/received', [ComplaintController::class, 'received'])->name('complaint.received');
