@extends('layouts.admin')

@section('title', 'Barangay Cases Docket')

@section('content')
<div class="w-full min-w-0 space-y-6">

    <!-- Page Header -->
    <x-ui.page-header
        title="Barangay Cases Docket"
        subtitle="Official Katarungang Pambarangay blotter registry and conciliation records"
    >
        <x-slot:actions>
            <a href="{{ route('admin.cases.export-csv', request()->query()) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors" data-turbo="false">
                <i class="fa-solid fa-download"></i>
                <span>Export CSV</span>
            </a>

            @can('cases.create')
                <a href="{{ route('admin.cases.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <i class="fa-solid fa-plus"></i>
                    <span>File New Case</span>
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Filters Card -->
    <x-ui.card class="p-4 bg-white border border-slate-200">
        <form method="GET" action="{{ route('admin.cases.index') }}" class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-3 text-sm">
            <!-- Search -->
            <div class="sm:col-span-2 xl:col-span-2">
                <label for="search" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Search Docket</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ $search }}"
                        placeholder="Case #, title, or party name..."
                        class="block w-full pl-8 pr-3 py-1.5 text-xs border border-slate-300 rounded-md focus:ring-1 focus:ring-blue-500 focus:border-blue-500 text-slate-800"
                    />
                </div>
            </div>

            <!-- Status Filter -->
            <div>
                <label for="status" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Status</label>
                <select name="status" id="status" class="block w-full py-1.5 px-2 text-xs border border-slate-300 rounded-md focus:ring-1 focus:ring-blue-500 text-slate-800">
                    <option value="">All Statuses</option>
                    @foreach(\App\Support\CaseStatus::all() as $st)
                        <option value="{{ $st }}" @selected($status === $st)>{{ \App\Support\CaseStatus::label($st) }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Case Type Filter -->
            <div>
                <label for="case_type_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Classification</label>
                <select name="case_type_id" id="case_type_id" class="block w-full py-1.5 px-2 text-xs border border-slate-300 rounded-md focus:ring-1 focus:ring-blue-500 text-slate-800">
                    <option value="">All Types</option>
                    @foreach($caseTypes as $type)
                        <option value="{{ $type->id }}" @selected($caseTypeId == $type->id)>{{ $type->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Purok Filter -->
            <div>
                <label for="purok_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Purok</label>
                <select name="purok_id" id="purok_id" class="block w-full py-1.5 px-2 text-xs border border-slate-300 rounded-md focus:ring-1 focus:ring-blue-500 text-slate-800">
                    <option value="">All Puroks</option>
                    @foreach($puroks as $pk)
                        <option value="{{ $pk->id }}" @selected($purokId == $pk->id)>{{ $pk->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Residency Filter -->
            <div>
                <label for="residency" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Residency</label>
                <select name="residency" id="residency" class="block w-full py-1.5 px-2 text-xs border border-slate-300 rounded-md focus:ring-1 focus:ring-blue-500 text-slate-800">
                    <option value="">All Parties</option>
                    <option value="resident" @selected($residency === 'resident')>Resident</option>
                    <option value="non_resident" @selected($residency === 'non_resident')>Non-Resident</option>
                    <option value="unverified" @selected($residency === 'unverified')>Unverified</option>
                </select>
            </div>

            <!-- Filter Action Buttons -->
            <div class="flex items-end gap-2 sm:col-span-2 md:col-span-1 xl:col-span-1">
                <button type="submit" class="flex-1 py-1.5 px-3 text-xs font-semibold text-white bg-slate-800 rounded-md hover:bg-slate-900 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'status', 'case_type_id', 'purok_id', 'residency', 'assigned_to']))
                    <a href="{{ route('admin.cases.index') }}" class="py-1.5 px-2 text-xs font-medium text-slate-500 hover:text-slate-800 border border-slate-300 rounded-md bg-white text-center">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </x-ui.card>

    <!-- Docket Table -->
    <x-ui.card class="overflow-hidden border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6 w-[12%]">Case No.</th>
                        <th scope="col" class="px-3 py-3.5 w-[9%]">Filed</th>
                        <th scope="col" class="px-3 py-3.5 w-[13%]">Type</th>
                        <th scope="col" class="px-3 py-3.5 w-[17%]">Complainant(s)</th>
                        <th scope="col" class="px-3 py-3.5 w-[17%]">Respondent(s)</th>
                        <th scope="col" class="px-3 py-3.5 w-[10%]">Status</th>
                        <th scope="col" class="px-3 py-3.5 w-[8%] text-center">Summons</th>
                        <th scope="col" class="px-3 py-3.5 w-[8%]">Assigned</th>
                        <th scope="col" class="relative py-3.5 pl-3 pr-4 sm:pr-6 w-[6%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                    @forelse($cases as $case)
                        @php
                            $summonsCount = $case->activeSummons->count();
                            $summonsLimit = (int) setting('cases.summons_limit', 3);
                            $isEligible = ($summonsCount >= $summonsLimit && in_array($case->status, [\App\Support\CaseStatus::FILED, \App\Support\CaseStatus::IN_MEDIATION, \App\Support\CaseStatus::UNSETTLED], true));
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Case No. -->
                            <td class="py-3 pl-4 pr-3 sm:pl-6 font-mono font-semibold text-xs text-blue-600 whitespace-nowrap">
                                <a href="{{ route('admin.cases.show', $case) }}" class="hover:underline flex items-center gap-1.5">
                                    <span>{{ $case->case_number }}</span>
                                    @if($case->is_confidential)
                                        <i class="fa-solid fa-lock text-red-500 text-xs" title="Confidential Case"></i>
                                    @endif
                                </a>
                                @if($case->title)
                                    <div class="text-[11px] text-slate-500 font-normal font-sans truncate max-w-[160px] mt-0.5">{{ $case->title }}</div>
                                @endif
                            </td>

                            <!-- Filed Date -->
                            <td class="px-3 py-3 text-xs text-slate-500 whitespace-nowrap">
                                {{ $case->filed_on?->format('M d, Y') ?? 'N/A' }}
                            </td>

                            <!-- Classification -->
                            <td class="px-3 py-3 text-xs">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">
                                    {{ $case->caseType?->name ?? 'Unclassified' }}
                                </span>
                            </td>

                            <!-- Complainants -->
                            <td class="px-3 py-3 text-xs">
                                <div class="font-medium text-slate-900 truncate max-w-[180px]">
                                    {{ $case->complainants->pluck('full_name')->join(', ') ?: 'None' }}
                                </div>
                                @if($case->purok)
                                    <div class="text-[11px] text-slate-400">{{ $case->purok->name }}</div>
                                @endif
                            </td>

                            <!-- Respondents -->
                            <td class="px-3 py-3 text-xs">
                                <div class="font-medium text-slate-900 truncate max-w-[180px]">
                                    {{ $case->respondents->pluck('full_name')->join(', ') ?: 'None' }}
                                </div>
                            </td>

                            <!-- Status Badge -->
                            <td class="px-3 py-3 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'filed' => 'blue',
                                        'in_mediation' => 'amber',
                                        'settled' => 'green',
                                        'unsettled' => 'red',
                                        'escalated' => 'red',
                                        'dismissed' => 'slate',
                                        'withdrawn' => 'slate',
                                    ];
                                    $currentColor = $statusColors[$case->status] ?? 'slate';
                                @endphp
                                <x-ui.badge :color="$currentColor" :dot="true">
                                    {{ \App\Support\CaseStatus::label($case->status) }}
                                </x-ui.badge>
                            </td>

                            <!-- Summons Counter -->
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                @if($isEligible)
                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-bold bg-red-100 text-red-800 border border-red-300 animate-pulse" title="Eligible for Court Escalation (CFA)">
                                        <i class="fa-solid fa-triangle-exclamation"></i>
                                        <span>{{ $summonsCount }}/{{ $summonsLimit }}</span>
                                    </span>
                                @elseif($summonsCount === $summonsLimit - 1)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                                        {{ $summonsCount }}/{{ $summonsLimit }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-slate-100 text-slate-700">
                                        {{ $summonsCount }}/{{ $summonsLimit }}
                                    </span>
                                @endif
                            </td>

                            <!-- Assigned Officer -->
                            <td class="px-3 py-3 text-xs text-slate-600 truncate max-w-[120px]">
                                {{ $case->assignedTo?->name ?? 'Unassigned' }}
                            </td>

                            <!-- Actions -->
                            <td class="py-3 pl-3 pr-4 sm:pr-6 text-right whitespace-nowrap text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.cases.show', $case) }}" class="text-blue-600 hover:text-blue-900" title="View Docket">
                                        <i class="fa-solid fa-folder-open"></i>
                                    </a>
                                    @can('cases.edit')
                                        <a href="{{ route('admin.cases.edit', $case) }}" class="text-slate-500 hover:text-slate-800" title="Edit Case">
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-12">
                                <x-ui.empty-state
                                    title="No Blotter Cases Found"
                                    description="No amicable dispute complaints match the active filter criteria for this fiscal year."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($cases->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $cases->links() }}
            </div>
        @endif
    </x-ui.card>

</div>
@endsection
