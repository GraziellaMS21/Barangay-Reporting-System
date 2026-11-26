<?php

namespace App\Services;

use App\Models\Report;
use App\Models\ReportProofPhoto;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Work/Resolve proof photos: multiple images per report with preview/removal
 * on the client and validated, logged storage on the server.
 */
class ProofPhotoService
{
    /** @return array{stored: int, skipped: list<string>, paths: list<string>} */
    public function storeMany(Report $report, array $files, User $user): array
    {
        $stored = 0;
        $skipped = [];
        $paths = [];

        DB::transaction(function () use ($report, $files, $user, &$stored, &$skipped, &$paths): void {
            foreach ($files as $file) {
                if (! $file || ! $file->isValid()) {
                    $skipped[] = 'An unreadable file was skipped.';

                    continue;
                }

                $path = $file->store('proof_photos', 'public');
                $paths[] = $path;
                $report->proofPhotos()->create([
                    'path' => $path,
                    'uploaded_by' => $user->id,
                ]);
                $stored++;
            }

            if ($stored > 0) {
                $report->statusLogs()->create([
                    'user_id' => $user->id,
                    'from_status' => $report->status,
                    'to_status' => $report->status,
                    'note' => 'Proof photo(s) uploaded ('.$stored.').',
                ]);
            }
        });

        return ['stored' => $stored, 'skipped' => $skipped, 'paths' => $paths];
    }

    public function delete(Report $report, ReportProofPhoto $proof, User $user): void
    {
        abort_unless($proof->report_id === $report->id, 404);

        DB::transaction(function () use ($report, $proof, $user): void {
            $this->deleteFileQuietly($proof->path);
            $proof->delete();

            $report->statusLogs()->create([
                'user_id' => $user->id,
                'from_status' => $report->status,
                'to_status' => $report->status,
                'note' => 'Proof photo removed.',
            ]);
        });
    }

    private function deleteFileQuietly(string $path): void
    {
        try {
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);

                return;
            }
            if (Storage::disk('local')->exists($path)) {
                Storage::disk('local')->delete($path);
            }
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    /** Serve the stored image, whether it came from the public or local disk (legacy rows). */
    public function response(ReportProofPhoto $proof)
    {
        if (Storage::disk('public')->exists($proof->path)) {
            return Storage::disk('public')->response($proof->path);
        }

        abort_unless(Storage::disk('local')->exists($proof->path), 404);

        return Storage::disk('local')->response($proof->path);
    }

    /** Validate a single pending upload against the shared config values. */
    public static function validateReason(object $file, int $maxSizeKb, string $mimes): ?string
    {
        if (! $file->isValid()) {
            return 'The file could not be read.';
        }

        $extension = strtolower($file->getClientOriginalExtension() ?: (string) $file->extension());
        $allowed = explode(',', $mimes);
        if (! in_array($extension, $allowed, true)) {
            return Str::of($file->getClientOriginalName())->limit(40).' is not a JPG, PNG, or WebP image.';
        }

        if ($file->getSize() > $maxSizeKb * 1024) {
            return Str::of($file->getClientOriginalName())->limit(40).' is larger than '.round($maxSizeKb / 1024).' MB.';
        }

        return null;
    }
}
