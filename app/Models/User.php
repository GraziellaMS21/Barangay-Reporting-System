<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
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
        return $this->role === 'admin';
    }

    public function isStaff(): bool
    {
        return $this->role === 'staff' || $this->role === 'personnel' || $this->role === 'admin';
    }

    public function isPersonnel(): bool
    {
        return $this->role === 'personnel' || $this->role === 'staff' || $this->personnel()->exists();
    }

    public function isAgency(): bool
    {
        return $this->role === 'agency';
    }

    public function isSecretary(): bool
    {
        if ($this->relationLoaded('personnel') && $this->personnel?->position?->code === 'barangay_secretary') {
            return true;
        }

        if ($this->personnel()->whereHas('position', fn ($q) => $q->where('code', 'barangay_secretary'))->exists()) {
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

    public function office(): BelongsTo
    {
        return $this->belongsTo(Office::class);
    }

    public function agencyRegistration(): HasOne
    {
        return $this->hasOne(AgencyRegistration::class);
    }
}
