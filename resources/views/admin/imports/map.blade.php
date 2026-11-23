@extends('layouts.admin')

@section('title', 'Map Columns — Batch #'.$batch->id)
@section('page-heading', 'Legacy Import Wizard')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="text-xs text-slate-500 flex items-center gap-2">
        <a href="{{ route('admin.imports.index') }}" class="hover:text-blue-700">Legacy File Imports</a>
        <span>/</span>
        <span>Batch #{{ $batch->id }} ({{ $batch->filename }})</span>
        <span>/</span>
        <span class="text-slate-800 font-medium">Step 2 of 3: Map Columns</span>
    </div>

    <x-ui.page-header
        title="Step 2 of 3: Interactive Column & Status Mapping"
        subtitle="Match the columns detected in '{{ $batch->filename }}' ({{ $batch->stats['total_rows'] ?? 0 }} rows) to the system fields."
    />

    <!-- Progress Stepper -->
    <div class="grid grid-cols-3 gap-3 text-xs font-semibold">
        <div class="p-3 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600" aria-hidden="true"></i>
            <span>1. File Uploaded</span>
        </div>
        <div class="p-3 rounded-lg bg-blue-600 text-white flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-white text-blue-700 inline-flex items-center justify-center font-bold">2</span>
            <span>2. Map Columns</span>
        </div>
        <div class="p-3 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 inline-flex items-center justify-center font-bold">3</span>
            <span>3. Validate, Preview & Commit</span>
        </div>
    </div>

    @if($errors->any())
        <div role="alert" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm">
            <p class="font-semibold mb-1">
                <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>
                Please check your column mapping:
            </p>
            <ul class="list-disc list-inside text-xs space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    @php
        $firstRowRaw = $sampleRows->first()?->raw ?? [];
    @endphp

    <form method="POST" action="{{ route('admin.imports.save-map', $batch) }}" class="space-y-6">
        @csrf

        <x-ui.card title="1. Column Mapping (Source Column → Target System Field)">
            <x-ui.table>
                <x-slot:header>
                    <tr class="bg-indigo-50/70 text-xs uppercase tracking-wider text-slate-700">
                        <th class="py-3 px-4 w-[38%]">System Target Field</th>
                        <th class="py-3 px-4 w-[34%]">Source Spreadsheet Column</th>
                        <th class="py-3 px-4 w-[28%]">Row 1 Sample Value</th>
                    </tr>
                </x-slot:header>

                @foreach($targetFields as $fieldKey => $meta)
                    @php
                        $selectedHeader = old("mapping.{$fieldKey}", $currentMapping[$fieldKey] ?? '');
                        $sampleVal = ($selectedHeader !== '' && isset($firstRowRaw[$selectedHeader]))
                            ? $firstRowRaw[$selectedHeader]
                            : '—';
                    @endphp
                    <tr class="hover:bg-slate-50">
                        <td class="py-3 px-4">
                            <label for="map_{{ $fieldKey }}" class="font-semibold text-slate-800 text-sm block">
                                {{ $meta['label'] }}
                                @if($meta['required'])
                                    <span class="text-red-600 font-bold">*</span>
                                @endif
                            </label>
                            <span class="text-xs text-slate-500 font-mono">{{ $fieldKey }}</span>
                        </td>
                        <td class="py-3 px-4">
                            <select
                                id="map_{{ $fieldKey }}"
                                name="mapping[{{ $fieldKey }}]"
                                @if($meta['required']) required @endif
                                class="w-full h-10 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                            >
                                <option value="">{{ $meta['required'] ? '— Select required column —' : '— Not mapped (optional) —' }}</option>
                                @foreach($headers as $header)
                                    <option value="{{ $header }}" @selected($selectedHeader === $header)>
                                        {{ $header }}
                                    </option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-3 px-4 text-xs text-slate-600 font-mono truncate max-w-xs">
                            {{ \Illuminate\Support\Str::limit((string) $sampleVal, 60) }}
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="2. Legacy Status Mapping">
            <p class="text-xs text-slate-600 mb-4">
                Map legacy status labels from the old file to canonical Katarungang Pambarangay statuses. Any unknown or unmapped status automatically defaults to <strong>Filed</strong> with a recorded internal note preserving the original status text. Blotter-only rows always stay at <strong>Filed</strong>.
            </p>

            @php
                $statusKeysToRender = collect($distinctRawStatuses)
                    ->map(fn ($s) => strtolower(trim($s)))
                    ->merge(['filed', 'in mediation', 'in conciliation', 'settled', 'unsettled', 'escalated', 'dismissed', 'withdrawn'])
                    ->filter()
                    ->unique()
                    ->values();
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                @foreach($statusKeysToRender as $rawStatusKey)
                    @php
                        $mappedVal = old("status_mapping.{$rawStatusKey}", $currentStatusMap[$rawStatusKey] ?? '');
                    @endphp
                    <div class="flex items-center justify-between gap-3 p-3 rounded-lg border border-slate-200 bg-slate-50">
                        <div class="text-xs">
                            <span class="text-slate-500 block">Legacy value:</span>
                            <strong class="font-mono text-slate-900">{{ $rawStatusKey }}</strong>
                        </div>
                        <div class="w-48">
                            <select
                                name="status_mapping[{{ $rawStatusKey }}]"
                                class="w-full h-9 px-2.5 py-1 text-xs border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                            >
                                <option value="">Default to Filed (+ note)</option>
                                @foreach($caseStatuses as $statusSlug => $statusLabel)
                                    <option value="{{ $statusSlug }}" @selected($mappedVal === $statusSlug)>
                                        {{ $statusLabel }} ({{ $statusSlug }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <x-ui.card title="3. Save Mapping Template (Optional)">
            <div class="max-w-md">
                <label for="save_mapping_name" class="block text-sm font-semibold text-slate-800 mb-1">
                    Save this column & status mapping as a preset
                </label>
                <input
                    type="text"
                    id="save_mapping_name"
                    name="save_mapping_name"
                    value="{{ old('save_mapping_name', $batch->source_name) }}"
                    placeholder="e.g. Old Hall Logbook Excel 2023-2025"
                    class="w-full h-10 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                >
                <p class="text-xs text-slate-500 mt-1">
                    Leave blank if you do not need to save this mapping for future uploads.
                </p>
            </div>
        </x-ui.card>

        <div class="sticky bottom-0 bg-white border-t border-slate-200 p-4 rounded-lg shadow-lg flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
            <x-ui.button variant="ghost" href="{{ route('admin.imports.create') }}">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                <span>Back to Upload</span>
            </x-ui.button>
            <x-ui.button type="submit" variant="primary">
                <span>Validate & Preview Rows</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
