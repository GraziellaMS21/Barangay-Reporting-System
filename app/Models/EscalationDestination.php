<?php

namespace App\Models;

use App\Models\Concerns\HasConfirmation;
use Database\Factories\EscalationDestinationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EscalationDestination extends Model
{
    /** @use HasFactory<EscalationDestinationFactory> */
    use HasConfirmation, HasFactory;

    protected $fillable = [
        'name',
        'office_id',
        'sort_order',
        'is_active',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'is_confirmed' => 'boolean',
        ];
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function escalations(): HasMany
    {
        return $this->hasMany(CaseEscalation::class);
    }
}
