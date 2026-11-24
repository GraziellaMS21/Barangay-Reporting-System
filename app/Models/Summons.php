<?php

namespace App\Models;

use Database\Factories\SummonsFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Summons extends Model
{
    /** @use HasFactory<SummonsFactory> */
    use HasFactory;

    protected $table = 'summons';

    protected $fillable = [
        'case_id',
        'respondent_case_party_id',
        'sequence_no',
        'summons_number',
        'issued_on',
        'issued_by',
        'hearing_at',
        'venue',
        'served_by_personnel_id',
        'served_on',
        'service_result',
        'attendance',
        'remarks',
        'superseded_at',
        'voided_at',
        'void_note',
        'override_note',
    ];

    protected function casts(): array
    {
        return [
            'sequence_no' => 'integer',
            'issued_on' => 'date',
            'hearing_at' => 'datetime',
            'served_on' => 'date',
            'superseded_at' => 'datetime',
            'voided_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(BarangayCase::class, 'case_id');
    }

    public function respondentCaseParty(): BelongsTo
    {
        return $this->belongsTo(CaseParty::class, 'respondent_case_party_id');
    }

    public function issuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function servedBy(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'served_by_personnel_id');
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class, 'summons_id');
    }
}
