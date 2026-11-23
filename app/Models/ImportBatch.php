<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ImportBatch extends Model
{
    use HasFactory;

    public const STATUS_UPLOADED = 'uploaded';

    public const STATUS_MAPPED = 'mapped';

    public const STATUS_PREVIEWED = 'previewed';

    public const STATUS_COMMITTED = 'committed';

    public const STATUS_ROLLED_BACK = 'rolled_back';

    protected $table = 'import_batches';

    protected $fillable = [
        'user_id',
        'filename',
        'stored_path',
        'target_fiscal_year',
        'source_name',
        'status',
        'headers',
        'mapping',
        'stats',
        'committed_at',
        'rolled_back_at',
        'rolled_back_by',
        'rollback_note',
    ];

    protected function casts(): array
    {
        return [
            'target_fiscal_year' => 'integer',
            'headers' => 'array',
            'mapping' => 'array',
            'stats' => 'array',
            'committed_at' => 'datetime',
            'rolled_back_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function rolledBackBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rolled_back_by');
    }

    public function rows(): HasMany
    {
        return $this->hasMany(ImportRow::class, 'import_batch_id')->orderBy('row_number');
    }

    public function cases(): HasMany
    {
        return $this->hasMany(BarangayCase::class, 'import_batch_id');
    }
}
