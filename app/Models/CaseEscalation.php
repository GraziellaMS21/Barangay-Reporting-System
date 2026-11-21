<?php

namespace App\Models;

use Database\Factories\CaseEscalationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseEscalation extends Model
{
    /** @use HasFactory<CaseEscalationFactory> */
    use HasFactory;

    protected $fillable = [
        'case_id',
        'escalation_destination_id',
        'escalated_by',
        'decided_by_personnel_id',
        'reason',
        'override_note',
        'summons_count_at_time',
        'referral_id',
        'escalated_at',
    ];

    protected function casts(): array
    {
        return [
            'summons_count_at_time' => 'integer',
            'escalated_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(BarangayCase::class, 'case_id');
    }

    public function destination(): BelongsTo
    {
        return $this->belongsTo(EscalationDestination::class, 'escalation_destination_id');
    }

    public function escalatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'escalated_by');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'decided_by_personnel_id');
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class, 'referral_id');
    }
}
