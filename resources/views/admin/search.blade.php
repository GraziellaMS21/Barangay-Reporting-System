@extends('layouts.admin')

@section('title', "Search Results: {$q}")
@section('page-heading', 'Global Search Results')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Search Results: &ldquo;{{ $q }}&rdquo;"
        subtitle="Found {{ $totalFound }} matching record(s) across all fiscal years, dockets, and registries." />

    @if($totalFound === 0)
        <x-ui.card>
            <div class="py-12 text-center">
                <i class="fa-solid fa-magnifying-glass text-slate-300 text-4xl mb-3"></i>
                <h3 class="text-base font-bold text-slate-800">No records found matching &ldquo;{{ $q }}&rdquo;</h3>
                <p class="text-xs text-slate-500 mt-1 max-w-md mx-auto">
                    Try searching by a full or partial case number, tracking code, complainant or respondent name, or referral ID.
                </p>
            </div>
        </x-ui.card>
    @else
        <!-- 1. Barangay Cases -->
        @if(!empty($results['cases']))
            <x-ui.card title="Barangay Cases & Blotter Records ({{ count($results['cases']) }})">
                <div class="divide-y divide-slate-100">
                    @foreach($results['cases'] as $case)
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-xs text-blue-700">{{ $case['case_number'] }}</span>
                                    @if(!empty($case['fiscal_year']))
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-semibold">
                                            {{ $case['fiscal_year']['label'] ?? 'FY ' . $case['fiscal_year']['year'] }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700">
                                        {{ ucwords(str_replace('_', ' ', $case['status'])) }}
                                    </span>
                                </div>
                                <h4 class="text-sm font-semibold text-slate-900 mt-1">{{ $case['title'] }}</h4>
                                <p class="text-xs text-slate-500 mt-0.5 line-clamp-1">{{ $case['narrative'] }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <span class="text-xs text-slate-400 block">{{ $case['filed_on'] ?? '' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        <!-- 2. Problem Reports -->
        @if(!empty($results['reports']))
            <x-ui.card title="Civil Incident Reports ({{ count($results['reports']) }})">
                <div class="divide-y divide-slate-100">
                    @foreach($results['reports'] as $report)
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.reports.show', $report['id']) }}" class="font-mono font-bold text-xs text-blue-600 hover:underline">
                                        {{ $report['tracking_code'] }}
                                    </a>
                                    @if(!empty($report['fiscal_year']))
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-semibold">
                                            {{ $report['fiscal_year']['label'] ?? 'FY ' . $report['fiscal_year']['year'] }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-slate-100 text-slate-700">
                                        {{ ucwords(str_replace('_', ' ', $report['status'])) }}
                                    </span>
                                    @if(!empty($report['category']))
                                        <span class="text-xs text-slate-500 font-medium">{{ $report['category']['name'] }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-600 mt-1 line-clamp-2">{{ $report['description'] }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <a href="{{ route('admin.reports.show', $report['id']) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                    View Report &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        <!-- 3. External Referrals -->
        @if(!empty($results['referrals']))
            <x-ui.card title="External Referrals ({{ count($results['referrals']) }})">
                <div class="divide-y divide-slate-100">
                    @foreach($results['referrals'] as $ref)
                        <div class="py-3.5 flex items-start justify-between gap-4">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-mono font-bold text-xs text-blue-700">{{ $ref['reference_no'] }}</span>
                                    @if(!empty($ref['fiscal_year']))
                                        <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] font-semibold">
                                            {{ $ref['fiscal_year']['label'] ?? 'FY ' . $ref['fiscal_year']['year'] }}
                                        </span>
                                    @endif
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold bg-emerald-50 text-emerald-700">
                                        {{ ucwords(str_replace('_', ' ', $ref['status'])) }}
                                    </span>
                                    @if(!empty($ref['office']))
                                        <span class="text-xs font-semibold text-slate-700">{{ $ref['office']['name'] }}</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-1 line-clamp-1">{{ $ref['notes'] ?? 'Referral transmittal' }}</p>
                            </div>
                            <div class="text-right shrink-0">
                                <a href="{{ route('admin.referrals.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
                                    Directory &rarr;
                                </a>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        <!-- 4. Parties & Citizens -->
        @if(!empty($results['parties']))
            <x-ui.card title="Master Parties & Citizen Directory ({{ count($results['parties']) }})">
                <div class="divide-y divide-slate-100">
                    @foreach($results['parties'] as $party)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ $party['full_name'] }}</span>
                                    @if(!empty($party['alias']))
                                        <span class="text-xs text-slate-500 italic">({{ $party['alias'] }})</span>
                                    @endif
                                    <span class="px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 text-[10px] uppercase font-semibold">
                                        {{ $party['residency_status'] ?? 'resident' }}
                                    </span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $party['contact_number'] ?? 'No contact' }} &bull; {{ $party['address_line'] ?? 'Address unlisted' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif

        <!-- 5. Personnel -->
        @if(!empty($results['personnel']))
            <x-ui.card title="Barangay Personnel & Officials ({{ count($results['personnel']) }})">
                <div class="divide-y divide-slate-100">
                    @foreach($results['personnel'] as $person)
                        <div class="py-3 flex items-center justify-between">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-slate-900">{{ $person['full_name'] }}</span>
                                    @if(!empty($person['position']))
                                        <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 text-xs font-medium">
                                            {{ $person['position']['name'] }}
                                        </span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">
                                    {{ $person['email'] ?? 'No email' }} &bull; {{ $person['contact_number'] ?? 'No contact' }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </x-ui.card>
        @endif
    @endif
</div>
@endsection
