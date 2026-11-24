<?php

namespace App\Models;

use Database\Factories\CaseStatusLogFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CaseStatusLog extends Model
{
    /** @use HasFactory<CaseStatusLogFactory> */
    use HasFactory;

    public $timestamps = false;

    protected static function booted(): void
    {
        static::creating(function (CaseStatusLog $log): void {
            $log->created_at ??= now();
        });
    }

    protected $fillable = [
        'case_id',
        'user_id',
        'from_status',
        'to_status',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(BarangayCase::class, 'case_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
