@extends('layouts.admin')

@section('title', 'Preview & Commit — Batch #'.$batch->id)
@section('page-heading', 'Legacy Import Wizard')

@section('content')
@php
    $stats = $batch->stats ?? [];
    $isCommitted = $batch->status === 'committed';
    $isRolledBack = $batch->status === 'rolled_back';
@endphp

<div class="max-w-7xl mx-auto space-y-6">
    <div class="text-xs text-slate-500 flex items-center gap-2">
        <a href="{{ route('admin.imports.index') }}" class="hover:text-blue-700">Legacy File Imports</a>
        <span>/</span>
        <span>Batch #{{ $batch->id }} ({{ $batch->filename }})</span>
        <span>/</span>
        <span class="text-slate-800 font-medium">Step 3 of 3: Validation, Duplicate Preview & Commit</span>
    </div>

    <x-ui.page-header
        title="Batch #{{ $batch->id }}: {{ $batch->filename }}"
        subtitle="Review validated rows, case number conflicts, 30-day duplicate case warnings, and likely party matches before committing."
    >
        <x-slot:actions>
            @if(!$isCommitted)
                <x-ui.button variant="secondary" href="{{ route('admin.imports.map', $batch) }}">
                    <i class="fa-solid fa-sliders" aria-hidden="true"></i>
                    <span>Adjust Column Mapping</span>
                </x-ui.button>
            @endif
            <x-ui.button variant="secondary" href="{{ route('admin.imports.index') }}">
                <span>All Batches</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Summary KPI Tiles -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
        <div class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
            <span class="text-xs text-slate-500 font-medium">Total Rows</span>
            <div class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['total_rows'] ?? 0 }}</div>
        </div>
        <div class="bg-emerald-50/70 rounded-lg border border-emerald-200 p-4 shadow-sm">
            <span class="text-xs text-emerald-800 font-medium">Valid (Ready)</span>
            <div class="text-2xl font-bold text-emerald-800 mt-1">{{ $stats['valid'] ?? 0 }}</div>
        </div>
        <div class="bg-amber-50/70 rounded-lg border border-amber-200 p-4 shadow-sm">
            <span class="text-xs text-amber-800 font-medium">Warnings / Likely Duplicates</span>
            <div class="text-2xl font-bold text-amber-800 mt-1">{{ $stats['warnings'] ?? 0 }}</div>
        </div>
        <div class="bg-red-50/70 rounded-lg border border-red-200 p-4 shadow-sm">
            <span class="text-xs text-red-800 font-medium">Conflicts / Errors</span>
            <div class="text-2xl font-bold text-red-800 mt-1">
                {{ $stats['errors'] ?? 0 }}
                @if(($stats['conflicts'] ?? 0) > 0)
                    <span class="text-xs font-normal">({{ $stats['conflicts'] }} conflicts)</span>
                @endif
            </div>
        </div>
        <div class="bg-blue-50/70 rounded-lg border border-blue-200 p-4 shadow-sm">
            <span class="text-xs text-blue-800 font-medium">Committed / Imported</span>
            <div class="text-2xl font-bold text-blue-800 mt-1">{{ $stats['imported'] ?? 0 }}</div>
        </div>
    </div>

    @if($isCommitted)
        <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="text-sm text-emerald-950">
                <p class="font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-700" aria-hidden="true"></i>
                    <span>Batch #{{ $batch->id }} was committed on {{ $batch->committed_at?->format('Y-m-d H:i') }}</span>
                </p>
                <p class="text-xs text-emerald-800 mt-1">
                    Imported <strong>{{ $stats['imported'] ?? 0 }}</strong> historical case(s), skipped <strong>{{ $stats['skipped'] ?? 0 }}</strong> row(s), created <strong>{{ $stats['created_parties'] ?? 0 }}</strong> new party profile(s), and linked <strong>{{ $stats['linked_parties'] ?? 0 }}</strong> exact existing party profile(s).
                    @if(!empty($stats['historical_years_closed']))
                        Historical fiscal year(s) automatically closed & locked: <strong>FY {{ implode(', FY ', $stats['historical_years_closed']) }}</strong>.
                    @endif
                </p>
            </div>
            <x-ui.button
                variant="danger"
                onclick="document.getElementById('rollback-batch-modal').showModal()"
            >
                <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                <span>Roll Back Entire Batch</span>
            </x-ui.button>
        </div>
    @elseif($isRolledBack)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-sm text-red-900">
            <p class="font-semibold flex items-center gap-2">
                <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                <span>Batch #{{ $batch->id }} was rolled back on {{ $batch->rolled_back_at?->format('Y-m-d H:i') }} by {{ $batch->rolledBackBy?->name ?? 'Administrator' }}</span>
            </p>
            <p class="text-xs mt-1">Reason: {{ $batch->rollback_note }}</p>
        </div>
    @endif

    <!-- Filter Tabs -->
    <div class="flex flex-wrap items-center gap-2 border-b border-slate-200 pb-3 text-xs font-semibold">
        @php
            $tabs = [
                'all' => 'All Rows ('.($stats['total_rows'] ?? 0).')',
                'valid' => 'Valid ('.($stats['valid'] ?? 0).')',
                'warning' => 'Warnings & Duplicates ('.($stats['warnings'] ?? 0).')',
                'error' => 'Conflicts & Errors ('.($stats['errors'] ?? 0).')',
                'imported' => 'Imported ('.($stats['imported'] ?? 0).')',
                'skipped' => 'Skipped ('.($stats['skipped'] ?? 0).')',
            ];
        @endphp
        @foreach($tabs as $tabKey => $tabLabel)
            <a
                href="{{ route('admin.imports.preview', ['batch' => $batch->id, 'status' => $tabKey]) }}"
                @class([
                    'px-3 py-1.5 rounded-lg transition-colors',
                    'bg-blue-600 text-white' => $statusFilter === $tabKey,
                    'bg-white text-slate-700 border border-slate-200 hover:bg-slate-50' => $statusFilter !== $tabKey,
                ])
            >
                {{ $tabLabel }}
            </a>
        @endforeach
    </div>

    <form method="POST" action="{{ route('admin.imports.commit', $batch) }}" class="space-y-6">
        @csrf

        <x-ui.card title="Parsed Rows & Duplicate / Conflict Review">
            <x-ui.table>
                <x-slot:header>
                    <tr class="bg-indigo-50/70 text-xs uppercase tracking-wider text-slate-700">
                        <th class="py-3 px-4 w-[6%]">Row</th>
                        <th class="py-3 px-4 w-[16%]">Case No. & Type</th>
                        <th class="py-3 px-4 w-[12%]">Filed & FY</th>
                        <th class="py-3 px-4 w-[19%]">Complainant</th>
                        <th class="py-3 px-4 w-[19%]">Respondent</th>
                        <th class="py-3 px-4 w-[10%]">Status</th>
                        <th class="py-3 px-4 w-[18%]">Validation & Duplicate Flags</th>
                    </tr>
                </x-slot:header>

                @forelse($rows as $row)
                    @php
                        $mapped = $row->mapped ?? [];
                        $comp = $mapped['complainant'] ?? [];
                        $resp = $mapped['respondent'] ?? [];
                        $rowErrors = $row->errors ?? [];

                        $rowBadgeColors = [
                            'valid' => 'green',
                            'warning' => 'amber',
                            'error' => 'red',
                            'imported' => 'blue',
                            'skipped' => 'slate',
                            'pending' => 'slate',
                        ];
                    @endphp
                    <tr class="hover:bg-slate-50 align-top">
                        <td data-label="Row" class="py-3 px-4 font-mono text-xs text-slate-600">
                            <div>#{{ $row->row_number }}</div>
                            @if(!$isCommitted && in_array($row->status, ['valid', 'warning'], true))
                                <label class="inline-flex items-center gap-1 mt-1 text-[11px] text-slate-500 cursor-pointer" title="Check to skip this row during commit">
                                    <input type="checkbox" name="skip_row_ids[]" value="{{ $row->id }}" class="rounded border-slate-300">
                                    <span>Skip</span>
                                </label>
                            @endif
                        </td>
                        <td data-label="Case No. & Type" class="py-3 px-4">
                            <div class="font-mono font-bold text-slate-900 text-sm">
                                {{ $mapped['case_number'] ?: '— Missing —' }}
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                <span class="uppercase font-semibold">{{ $mapped['record_type'] ?? 'complaint' }}</span>
                                · {{ $mapped['case_type'] ?? 'General' }}
                            </div>
                        </td>
                        <td data-label="Filed & FY" class="py-3 px-4 text-xs text-slate-700">
                            <div class="font-medium">{{ $mapped['filed_on'] ?? 'Invalid' }}</div>
                            @if(!empty($mapped['fiscal_year']))
                                <span class="inline-block mt-0.5 px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-semibold">
                                    FY {{ $mapped['fiscal_year'] }}
                                </span>
                            @endif
                        </td>
                        <td data-label="Complainant" class="py-3 px-4 text-xs">
                            <div class="font-semibold text-slate-900">{{ $comp['full_name'] ?? '—' }}</div>
                            @if(!empty($comp['exact_party_id']))
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-medium mt-0.5">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                                    Links to Party #{{ $comp['exact_party_id'] }}
                                </span>
                            @elseif(!empty($comp['likely_matches']))
                                <span class="inline-flex items-center gap-1 text-amber-800 font-medium mt-0.5">
                                    <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                    Likely match (review list, won't auto-merge)
                                </span>
                            @endif
                        </td>
                        <td data-label="Respondent" class="py-3 px-4 text-xs">
                            <div class="font-semibold text-slate-900">{{ $resp['full_name'] ?? '—' }}</div>
                            @if(!empty($resp['exact_party_id']))
                                <span class="inline-flex items-center gap-1 text-emerald-700 font-medium mt-0.5">
                                    <i class="fa-solid fa-link" aria-hidden="true"></i>
                                    Links to Party #{{ $resp['exact_party_id'] }}
                                </span>
                            @elseif(!empty($resp['likely_matches']))
                                <span class="inline-flex items-center gap-1 text-amber-800 font-medium mt-0.5">
                                    <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                                    Likely match (review list, won't auto-merge)
                                </span>
                            @endif
                        </td>
                        <td data-label="Status" class="py-3 px-4 text-xs">
                            <x-ui.badge :color="$rowBadgeColors[$row->status] ?? 'slate'" :dot="true">
                                {{ ucfirst($row->status) }}
                            </x-ui.badge>
                            <div class="text-[11px] text-slate-500 mt-1">
                                Case: <strong>{{ $caseStatusLabels[$mapped['status'] ?? 'filed'] ?? 'Filed' }}</strong>
                            </div>
                        </td>
                        <td data-label="Validation & Duplicate Flags" class="py-3 px-4 text-xs">
                            @if(empty($rowErrors))
                                <span class="text-emerald-700 font-medium">
                                    <i class="fa-solid fa-check mr-1" aria-hidden="true"></i>Ready to import
                                </span>
                            @else
                                <ul class="space-y-1">
                                    @foreach($rowErrors as $msg)
                                        <li @class([
                                            'p-1.5 rounded border text-[11px] leading-tight',
                                            'bg-red-50 border-red-200 text-red-800' => $row->status === 'error',
                                            'bg-amber-50 border-amber-200 text-amber-900' => $row->status !== 'error',
                                        ])>
                                            {{ $msg }}
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-sm text-slate-500">
                            No rows match the selected filter.
                        </td>
                    </tr>
                @endforelse
            </x-ui.table>

            @if($rows->hasPages())
                <div class="mt-4">
                    {{ $rows->links() }}
                </div>
            @endif
        </x-ui.card>

        @if(!$isCommitted)
            <div class="sticky bottom-0 bg-white border-t border-slate-200 p-4 rounded-lg shadow-lg flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                <div class="space-y-2 text-xs text-slate-700">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="include_warnings" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span><strong>Import rows with warnings</strong> (possible 30-day case duplicates, likely party matches, and unmapped legacy statuses). Conflict/error rows are always skipped.</span>
                    </label>
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="delete_source_file" value="1" checked class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span><strong>Delete uploaded source spreadsheet from private disk</strong> after commit completes (Rule 7 privacy cleanup).</span>
                    </label>
                </div>

                <div class="flex items-center justify-end gap-3 shrink-0">
                    <x-ui.button variant="ghost" href="{{ route('admin.imports.map', $batch) }}">
                        Back to Mapping
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        <i class="fa-solid fa-cloud-arrow-up" aria-hidden="true"></i>
                        <span>Commit Batch ({{ ($stats['valid'] ?? 0) + ($stats['warnings'] ?? 0) }} eligible rows)</span>
                    </x-ui.button>
                </div>
            </div>
        @endif
    </form>
</div>

@if($isCommitted)
    <x-ui.modal id="rollback-batch-modal" title="Roll Back Entire Import Batch #{{ $batch->id }}">
        <form method="POST" action="{{ route('admin.imports.rollback', $batch) }}" class="space-y-4">
            @csrf
            <p class="text-sm text-slate-700">
                Rolling back this batch will permanently remove all <strong>{{ $stats['imported'] ?? 0 }}</strong> imported case record(s) from this batch and any newly created party records that have no other case links. Existing pre-import records and numbering sequences will remain untouched.
            </p>
            <div>
                <label for="rollback_note" class="block text-sm font-semibold text-slate-800 mb-1">
                    Reason for Rollback <span class="text-red-600">*</span>
                </label>
                <textarea
                    id="rollback_note"
                    name="rollback_note"
                    rows="3"
                    required
                    minlength="5"
                    maxlength="500"
                    placeholder="Explain why this batch is being rolled back (5–500 characters)..."
                    class="w-full p-3 text-sm border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-red-600"
                ></textarea>
            </div>
            <div class="flex items-center justify-end gap-3 pt-2">
                <x-ui.button type="button" variant="ghost" onclick="document.getElementById('rollback-batch-modal').close()">
                    Cancel
                </x-ui.button>
                <x-ui.button type="submit" variant="danger">
                    Confirm Rollback
                </x-ui.button>
            </div>
        </form>
    </x-ui.modal>
@endif
@endsection
