@extends('layouts.admin')

@section('title', "Permissions: {$user->name}")
@section('page-heading', 'User Permission Matrix')

@section('content')
<x-ui.page-header :title="'Permissions: '.$user->name" :subtitle="$user->email.' · Role: '.ucfirst($user->role)">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('admin.users.index')">
            <i class="fa-solid fa-arrow-left"></i> Back to Users
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<form method="POST" action="{{ route('admin.users.permissions.update', $user) }}" class="space-y-6" data-permissions-form>
    @csrf
    @method('PUT')

    <x-ui.card title="1. Quick Role Preset Selector">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 items-end">
            <x-ui.form-field label="Apply a Role Preset" name="preset" hint="Selecting a preset checks the matching permissions below.">
                <select id="preset-selector" name="preset" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm" data-preset-select>
                    <option value="">Custom granular selection</option>
                    @foreach($presets as $presetKey => $presetMeta)
                        <option value="{{ $presetKey }}" data-permissions='@json($presetMeta['permissions'] ?? [])'>
                            {{ $presetMeta['label'] }}
                        </option>
                    @endforeach
                </select>
            </x-ui.form-field>
            <div class="text-xs text-slate-500 pb-2">
                Confidential case access (<code>cases.view_confidential</code>) and system backups (<code>backups.manage</code>) are restricted by default.
            </div>
        </div>
    </x-ui.card>

    <x-ui.card title="2. Granular Permission Matrix">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            @foreach($permissionGroups as $groupName => $perms)
                <div class="rounded-xl border border-slate-200 p-4 bg-slate-50/50" data-perm-group>
                    <div class="flex items-center justify-between border-b border-slate-200 pb-2 mb-3">
                        <h4 class="font-bold text-slate-900 text-sm uppercase tracking-wider">{{ str_replace('_', ' ', $groupName) }}</h4>
                        <button type="button" class="text-xs font-semibold text-blue-600 hover:text-blue-800" data-select-all-group>
                            Select All
                        </button>
                    </div>
                    <div class="space-y-2">
                        @foreach($perms as $perm)
                            <label class="flex items-start gap-2.5 text-sm text-slate-800 cursor-pointer">
                                <input type="checkbox"
                                       name="permissions[]"
                                       value="{{ $perm->slug }}"
                                       data-perm-checkbox
                                       @checked(in_array($perm->slug, old('permissions', $selectedSlugs), true))
                                       class="mt-0.5 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <span>
                                    <span class="font-medium">{{ $perm->label }}</span>
                                    <span class="block text-xs font-mono text-slate-500">{{ $perm->slug }}</span>
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </x-ui.card>

    <div class="flex items-center justify-end gap-3">
        <x-ui.button variant="ghost" :href="route('admin.users.index')">Cancel</x-ui.button>
        <x-ui.button type="submit" variant="primary">Save Permissions</x-ui.button>
    </div>
</form>

@push('scripts')
<script>
(() => {
    const form = document.querySelector('[data-permissions-form]');
    if (!form) return;

    const presetSelect = form.querySelector('[data-preset-select]');
    const allCheckboxes = [...form.querySelectorAll('[data-perm-checkbox]')];

    presetSelect?.addEventListener('change', () => {
        const opt = presetSelect.selectedOptions[0];
        if (!opt || !opt.dataset.permissions) return;
        try {
            const slugs = JSON.parse(opt.dataset.permissions);
            allCheckboxes.forEach((cb) => {
                cb.checked = slugs.includes(cb.value);
            });
        } catch {}
    });

    form.querySelectorAll('[data-select-all-group]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const group = btn.closest('[data-perm-group]');
            if (!group) return;
            const boxes = [...group.querySelectorAll('[data-perm-checkbox]')];
            const allChecked = boxes.every((cb) => cb.checked);
            boxes.forEach((cb) => { cb.checked = !allChecked; });
            if (presetSelect) presetSelect.value = '';
        });
    });
})();
</script>
@endpush
@endsection
