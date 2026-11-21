<?php

namespace App\Models;

use Database\Factories\AgencyRegistrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgencyRegistration extends Model
{
    /** @use HasFactory<AgencyRegistrationFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'office_id',
        'agency_name_claimed',
        'position_title',
        'contact_number',
        'id_document_path',
        'status',
        'is_office_admin',
        'reviewed_by',
        'reviewed_at',
        'decision_note',
    ];

    protected function casts(): array
    {
        return [
            'is_office_admin' => 'boolean',
            'reviewed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
