<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ComplaintIntake extends Model
{
    use HasFactory;

    protected $fillable = [
        'complainant_name',
        'complainant_alias',
        'complainant_age',
        'complainant_contact',
        'complainant_address',
        'complainant_residency_claim',
        'respondent_name',
        'respondent_alias',
        'respondent_address',
        'respondent_contact',
        'what_happened',
        'who_involved',
        'when_happened',
        'where_happened',
        'why_how_happened',
        'rights_violated',
        'relief_requested',
        'case_type_suggestion',
        'confidentiality_screen_passed',
        'consent_at',
        'status',
        'reviewed_by',
        'reviewed_at',
        'outcome_reason',
        'case_id',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'complainant_age' => 'integer',
            'when_happened' => 'datetime',
            'confidentiality_screen_passed' => 'boolean',
            'consent_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'submitted_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(BarangayCase::class, 'case_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
