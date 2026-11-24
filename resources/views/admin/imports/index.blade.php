@extends('layouts.admin')

@section('title', 'Legacy File Imports')
@section('page-heading', 'Legacy File Imports')

@section('content')
<div class="max-w-7xl mx-auto space-y-6">
    <x-ui.page-header
        title="Legacy Excel / CSV File Imports"
        subtitle="Import historical cases, blotter entries, and parties across the last {{ count($allowedYears) - 1 }} record years ({{ min($allowedYears) }}–{{ max($allowedYears) }}) without consuming active numbering sequences."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('admin.imports.template', ['format' => 'csv']) }}" data-turbo="false">
                <i class="fa-solid fa-file-csv" aria-hidden="true"></i>
                <span>CSV Template</span>
            </x-ui.button>
            <x-ui.button variant="secondary" href="{{ route('admin.imports.template') }}" data-turbo="false">
                <i class="fa-solid fa-file-excel text-emerald-700" aria-hidden="true"></i>
                <span>Download Blank Template (.xlsx)</span>
            </x-ui.button>
            <x-ui.button variant="primary" href="{{ route('admin.imports.create') }}">
                <i class="fa-solid fa-file-import" aria-hidden="true"></i>
                <span>+ New Legacy Import</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 text-sm text-blue-900">
        <div class="flex items-start gap-3">
            <i class="fa-solid fa-shield-halved text-blue-700 mt-0.5" aria-hidden="true"></i>
            <div>
                <p class="font-semibold">Data Privacy & Docket Integrity Safeguards (RA 10173)</p>
                <p class="text-xs text-blue-800 mt-0.5">
                    Files are stored strictly on the private disk and processed locally without AI or OCR. Original docket numbers are preserved verbatim, active sequences remain untouched, and historical fiscal years are automatically locked upon batch completion.
                </p>
            </div>
        </div>
        @if(!config('features.legacy_import'))
            <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-900 border border-amber-300 shrink-0">
                <i class="fa-solid fa-flask" aria-hidden="true"></i>
                Stretch Preview Mode
            </span>
        @endif
    </div>

    <x-ui.card title="Import Batch History">
        @if($batches->isEmpty())
            <x-ui.empty-state
                title="No legacy import batches yet"
                subtitle="Download the blank Excel template or upload an exported Excel/CSV file from the barangay's historical records."
                icon="fa-solid fa-file-Arrow-up"
            />
        @else
            <x-ui.table>
                <x-slot:header>
                    <tr class="bg-indigo-50/70 text-xs uppercase tracking-wider text-slate-700">
                        <th class="py-3 px-4 w-[8%]">Batch #</th>
                        <th class="py-3 px-4 w-[24%]">File & Source Preset</th>
                        <th class="py-3 px-4 w-[12%]">Target Year</th>
                        <th class="py-3 px-4 w-[18%]">Row Summary</th>
                        <th class="py-3 px-4 w-[12%]">Status</th>
                        <th class="py-3 px-4 w-[14%]">Uploaded By</th>
                        <th class="py-3 px-4 w-[12%] text-right">Actions</th>
                    </tr>
                </x-slot:header>

                @foreach($batches as $batch)
                    @php
                        $stats = $batch->stats ?? [];
                        $statusColors = [
                            'uploaded' => 'slate',
                            'mapped' => 'blue',
                            'previewed' => 'amber',
                            'committed' => 'green',
                            'rolled_back' => 'red',
                        ];
                        $badgeColor = $statusColors[$batch->status] ?? 'slate';
                    @endphp
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td data-label="Batch #" class="py-3 px-4 font-mono font-semibold text-slate-900">
                            #{{ $batch->id }}
                        </td>
                        <td data-label="File & Source Preset" class="py-3 px-4">
                            <div class="font-medium text-slate-900">{{ $batch->filename }}</div>
                            @if($batch->source_name)
                                <div class="text-xs text-slate-500 mt-0.5">
                                    Preset: <span class="font-mono">{{ $batch->source_name }}</span>
                                </div>
                            @endif
                        </td>
                        <td data-label="Target Year" class="py-3 px-4 text-sm text-slate-700">
                            {{ $batch->target_fiscal_year ? 'FY '.$batch->target_fiscal_year : 'From Filed Date' }}
                        </td>
                        <td data-label="Row Summary" class="py-3 px-4 text-xs text-slate-600 space-y-0.5">
                            <div>Total: <strong class="text-slate-900">{{ $stats['total_rows'] ?? 0 }}</strong></div>
                            <div class="flex flex-wrap gap-2">
                                <span class="text-emerald-700">Valid: {{ $stats['valid'] ?? 0 }}</span>
                                <span class="text-amber-700">Warn: {{ $stats['warnings'] ?? 0 }}</span>
                                <span class="text-red-700">Conflicts: {{ $stats['conflicts'] ?? 0 }}</span>
                                @if(($stats['imported'] ?? 0) > 0)
                                    <span class="text-blue-700 font-semibold">Imported: {{ $stats['imported'] }}</span>
                                @endif
                            </div>
                        </td>
                        <td data-label="Status" class="py-3 px-4">
                            <x-ui.badge :color="$badgeColor" :dot="true">
                                {{ ucfirst(str_replace('_', ' ', $batch->status)) }}
                            </x-ui.badge>
                        </td>
                        <td data-label="Uploaded By" class="py-3 px-4 text-xs text-slate-600">
                            <div class="font-medium text-slate-800">{{ $batch->user?->name ?? 'System' }}</div>
                            <div>{{ $batch->created_at?->format('Y-m-d H:i') }}</div>
                        </td>
                        <td data-label="Actions" class="py-3 px-4 text-right">
                            <div class="inline-flex items-center justify-end gap-2">
                                @if($batch->status === 'uploaded')
                                    <x-ui.button size="sm" variant="primary" href="{{ route('admin.imports.map', $batch) }}">
                                        Map Columns
                                    </x-ui.button>
                                @else
                                    <x-ui.button size="sm" variant="secondary" href="{{ route('admin.imports.preview', $batch) }}">
                                        View / Preview
                                    </x-ui.button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </x-ui.table>

            @if($batches->hasPages())
                <div class="mt-4">
                    {{ $batches->links() }}
                </div>
            @endif
        @endif
    </x-ui.card>
</div>
@endsection
