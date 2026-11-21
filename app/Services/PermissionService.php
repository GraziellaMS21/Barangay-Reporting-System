<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;

class PermissionService
{
    /**
     * @return Collection<int, Permission>
     */
    public function all(): Collection
    {
        return Permission::all();
    }

    /**
     * @return Collection<int, Permission>
     */
    public function forUser(User $user): Collection
    {
        return $user->permissions;
    }

    /**
     * @param  array<int, string>  $slugs
     */
    public function sync(User $user, array $slugs): void
    {
        $permissionIds = Permission::whereIn('slug', $slugs)->pluck('id');
        $user->permissions()->sync($permissionIds);
    }

    public function applyPreset(User $user, string $preset): void
    {
        $presets = config('permission_presets.presets', []);
        if (! isset($presets[$preset])) {
            throw new InvalidArgumentException("Unknown permission preset: {$preset}");
        }

        $slugs = $presets[$preset]['permissions'] ?? [];
        $this->sync($user, $slugs);
    }
}
