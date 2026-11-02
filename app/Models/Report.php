<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Report extends Model
{
    use HasFactory;

    public const STATUSES = [
        'submitted' => 'Submitted',
        'under_review' => 'Under Review',
        'routed' => 'Routed',
        'in_progress' => 'In Progress',
        'resolved' => 'Resolved',
        'referred' => 'Referred',
        'merged' => 'Merged',
        'out_of_scope' => 'Out of Scope',
        'emergency_flagged' => 'Emergency Flagged',
    ];

    public const MANUAL_STATUSES = [
        'under_review',
        'routed',
        'in_progress',
        'resolved',
        'out_of_scope',
    ];

    public const CLOSED_STATUSES = [
        'resolved',
        'out_of_scope',
        'merged',
        'referred',
    ];

    protected $fillable = [
        'tracking_code',
        'description',
        'photo_path',
        'lat',
        'lng',
        'purok_id',
        'landmark',
        'reporter_email',
        'status',
    ];

    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', self::CLOSED_STATUSES);
    }

    public function purok()
    {
        return $this->belongsTo(Purok::class);
    }
    public function statusLogs()
    {
        return $this->hasMany(StatusLog::class)->oldest();
    }

    public static function generateTrackingCode(): string
    {
        do {
            $code = 'BRG-' . strtoupper(Str::random(6));
        } while (static::where('tracking_code', $code)->exists());

        return $code;
    }
}
