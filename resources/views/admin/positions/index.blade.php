@extends('layouts.admin')

@section('title', 'Barangay Positions')
@section('page-heading', 'Barangay Positions')

@section('content')
<x-ui.page-header title="Barangay Positions & Titles" subtitle="Manage official barangay positions, ordering, and default signatory eligibility.">
    <x-slot:actions>
        @if($unconfirmedCount > 0)
            <x-ui.pending-badge :count="$unconfirmedCount" />
        @endif
        <x-ui.button variant="secondary" :href="route('admin.personnel.index')">
            <i class="fa-solid fa-users"></i> Personnel Directory
        </x-ui.button>
        <x-ui.button variant="primary" onclick="document.getElementById('add-position-modal').showModal()">
            <i class="fa-solid fa-plus"></i> Add Position
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

@if($positions->isEmpty())
    <x-ui.empty-state title="No positions configured" description="Create barangay positions to assign officials and document signatories." icon="fa-solid fa-id-badge">
        <x-slot:action>
            <x-ui.button variant="primary" onclick="document.getElementById('add-position-modal').showModal()">
                <i class="fa-solid fa-plus"></i> Add Position
            </x-ui.button>
        </x-slot:action>
    </x-ui.empty-state>
@else
    <x-ui.table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 w-[34%]">Name</th>
                <th class="px-4 py-3 w-[20%]">Code</th>
                <th class="px-4 py-3 w-[14%] text-center">Signatory by Default</th>
                <th class="px-4 py-3 w-[8%] text-right">Order</th>
                <th class="px-4 py-3 w-[12%]">Status</th>
                <th class="px-4 py-3 w-[12%] text-right">Actions</th>
            </tr>
        </x-slot:header>

        @foreach($positions as $position)
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 font-semibold text-slate-900">
                    <div class="flex items-center gap-2 flex-wrap">
                        <span>{{ $position->name }}</span>
                        @if(! $position->is_confirmed)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-dashed border-amber-300">
                                Pending confirmation
                            </span>
                        @endif
                    </div>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-slate-600">{{ $position->code }}</td>
                <td class="px-4 py-3 text-center">
                    @if($position->is_signatory_default)
                        <i class="fa-solid fa-circle-check text-emerald-600" aria-label="Yes"></i>
                    @else
                        <span class="text-slate-400">—</span>
                    @endif
                </td>
                <td class="px-4 py-3 text-right text-slate-600">{{ $position->sort_order }}</td>
                <td class="px-4 py-3">
                    <x-ui.badge :color="$position->is_active ? 'green' : 'slate'" :dot="true">
                        {{ $position->is_active ? 'Active' : 'Inactive' }}
                    </x-ui.badge>
                </td>
                <td class="px-4 py-3 text-right">
                    <div class="inline-flex items-center justify-end gap-2">
                        <x-ui.button size="sm" variant="secondary" :href="route('admin.positions.edit', $position)">
                            Edit
                        </x-ui.button>
                        <form method="POST" action="{{ route('admin.positions.toggle-active', $position) }}">
                            @csrf
                            <x-ui.button size="sm" variant="ghost" type="submit">
                                {{ $position->is_active ? 'Deactivate' : 'Activate' }}
                            </x-ui.button>
                        </form>
                    </div>
                </td>
            </tr>
        @endforeach
    </x-ui.table>
@endif

<x-ui.modal id="add-position-modal" title="Add Position">
    <form method="POST" action="{{ route('admin.positions.store') }}" class="space-y-4">
        @csrf
        <x-ui.form-field label="Position Title" name="name" :required="true">
            <input id="name" name="name" type="text" required maxlength="80" value="{{ old('name') }}"
                   placeholder="e.g. Kagawad (Peace & Order)"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </x-ui.form-field>

        <x-ui.form-field label="System Code" name="code" :required="true" hint="Lowercase letters and underscores (e.g. kagawad_peace_order)">
            <input id="code" name="code" type="text" required maxlength="60" value="{{ old('code') }}"
                   placeholder="e.g. kagawad_peace_order"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
        </x-ui.form-field>

        <x-ui.form-field label="Sort Order" name="sort_order">
            <input id="sort_order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', $positions->count() + 1) }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </x-ui.form-field>

        <div class="flex items-center gap-6 pt-1">
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="is_signatory_default" value="0">
                <input type="checkbox" name="is_signatory_default" value="1" @checked(old('is_signatory_default'))>
                <span>Signatory by default</span>
            </label>
            <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))>
                <span>Active</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
            <x-ui.button type="button" variant="ghost" onclick="document.getElementById('add-position-modal').close()">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary">Save Position</x-ui.button>
        </div>
    </form>
</x-ui.modal>
@endsection
