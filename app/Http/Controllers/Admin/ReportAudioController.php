<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Services\Voice\VoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportAudioController extends Controller
{
    /**
     * Stream an authenticated private audio recording with byte-range seek support.
     */
    public function stream(Report $report): BinaryFileResponse
    {
        $audio = $report->audio;

        if (! $audio) {
            abort(404, 'Audio recording not found.');
        }

        if ($audio->disk_path === '[purged]' || ! Storage::disk('private')->exists($audio->disk_path)) {
            abort(404, 'Audio file has been purged or is unavailable.');
        }

        $fullPath = Storage::disk('private')->path($audio->disk_path);

        return response()->file($fullPath, [
            'Content-Type' => $audio->mime ?: 'audio/webm',
            'Cache-Control' => 'private, no-store, must-revalidate',
            'Content-Disposition' => 'inline',
        ]);
    }

    /**
     * Staff verification of uncertain or failed voice recording.
     */
    public function verify(Request $request, Report $report, VoiceService $voiceService): RedirectResponse
    {
        $validated = $request->validate([
            'description' => ['required', 'string', 'min:5'],
            'verification_note' => ['nullable', 'string', 'max:500'],
        ]);

        $voiceService->verify(
            $report,
            $validated['description'],
            $validated['verification_note'] ?? null,
            auth()->user()
        );

        return back()->with('toast', [
            'type' => 'success',
            'message' => 'Voice audio verified and report description updated.',
        ]);
    }
}
