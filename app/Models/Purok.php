<?php

namespace App\Models;

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
                $purok->code = Str::slug($purok->name) ?: 'purok-'.Str::lower(Str::random(6));
            }
        });
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
