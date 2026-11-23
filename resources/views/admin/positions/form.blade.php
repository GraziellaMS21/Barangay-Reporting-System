@extends('layouts.admin')

@section('title', $position->exists ? "Edit Position: {$position->name}" : 'Create Position')
@section('page-heading', $position->exists ? 'Edit Position' : 'Create Position')

@section('content')
<x-ui.page-header :title="$position->exists ? 'Edit Position: '.$position->name : 'Create Position'" subtitle="Configure position code, display order, and default signatory status.">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('admin.positions.index')">
            <i class="fa-solid fa-arrow-left"></i> Back to Positions
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card>
    <form method="POST" action="{{ $position->exists ? route('admin.positions.update', $position) : route('admin.positions.store') }}" class="space-y-5 max-w-2xl">
        @csrf
        @if($position->exists)
            @method('PUT')
        @endif

        <x-ui.form-field label="Position Title" name="name" :required="true">
            <input id="name" name="name" type="text" required maxlength="80" value="{{ old('name', $position->name) }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </x-ui.form-field>

        <x-ui.form-field label="System Code" name="code" :required="true" hint="Lowercase letters and underscores only (e.g. punong_barangay).">
            <input id="code" name="code" type="text" required maxlength="60" value="{{ old('code', $position->code) }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm font-mono">
        </x-ui.form-field>

        <x-ui.form-field label="Sort Order" name="sort_order">
            <input id="sort_order" name="sort_order" type="number" min="0" max="999" value="{{ old('sort_order', $position->sort_order ?? 0) }}"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
        </x-ui.form-field>

        <div class="flex flex-wrap items-center gap-6">
            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="hidden" name="is_signatory_default" value="0">
                <input type="checkbox" name="is_signatory_default" value="1" @checked(old('is_signatory_default', $position->is_signatory_default))>
                <span>Signatory by default</span>
            </label>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $position->exists ? $position->is_active : true))>
                <span>Active</span>
            </label>

            <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
                <input type="hidden" name="is_confirmed" value="0">
                <input type="checkbox" name="is_confirmed" value="1" @checked(old('is_confirmed', $position->is_confirmed))>
                <span>Confirmed by Barangay</span>
            </label>
        </div>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            <x-ui.button variant="ghost" :href="route('admin.positions.index')">Cancel</x-ui.button>
            <x-ui.button type="submit" variant="primary">Save Position</x-ui.button>
        </div>
    </form>
</x-ui.card>
@endsection
