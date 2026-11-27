<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Purok extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'boundary_geometry', 'is_active'];

    protected function casts(): array
    {
        return [
            'boundary_geometry' => 'array',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Purok $purok): void {
            if (! is_string($purok->code) || trim($purok->code) === '') {
                $base = Str::slug($purok->name) ?: 'purok-'.Str::lower(Str::random(6));
                $code = $base;
                $suffix = 2;
                while (static::query()->where('code', $code)->exists()) {
                    $code = $base.'-'.$suffix++;
                }
                $purok->code = $code;
            }
        });
    }

    public static function allowsMockPuroks(): bool
    {
        return app()->environment('local', 'testing');
    }

    /**
     * Exclude development-only mock-* Purok records outside local and testing environments.
     */
    public function scopeVisibleInCurrentEnvironment(Builder $query): Builder
    {
        if (self::allowsMockPuroks()) {
            return $query;
        }

        return $query->where(function (Builder $q): void {
            $q->whereNull('code')
                ->orWhere('code', 'not like', 'mock-%');
        });
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
