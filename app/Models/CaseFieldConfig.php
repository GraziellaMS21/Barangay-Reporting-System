<?php

namespace App\Models;

use App\Models\Concerns\HasConfirmation;
use Database\Factories\CaseFieldConfigFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseFieldConfig extends Model
{
    /** @use HasFactory<CaseFieldConfigFactory> */
    use HasConfirmation, HasFactory;

    protected $fillable = [
        'entity',
        'field_key',
        'label',
        'is_required',
        'is_visible',
        'applies_to',
        'sort_order',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'sort_order' => 'integer',
            'is_confirmed' => 'boolean',
        ];
    }
}
