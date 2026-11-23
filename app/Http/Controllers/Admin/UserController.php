<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\SaveUserRequest;
use App\Http\Requests\Admin\People\UpdateUserPermissionsRequest;
use App\Models\AgencyRegistration;
use App\Models\Office;
use App\Models\Permission;
use App\Models\Position;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\PermissionService;
use App\Support\UserRole;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        protected PermissionService $permissionService,
        protected AuditLogger $audit,
    ) {}

    public function index(Request $request): View
    {
        $query = User::query()
            ->with(['permissions', 'personnel.position', 'office'])
            ->orderByDesc('is_active')
            ->orderBy('role')
            ->orderBy('name');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query->paginate(25)->withQueryString();
        $totalPermissionsCount = Permission::count();

        return view('admin.users.index', compact('users', 'totalPermissionsCount'));
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['role' => UserRole::STAFF, 'is_active' => true, 'must_change_password' => true]),
            'presets' => config('permission_presets.presets', []),
            'permissionGroups' => Permission::query()->orderBy('group')->orderBy('label')->get()->groupBy('group'),
            'selectedSlugs' => config('permission_presets.presets.staff_full.permissions', []),
            'positions' => Position::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'externalOffices' => Office::query()->where(fn ($q) => $q->where('is_external', true)->orWhere('type', 'external'))->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function store(SaveUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $plainPassword = ! empty($validated['password'])
            ? $validated['password']
            : Str::password(12);

        $user = DB::transaction(function () use ($validated, $plainPassword, $request): User {
            $created = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($plainPassword),
                'role' => $validated['role'],
                'office_id' => $validated['role'] === UserRole::AGENCY ? ($validated['office_id'] ?? null) : null,
                'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
                'email_verified_at' => now(),
                'must_change_password' => $request->has('must_change_password')
                    ? $request->boolean('must_change_password')
                    : true,
            ]);

            if ($created->role === UserRole::STAFF) {
                if (! empty($validated['preset'])) {
                    $this->permissionService->applyPreset($created, $validated['preset']);
                } elseif (isset($validated['permissions'])) {
                    $this->permissionService->sync($created, $validated['permissions']);
                }
            } elseif ($created->role === UserRole::AGENCY) {
                AgencyRegistration::updateOrCreate(
                    ['user_id' => $created->id],
                    [
                        'office_id' => $created->office_id,
                        'agency_name_claimed' => $created->office?->name ?? 'Partner Agency',
                        'position_title' => 'Agency Representative',
                        'contact_number' => 'N/A',
                        'id_document_path' => 'system/admin_created.pdf',
                        'status' => 'approved',
                        'is_office_admin' => true,
                        'reviewed_by' => $request->user()?->id,
                        'reviewed_at' => now(),
                        'decision_note' => 'Account created by system administrator.',
                    ]
                );
            }

            if (! empty($validated['position_id']) && $created->role !== UserRole::AGENCY) {
                $created->personnel()->create([
                    'position_id' => $validated['position_id'],
                    'full_name' => $created->name,
                    'email' => $created->email,
                    'term_start' => now()->toDateString(),
                    'is_active' => true,
                ]);
            }

            $this->audit->log($request->user(), 'user.created', $created, [], [
                'id' => $created->id,
                'name' => $created->name,
                'email' => $created->email,
                'role' => $created->role,
            ]);

            return $created;
        });

        return redirect()->route('admin.users.index')
            ->with('success', "User account created for {$user->name}.")
            ->with('generated_password', $plainPassword)
            ->with('generated_email', $user->email);
    }

    public function edit(User $user): View
    {
        $user->load(['permissions', 'personnel.position', 'office']);

        return view('admin.users.form', [
            'user' => $user,
            'presets' => config('permission_presets.presets', []),
            'permissionGroups' => Permission::query()->orderBy('group')->orderBy('label')->get()->groupBy('group'),
            'selectedSlugs' => $user->permissions->pluck('slug')->all(),
            'positions' => Position::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'externalOffices' => Office::query()->where(fn ($q) => $q->where('is_external', true)->orWhere('type', 'external'))->where('is_active', true)->orderBy('name')->get(),
        ]);
    }

    public function update(SaveUserRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $newRole = $validated['role'];
        $newActive = $request->has('is_active') ? $request->boolean('is_active') : $user->is_active;

        // Guard against removing or deactivating the last active administrator
        if ($user->role === UserRole::ADMIN && ($newRole !== UserRole::ADMIN || ! $newActive)) {
            $otherActiveAdmins = User::query()
                ->where('role', UserRole::ADMIN)
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveAdmins === 0) {
                return back()->withInput()->withErrors([
                    'role' => 'At least one active administrator account must remain in the system.',
                ]);
            }
        }

        DB::transaction(function () use ($user, $validated, $newRole, $newActive, $request): void {
            $old = [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ];

            $updates = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'role' => $newRole,
                'office_id' => $newRole === UserRole::AGENCY ? ($validated['office_id'] ?? null) : null,
                'is_active' => $newActive,
            ];

            if (! empty($validated['password'])) {
                $updates['password'] = Hash::make($validated['password']);
            }
            if ($request->has('must_change_password')) {
                $updates['must_change_password'] = $request->boolean('must_change_password');
            }

            $user->update($updates);

            if ($newRole === UserRole::STAFF) {
                if (! empty($validated['preset'])) {
                    $this->permissionService->applyPreset($user, $validated['preset']);
                } elseif ($request->has('permissions')) {
                    $this->permissionService->sync($user, $validated['permissions'] ?? []);
                }
            } elseif ($newRole === UserRole::AGENCY) {
                AgencyRegistration::updateOrCreate(
                    ['user_id' => $user->id],
                    [
                        'office_id' => $user->office_id,
                        'agency_name_claimed' => $user->office?->name ?? 'Partner Agency',
                        'position_title' => 'Agency Representative',
                        'contact_number' => 'N/A',
                        'id_document_path' => 'system/admin_created.pdf',
                        'status' => 'approved',
                        'is_office_admin' => true,
                        'reviewed_by' => $request->user()?->id,
                        'reviewed_at' => now(),
                        'decision_note' => 'Account updated by system administrator.',
                    ]
                );
            }

            // Sync position via Personnel
            if ($newRole === UserRole::AGENCY) {
                if ($user->personnel) {
                    $user->personnel->update(['position_id' => null]);
                }
            } else {
                $posId = $validated['position_id'] ?? null;
                if ($posId) {
                    if ($user->personnel) {
                        $user->personnel->update(['position_id' => $posId]);
                    } else {
                        $user->personnel()->create([
                            'position_id' => $posId,
                            'full_name' => $user->name,
                            'email' => $user->email,
                            'term_start' => now()->toDateString(),
                            'is_active' => true,
                        ]);
                    }
                } elseif (array_key_exists('position_id', $validated) && $user->personnel) {
                    $user->personnel->update(['position_id' => null]);
                }
            }

            $this->audit->log($request->user(), 'user.updated', $user, $old, [
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'is_active' => $user->is_active,
            ]);
        });

        return redirect()->route('admin.users.index')->with('success', 'User updated.');
    }

    public function editPermissions(User $user): View
    {
        $user->load('permissions');

        return view('admin.users.permissions', [
            'user' => $user,
            'presets' => config('permission_presets.presets', []),
            'permissionGroups' => Permission::query()->orderBy('group')->orderBy('label')->get()->groupBy('group'),
            'selectedSlugs' => $user->permissions->pluck('slug')->all(),
        ]);
    }

    public function updatePermissions(UpdateUserPermissionsRequest $request, User $user): RedirectResponse
    {
        $validated = $request->validated();
        $oldSlugs = $user->permissions()->pluck('slug')->all();

        DB::transaction(function () use ($user, $validated, $oldSlugs, $request): void {
            if (! empty($validated['preset'])) {
                $this->permissionService->applyPreset($user, $validated['preset']);
            } else {
                $this->permissionService->sync($user, $validated['permissions'] ?? []);
            }

            $newSlugs = $user->fresh()->permissions->pluck('slug')->all();
            $this->audit->log($request->user(), 'user.permissions_updated', $user, [
                'permissions' => $oldSlugs,
            ], [
                'preset' => $validated['preset'] ?? null,
                'permissions' => $newSlugs,
            ]);
        });

        return redirect()->route('admin.users.index')->with('success', "Permissions updated for {$user->name}.");
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($user->id === $request->user()?->id && $user->is_active) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot deactivate your own active account.');
        }

        if ($user->role === 'admin' && $user->is_active) {
            $otherActiveAdmins = User::query()
                ->where('role', 'admin')
                ->where('is_active', true)
                ->where('id', '!=', $user->id)
                ->count();

            if ($otherActiveAdmins === 0) {
                return redirect()->route('admin.users.index')
                    ->with('error', 'Cannot deactivate the last active administrator account.');
            }
        }

        $old = ['is_active' => $user->is_active];
        $newState = ! $user->is_active;
        $user->update(['is_active' => $newState]);

        $this->audit->log($request->user(), $newState ? 'user.activated' : 'user.deactivated', $user, $old, [
            'is_active' => $newState,
            'note' => $request->input('note'),
        ]);

        return redirect()->route('admin.users.index')->with('success', 'User account status changed.');
    }

    public function resetPassword(Request $request, User $user): RedirectResponse
    {
        $tempPassword = Str::password(12);

        $user->update([
            'password' => Hash::make($tempPassword),
            'must_change_password' => true,
        ]);

        $this->audit->log($request->user(), 'user.password_reset', $user, [], [
            'must_change_password' => true,
        ]);

        return redirect()->route('admin.users.index')
            ->with('success', 'Temporary password generated.')
            ->with('generated_password', $tempPassword)
            ->with('generated_email', $user->email);
    }
}
