<?php

namespace App\Models;

use App\Models\Concerns\HasConfirmation;
use Database\Factories\CaseTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseType extends Model
{
    /** @use HasFactory<CaseTypeFactory> */
    use HasConfirmation, HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'sort_order',
        'is_active',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_confirmed' => 'boolean',
        ];
    }

    public function cases(): HasMany
    {
        return $this->hasMany(BarangayCase::class);
    }
}
