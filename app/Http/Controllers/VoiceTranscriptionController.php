<?php

namespace App\Http\Controllers;

use App\Models\ReportAudio;
use App\Services\Voice\VoiceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoiceTranscriptionController extends Controller
{
    public function transcribe(Request $request, VoiceService $voiceService): JsonResponse
    {
        if (! config('voice.enabled', true)) {
            return response()->json(['message' => 'Voice reporting is currently disabled.'], 403);
        }

        $request->validate([
            'audio' => ['required', 'file', 'max:10240'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:60'],
        ]);

        $audio = $voiceService->storeAudio(
            $request->file('audio'),
            ReportAudio::ORIGIN_RESIDENT,
            null,
            $request->integer('duration_seconds') ?: null
        );

        $voiceService->transcribe($audio);

        $isUnclear = $audio->transcript_status === ReportAudio::STATUS_NEEDS_VERIFICATION;
        $isFailed = $audio->transcript_status === ReportAudio::STATUS_FAILED;
        $notice = null;

        if ($isUnclear || $isFailed) {
            $notice = "We couldn't clearly transcribe parts of your voice note. Barangay staff will listen and review it before processing.";
        }

        return response()->json([
            'token' => $audio->token,
            'transcript' => $audio->transcript ?? '',
            'status' => $audio->transcript_status,
            'unverified_draft' => (bool) $audio->unverified_draft,
            'language' => $audio->transcript_language,
            'unclear' => $isUnclear,
            'notice' => $notice,
        ]);
    }
}
