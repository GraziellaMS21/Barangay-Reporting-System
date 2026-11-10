<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'risk_weight', 'default_office_id', 'is_active', 'question_schema', 'sla_hours'];

    protected function casts(): array
    {
        return [
            'risk_weight' => 'integer',
            'is_active' => 'boolean',
            'question_schema' => 'array',
            'sla_hours' => 'integer',
        ];
    }

    public function defaultOffice(): BelongsTo
    {
        return $this->belongsTo(Office::class, 'default_office_id');
    }

    public function routingRules(): HasMany
    {
        return $this->hasMany(RoutingRule::class);
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }
}
