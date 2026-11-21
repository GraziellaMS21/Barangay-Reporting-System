<?php

namespace App\Models;

use Database\Factories\HearingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Hearing extends Model
{
    /** @use HasFactory<HearingFactory> */
    use HasFactory;

    protected $fillable = [
        'case_id',
        'summons_id',
        'scheduled_at',
        'venue',
        'presided_by_personnel_id',
        'status',
        'outcome_note',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(BarangayCase::class, 'case_id');
    }

    public function summons(): BelongsTo
    {
        return $this->belongsTo(Summons::class, 'summons_id');
    }

    public function presidedBy(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'presided_by_personnel_id');
    }
}
