<?php

namespace App\Models;

use Database\Factories\PartyFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Party extends Model
{
    /** @use HasFactory<PartyFactory> */
    use HasFactory;

    protected $fillable = [
        'full_name',
        'alias',
        'birth_date',
        'sex',
        'civil_status',
        'occupation',
        'contact_number',
        'email',
        'address_line',
        'purok_id',
        'home_barangay',
        'home_city',
        'residency_status',
        'residency_basis',
        'residency_verified_by',
        'residency_verified_at',
        'id_type',
        'id_number',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
            'residency_verified_at' => 'datetime',
            'extra' => 'array',
        ];
    }

    public function purok(): BelongsTo
    {
        return $this->belongsTo(Purok::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'residency_verified_by');
    }

    public function caseParties(): HasMany
    {
        return $this->hasMany(CaseParty::class);
    }

    public function cases(): BelongsToMany
    {
        return $this->belongsToMany(BarangayCase::class, 'case_parties', 'party_id', 'case_id')
            ->withPivot(['role', 'residency_at_filing', 'representative_name', 'notes'])
            ->withTimestamps();
    }
}
