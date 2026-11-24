<?php

namespace App\Http\Controllers;

use App\Models\ReportAudio;
use App\Services\Voice\VoiceAnalyzeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceAnalyzeController extends Controller
{
    /**
     * Resident voice analyze intake endpoint.
     */
    public function analyzeResident(Request $request, VoiceAnalyzeService $analyzeService): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:10240'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
            'anonymous' => ['nullable', 'boolean'],
            'mock_scenario' => ['nullable', 'string', 'in:full,no_contact,partial,unclear,emergency'],
        ], [
            'audio.required' => 'Please provide an audio recording or file to analyze.',
            'audio.max' => 'The audio recording must not exceed 10 MB.',
            'duration_seconds.max' => 'Audio recordings must be 120 seconds or less.',
        ]);

        $mockScenario = (app()->environment('local', 'testing') || config('voice.mock_transcription'))
            ? $request->string('mock_scenario')->value() ?: null
            : null;

        $result = $analyzeService->analyze(
            file: $request->file('audio'),
            origin: ReportAudio::ORIGIN_RESIDENT,
            user: null,
            durationSeconds: $request->integer('duration_seconds') ?: null,
            isAnonymous: $request->boolean('anonymous'),
            mockScenario: $mockScenario,
        );

        return response()->json($result);
    }

    /**
     * Staff phone report voice analyze intake endpoint.
     */
    public function analyzeStaff(Request $request, VoiceAnalyzeService $analyzeService): JsonResponse
    {
        $request->validate([
            'audio' => ['required', 'file', 'max:10240'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:120'],
            'mock_scenario' => ['nullable', 'string', 'in:full,no_contact,partial,unclear,emergency'],
        ], [
            'audio.required' => 'Please provide an audio recording or file to analyze.',
            'audio.max' => 'The audio recording must not exceed 10 MB.',
            'duration_seconds.max' => 'Audio recordings must be 120 seconds or less.',
        ]);

        $mockScenario = (app()->environment('local', 'testing') || config('voice.mock_transcription'))
            ? $request->string('mock_scenario')->value() ?: null
            : null;

        $result = $analyzeService->analyze(
            file: $request->file('audio'),
            origin: ReportAudio::ORIGIN_STAFF,
            user: $request->user(),
            durationSeconds: $request->integer('duration_seconds') ?: null,
            isAnonymous: false,
            mockScenario: $mockScenario,
        );

        return response()->json($result);
    }
}
