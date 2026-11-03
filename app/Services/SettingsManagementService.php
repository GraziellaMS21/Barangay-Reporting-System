<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class SettingsManagementService
{
    /**
     * @param  list<string>  $auditedFields
     */
    public function save(Model $record, array $attributes, User $actor, array $auditedFields): Model
    {
        return DB::transaction(function () use ($record, $attributes, $actor, $auditedFields): Model {
            $isExisting = $record->exists;
            $oldValues = $isExisting ? $record->only($auditedFields) : null;

            $record->fill($attributes);
            $record->save();

            $this->record(
                $actor,
                $isExisting ? 'updated' : 'created',
                $record,
                $oldValues,
                $record->only($auditedFields)
            );

            return $record;
        });
    }

    /**
     * @param  list<string>  $auditedFields
     */
    public function delete(Model $record, User $actor, array $auditedFields): void
    {
        DB::transaction(function () use ($record, $actor, $auditedFields): void {
            $oldValues = $record->only($auditedFields);
            $record->delete();

            $this->record($actor, 'deleted', $record, $oldValues, null);
        });
    }

    /**
     * @param  array<string, int|string>  $values
     */
    public function updateSettings(array $values, User $actor): void
    {
        DB::transaction(function () use ($values, $actor): void {
            foreach ($values as $key => $value) {
                $setting = Setting::query()
                    ->where('key', $key)
                    ->lockForUpdate()
                    ->first() ?? new Setting(['key' => $key]);
                $isExisting = $setting->exists;
                $oldValues = $isExisting
                    ? ['key' => $key, 'value' => $setting->value]
                    : null;

                $setting->value = (string) $value;
                $setting->save();

                $this->record(
                    $actor,
                    $isExisting ? 'updated' : 'created',
                    $setting,
                    $oldValues,
                    ['key' => $key, 'value' => $setting->value]
                );
            }
        });
    }

    /**
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    private function record(
        User $actor,
        string $action,
        Model $record,
        ?array $oldValues,
        ?array $newValues
    ): void {
        AuditLog::query()->create([
            'user_id' => $actor->id,
            'action' => $action,
            'model' => $record::class,
            'model_id' => $record->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
        ]);
    }
}
