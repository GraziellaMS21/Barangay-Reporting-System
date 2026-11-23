@extends('layouts.admin')

@section('title', $user->exists ? "Edit User: {$user->name}" : 'Create User Account')
@section('page-heading', $user->exists ? 'Edit User Account' : 'Create User Account')

@section('content')
<x-ui.page-header :title="$user->exists ? 'Edit User: '.$user->name : 'Create User Account'" subtitle="Configure user login details, system role, and permission preset.">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('admin.users.index')">
            <i class="fa-solid fa-arrow-left"></i> Back to Users
        </x-ui.button>
        @if($user->exists && $user->role === 'staff')
            <x-ui.button variant="secondary" :href="route('admin.users.permissions.edit', $user)">
                <i class="fa-solid fa-key"></i> Granular Permissions
            </x-ui.button>
        @endif
    </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-6 max-w-3xl">
    @csrf
    @if($user->exists)
        @method('PUT')
    @endif

    <x-ui.card title="Account Information">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <x-ui.form-field label="Full Name" name="name" :required="true">
                <input id="name" name="name" type="text" required maxlength="120" value="{{ old('name', $user->name) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </x-ui.form-field>

            <x-ui.form-field label="Email Address" name="email" :required="true">
                <input id="email" name="email" type="email" required maxlength="150" value="{{ old('email', $user->email) }}"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </x-ui.form-field>

            <x-ui.form-field label="System Role" name="role" :required="true" hint="Captain and Barangay Secretary may both hold Admin roles.">
                <select id="role" name="role" required class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="staff" @selected(old('role', $user->role) === 'staff')>Staff (Granular Permissions)</option>
                    <option value="admin" @selected(old('role', $user->role) === 'admin')>Administrator (Full Access)</option>
                </select>
            </x-ui.form-field>

            <x-ui.form-field label="Apply Permission Preset (Staff)" name="preset" hint="Select a preset to initialize or replace staff permissions.">
                <select id="preset" name="preset" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="">Keep current / custom permissions</option>
                    @foreach($presets as $presetKey => $presetMeta)
                        <option value="{{ $presetKey }}" @selected(old('preset') === $presetKey)>{{ $presetMeta['label'] }}</option>
                    @endforeach
                </select>
            </x-ui.form-field>

            <x-ui.form-field label="Password (Optional)" name="password" hint="Min 10 characters. Leave blank to auto-generate a temporary password (new users) or keep existing password.">
                <input id="password" name="password" type="password" minlength="10" autocomplete="new-password"
                       class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
            </x-ui.form-field>

            <div class="flex flex-col justify-center gap-3 pt-4">
                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $user->exists ? $user->is_active : true))>
                    <span>Account is active</span>
                </label>

                <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                    <input type="hidden" name="must_change_password" value="0">
                    <input type="checkbox" name="must_change_password" value="1" @checked(old('must_change_password', $user->exists ? $user->must_change_password : true))>
                    <span>Require password change on next sign-in</span>
                </label>
            </div>
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="ghost" :href="route('admin.users.index')">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary">{{ $user->exists ? 'Save User' : 'Create User' }}</x-ui.button>
    </div>
</form>
@endsection
