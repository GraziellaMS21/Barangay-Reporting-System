<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReportAudio extends Model
{
    use HasFactory;

    protected $table = 'report_audios';

    public const ORIGIN_RESIDENT = 'resident';

    public const ORIGIN_STAFF = 'staff';

    public const STATUS_PENDING = 'pending';

    public const STATUS_DONE = 'done';

    public const STATUS_NEEDS_VERIFICATION = 'needs_verification';

    public const STATUS_FAILED = 'failed';

    public const STATUS_MOCK = 'mock';

    protected $fillable = [
        'report_id',
        'token',
        'origin',
        'disk_path',
        'mime',
        'size_bytes',
        'duration_seconds',
        'transcript',
        'unverified_draft',
        'transcript_language',
        'transcript_status',
        'error',
        'transcribed_at',
        'extracted_data',
        'ai_description',
        'provenance',
        'verified_by',
        'verified_at',
        'uploaded_by',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'unverified_draft' => 'boolean',
            'size_bytes' => 'integer',
            'duration_seconds' => 'integer',
            'transcribed_at' => 'datetime',
            'extracted_data' => 'array',
            'provenance' => 'array',
            'verified_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->whereIn('transcript_status', [self::STATUS_NEEDS_VERIFICATION, self::STATUS_FAILED])
            ->whereNull('verified_at');
    }

    public function scopeExpired(Builder $query): Builder
    {
        return $query->whereNotNull('expires_at')
            ->where('expires_at', '<=', now());
    }

    public function scopeOrphaned(Builder $query): Builder
    {
        return $query->whereNull('report_id')
            ->where('created_at', '<=', now()->subHours(24));
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function needsVerification(): bool
    {
        return in_array($this->transcript_status, [self::STATUS_NEEDS_VERIFICATION, self::STATUS_FAILED], true)
            && $this->verified_at === null;
    }
}
