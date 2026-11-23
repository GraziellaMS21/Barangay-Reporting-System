@extends('layouts.admin')

@section('title', $template->title)
@section('page-heading', 'Reports Center')

@section('content')
<x-ui.page-header :title="$template->title" :subtitle="$template->description">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('admin.reports-center.index')">
            <i class="fa-solid fa-arrow-left"></i> All Reports
        </x-ui.button>
        <x-ui.button variant="secondary" :href="route('admin.reports-center.history')">
            <i class="fa-solid fa-clock-rotate-left"></i> History
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<x-ui.card class="mb-6">
    <form method="GET" action="{{ route('admin.reports-center.show', $template->key) }}" class="space-y-4">
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4">
            <x-ui.form-field label="Fiscal Year" name="fiscal_year_id">
                <select name="fiscal_year_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                    <option value="all" @selected(($filters['fiscal_year_id'] ?? '') === 'all')>All Fiscal Years</option>
                    @foreach($filterOptions['fiscalYears'] as $fy)
                        <option value="{{ $fy->id }}" @selected((string) ($filters['fiscal_year_id'] ?? $fiscal_year?->id) === (string) $fy->id)>
                            {{ $fy->label }} ({{ $fy->year }})
                        </option>
                    @endforeach
                </select>
            </x-ui.form-field>

            @if($template->key === 'kp_accomplishment')
                <x-ui.form-field label="Month (Optional)" name="month">
                    <select name="month" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Full Year (Annual)</option>
                        @foreach($filterOptions['months'] as $mNum => $mName)
                            <option value="{{ $mNum }}" @selected((string) ($filters['month'] ?? '') === (string) $mNum)>{{ $mName }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            @endif

            @if($template->key === 'year_over_year')
                <x-ui.form-field label="Compare Against Fiscal Year" name="compare_fiscal_year_id">
                    <select name="compare_fiscal_year_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">Previous Fiscal Year (Auto)</option>
                        @foreach($filterOptions['fiscalYears'] as $fy)
                            <option value="{{ $fy->id }}" @selected((string) ($filters['compare_fiscal_year_id'] ?? '') === (string) $fy->id)>
                                {{ $fy->label }} ({{ $fy->year }})
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            @endif

            @if(in_array($template->key, ['case_summary', 'cases_summary', 'purok_category_matrix', 'problem_reports_summary', 'referral_register', 'referrals_status', 'summons_log'], true))
                <x-ui.form-field label="Date From" name="date_from">
                    <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </x-ui.form-field>
                <x-ui.form-field label="Date To" name="date_to">
                    <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}"
                           class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                </x-ui.form-field>
            @endif

            @if(in_array($template->key, ['case_summary', 'cases_summary', 'summons_log'], true))
                <x-ui.form-field label="Case Type" name="case_type_id">
                    <select name="case_type_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Case Types</option>
                        @foreach($filterOptions['caseTypes'] as $ct)
                            <option value="{{ $ct->id }}" @selected((string) ($filters['case_type_id'] ?? '') === (string) $ct->id)>{{ $ct->name }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            @endif

            @if(in_array($template->key, ['case_summary', 'problem_reports_summary'], true))
                <x-ui.form-field label="Purok" name="purok_id">
                    <select name="purok_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Puroks</option>
                        @foreach($filterOptions['puroks'] as $purok)
                            <option value="{{ $purok->id }}" @selected((string) ($filters['purok_id'] ?? '') === (string) $purok->id)>{{ $purok->name }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            @endif

            @if($template->key === 'problem_reports_summary')
                <x-ui.form-field label="Category" name="category_id">
                    <select name="category_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Categories</option>
                        @foreach($filterOptions['categories'] as $cat)
                            <option value="{{ $cat->id }}" @selected((string) ($filters['category_id'] ?? '') === (string) $cat->id)>{{ $cat->name }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            @endif

            @if(in_array($template->key, ['referral_register', 'referrals_status'], true))
                <x-ui.form-field label="Office / Agency" name="office_id">
                    <select name="office_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Offices</option>
                        @foreach($filterOptions['offices'] as $off)
                            <option value="{{ $off->id }}" @selected((string) ($filters['office_id'] ?? '') === (string) $off->id)>{{ $off->name }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            @endif

            @if($template->key === 'personnel_list')
                <x-ui.form-field label="Position" name="position_id">
                    <select name="position_id" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm">
                        <option value="">All Positions</option>
                        @foreach($filterOptions['positions'] as $pos)
                            <option value="{{ $pos->id }}" @selected((string) ($filters['position_id'] ?? '') === (string) $pos->id)>{{ $pos->name }}</option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            @endif
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3 pt-3 border-t border-slate-100">
            <x-ui.button variant="ghost" :href="route('admin.reports-center.show', $template->key)">
                Reset Filters
            </x-ui.button>

            <div class="flex flex-wrap items-center gap-2">
                <x-ui.button type="submit" variant="secondary">
                    <i class="fa-solid fa-rotate"></i> Preview
                </x-ui.button>

                @can('reports_center.export')
                    <x-ui.button type="submit"
                                 variant="secondary"
                                 formaction="{{ route('admin.reports-center.export', $template->key) }}"
                                 formmethod="POST"
                                 name="format"
                                 value="pdf">
                        <i class="fa-solid fa-file-pdf"></i> Export PDF
                    </x-ui.button>

                    <x-ui.button type="submit"
                                 variant="secondary"
                                 formaction="{{ route('admin.reports-center.export', $template->key) }}"
                                 formmethod="POST"
                                 name="format"
                                 value="xlsx">
                        <i class="fa-solid fa-file-excel"></i> Export Excel
                    </x-ui.button>

                    <x-ui.button type="submit"
                                 variant="secondary"
                                 formaction="{{ route('admin.reports-center.export', $template->key) }}"
                                 formmethod="POST"
                                 name="format"
                                 value="csv">
                        <i class="fa-solid fa-file-csv"></i> Export CSV
                    </x-ui.button>
                @endcan

                <x-ui.button variant="primary"
                             :href="route('admin.reports-center.show', array_merge(['key' => $template->key, 'print' => 1], $filters))"
                             target="_blank">
                    <i class="fa-solid fa-print"></i> Print
                </x-ui.button>
            </div>
        </div>
        @csrf
    </form>
</x-ui.card>

<!-- Pattern H Document Preview Panel -->
<div class="bg-white rounded-2xl shadow-md border border-slate-200 p-8 max-w-6xl mx-auto">
    <div class="flex items-center justify-between flex-wrap gap-2 mb-4">
        <x-ui.draft-watermark :confirmed="$template->is_confirmed" />
        <span class="text-xs text-slate-500">Paper Size: {{ strtoupper($paperSize) }} · Default Output: {{ strtoupper($defaultFormat) }}</span>
    </div>

    <div class="text-center border-b-2 border-slate-900 pb-4 mb-6">
        <p class="text-xs uppercase tracking-widest text-slate-600">Republic of the Philippines · {{ setting('barangay.city', 'Zamboanga City') }}</p>
        <h2 class="text-xl font-bold uppercase text-slate-900 mt-0.5">{{ setting('barangay.name', 'Barangay Calarian') }}</h2>
        <p class="text-xs text-slate-500">Office of the Punong Barangay / Lupong Tagapamayapa</p>
        <h3 class="text-base font-bold uppercase text-slate-900 mt-4">{{ $template->title }}</h3>
        <p class="text-xs text-slate-500 mt-1">
            @foreach($applied_filters as $fLabel => $fVal)
                <span><strong>{{ $fLabel }}:</strong> {{ $fVal }}</span>@if(! $loop->last) · @endif
            @endforeach
            · Generated {{ now()->format('M d, Y h:i A') }}
        </p>
    </div>

    @if(!empty($summary))
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
            @foreach($summary as $sLabel => $sValue)
                <div class="rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">
                    <span class="text-xs text-slate-500 block">{{ $sLabel }}</span>
                    <strong class="text-lg font-bold text-slate-900">{{ $sValue }}</strong>
                </div>
            @endforeach
        </div>
    @endif

    <div class="overflow-x-auto">
        <table class="min-w-full border-collapse border border-slate-300 text-xs">
            <thead class="bg-indigo-50 text-slate-800">
                <tr>
                    @foreach($columns as $col)
                        <th class="border border-slate-300 px-3 py-2 font-bold text-{{ $col['align'] ?? 'left' }}">
                            {{ $col['label'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="hover:bg-slate-50">
                        @foreach($columns as $col)
                            <td class="border border-slate-300 px-3 py-2 text-{{ $col['align'] ?? 'left' }}">
                                {{ $row[$col['key']] ?? '—' }}
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ count($columns) }}" class="border border-slate-300 px-4 py-6 text-center text-slate-500">
                            No records found for the selected parameters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if(!empty($totals))
                <tfoot class="bg-slate-100 font-bold text-slate-900">
                    <tr>
                        @foreach($columns as $col)
                            <td class="border border-slate-300 px-3 py-2 text-{{ $col['align'] ?? 'left' }}">
                                {{ $totals[$col['key']] ?? '' }}
                            </td>
                        @endforeach
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>

    <!-- Signatory Block -->
    <div class="mt-10 pt-6 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
        @forelse($signatories as $sig)
            <div class="text-center space-y-1">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $sig['label'] ?? 'Prepared by' }}</p>
                <div class="h-14 flex items-center justify-center">
                    @if(!empty($sig['signature_data_uri']))
                        <img src="{{ $sig['signature_data_uri'] }}" alt="Signature" class="max-h-12 max-w-full object-contain">
                    @else
                        <div class="w-40 border-b border-slate-400"></div>
                    @endif
                </div>
                <p class="text-sm font-bold text-slate-900 uppercase">{{ $sig['name'] ?? '____________________' }}</p>
                <p class="text-xs text-slate-600">{{ $sig['position'] ?? 'Barangay Official' }}</p>
            </div>
        @empty
            <div class="text-center space-y-1">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Prepared by</p>
                <div class="h-14 flex items-end justify-center"><div class="w-44 border-b border-slate-400"></div></div>
                <p class="text-xs text-slate-600">Barangay Secretary</p>
            </div>
            <div class="text-center space-y-1">
                <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">Approved by</p>
                <div class="h-14 flex items-end justify-center"><div class="w-44 border-b border-slate-400"></div></div>
                <p class="text-xs text-slate-600">Punong Barangay</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
