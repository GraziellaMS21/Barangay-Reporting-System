@extends('layouts.admin')

@section('title', 'Barangay Personnel Directory')
@section('page-heading', 'Barangay Personnel Directory')

@section('content')
<x-ui.page-header title="Barangay Personnel Directory" subtitle="Manage elected officials, Lupon Tagapamayapa members, terms of office, and authorized signatories.">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('admin.positions.index')">
            <i class="fa-solid fa-id-badge"></i> Positions
        </x-ui.button>
        <x-ui.button variant="secondary" :href="route('admin.signatories.edit')">
            <i class="fa-solid fa-file-signature"></i> Signatories Matrix
        </x-ui.button>
        <x-ui.button variant="primary" :href="route('admin.personnel.create')">
            <i class="fa-solid fa-plus"></i> + Add Official
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if(session('generated_password'))
    <div class="mb-6 rounded-xl border border-emerald-300 bg-emerald-50 p-4 text-emerald-950">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-key text-emerald-600 mt-0.5"></i>
            <div class="text-sm">
                <strong class="block font-bold">Temporary Login Credentials Generated (Copy Now)</strong>
                <p class="mt-1">
                    Email: <code class="font-mono font-semibold bg-white px-2 py-0.5 rounded border border-emerald-200">{{ session('generated_email') }}</code>
                    · Temporary Password: <code class="font-mono font-bold bg-white px-2 py-0.5 rounded border border-emerald-200">{{ session('generated_password') }}</code>
                </p>
                <p class="text-xs text-emerald-800 mt-1">The user will be required to change this password on first sign-in.</p>
            </div>
        </div>
    </div>
@endif

<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('admin.personnel.index') }}" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
        <x-ui.form-field label="Search Name / Email" name="q">
            <input type="text" name="q" value="{{ request('q') }}" placeholder="Search official..."
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </x-ui.form-field>

        <x-ui.form-field label="Position" name="position_id">
            <select name="position_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">All positions</option>
                @foreach($positions as $pos)
                    <option value="{{ $pos->id }}" @selected((string) request('position_id') === (string) $pos->id)>{{ $pos->name }}</option>
                @endforeach
            </select>
        </x-ui.form-field>

        <x-ui.form-field label="Status" name="status">
            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                <option value="">All statuses</option>
                <option value="active" @selected(request('status') === 'active')>Active</option>
                <option value="inactive" @selected(request('status') === 'inactive')>Inactive</option>
            </select>
        </x-ui.form-field>

        <div class="flex items-center gap-2">
            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>
            <x-ui.button variant="ghost" :href="route('admin.personnel.index')">Clear</x-ui.button>
        </div>
    </form>
</x-ui.card>

@if($personnelList->isEmpty())
    <x-ui.empty-state title="No officials or personnel found" description="Add barangay officials, Lupon members, and tanods to populate the roster." icon="fa-solid fa-user-tie">
        <x-slot:action>
            <x-ui.button variant="primary" :href="route('admin.personnel.create')">
                <i class="fa-solid fa-plus"></i> + Add Official
            </x-ui.button>
        </x-slot:action>
    </x-ui.empty-state>
@else
    <x-ui.table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 w-[24%]">Name</th>
                <th class="px-4 py-3 w-[18%]">Position</th>
                <th class="px-4 py-3 w-[18%]">Term</th>
                <th class="px-4 py-3 w-[8%] text-center">Signatory</th>
                <th class="px-4 py-3 w-[8%] text-center">Login</th>
                <th class="px-4 py-3 w-[10%]">Status</th>
                <th class="px-4 py-3 w-[14%] text-right">Actions</th>
            </tr>
        </x-slot:header>

        @foreach($personnelList as $person)
            <tr class="hover:bg-slate-50">
                <td data-label="Name" class="px-4 py-3">
                    <a href="{{ route('admin.personnel.show', $person) }}" class="font-bold text-slate-900 hover:text-blue-700">
                        {{ $person->full_name }}
                    </a>
                    @if($person->email || $person->contact_number)
                        <div class="text-xs text-slate-500">
                            {{ collect([$person->email, $person->contact_number])->filter()->implode(' · ') }}
                        </div>
                    @endif
                </td>
                <td data-label="Position" class="px-4 py-3 text-slate-700">{{ $person->position?->name ?? 'Unassigned' }}</td>
                <td data-label="Term" class="px-4 py-3 text-slate-600 text-xs">
                    {{ $person->term_start?->format('Y-m-d') ?? '—' }} — {{ $person->term_end?->format('Y-m-d') ?? 'Present' }}
                </td>
                <td data-label="Signatory" class="px-4 py-3 text-center">
                    @if($person->is_signatory)
                        <i class="fa-solid fa-circle-check text-emerald-600" title="Authorized Signatory"></i>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td data-label="Login" class="px-4 py-3 text-center">
                    @if($person->user_id)
                        <i class="fa-solid fa-user-check text-blue-600" title="Linked account: {{ $person->user?->email }}"></i>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td data-label="Status" class="px-4 py-3">
                    <x-ui.badge :color="$person->is_active ? 'green' : 'slate'" :dot="true">
                        {{ $person->is_active ? 'Active' : 'Inactive' }}
                    </x-ui.badge>
                </td>
                <td data-label="Actions" class="px-4 py-3 text-right">
                    <div class="inline-flex items-center justify-end gap-2">
                        <x-ui.button size="sm" variant="ghost" :href="route('admin.personnel.show', $person)">View</x-ui.button>
                        <x-ui.button size="sm" variant="secondary" :href="route('admin.personnel.edit', $person)">Edit</x-ui.button>
                        @if($person->is_active)
                            <x-ui.button size="sm" variant="danger" onclick="document.getElementById('deactivate-personnel-{{ $person->id }}').showModal()">
                                Deactivate
                            </x-ui.button>
                        @endif
                    </div>

                    @if($person->is_active)
                        <x-ui.modal id="deactivate-personnel-{{ $person->id }}" title="Deactivate {{ $person->full_name }}">
                            <form method="POST" action="{{ route('admin.personnel.deactivate', $person) }}" class="space-y-4 text-left">
                                @csrf
                                <p class="text-sm text-slate-600">
                                    Deactivating an official ends their active term while preserving historical audit logs and past document signature references.
                                </p>
                                <x-ui.form-field label="Term End Date" name="term_end" :required="true">
                                    <input type="date" name="term_end" value="{{ now()->toDateString() }}" required
                                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                                </x-ui.form-field>
                                <x-ui.form-field label="Reason / Note" name="note" :required="true" hint="Minimum 5 characters.">
                                    <textarea name="note" rows="3" required minlength="5" maxlength="500"
                                              placeholder="e.g. End of term or reassignment..."
                                              class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"></textarea>
                                </x-ui.form-field>
                                <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                                    <x-ui.button type="button" variant="ghost" onclick="document.getElementById('deactivate-personnel-{{ $person->id }}').close()">Cancel</x-ui.button>
                                    <x-ui.button type="submit" variant="danger">Confirm Deactivation</x-ui.button>
                                </div>
                            </form>
                        </x-ui.modal>
                    @endif
                </td>
            </tr>
        @endforeach
    </x-ui.table>

    <div class="mt-4">
        {{ $personnelList->links() }}
    </div>
@endif
@endsection
