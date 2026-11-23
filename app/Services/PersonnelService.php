<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PersonnelService
{
    public function __construct(
        protected AuditLogger $audit,
        protected PermissionService $permissionService,
    ) {}

    public function create(array $data): Personnel
    {
        return DB::transaction(function () use ($data): Personnel {
            $userId = $data['user_id'] ?? null;
            $position = isset($data['position_id']) ? Position::find($data['position_id']) : null;

            if (($data['create_login'] ?? false) && empty($userId) && ! empty($data['email'])) {
                $password = $data['password'] ?? Str::password(12);
                $role = $data['role'] ?? $this->defaultRoleForPosition($position);

                $user = User::create([
                    'name' => $data['full_name'],
                    'email' => $data['email'],
                    'password' => Hash::make($password),
                    'role' => $role,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'must_change_password' => (bool) ($data['must_change_password'] ?? true),
                ]);

                if ($role === 'staff') {
                    $preset = $data['preset'] ?? $this->defaultPresetForPosition($position);
                    try {
                        $this->permissionService->applyPreset($user, $preset);
                    } catch (\Throwable) {
                        // Ignore if permissions table is not yet seeded
                    }
                }

                $userId = $user->id;
            }

            $signaturePath = $data['signature_path'] ?? null;
            if (($data['signature'] ?? null) instanceof UploadedFile) {
                $signaturePath = $this->storeSignature($data['signature']);
            }

            $personnel = Personnel::create([
                'user_id' => $userId,
                'position_id' => $data['position_id'],
                'full_name' => $data['full_name'],
                'contact_number' => $data['contact_number'] ?? null,
                'email' => $data['email'] ?? null,
                'term_start' => $data['term_start'] ?? now()->toDateString(),
                'term_end' => $data['term_end'] ?? null,
                'is_signatory' => (bool) ($data['is_signatory'] ?? ($position?->is_signatory_default ?? false)),
                'signature_path' => $signaturePath,
                'is_active' => (bool) ($data['is_active'] ?? true),
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->log(auth()->user(), 'personnel.created', $personnel, [], $personnel->toArray());

            return $personnel;
        });
    }

    public function update(Personnel $personnel, array $data, ?User $by = null): Personnel
    {
        return DB::transaction(function () use ($personnel, $data, $by): Personnel {
            $old = $personnel->toArray();
            $position = isset($data['position_id']) ? Position::find($data['position_id']) : $personnel->position;

            $userId = array_key_exists('user_id', $data) ? $data['user_id'] : $personnel->user_id;
            if (($data['create_login'] ?? false) && empty($userId) && ! empty($data['email'] ?? $personnel->email)) {
                $email = $data['email'] ?? $personnel->email;
                $password = $data['password'] ?? Str::password(12);
                $role = $data['role'] ?? $this->defaultRoleForPosition($position);

                $user = User::create([
                    'name' => $data['full_name'] ?? $personnel->full_name,
                    'email' => $email,
                    'password' => Hash::make($password),
                    'role' => $role,
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'must_change_password' => true,
                ]);

                if ($role === 'staff') {
                    $preset = $data['preset'] ?? $this->defaultPresetForPosition($position);
                    try {
                        $this->permissionService->applyPreset($user, $preset);
                    } catch (\Throwable) {
                        // Ignore if permissions table is not yet seeded
                    }
                }

                $userId = $user->id;
            }

            $signaturePath = $personnel->signature_path;
            if (! empty($data['remove_signature']) && $signaturePath) {
                $this->deleteSignature($signaturePath);
                $signaturePath = null;
            }
            if (($data['signature'] ?? null) instanceof UploadedFile) {
                if ($signaturePath) {
                    $this->deleteSignature($signaturePath);
                }
                $signaturePath = $this->storeSignature($data['signature']);
            } elseif (array_key_exists('signature_path', $data)) {
                $signaturePath = $data['signature_path'];
            }

            $personnel->update([
                'user_id' => $userId,
                'position_id' => $data['position_id'] ?? $personnel->position_id,
                'full_name' => $data['full_name'] ?? $personnel->full_name,
                'contact_number' => array_key_exists('contact_number', $data) ? $data['contact_number'] : $personnel->contact_number,
                'email' => array_key_exists('email', $data) ? $data['email'] : $personnel->email,
                'term_start' => array_key_exists('term_start', $data) ? $data['term_start'] : $personnel->term_start?->toDateString(),
                'term_end' => array_key_exists('term_end', $data) ? $data['term_end'] : $personnel->term_end?->toDateString(),
                'is_signatory' => array_key_exists('is_signatory', $data) ? (bool) $data['is_signatory'] : $personnel->is_signatory,
                'signature_path' => $signaturePath,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $personnel->is_active,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : $personnel->notes,
            ]);

            $this->audit->log($by ?? auth()->user(), 'personnel.updated', $personnel, $old, $personnel->fresh()->toArray());

            return $personnel->fresh();
        });
    }

    public function deactivate(Personnel $personnel, CarbonInterface $termEnd, string $note, User $by): Personnel
    {
        return DB::transaction(function () use ($personnel, $termEnd, $note, $by): Personnel {
            $old = [
                'is_active' => $personnel->is_active,
                'term_end' => $personnel->term_end?->toDateString(),
            ];

            $personnel->update([
                'is_active' => false,
                'term_end' => $termEnd->toDateString(),
                'notes' => ($personnel->notes ? $personnel->notes."\n" : '')."Deactivated: {$note}",
            ]);

            $this->audit->log($by, 'personnel.deactivated', $personnel, $old, [
                'is_active' => false,
                'term_end' => $termEnd->toDateString(),
                'note' => $note,
            ]);

            return $personnel->fresh();
        });
    }

    protected function storeSignature(UploadedFile $file): string
    {
        $filename = 'signatures/'.Str::uuid().'.'.($file->extension() ?: 'png');
        Storage::disk('private')->put($filename, $file->getContent());

        return $filename;
    }

    protected function deleteSignature(string $path): void
    {
        if (Storage::disk('private')->exists($path)) {
            Storage::disk('private')->delete($path);
        }
    }

    protected function defaultRoleForPosition(?Position $position): string
    {
        if ($position && in_array($position->code, ['punong_barangay', 'barangay_secretary'], true)) {
            return 'admin';
        }

        return 'staff';
    }

    protected function defaultPresetForPosition(?Position $position): string
    {
        return match ($position?->code) {
            'lupon_member', 'lupon_secretary' => 'case_encoder',
            'kagawad_peace_order', 'vawc_focal_person' => 'case_reviewer',
            default => 'staff_full',
        };
    }
}
