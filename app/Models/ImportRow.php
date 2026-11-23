<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ImportRow extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_VALID = 'valid';

    public const STATUS_WARNING = 'warning';

    public const STATUS_ERROR = 'error';

    public const STATUS_IMPORTED = 'imported';

    public const STATUS_SKIPPED = 'skipped';

    protected $table = 'import_rows';

    protected $fillable = [
        'import_batch_id',
        'row_number',
        'raw',
        'mapped',
        'status',
        'errors',
        'case_id',
        'created_party_ids',
    ];

    protected function casts(): array
    {
        return [
            'row_number' => 'integer',
            'raw' => 'array',
            'mapped' => 'array',
            'errors' => 'array',
            'created_party_ids' => 'array',
        ];
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(ImportBatch::class, 'import_batch_id');
    }

    public function case(): BelongsTo
    {
        return $this->belongsTo(BarangayCase::class, 'case_id');
    }
}
