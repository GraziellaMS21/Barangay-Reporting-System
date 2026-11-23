@extends('layouts.admin')

@section('title', 'User & Staff Accounts')
@section('page-heading', 'User Accounts Directory')

@section('content')
<x-ui.page-header title="User Accounts Directory" subtitle="Manage administrator and barangay staff logins, role presets, and granular permissions.">
    <x-slot:actions>
        <x-ui.button variant="primary" :href="route('admin.users.create')">
            <i class="fa-solid fa-user-plus"></i> + Add User
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('generated_password'))
    <div class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-950">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-key text-emerald-600 mt-0.5"></i>
            <div class="text-sm">
                <strong class="block font-bold">Temporary Password Generated (Copy Now)</strong>
                <p class="mt-1">
                    Account: <code class="font-mono font-semibold bg-white px-2 py-0.5 rounded border border-emerald-200">{{ session('generated_email') }}</code>
                    · Temporary Password: <code class="font-mono font-bold bg-white px-2 py-0.5 rounded border border-emerald-200">{{ session('generated_password') }}</code>
                </p>
                <p class="text-xs text-emerald-800 mt-1">This password is shown only once. The user must set a new password upon sign-in.</p>
            </div>
        </div>
    </div>
@endif

<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('admin.users.index') }}" class="grid grid-cols-1 sm:grid-cols-3 gap-4 items-end">
        <x-ui.form-field label="Search Name / Email" name="q">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search user..."
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </x-ui.form-field>

        <x-ui.form-field label="Role" name="role">
            <select name="role" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">All roles</option>
                <option value="admin" @selected(request('role') === 'admin')>Admin</option>
                <option value="staff" @selected(request('role') === 'staff')>Staff</option>
                <option value="agency" @selected(request('role') === 'agency')>Agency</option>
            </select>
        </x-ui.form-field>

        <div class="flex items-center gap-2">
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
            <x-ui.button variant="ghost" :href="route('admin.users.index')">Clear</x-ui.button>
        </div>
    </form>
</x-ui.card>

<x-ui.table>
    <x-slot:header>
        <tr>
            <th class="px-4 py-3 w-[22%]">Name</th>
            <th class="px-4 py-3 w-[26%]">Email</th>
            <th class="px-4 py-3 w-[10%]">Role</th>
            <th class="px-4 py-3 w-[12%]">Permissions</th>
            <th class="px-4 py-3 w-[10%]">Status</th>
            <th class="px-4 py-3 w-[12%]">Last Login</th>
            <th class="px-4 py-3 w-[8%] text-right">Actions</th>
        </tr>
    </x-slot:header>

    @forelse($users as $u)
        @php
            $roleColor = match ($u->role) {
                'admin' => 'amber',
                'agency' => 'purple',
                default => 'blue',
            };
        @endphp
        <tr class="hover:bg-slate-50">
            <td class="px-4 py-3 font-semibold text-slate-900">
                {{ $u->name }}
                @if($u->role === 'agency')
                    <div class="text-xs font-normal text-purple-700">{{ $u->office?->name ?? 'External Agency' }}</div>
                @elseif($u->personnel?->position)
                    <div class="text-xs font-normal text-slate-500">{{ $u->personnel->position->name }}</div>
                @endif
            </td>
            <td class="px-4 py-3 text-slate-700">{{ $u->email }}</td>
            <td class="px-4 py-3">
                <x-ui.badge :color="$roleColor">{{ ucfirst($u->role) }}</x-ui.badge>
            </td>
            <td class="px-4 py-3 text-xs text-slate-600">
                @if($u->role === 'admin')
                    <span class="font-semibold text-slate-800">All ({{ $totalPermissionsCount }} perms)</span>
                @elseif($u->role === 'agency')
                    <span>Agency Portal</span>
                @else
                    <span>{{ $u->permissions->count() }} perms</span>
                @endif
            </td>
            <td class="px-4 py-3">
                <x-ui.badge :color="$u->is_active ? 'green' : 'slate'" :dot="true">
                    {{ $u->is_active ? 'Active' : 'Inactive' }}
                </x-ui.badge>
            </td>
            <td class="px-4 py-3 text-xs text-slate-500">
                {{ $u->last_login_at ? $u->last_login_at->diffForHumans() : 'Never' }}
            </td>
            <td class="px-4 py-3 text-right">
                <div class="inline-flex items-center justify-end gap-1.5">
                    <x-ui.button size="sm" variant="secondary" :href="route('admin.users.edit', $u)">Edit</x-ui.button>
                    @if($u->role === 'staff')
                        <x-ui.button size="sm" variant="secondary" :href="route('admin.users.permissions.edit', $u)">Permissions</x-ui.button>
                    @endif
                    <form method="POST" action="{{ route('admin.users.reset-password', $u) }}">
                        @csrf
                        <x-ui.button size="sm" variant="ghost" type="submit" title="Generate temporary password">Reset PW</x-ui.button>
                    </form>
                    @if($u->id !== auth()->id())
                        <form method="POST" action="{{ route('admin.users.deactivate', $u) }}">
                            @csrf
                            <x-ui.button size="sm" :variant="$u->is_active ? 'danger' : 'secondary'" type="submit">
                                {{ $u->is_active ? 'Deactivate' : 'Activate' }}
                            </x-ui.button>
                        </form>
                    @endif
                </div>
            </td>
        </tr>
    @empty
        <tr>
            <td colspan="7" class="px-4 py-8 text-center text-slate-500">No user accounts match the current filter.</td>
        </tr>
    @endforelse
</x-ui.table>

<div class="mt-4">
    {{ $users->links() }}
</div>
@endsection
