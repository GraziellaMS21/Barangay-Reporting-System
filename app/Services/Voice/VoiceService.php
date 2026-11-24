<?php

namespace App\Services\Voice;

use App\Models\Report;
use App\Models\ReportAudio;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\GeminiService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VoiceService
{
    public function __construct(
        public GeminiService $gemini,
        public AuditLogger $auditLogger,
    ) {}

    /**
     * Store uploaded or recorded audio to private disk and create a ReportAudio record.
     */
    public function storeAudio(
        UploadedFile|string $file,
        string $origin = ReportAudio::ORIGIN_RESIDENT,
        ?int $userId = null,
        ?int $durationSeconds = null,
        ?string $clientMime = null,
    ): ReportAudio {
        $token = (string) Str::uuid();
        $datePath = date('Y/m');

        if ($file instanceof UploadedFile) {
            $extension = $file->getClientOriginalExtension() ?: 'webm';
            $filename = "{$token}.{$extension}";
            $diskPath = $file->storeAs("audio/{$datePath}", $filename, 'private');
            $mime = $clientMime ?: ($file->getClientMimeType() ?: 'audio/webm');
            $sizeBytes = $file->getSize() ?: 0;
        } else {
            $extension = 'webm';
            $filename = "{$token}.{$extension}";
            $diskPath = "audio/{$datePath}/{$filename}";
            Storage::disk('private')->put($diskPath, $file);
            $mime = $clientMime ?: 'audio/webm';
            $sizeBytes = strlen($file);
        }

        $retentionDays = (int) config('voice.audio_retention_days', 90);

        return ReportAudio::create([
            'token' => $token,
            'origin' => $origin,
            'disk_path' => $diskPath,
            'mime' => $mime,
            'size_bytes' => $sizeBytes,
            'duration_seconds' => $durationSeconds ?? 0,
            'transcript_status' => ReportAudio::STATUS_PENDING,
            'uploaded_by' => $userId,
            'expires_at' => now()->addDays($retentionDays),
        ]);
    }

    /**
     * Transcribe an audio record using GeminiService.
     */
    public function transcribe(ReportAudio $audio): ReportAudio
    {
        $bytes = Storage::disk('private')->get($audio->disk_path);

        $result = $this->gemini->transcribeAudio($bytes, $audio->mime);

        $audio->update([
            'transcript' => $result['transcript'],
            'transcript_language' => $result['language'],
            'transcript_status' => $result['status'],
            'unverified_draft' => in_array($result['status'], [ReportAudio::STATUS_NEEDS_VERIFICATION, ReportAudio::STATUS_FAILED], true),
            'error' => $result['status'] === ReportAudio::STATUS_FAILED ? $result['confidence_note'] : null,
            'transcribed_at' => now(),
        ]);

        return $audio;
    }

    /**
     * Staff verification of an uncertain or failed voice recording.
     */
    public function verify(Report $report, string $verifiedDescription, ?string $note = null, ?User $user = null): void
    {
        $audio = $report->audio;

        if ($audio) {
            $audio->update([
                'verified_by' => $user?->id ?? auth()->id(),
                'verified_at' => now(),
                'unverified_draft' => false,
            ]);
        }

        $oldDesc = $report->description;
        $report->update([
            'description' => $verifiedDescription,
            'staff_description' => $verifiedDescription,
        ]);

        $this->auditLogger->log(
            $user ?? auth()->user(),
            'voice.audio_verified',
            $report,
            ['description' => $oldDesc, 'audio_id' => $audio?->id],
            ['description' => $verifiedDescription, 'note' => $note, 'verified_at' => now()->toIso8601String()]
        );
    }

    /**
     * Purge expired audio files from private disk.
     * Unattached audio keeps existing expiry (24h orphaned or past expires_at).
     * Attached audio retention (default 90 days) is counted from report closure.
     */
    public function purgeExpired(): int
    {
        $retentionDays = (int) (Setting::value('voice.audio_retention_days') ?? config('voice.audio_retention_days', 90));
        $closureCutoff = now()->subDays($retentionDays);

        // 1. Unattached audio past expires_at
        $expiredUnattached = ReportAudio::query()
            ->whereNull('report_id')
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->where('disk_path', '!=', '[purged]')
            ->get();

        // 2. Orphaned unattached audio (> 24 hours)
        $orphaned = ReportAudio::query()
            ->whereNull('report_id')
            ->where('created_at', '<=', now()->subHours(24))
            ->where('disk_path', '!=', '[purged]')
            ->get();

        // 3. Attached audio where parent report is closed and closure date is past retention period
        $expiredAttached = ReportAudio::query()
            ->whereNotNull('report_id')
            ->where('disk_path', '!=', '[purged]')
            ->whereHas('report', function ($query) use ($closureCutoff) {
                $query->whereIn('status', Report::CLOSED_STATUSES)
                    ->where(function ($sub) use ($closureCutoff) {
                        $sub->where('resolved_at', '<=', $closureCutoff)
                            ->orWhere(function ($s2) use ($closureCutoff) {
                                $s2->whereNull('resolved_at')
                                    ->where('updated_at', '<=', $closureCutoff);
                            });
                    });
            })
            ->get();

        $allToPurge = $expiredUnattached->concat($orphaned)->concat($expiredAttached)->unique('id');
        $purgedCount = 0;

        foreach ($allToPurge as $item) {
            if ($item->disk_path && $item->disk_path !== '[purged]' && Storage::disk('private')->exists($item->disk_path)) {
                Storage::disk('private')->delete($item->disk_path);
            }
            $item->update(['disk_path' => '[purged]']);
            $purgedCount++;
        }

        return $purgedCount;
    }
}
