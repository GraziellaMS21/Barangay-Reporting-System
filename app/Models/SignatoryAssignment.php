<?php

namespace App\Models;

use App\Models\Concerns\HasConfirmation;
use Database\Factories\SignatoryAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SignatoryAssignment extends Model
{
    /** @use HasFactory<SignatoryAssignmentFactory> */
    use HasConfirmation, HasFactory;

    protected $fillable = [
        'document_type',
        'role_label',
        'position_id',
        'sort_order',
        'is_confirmed',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_confirmed' => 'boolean',
        ];
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
