<?php

namespace App\Models;

use App\Models\Concerns\HasConfirmation;
use Database\Factories\PositionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Position extends Model
{
    /** @use HasFactory<PositionFactory> */
    use HasConfirmation, HasFactory;

    protected $fillable = [
        'name',
        'code',
        'sort_order',
        'is_signatory_default',
        'is_active',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_signatory_default' => 'boolean',
            'is_active' => 'boolean',
            'is_confirmed' => 'boolean',
        ];
    }

    public function personnel(): HasMany
    {
        return $this->hasMany(Personnel::class);
    }

    public function signatoryAssignments(): HasMany
    {
        return $this->hasMany(SignatoryAssignment::class);
    }
}
