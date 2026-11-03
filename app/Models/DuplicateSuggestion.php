<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DuplicateSuggestion extends Model
{
    protected $fillable = ['report_id', 'candidate_id', 'score', 'decision', 'decided_by'];

    protected function casts(): array
    {
        return [
            'score' => 'float',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }

    public function candidate(): BelongsTo
    {
        return $this->belongsTo(Report::class, 'candidate_id');
    }

    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
