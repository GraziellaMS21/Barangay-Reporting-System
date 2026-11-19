<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    public const TYPES = [
        'internal' => 'Within barangay (Internal office)',
        'external' => 'Beyond barangay (External agency)',
    ];

    protected $fillable = [
        'name',
        'type',
        'description',
        'is_internal',
        'is_external',
        'contact',
        'contact_person',
        'contact_email',
        'contact_phone',
        'address',
        'default_assignee_id',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'is_external' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Office $office): void {
            if ($office->is_active === null) {
                $office->is_active = true;
            }

            if ($office->type === 'internal') {
                $office->is_internal = true;
                $office->is_external = false;
            } elseif ($office->type === 'external') {
                $office->is_internal = false;
                $office->is_external = true;
                $office->default_assignee_id = null;
            } else {
                // Legacy type strings (e.g. 'barangay committee', 'city', 'utility')
                if ($office->isDirty('is_external') && ! $office->isDirty('is_internal')) {
                    $office->is_internal = ! $office->is_external;
                } else {
                    $office->is_internal = $office->is_internal ?? true;
                    $office->is_external = ! $office->is_internal;
                }
            }
        });
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('is_internal', true)->where('is_external', false);
    }

    public function scopeExternal(Builder $query): Builder
    {
        return $query->where(fn (Builder $q) => $q->where('is_external', true)->orWhere('is_internal', false));
    }

    public function directoryType(): string
    {
        return ($this->is_external || ! $this->is_internal || $this->type === 'external')
            ? 'external'
            : 'internal';
    }

    public function defaultAssignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'default_assignee_id');
    }

    public function routingRules(): HasMany
    {
        return $this->hasMany(RoutingRule::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class, 'default_office_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(Report::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(Referral::class);
    }
}
