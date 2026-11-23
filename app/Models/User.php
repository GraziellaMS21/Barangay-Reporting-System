<?php

namespace App\Models;

use App\Support\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasOneThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'is_active',
        'office_id',
        'must_change_password',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'must_change_password' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::ADMIN;
    }

    public function isStaff(): bool
    {
        return $this->role === UserRole::STAFF || $this->role === UserRole::ADMIN;
    }

    public function isPersonnel(): bool
    {
        return $this->role === UserRole::STAFF || $this->personnel()->exists();
    }

    public function isAgency(): bool
    {
        return $this->role === UserRole::AGENCY;
    }

    public function hasPosition(string $code): bool
    {
        if ($this->relationLoaded('personnel') && $this->personnel?->relationLoaded('position')) {
            return $this->personnel?->position?->code === $code;
        }

        if ($this->relationLoaded('position') && $this->position !== null) {
            return $this->position->code === $code;
        }

        return $this->personnel()->whereHas('position', fn ($q) => $q->where('code', $code))->exists();
    }

    public function isSecretary(): bool
    {
        if ($this->hasPosition('barangay_secretary')) {
            return true;
        }

        $email = strtolower($this->email ?? '');
        $name = strtolower($this->name ?? '');

        return str_contains($email, 'secretary')
            || str_contains($email, 'staff@calarian.gov.ph')
            || str_contains($name, '(sec')
            || str_contains($name, 'secretary');
    }

    public function hasPermission(string $slug): bool
    {
        if ($this->isAdmin()) {
            return true;
        }

        if ($this->relationLoaded('permissions')) {
            return $this->permissions->contains('slug', $slug);
        }

        return $this->permissions()->where('slug', $slug)->exists();
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'permission_user');
    }

    public function personnel(): HasOne
    {
        return $this->hasOne(Personnel::class);
    }

    public function position(): HasOneThrough
    {
        return $this->hasOneThrough(
            Position::class,
            Personnel::class,
            'user_id',
            'id',
            'id',
            'position_id'
        );
    }

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function agencyRegistration(): HasOne
    {
        return $this->hasOne(AgencyRegistration::class);
    }
}
