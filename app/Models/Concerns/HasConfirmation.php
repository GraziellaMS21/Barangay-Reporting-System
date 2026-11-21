<?php

namespace App\Models\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait HasConfirmation
{
    public function scopeUnconfirmed(Builder $query): Builder
    {
        return $query->where('is_confirmed', false);
    }

    public function markConfirmed(User $by): bool
    {
        $this->is_confirmed = true;
        if (array_key_exists('confirmed_by', $this->attributes) || $this->isFillable('confirmed_by')) {
            $this->confirmed_by = $by->id;
        }
        if (array_key_exists('confirmed_at', $this->attributes) || $this->isFillable('confirmed_at')) {
            $this->confirmed_at = now();
        }

        return $this->save();
    }
}
