<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class SettingsService
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever("setting.{$key}", function () use ($key, $default) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting) {
                return $default;
            }

            return $this->castValue($setting->value, $setting->type);
        });
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);

        return (int) $value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function set(string $key, mixed $value, ?User $by = null): void
    {
        $setting = Setting::where('key', $key)->first();
        $oldValue = $setting?->value;
        $storedValue = is_array($value) ? json_encode($value) : (string) $value;

        if ($setting) {
            $setting->update(['value' => $storedValue]);
        } else {
            $setting = Setting::create([
                'key' => $key,
                'value' => $storedValue,
                'type' => is_bool($value) ? 'boolean' : (is_int($value) ? 'integer' : (is_array($value) ? 'json' : 'string')),
            ]);
        }

        Cache::forget("setting.{$key}");

        $this->audit->log($by, 'setting.updated', $setting, ['value' => $oldValue], ['value' => $storedValue]);
    }

    public function confirm(string $key, User $by): void
    {
        $setting = Setting::where('key', $key)->first();
        if ($setting) {
            $setting->update([
                'is_confirmed' => true,
                'confirmed_by' => $by->id,
                'confirmed_at' => now(),
            ]);

            Cache::forget("setting.{$key}");

            $this->audit->log($by, 'setting.confirmed', $setting, ['is_confirmed' => false], ['is_confirmed' => true]);
        }
    }

    public function isConfirmed(string $key): bool
    {
        return (bool) Setting::where('key', $key)->where('is_confirmed', true)->exists();
    }

    /**
     * @return Collection<int, Setting>
     */
    public function unconfirmed(): Collection
    {
        return Setting::unconfirmed()->get();
    }

    protected function castValue(?string $value, ?string $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            'boolean', 'bool' => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            'integer', 'int' => (int) $value,
            'json', 'array' => json_decode($value, true),
            default => $value,
        };
    }
}
