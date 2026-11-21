<?php

namespace App\Models;

use App\Models\Concerns\HasConfirmation;
use Database\Factories\SettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Setting extends Model
{
    /** @use HasFactory<SettingFactory> */
    use HasConfirmation, HasFactory;

    protected $fillable = [
        'key',
        'type',
        'group',
        'label',
        'description',
        'value',
        'is_confirmed',
        'confirmed_by',
        'confirmed_at',
        'source_question',
    ];

    protected function casts(): array
    {
        return [
            'is_confirmed' => 'boolean',
            'confirmed_at' => 'datetime',
        ];
    }

    public function confirmedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }

    public static function value(string $key, mixed $default = null): mixed
    {
        $value = static::query()->where('key', $key)->value('value');

        return $value !== null ? $value : $default;
    }
}
