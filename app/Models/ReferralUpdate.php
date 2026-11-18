<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReferralUpdate extends Model
{
    protected $fillable = [
        'referral_id',
        'user_id',
        'note',
        'channel',
        'occurred_at',
        'photo_path',
        'visible_to_resident',
    ];

    protected function casts(): array
    {
        return [
            'occurred_at' => 'datetime',
            'visible_to_resident' => 'boolean',
        ];
    }

    public function referral(): BelongsTo
    {
        return $this->belongsTo(Referral::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
