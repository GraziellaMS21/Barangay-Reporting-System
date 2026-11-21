<?php

namespace App\Services;

use App\Models\Personnel;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PersonnelService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function create(array $data): Personnel
    {
        return DB::transaction(function () use ($data) {
            $userId = $data['user_id'] ?? null;

            if (($data['create_login'] ?? false) && empty($userId)) {
                $password = $data['password'] ?? Str::password(12);
                $user = User::create([
                    'name' => $data['full_name'],
                    'email' => $data['email'],
                    'password' => Hash::make($password),
                    'role' => $data['role'] ?? 'staff',
                    'is_active' => true,
                    'email_verified_at' => now(),
                    'must_change_password' => true,
                ]);
                $userId = $user->id;
            }

            $personnel = Personnel::create([
                'user_id' => $userId,
                'position_id' => $data['position_id'],
                'full_name' => $data['full_name'],
                'contact_number' => $data['contact_number'] ?? null,
                'email' => $data['email'] ?? null,
                'term_start' => $data['term_start'],
                'term_end' => $data['term_end'] ?? null,
                'is_signatory' => (bool) ($data['is_signatory'] ?? false),
                'signature_path' => $data['signature_path'] ?? null,
                'is_active' => true,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->audit->log(auth()->user(), 'personnel.created', $personnel, [], $personnel->toArray());

            return $personnel;
        });
    }

    /**
     * STUB: WS2 completes deactivation logic.
     */
    public function deactivate(Personnel $personnel, CarbonInterface $termEnd, string $note, User $by): Personnel
    {
        $old = ['is_active' => $personnel->is_active, 'term_end' => $personnel->term_end];

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
    }
}
