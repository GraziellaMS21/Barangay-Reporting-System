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
        'sequence_no',
        'scheduled_at',
        'venue',
        'presided_by_personnel_id',
        'status',
        'complainant_attendance',
        'respondent_attendance',
        'respondent_barred_counterclaim',
        'outcome_note',
        'superseded_at',
        'superseded_reason',
        'no_summons_flag',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'scheduled_at' => 'datetime',
            'respondent_barred_counterclaim' => 'boolean',
            'superseded_at' => 'datetime',
            'no_summons_flag' => 'boolean',
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
