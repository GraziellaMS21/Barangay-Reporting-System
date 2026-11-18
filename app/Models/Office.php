<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Office extends Model
{
    protected $fillable = ['name', 'type', 'is_internal', 'is_external', 'contact', 'contact_person', 'contact_email', 'contact_phone', 'address'];

    protected function casts(): array
    {
        return [
            'is_internal' => 'boolean',
            'is_external' => 'boolean',
        ];
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
