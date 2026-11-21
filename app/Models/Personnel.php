<?php

namespace App\Models;

use Database\Factories\PersonnelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personnel extends Model
{
    /** @use HasFactory<PersonnelFactory> */
    use HasFactory;

    protected $table = 'personnel';

    protected $fillable = [
        'user_id',
        'position_id',
        'full_name',
        'contact_number',
        'email',
        'term_start',
        'term_end',
        'is_signatory',
        'signature_path',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'term_start' => 'date',
            'term_end' => 'date',
            'is_signatory' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function position(): BelongsTo
    {
        return $this->belongsTo(Position::class);
    }
}
