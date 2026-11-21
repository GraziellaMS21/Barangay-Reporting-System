<?php

namespace App\Models;

use Database\Factories\CaseNoteFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseNote extends Model
{
    /** @use HasFactory<CaseNoteFactory> */
    use HasFactory;

    protected $fillable = [
        'case_id',
        'user_id',
        'body',
    ];

    public function case(): BelongsTo
    {
        return $this->belongsTo(BarangayCase::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
