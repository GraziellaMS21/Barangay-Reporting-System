<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserManagementService
{
    private const AUDITED_FIELDS = ['name', 'email', 'role', 'is_active'];

    public function __construct(private SettingsManagementService $settingsManagementService) {}

    /**
     * @param  array{name: string, email: string, password: string, role: string, is_active: bool}  $attributes
     */
    public function create(array $attributes, User $actor): User
    {
        $user = new User;
        $this->settingsManagementService->save($user, $attributes, $actor, self::AUDITED_FIELDS);

        return $user;
    }

    /**
     * @param  array{name: string, email: string, password?: string, role: string, is_active: bool}  $attributes
     */
    public function update(User $user, array $attributes, User $actor): User
    {
        return DB::transaction(function () use ($user, $attributes, $actor): User {
            $managedUser = User::query()->lockForUpdate()->findOrFail($user->id);
            $isChangingOwnAccess = $managedUser->id === $actor->id
                && ($attributes['role'] !== $managedUser->role || ! $attributes['is_active']);

            if ($isChangingOwnAccess) {
                throw ValidationException::withMessages([
                    'role' => 'You cannot change your own role or deactivate your own account.',
                ]);
            }

            $isRemovingAdminAccess = $managedUser->isAdmin()
                && ($attributes['role'] !== 'admin' || ! $attributes['is_active']);

            if ($isRemovingAdminAccess) {
                $activeAdmins = User::query()
                    ->where('role', 'admin')
                    ->where('is_active', true)
                    ->lockForUpdate()
                    ->get(['id']);

                if ($activeAdmins->count() <= 1) {
                    throw ValidationException::withMessages([
                        'role' => 'At least one active administrator must remain.',
                    ]);
                }
            }

            $this->settingsManagementService->save(
                $managedUser,
                $attributes,
                $actor,
                self::AUDITED_FIELDS
            );

            return $managedUser;
        });
    }
}
