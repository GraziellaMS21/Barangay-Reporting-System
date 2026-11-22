<?php

namespace App\Models;

use App\Models\Concerns\BelongsToFiscalYear;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Referral extends Model
{
    use BelongsToFiscalYear;

    public const STATUSES = [
        'prepared' => 'Prepared',
        'sent' => 'Sent',
        'acknowledged' => 'Acknowledged',
        'action_taken' => 'Action taken',
        'closed' => 'Closed',
    ];

    public const OUTCOMES = [
        'resolved' => 'Resolved',
        'declined' => 'Declined',
        'no_response' => 'No response',
    ];

    public const SENT_VIA = [
        'email' => 'Email',
        'hand_delivered' => 'Hand delivered',
        'phone' => 'Phone',
    ];

    public const CHANNELS = [
        'phone' => 'Phone call',
        'email' => 'Email',
        'site_visit' => 'Site visit',
        'other' => 'Other',
    ];

    /** Statuses an open referral may move to through an agency update. */
    public const UPDATE_TARGET_STATUSES = ['acknowledged', 'action_taken'];

    protected $fillable = [
        'fiscal_year_id',
        'report_id',
        'office_id',
        'reference_no',
        'referral_text',
        'status',
        'sent_at',
        'sent_via',
        'agency_reference_no',
        'follow_up_at',
        'closed_at',
        'outcome',
        'prepared_by',
        'follow_up_owner_id',
    ];

    protected static function booted(): void
    {
        static::creating(function (Referral $referral): void {
            if ($referral->follow_up_owner_id === null && $referral->prepared_by !== null) {
                $referral->follow_up_owner_id = $referral->prepared_by;
            }
        });
    }

    protected function casts(): array
    {
        return [
            'sent_at' => 'datetime',
            'follow_up_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function followUpOwner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follow_up_owner_id');
    }

    public function updates(): HasMany
    {
        return $this->hasMany(ReferralUpdate::class)->latest('occurred_at')->latest('id');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(ReferralAttachment::class);
    }

    public function isOpen(): bool
    {
        return $this->status !== 'closed';
    }

    public function isOverdue(): bool
    {
        return $this->isOpen()
            && $this->follow_up_at !== null
            && $this->follow_up_at->isPast();
    }

    public function scopeNeedsAttention($query)
    {
        return $query->where(function ($query): void {
            $query->where('status', 'prepared')
                ->orWhere(function ($inner): void {
                    $inner->whereNotNull('follow_up_at')
                        ->where('follow_up_at', '<', now())
                        ->whereNotIn('status', ['closed']);
                });
        });
    }
}
