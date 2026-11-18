<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StatusLog extends Model
{
    protected $fillable = [
        'report_id',
        'user_id',
        'office_id',
        'from_status',
        'to_status',
        'update_type',
        'note',
        'expected_completion_date',
        'visible_to_resident',
        'attachments',
    ];

    protected function casts(): array
    {
        return [
            'expected_completion_date' => 'date',
            'visible_to_resident' => 'boolean',
            'attachments' => 'array',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function getUpdateTitleAttribute(): string
    {
        if ($this->update_type && isset(Report::UPDATE_TYPES[$this->update_type])) {
            return Report::UPDATE_TYPES[$this->update_type]['label'];
        }

        return Report::STATUSES[$this->to_status] ?? ucfirst(str_replace('_', ' ', $this->to_status));
    }
}
