<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFiscalYear;
use Database\Factories\BarangayCaseFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BarangayCase extends Model
{
    /** @use HasFactory<BarangayCaseFactory> */
    use BelongsToFiscalYear, HasFactory;

    protected $table = 'cases';

    protected $fillable = [
        'case_number',
        'fiscal_year_id',
        'case_type_id',
        'record_type',
        'source',
        'source_report_id',
        'title',
        'narrative',
        'filed_on',
        'first_meeting_on',
        'complaint_started_on',
        'endorsed_to_pangkat_on',
        'conciliation_extension_days',
        'incident_date',
        'incident_location',
        'purok_id',
        'status',
        'encoded_by',
        'assigned_to',
        'pangkat_chairman_personnel_id',
        'settled_on',
        'settlement_terms',
        'settlement_amount',
        'settlement_agreement_path',
        'outcome_note',
        'is_confidential',
        'eligibility_checklist',
    ];

    protected function casts(): array
    {
        return [
            'filed_on' => 'date',
            'first_meeting_on' => 'date',
            'complaint_started_on' => 'date',
            'endorsed_to_pangkat_on' => 'date',
            'incident_date' => 'date',
            'settled_on' => 'date',
            'settlement_amount' => 'decimal:2',
            'is_confidential' => 'boolean',
            'eligibility_checklist' => 'array',
        ];
    }

    public function caseType(): BelongsTo
    {
        return $this->belongsTo(CaseType::class);
    }

    public function sourceReport(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'source_report_id');
    }

    public function purok(): BelongsTo
    {
        return $this->belongsTo(Purok::class);
    }

    public function encodedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'encoded_by');
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function pangkatChairman(): BelongsTo
    {
        return $this->belongsTo(Personnel::class, 'pangkat_chairman_personnel_id');
    }

    public function caseParties(): HasMany
    {
        return $this->hasMany(CaseParty::class, 'case_id');
    }

    public function parties(): BelongsToMany
    {
        return $this->belongsToMany(Party::class, 'case_parties', 'case_id', 'party_id')
            ->withPivot(['role', 'residency_at_filing', 'representative_name', 'notes'])
            ->withTimestamps();
    }

    public function complainants(): BelongsToMany
    {
        return $this->parties()->wherePivot('role', 'complainant');
    }

    public function respondents(): BelongsToMany
    {
        return $this->parties()->wherePivot('role', 'respondent');
    }

    public function witnesses(): BelongsToMany
    {
        return $this->parties()->wherePivot('role', 'witness');
    }

    public function summons(): HasMany
    {
        return $this->hasMany(Summons::class, 'case_id');
    }

    public function activeSummons(): HasMany
    {
        return $this->summons()->whereNull('voided_at');
    }

    public function hearings(): HasMany
    {
        return $this->hasMany(Hearing::class, 'case_id');
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(CaseEscalation::class, 'case_id');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(CaseStatusLog::class, 'case_id');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(CaseNote::class, 'case_id');
    }

    /**
     * Scope visible cases according to SPEC_ADDENDUM.md section 7:
     * - Admin: see everything
     * - Others: see all non-confidential cases, and confidential cases where the user has
     *   cases.view_confidential permission, or is the encoder, or is the assignee.
     */
    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isAdmin()) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($user) {
            $q->where('is_confidential', false);

            if ($user->hasPermission('cases.view_confidential')) {
                $q->orWhere('is_confidential', true);
            } else {
                $q->orWhere(function (Builder $sub) use ($user) {
                    $sub->where('is_confidential', true)
                        ->where(function (Builder $owner) use ($user) {
                            $owner->where('encoded_by', $user->id)
                                ->orWhere('assigned_to', $user->id);
                        });
                });
            }
        });
    }
}
