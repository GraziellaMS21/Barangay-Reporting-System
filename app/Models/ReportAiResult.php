<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAiResult extends Model
{
    protected $fillable = [
        'report_id',
        'suggested_category',
        'suggested_urgency',
        'summary',
        'photo_description',
        'photo_mismatch',
        'emergency_flag',
        'location_details',
        'key_facts',
        'confidence',
        'raw_response',
        'model',
        'error',
        'confirmed_by',
        'confirmed_at',
        'confirmation_note',
    ];

    protected function casts(): array
    {
        return [
            'photo_mismatch' => 'boolean',
            'emergency_flag' => 'boolean',
            'key_facts' => 'array',
            'confidence' => 'float',
            'raw_response' => 'array',
            'confirmed_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}
