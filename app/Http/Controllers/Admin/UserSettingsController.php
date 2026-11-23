<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserSettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.users.index', [
            'users' => User::query()->orderBy('name')->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('admin.settings.users.form', [
            'managedUser' => new User,
        ]);
    }

    public function edit(Request $request, User $user): View
    {
        return view('admin.settings.users.form', [
            'managedUser' => $user,
            'isSelf' => $user->id === $request->user()->id,
        ]);
    }

    public function store(Request $request, UserManagementService $userManagementService): RedirectResponse
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in(['admin', 'staff', 'agency'])],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ], [
            'name.required' => 'Enter the staff member\'s name.',
            'email.unique' => 'A user with this email address already exists.',
            'role.in' => 'Choose personnel, staff, or administrator access.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
        ]);
        $attributes['is_active'] = true;
        $userManagementService->create($attributes, $request->user());

        return redirect()->route('admin.settings.users.index')->with('success', 'User account created.');
    }

    public function update(
        Request $request,
        User $user,
        UserManagementService $userManagementService
    ): RedirectResponse {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'role' => ['required', Rule::in(['admin', 'staff', 'agency'])],
            'is_active' => ['required', 'boolean'],
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
        ], [
            'email.unique' => 'A user with this email address already exists.',
            'role.in' => 'Choose personnel, staff, or administrator access.',
            'password.confirmed' => 'The password confirmation does not match.',
            'password.min' => 'The password must be at least 8 characters.',
        ]);
        $attributes['is_active'] = $request->boolean('is_active');

        if (($attributes['password'] ?? null) === null) {
            unset($attributes['password']);
        }

        $userManagementService->update($user, $attributes, $request->user());

        return redirect()->route('admin.settings.users.index')->with('success', 'User account updated.');
    }
}
