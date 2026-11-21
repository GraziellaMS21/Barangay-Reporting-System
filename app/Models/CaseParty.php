<?php

namespace App\Models;

use Database\Factories\CasePartyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CaseParty extends Model
{
    /** @use HasFactory<CasePartyFactory> */
    use HasFactory;

    protected $table = 'case_parties';

    protected $fillable = [
        'case_id',
        'party_id',
        'role',
        'residency_at_filing',
        'representative_name',
        'notes',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(BarangayCase::class, 'case_id');
    }

    public function party(): BelongsTo
    {
        return $this->belongsTo(Party::class, 'party_id');
    }

    public function summons(): HasMany
    {
        return $this->hasMany(Summons::class, 'respondent_case_party_id');
    }
}
