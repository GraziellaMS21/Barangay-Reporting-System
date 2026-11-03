<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\EmergencyContact;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class EmergencyContactService
{
    private const AUDITED_FIELDS = ['name', 'number', 'type', 'is_active', 'sort_order'];

    /**
     * @param  array{name: string, number: string, type: string, is_active: bool, sort_order: int}  $attributes
     */
    public function create(array $attributes, User $actor): EmergencyContact
    {
        return DB::transaction(function () use ($attributes, $actor): EmergencyContact {
            $contact = EmergencyContact::create($attributes);

            $this->recordAudit($contact, $actor, 'created', null, $contact->only(self::AUDITED_FIELDS));

            return $contact;
        });
    }

    /**
     * @param  array{name: string, number: string, type: string, is_active: bool, sort_order: int}  $attributes
     */
    public function update(EmergencyContact $contact, array $attributes, User $actor): EmergencyContact
    {
        return DB::transaction(function () use ($contact, $attributes, $actor): EmergencyContact {
            $oldValues = $contact->only(self::AUDITED_FIELDS);
            $contact->update($attributes);

            $this->recordAudit($contact, $actor, 'updated', $oldValues, $contact->only(self::AUDITED_FIELDS));

            return $contact;
        });
    }

    public function delete(EmergencyContact $contact, User $actor): void
    {
        DB::transaction(function () use ($contact, $actor): void {
            $oldValues = $contact->only(self::AUDITED_FIELDS);
            $contact->delete();

            $this->recordAudit($contact, $actor, 'deleted', $oldValues, null);
        });
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function recordAudit(
        EmergencyContact $contact,
        User $actor,
        string $action,
        ?array $oldValues,
        ?array $newValues
    ): void {
        AuditLog::create([
            'user_id' => $actor->id,
            'action' => $action,
            'model' => EmergencyContact::class,
            'model_id' => $contact->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
