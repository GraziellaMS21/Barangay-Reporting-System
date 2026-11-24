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
    <x-ui.table>
        <x-slot:header>
            <tr>
                <th scope="col" class="py-3 pl-4 pr-3 sm:pl-6 w-[34%] sm:w-[26%] md:w-[24%] lg:w-[22%]">Case No. &amp; Details</th>
                <th scope="col" class="hidden sm:table-cell px-3 py-3 w-[18%] md:w-[15%] lg:w-[14%]">Classification</th>
                <th scope="col" class="px-3 py-3 w-[36%] sm:w-[32%] md:w-[30%] lg:w-[28%]">Parties</th>
                <th scope="col" class="px-3 py-3 w-[20%] sm:w-[18%] md:w-[16%] lg:w-[16%]">Status</th>
                <th scope="col" class="hidden xl:table-cell px-3 py-3 w-[12%]">Assigned</th>
                <th scope="col" class="py-3 pl-3 pr-4 sm:pr-6 w-[10%] sm:w-[8%] text-right">Actions</th>
            </tr>
        </x-slot:header>

        @forelse($cases as $case)
            @php
                $summonsCount = $case->activeSummons->count();
                $summonsLimit = (int) setting('cases.summons_limit', 3);
                $isEligible = ($summonsCount >= $summonsLimit && in_array($case->status, [\App\Support\CaseStatus::FILED, \App\Support\CaseStatus::IN_MEDIATION, \App\Support\CaseStatus::UNSETTLED], true));
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
            <tr class="hover:bg-slate-50/80 transition-colors">
                <!-- Case No. & Details -->
                <td class="py-3 pl-4 pr-3 sm:pl-6 text-xs" data-label="Case No.">
                    <a href="{{ route('admin.cases.show', $case) }}" class="font-mono font-bold text-blue-600 hover:underline inline-flex items-center gap-1.5">
                        <span>{{ $case->case_number }}</span>
                        @if($case->is_confidential)
                            <i class="fa-solid fa-lock text-red-500 text-[10px]" title="Confidential Case"></i>
                        @endif
                    </a>
                    @if($case->title)
                        <div class="text-[11px] text-slate-700 font-medium truncate max-w-[180px] mt-0.5" title="{{ $case->title }}">{{ $case->title }}</div>
                    @endif
                    <div class="text-[10px] text-slate-400 mt-0.5">
                        Filed {{ $case->filed_on?->format('M d, Y') ?? 'N/A' }}
                    </div>
                </td>

                <!-- Classification -->
                <td class="hidden sm:table-cell px-3 py-3 text-xs" data-label="Type">
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-slate-100 text-slate-800 line-clamp-2" title="{{ $case->caseType?->name ?? 'Unclassified' }}">
                        {{ $case->caseType?->name ?? 'Unclassified' }}
                    </span>
                </td>

                <!-- Parties (Complainant vs. Respondent) -->
                <td class="px-3 py-3 text-xs" data-label="Parties">
                    <div class="font-medium text-slate-900 truncate max-w-[200px]" title="Complainant(s): {{ $case->complainants->pluck('full_name')->join(', ') ?: 'None' }}">
                        {{ $case->complainants->pluck('full_name')->join(', ') ?: 'None' }}
                    </div>
                    <div class="text-[10px] text-slate-400 my-0.5 italic">vs.</div>
                    <div class="font-medium text-slate-700 truncate max-w-[200px]" title="Respondent(s): {{ $case->respondents->pluck('full_name')->join(', ') ?: 'None' }}">
                        {{ $case->respondents->pluck('full_name')->join(', ') ?: 'None' }}
                    </div>
                    @if($case->purok)
                        <div class="text-[10px] text-slate-400 mt-0.5">{{ $case->purok->name }}</div>
                    @endif
                </td>

                <!-- Status & Summons -->
                <td class="px-3 py-3 text-xs" data-label="Status">
                    <div class="flex flex-col gap-1 items-start">
                        <x-ui.badge :color="$currentColor" :dot="true">
                            {{ \App\Support\CaseStatus::label($case->status) }}
                        </x-ui.badge>
                        @if($isEligible)
                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-800 border border-red-300" title="Eligible for Court Escalation (CFA)">
                                <i class="fa-solid fa-triangle-exclamation"></i>
                                <span>Summons {{ $summonsCount }}/{{ $summonsLimit }}</span>
                            </span>
                        @elseif($summonsCount > 0)
                            <span class="text-[10px] text-slate-500 font-medium">
                                Summons {{ $summonsCount }}/{{ $summonsLimit }}
                            </span>
                        @endif
                    </div>
                </td>

                <!-- Assigned Officer -->
                <td class="hidden xl:table-cell px-3 py-3 text-xs text-slate-600 truncate max-w-[120px]" data-label="Assigned" title="{{ $case->assignedTo?->name ?? 'Unassigned' }}">
                    {{ $case->assignedTo?->name ?? 'Unassigned' }}
                </td>

                <!-- Actions -->
                <td class="py-3 pl-3 pr-4 sm:pr-6 text-right text-xs" data-label="Actions">
                    <div class="flex items-center justify-end gap-2">
                        <a href="{{ route('admin.cases.show', $case) }}" class="p-1.5 rounded text-blue-600 hover:text-blue-900 hover:bg-blue-50 transition-colors" title="View Docket" aria-label="View Docket {{ $case->case_number }}">
                            <i class="fa-solid fa-folder-open"></i>
                        </a>
                        @can('cases.edit')
                            <a href="{{ route('admin.cases.edit', $case) }}" class="p-1.5 rounded text-slate-500 hover:text-slate-800 hover:bg-slate-100 transition-colors" title="Edit Case" aria-label="Edit Case {{ $case->case_number }}">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </a>
                        @endcan
                    </div>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6" class="py-12">
                    <x-ui.empty-state
                        title="No Blotter Cases Found"
                        description="No amicable dispute complaints match the active filter criteria for this fiscal year."
                    />
                </td>
            </tr>
        @endforelse

        @if($cases->hasPages())
            <x-slot:footer>
                <div class="p-3">
                    {{ $cases->links() }}
                </div>
            </x-slot:footer>
        @endif
    </x-ui.table>

</div>
@endsection
