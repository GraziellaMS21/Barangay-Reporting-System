<?php

namespace App\Models;

use App\Models\Concerns\HasConfirmation;
use Database\Factories\ReportTemplateFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReportTemplate extends Model
{
    /** @use HasFactory<ReportTemplateFactory> */
    use HasConfirmation, HasFactory;

    protected $fillable = [
        'key',
        'title',
        'description',
        'source',
        'columns',
        'filters',
        'orientation',
        'signatory_document_type',
        'is_active',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'columns' => 'array',
            'filters' => 'array',
            'is_active' => 'boolean',
            'is_confirmed' => 'boolean',
        ];
    }

    public function generatedReports(): HasMany
    {
        return $this->hasMany(GeneratedReport::class);
    }
}
