@extends('layouts.admin')

@section('title', 'Case ' . $case->case_number)

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Header & Action Cluster -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <div class="flex items-center flex-wrap gap-2.5">
                <h1 class="text-2xl font-bold font-mono text-slate-900">{{ $case->case_number }}</h1>
                
                @if($case->is_confidential)
                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200" title="Confidential dispute under RA 10173">
                        <i class="fa-solid fa-lock text-[10px]"></i>
                        <span>Confidential</span>
                    </span>
                @endif

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

                <span class="text-xs text-slate-500 font-medium">FY {{ $case->fiscalYear?->year ?? date('Y') }}</span>
            </div>
            <p class="text-sm font-medium text-slate-700 mt-1">{{ $case->title }}</p>
        </div>

        <!-- Action Cluster -->
        <div class="flex items-center flex-wrap gap-2">
            <a href="{{ route('admin.cases.print', $case) }}" target="_blank" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 shadow-sm transition-colors">
                <i class="fa-solid fa-print"></i>
                <span>Print Docket</span>
            </a>

            @can('cases.settlement')
                @if(in_array($case->status, [\App\Support\CaseStatus::IN_MEDIATION, \App\Support\CaseStatus::IN_CONCILIATION, \App\Support\CaseStatus::UNSETTLED]))
                    <button type="button" onclick="openSettlementModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-300 rounded-md hover:bg-emerald-100 shadow-sm transition-colors">
                        <i class="fa-solid fa-handshake text-emerald-600"></i>
                        <span>Record Settlement</span>
                    </button>
                @endif
            @endcan

            @can('cases.edit')
                <button type="button" onclick="openStatusModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 shadow-sm transition-colors">
                    <i class="fa-solid fa-arrows-rotate text-blue-600"></i>
                    <span>Change Status</span>
                </button>

                <button type="button" onclick="openAssignModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 shadow-sm transition-colors">
                    <i class="fa-solid fa-user-check text-emerald-600"></i>
                    <span>Assign</span>
                </button>

                <a href="{{ route('admin.cases.edit', $case) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-md hover:bg-blue-700 shadow-sm transition-colors">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit Particulars</span>
                </a>
            @endcan

            <a href="{{ route('admin.cases.index') }}" class="px-3 py-1.5 text-xs font-medium text-slate-500 hover:text-slate-800">
                Back to List
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-sm text-emerald-800 font-medium flex items-center justify-between">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Tabs Navigation Bar -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6 -mb-px" aria-label="Tabs">
            <button type="button" onclick="switchCaseTab('overview')" id="tab-btn-overview" class="tab-btn border-b-2 border-blue-600 py-3 px-1 text-xs font-semibold text-blue-600">
                Overview
            </button>
            <button type="button" onclick="switchCaseTab('parties')" id="tab-btn-parties" class="tab-btn border-b-2 border-transparent py-3 px-1 text-xs font-medium text-slate-500 hover:text-slate-700">
                Parties ({{ $case->caseParties->count() }})
            </button>
            <button type="button" onclick="switchCaseTab('summons')" id="tab-btn-summons" class="tab-btn border-b-2 border-transparent py-3 px-1 text-xs font-medium text-slate-500 hover:text-slate-700">
                Summons ({{ $case->activeSummons->count() }})
            </button>
            <button type="button" onclick="switchCaseTab('hearings')" id="tab-btn-hearings" class="tab-btn border-b-2 border-transparent py-3 px-1 text-xs font-medium text-slate-500 hover:text-slate-700">
                Hearings ({{ $case->hearings->count() }})
            </button>
            <button type="button" onclick="switchCaseTab('escalation')" id="tab-btn-escalation" class="tab-btn border-b-2 border-transparent py-3 px-1 text-xs font-medium text-slate-500 hover:text-slate-700">
                Escalation
            </button>
            <button type="button" onclick="switchCaseTab('notes')" id="tab-btn-notes" class="tab-btn border-b-2 border-transparent py-3 px-1 text-xs font-medium text-slate-500 hover:text-slate-700">
                Notes ({{ $case->notes->count() }})
            </button>
            <button type="button" onclick="switchCaseTab('timeline')" id="tab-btn-timeline" class="tab-btn border-b-2 border-transparent py-3 px-1 text-xs font-medium text-slate-500 hover:text-slate-700">
                Timeline & Logs
            </button>
        </nav>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Active Tab Content -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Tab 1: Overview -->
            <div id="tab-pane-overview" class="tab-pane space-y-6">
                <!-- Case Facts Card -->
                <x-ui.card class="p-6 border border-slate-200 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                        Complaint Narrative & Facts
                    </h2>
                    <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap font-serif">{{ $case->narrative }}</p>
                </x-ui.card>

                <!-- Incident Particulars Card -->
                <x-ui.card class="p-6 border border-slate-200 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                        Incident Particulars
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <dt class="font-semibold text-slate-500">Classification</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $case->caseType?->name ?? 'Unclassified' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Incident Date</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $case->incident_date?->format('M d, Y') ?? 'Not specified' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Incident Location</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $case->incident_location ?: 'Barangay Calarian jurisdiction' }}</dd>
                        </div>
                        <div>
                            <dt class="font-semibold text-slate-500">Purok Area</dt>
                            <dd class="mt-1 font-medium text-slate-900">{{ $case->purok?->name ?? 'Barangay-wide' }}</dd>
                        </div>
                        @if($case->sourceReport)
                            <div class="sm:col-span-2 p-3 bg-blue-50 rounded-lg border border-blue-200">
                                <dt class="font-semibold text-blue-900">Converted from Online Citizen Report</dt>
                                <dd class="mt-1 text-blue-800 flex items-center justify-between">
                                    <span>Tracking Code: <strong class="font-mono">{{ $case->sourceReport->tracking_code }}</strong></span>
                                    <a href="{{ route('admin.reports.review', $case->sourceReport) }}" class="underline text-xs font-semibold">View Original Review</a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card>
            </div>

            <!-- Tab 2: Parties -->
            <div id="tab-pane-parties" class="tab-pane hidden space-y-6">
                <!-- Complainants Section -->
                <x-ui.card class="p-6 border border-slate-200 space-y-3">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-user text-emerald-600"></i>
                        <span>Complainant(s)</span>
                    </h3>
                    <div class="divide-y divide-slate-100">
                        @foreach($case->caseParties->where('role', 'complainant') as $cp)
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <a href="{{ route('admin.parties.show', $cp->party) }}" class="font-bold text-slate-900 hover:text-blue-600 hover:underline">
                                        {{ $cp->party->full_name }}
                                    </a>
                                    <div class="text-slate-500 mt-0.5">
                                        {{ $cp->party->address_line ?? $cp->party->purok?->name ?? 'Address not specified' }}
                                        · {{ $cp->party->contact_number ?? 'No contact' }}
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $cp->residency_at_filing === 'resident' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($cp->residency_at_filing) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <!-- Respondents Section -->
                <x-ui.card class="p-6 border border-slate-200 space-y-3">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-user-shield text-amber-600"></i>
                        <span>Respondent(s)</span>
                    </h3>
                    <div class="divide-y divide-slate-100">
                        @foreach($case->caseParties->where('role', 'respondent') as $cp)
                            <div class="py-3 flex items-center justify-between text-xs">
                                <div>
                                    <a href="{{ route('admin.parties.show', $cp->party) }}" class="font-bold text-slate-900 hover:text-blue-600 hover:underline">
                                        {{ $cp->party->full_name }}
                                    </a>
                                    <div class="text-slate-500 mt-0.5">
                                        {{ $cp->party->address_line ?? $cp->party->home_barangay ?? 'Address not specified' }}
                                        · {{ $cp->party->contact_number ?? 'No contact' }}
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[11px] font-semibold {{ $cp->residency_at_filing === 'resident' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($cp->residency_at_filing) }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>

                <!-- Witnesses Section -->
                @if($case->caseParties->where('role', 'witness')->isNotEmpty())
                    <x-ui.card class="p-6 border border-slate-200 space-y-3">
                        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-users text-purple-600"></i>
                            <span>Witnesses</span>
                        </h3>
                        <div class="divide-y divide-slate-100">
                            @foreach($case->caseParties->where('role', 'witness') as $cp)
                                <div class="py-3 flex items-center justify-between text-xs">
                                    <a href="{{ route('admin.parties.show', $cp->party) }}" class="font-bold text-slate-900 hover:underline">
                                        {{ $cp->party->full_name }}
                                    </a>
                                    <span class="text-slate-500">{{ $cp->party->contact_number ?? 'No contact' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endif
            </div>

            <!-- Tab 3: Summons (WS4 Partial) -->
            <div id="tab-pane-summons" class="tab-pane hidden space-y-6">
                @includeIf('admin.case-actions.partials.summons-tab', ['case' => $case])
            </div>

            <!-- Tab 4: Hearings (WS4 Partial) -->
            <div id="tab-pane-hearings" class="tab-pane hidden space-y-6">
                @includeIf('admin.case-actions.partials.hearings-tab', ['case' => $case])
            </div>

            <!-- Tab 5: Escalation (WS4 Partial) -->
            <div id="tab-pane-escalation" class="tab-pane hidden space-y-6">
                @includeIf('admin.case-actions.partials.escalation-tab', ['case' => $case])
            </div>

            <!-- Tab 6: Notes -->
            <div id="tab-pane-notes" class="tab-pane hidden space-y-6">
                <x-ui.card class="p-6 border border-slate-200 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                        Internal Staff Notes
                    </h2>

                    <!-- Note Form -->
                    <form method="POST" action="{{ route('admin.cases.notes.store', $case) }}" class="space-y-3">
                        @csrf
                        <textarea
                            name="body"
                            rows="3"
                            required
                            minlength="3"
                            placeholder="Add confidential investigative note, observation, or staff instruction..."
                            class="w-full text-xs border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                        ></textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="px-3 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded hover:bg-blue-700 shadow-sm">
                                Post Internal Note
                            </button>
                        </div>
                    </form>

                    <!-- Notes Thread -->
                    <div class="divide-y divide-slate-100 pt-3">
                        @forelse($case->notes as $note)
                            <div class="py-3 text-xs space-y-1">
                                <div class="flex items-center justify-between text-slate-500">
                                    <span class="font-bold text-slate-800">{{ $note->user?->name ?? 'Staff Officer' }}</span>
                                    <span>{{ $note->created_at?->format('M d, Y h:i A') ?? '—' }}</span>
                                </div>
                                <p class="text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $note->body }}</p>
                            </div>
                        @empty
                            <p class="text-xs text-slate-400 italic py-4 text-center">No internal staff notes recorded yet.</p>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            <!-- Tab 7: Timeline & Logs -->
            <div id="tab-pane-timeline" class="tab-pane hidden space-y-6">
                <x-ui.card class="p-6 border border-slate-200 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                        Official Status Transition History
                    </h2>
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach($case->statusLogs as $log)
                                <li>
                                    <div class="relative pb-8">
                                        @if(!$loop->last)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3 text-xs">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-blue-50 border border-blue-200 flex items-center justify-center text-blue-600">
                                                    <i class="fa-solid fa-arrows-spin text-xs"></i>
                                                </span>
                                            </div>
                                            <div class="min-w-0 flex-1 pt-1.5 flex justify-between space-x-4">
                                                <div>
                                                    <p class="font-medium text-slate-900">
                                                        Status transitioned to <strong class="text-blue-700">{{ \App\Support\CaseStatus::label($log->to_status) }}</strong>
                                                        @if($log->from_status)
                                                            <span class="text-slate-500">(from {{ \App\Support\CaseStatus::label($log->from_status) }})</span>
                                                        @endif
                                                    </p>
                                                    @if($log->note)
                                                        <p class="text-slate-600 italic mt-0.5">{{ $log->note }}</p>
                                                    @endif
                                                </div>
                                                <div class="text-right whitespace-nowrap text-slate-500">
                                                    <div>{{ $log->created_at?->format('M d, Y') ?? '—' }}</div>
                                                    <div class="text-[10px]">{{ $log->user?->name ?? 'System' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-ui.card>

                @if($case->is_confidential && auth()->user()->isAdmin())
                    <x-ui.card class="p-6 border border-red-200 bg-red-50/20 space-y-3">
                        <h3 class="text-xs font-bold text-red-900 uppercase tracking-wider flex items-center gap-1.5">
                            <i class="fa-solid fa-shield-halved text-red-600"></i>
                            <span>RA 10173 Confidential Access Log (Administrator Audit)</span>
                        </h3>
                        <p class="text-[11px] text-slate-600">Recorded access audit entries for this confidential record per Data Privacy Act regulations.</p>
                        <div class="text-xs text-slate-500">
                            <span>Audited view activities are automatically recorded in central <a href="{{ route('admin.audit-log.index') }}" class="text-blue-600 underline">Audit Trail</a>.</span>
                        </div>
                    </x-ui.card>
                @endif
            </div>

        </div>

        <!-- Right 1 Col: Metadata & Action Panels -->
        <div class="space-y-6">

            <!-- Summons Progress Widget -->
            @includeIf('admin.case-actions.partials.summons-counter', ['case' => $case])

            <!-- Metadata Card -->
            <x-ui.card class="p-5 border border-slate-200 space-y-3 text-xs">
                <h3 class="font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">Docket Details</h3>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Filed Date</span>
                    <span class="font-medium text-slate-900">{{ $case->filed_on?->format('M d, Y') ?? 'N/A' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Active Fiscal Year</span>
                    <span class="font-medium text-slate-900">{{ $case->fiscalYear?->label ?? 'FY 2026' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Docketing Source</span>
                    <span class="font-medium text-slate-900">{{ ucfirst(str_replace('_', ' ', $case->source)) }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Docketing Clerk</span>
                    <span class="font-medium text-slate-900">{{ $case->encodedBy?->name ?? 'Staff' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Assigned Conciliator</span>
                    <span class="font-bold text-blue-700">{{ $case->assignedTo?->name ?? 'Unassigned' }}</span>
                </div>

                @if($case->settled_on)
                    <div class="space-y-1.5 py-1.5 border-b border-slate-50 bg-emerald-50/70 p-2.5 rounded border border-emerald-200">
                        <div class="flex justify-between items-center text-xs">
                            <span class="text-emerald-800 font-semibold">Settled On</span>
                            <span class="font-bold text-emerald-900">{{ $case->settled_on?->format('M d, Y') ?? '—' }}</span>
                        </div>
                        @if($case->settlement_amount)
                            <div class="flex justify-between items-center text-xs text-slate-700">
                                <span class="text-slate-500">Amount:</span>
                                <span class="font-bold text-slate-900">₱{{ number_format($case->settlement_amount, 2) }}</span>
                            </div>
                        @endif
                        @if($case->settlement_terms)
                            <div class="text-[11px] text-slate-600 pt-1 border-t border-emerald-100">
                                <span class="font-semibold text-slate-700">Terms:</span> {{ \Illuminate\Support\Str::limit($case->settlement_terms, 100) }}
                            </div>
                        @endif
                        @if($case->settlement_agreement_path)
                            <div class="pt-1">
                                <a href="{{ route('admin.cases.settlement.agreement', $case) }}" target="_blank" class="inline-flex items-center gap-1 text-[11px] font-bold text-emerald-700 hover:underline">
                                    <i class="fa-solid fa-file-pdf"></i> View Signed Agreement
                                </a>
                            </div>
                        @endif
                    </div>
                @endif
            </x-ui.card>

        </div>
    </div>
</div>

<!-- Modal: Change Status -->
<div id="status-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900">Transition Case Status</h3>
            <button type="button" onclick="closeStatusModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.cases.status', $case) }}" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Target Status <span class="text-red-500">*</span></label>
                <select name="to_status" required class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500">
                    <option value="">Select Allowed Status Transition...</option>
                    @foreach(\App\Support\CaseStatus::all() as $st)
                        @if($st !== $case->status && app(\App\Services\CaseStatusService::class)->canTransition($case->status, $st))
                            <option value="{{ $st }}">{{ \App\Support\CaseStatus::label($st) }}</option>
                        @endif
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Official Transition Note <span class="text-red-500">*</span></label>
                <textarea
                    name="note"
                    rows="3"
                    required
                    minlength="5"
                    maxlength="1000"
                    placeholder="State reason or official order for status change..."
                    class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500"
                ></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeStatusModal()" class="px-3 py-1.5 text-slate-600 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-1.5 font-semibold text-white bg-blue-600 rounded hover:bg-blue-700">Submit Transition</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Assign Staff -->
<div id="assign-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900">Assign Officer to Case</h3>
            <button type="button" onclick="closeAssignModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.cases.assign', $case) }}" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Assigned Officer</label>
                <select name="assigned_to" class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500">
                    <option value="">Unassigned</option>
                    @foreach($staffMembers as $member)
                        <option value="{{ $member->id }}" @selected($case->assigned_to == $member->id)>{{ $member->name }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeAssignModal()" class="px-3 py-1.5 text-slate-600 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-1.5 font-semibold text-white bg-blue-600 rounded hover:bg-blue-700">Save Assignment</button>
            </div>
        </form>
    </div>
</div>

<script>
    function switchCaseTab(tabKey) {
        document.querySelectorAll('.tab-pane').forEach(el => el.classList.add('hidden'));
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('border-blue-600', 'text-blue-600', 'font-semibold');
            btn.classList.add('border-transparent', 'text-slate-500', 'font-medium');
        });

        const activePane = document.getElementById(`tab-pane-${tabKey}`);
        const activeBtn = document.getElementById(`tab-btn-${tabKey}`);
        if (activePane) activePane.classList.remove('hidden');
        if (activeBtn) {
            activeBtn.classList.remove('border-transparent', 'text-slate-500', 'font-medium');
            activeBtn.classList.add('border-blue-600', 'text-blue-600', 'font-semibold');
        }
    }

    function openStatusModal() { document.getElementById('status-modal').classList.remove('hidden'); }
    function closeStatusModal() { document.getElementById('status-modal').classList.add('hidden'); }
    function openAssignModal() { document.getElementById('assign-modal').classList.remove('hidden'); }
    function closeAssignModal() { document.getElementById('assign-modal').classList.add('hidden'); }
    function openSettlementModal() { document.getElementById('settlement-modal').classList.remove('hidden'); }
    function closeSettlementModal() { document.getElementById('settlement-modal').classList.add('hidden'); }

    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab');
    if (initialTab) {
        switchCaseTab(initialTab);
    }
</script>

<!-- Modal: Record Settlement -->
<div id="settlement-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-handshake text-emerald-600"></i> Record Amicable Settlement
            </h3>
            <button type="button" onclick="closeSettlementModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.cases.settlement.store', $case) }}" enctype="multipart/form-data" class="space-y-4 text-xs">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Settlement Date <span class="text-red-500">*</span></label>
                <input type="date" name="settled_on" value="{{ date('Y-m-d') }}" required class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Agreed Settlement Terms <span class="text-red-500">*</span></label>
                <textarea name="terms" rows="4" required class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500" placeholder="Detail the terms, obligations, payment schedules, or conditions agreed upon by both parties..."></textarea>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Monetary Settlement Amount (₱, Optional)</label>
                <input type="number" step="0.01" min="0" name="settlement_amount" class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500" placeholder="0.00">
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Signed Agreement Document (PDF / Image, Max 5MB)</label>
                <input type="file" name="agreement_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs border border-slate-300 rounded p-2 bg-slate-50">
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeSettlementModal()" class="px-3 py-1.5 text-slate-600 font-medium hover:text-slate-800">Cancel</button>
                <button type="submit" class="px-4 py-1.5 font-semibold text-white bg-emerald-600 rounded hover:bg-emerald-700 shadow-sm">Confirm Settlement</button>
            </div>
        </form>
    </div>
</div>
</script>
@endsection
