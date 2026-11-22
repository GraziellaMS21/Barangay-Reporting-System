@extends('layouts.admin')

@section('title', 'Case Form Fields Configuration')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <x-ui.page-header
        title="Dynamic Form Field Configurations"
        subtitle="Manage dynamic visibility, requirement rules, and residency applicability for docketing forms"
    />

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-sm text-emerald-800 font-medium">
            {{ session('success') }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.case-fields.update') }}" class="space-y-6">
        @csrf

        @php $fieldIndex = 0; @endphp
        @foreach($configsByEntity as $entity => $configs)
            <x-ui.card class="overflow-hidden border border-slate-200">
                <div class="p-4 bg-slate-50 border-b border-slate-200 flex items-center justify-between">
                    <div>
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider">
                            {{ ucfirst($entity) }} Form Fields ({{ $configs->count() }})
                        </h2>
                        <p class="text-xs text-slate-500">Field rules applying to {{ $entity }} entry forms</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                        <thead class="bg-white text-xs font-bold text-slate-500 uppercase tracking-wider">
                            <tr>
                                <th class="py-3 pl-4 pr-3 sm:pl-6 w-[35%]">Field / Key</th>
                                <th class="px-3 py-3 w-[15%] text-center">Required</th>
                                <th class="px-3 py-3 w-[15%] text-center">Visible</th>
                                <th class="px-3 py-3 w-[20%]">Applies To</th>
                                <th class="py-3 pl-3 pr-4 sm:pr-6 w-[15%] text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                            @foreach($configs as $cfg)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="py-3 pl-4 pr-3 sm:pl-6 text-xs">
                                        <input type="hidden" name="fields[{{ $fieldIndex }}][id]" value="{{ $cfg->id }}">
                                        <div class="font-bold text-slate-900">{{ $cfg->label }}</div>
                                        <span class="font-mono text-[11px] text-slate-400">{{ $cfg->field_key }}</span>
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            name="fields[{{ $fieldIndex }}][is_required]"
                                            value="1"
                                            @checked($cfg->is_required)
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                    </td>

                                    <td class="px-3 py-3 text-center">
                                        <input
                                            type="checkbox"
                                            name="fields[{{ $fieldIndex }}][is_visible]"
                                            value="1"
                                            @checked($cfg->is_visible)
                                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                                        />
                                    </td>

                                    <td class="px-3 py-3 text-xs">
                                        <select name="fields[{{ $fieldIndex }}][applies_to]" class="text-xs border border-slate-300 rounded p-1 w-full focus:ring-1 focus:ring-blue-500">
                                            <option value="all" @selected($cfg->applies_to === 'all')>All Parties</option>
                                            <option value="resident" @selected($cfg->applies_to === 'resident')>Residents Only</option>
                                            <option value="non_resident" @selected($cfg->applies_to === 'non_resident')>Non-Residents Only</option>
                                        </select>
                                    </td>

                                    <td class="py-3 pl-3 pr-4 sm:pr-6 text-center text-xs">
                                        @if(!$cfg->is_confirmed)
                                            <x-ui.pending-badge />
                                        @else
                                            <span class="text-[11px] font-semibold text-emerald-700 bg-emerald-50 px-2 py-0.5 rounded-full border border-emerald-200">Confirmed</span>
                                        @endif
                                    </td>
                                </tr>
                                @php $fieldIndex++; @endphp
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-ui.card>
        @endforeach

        <div class="flex justify-end">
            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                Save Field Configurations
            </button>
        </div>
    </form>
</div>
@endsection
