<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Feedback extends Model
{
    protected $fillable = ['report_id', 'rating', 'comment'];

    public function report(): BelongsTo
    {
        return $this->belongsTo(Report::class);
    }
}
