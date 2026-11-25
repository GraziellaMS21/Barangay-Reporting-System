@extends('layouts.admin')

@section('title', 'Case ' . $case->case_number)

@section('content')
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

    $checklist = $case->eligibility_checklist ?? [];
    $isAllChecked = !empty($checklist['same_barangay']) && !empty($checklist['kp_matter']) && !empty($checklist['non_confidential']) && !empty($checklist['residency_verified']);

    $overdue = app(\App\Services\OverdueService::class)->forCase($case);
    $nextHearing = $case->hearings->whereNull('superseded_at')->where('scheduled_at', '>=', now())->sortBy('scheduled_at')->first();

    $sourceIntake = \App\Models\ComplaintIntake::where('case_id', $case->id)->first();
    $narrativeText = $case->narrative ?? '';
    $parsedWhat = $sourceIntake?->what_happened;
    $parsedCircumstances = $sourceIntake?->why_how_happened;
    $parsedRights = $sourceIntake?->rights_violated;
    $parsedRelief = $sourceIntake?->relief_requested;

    if (!$parsedWhat) {
        if (preg_match('/^(.*?)(?:\n\nCircumstances:\s*(.*?))?(?:\n\nRights Violated:\s*(.*?))?(?:\n\nRelief Requested:\s*(.*?))?$/s', $narrativeText, $matches)) {
            $parsedWhat = !empty($matches[1]) ? trim($matches[1]) : $narrativeText;
            $parsedCircumstances = !empty($matches[2]) ? trim($matches[2]) : null;
            $parsedRights = !empty($matches[3]) ? trim($matches[3]) : null;
            $parsedRelief = !empty($matches[4]) ? trim($matches[4]) : null;
        } else {
            $parsedWhat = $narrativeText;
        }
    }
@endphp

<div class="max-w-7xl mx-auto py-4 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Sticky Case Header & Action Cluster -->
    <div class="sticky top-[4.25rem] z-20 bg-white/95 backdrop-blur-sm border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-3.5 shadow-xs">
        <!-- Back Link Breadcrumb -->
        <div class="mb-2">
            <a href="{{ route('admin.cases.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-500 hover:text-slate-900 transition-colors">
                <i class="fa-solid fa-arrow-left text-[11px]" aria-hidden="true"></i>
                <span>Back to Cases List</span>
            </a>
        </div>

        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
            <div>
                <div class="flex items-center flex-wrap gap-2.5">
                    <h1 class="text-2xl font-bold font-mono text-slate-900">{{ $case->case_number }}</h1>
                    
                    @if($case->is_confidential)
                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-800 border border-red-200" title="Confidential dispute under RA 10173">
                            <i class="fa-solid fa-lock text-[10px]"></i>
                            <span>Confidential</span>
                        </span>
                    @endif

                    <x-ui.badge :color="$currentColor" :dot="true">
                        {{ \App\Support\CaseStatus::label($case->status) }}
                    </x-ui.badge>

                    <span class="text-xs text-slate-500 font-medium">FY {{ $case->fiscalYear?->year ?? date('Y') }}</span>
                </div>
                <p class="text-sm font-medium text-slate-700 mt-1 truncate max-w-2xl">{{ $case->title }}</p>
            </div>

            <!-- Action Cluster -->
            <div class="flex items-center flex-wrap gap-2">
                @can('cases.edit')
                    <!-- Primary Button -->
                    <button type="button" onclick="openStatusModal()" class="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                        <i class="fa-solid fa-arrows-rotate"></i>
                        <span>Change Status</span>
                    </button>

                    <!-- Secondary Buttons -->
                    <button type="button" onclick="openAssignModal()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-sm transition-colors">
                        <i class="fa-solid fa-user-check text-emerald-600"></i>
                        <span>Assign</span>
                    </button>

                    <a href="{{ route('admin.cases.edit', $case) }}" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-sm transition-colors">
                        <i class="fa-solid fa-pen-to-square text-slate-500"></i>
                        <span>Edit Particulars</span>
                    </a>
                @endcan

                @can('cases.edit')
                    @if(in_array($case->status, [\App\Support\CaseStatus::IN_MEDIATION, \App\Support\CaseStatus::IN_CONCILIATION, \App\Support\CaseStatus::UNSETTLED]))
                        <button type="button" onclick="openSettlementModal()" class="inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-emerald-800 bg-emerald-50 border border-emerald-300 rounded-lg hover:bg-emerald-100 shadow-sm transition-colors">
                            <i class="fa-solid fa-handshake text-emerald-600"></i>
                            <span>Record Settlement</span>
                        </button>
                    @endif
                @endcan

                <!-- More Dropdown -->
                <details class="relative">
                    <summary class="cursor-pointer inline-flex items-center gap-1.5 px-3 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50 shadow-sm transition-colors list-none">
                        <i class="fa-solid fa-ellipsis"></i>
                        <span>More</span>
                        <i class="fa-solid fa-chevron-down text-[10px] text-slate-400 ml-0.5"></i>
                    </summary>
                    <div class="absolute right-0 mt-1.5 z-40 w-64 rounded-xl border border-slate-200 bg-white p-2 shadow-xl text-xs space-y-1">
                        <a href="{{ route('admin.cases.print', $case) }}" target="_blank" class="flex items-center gap-2 px-2.5 py-2 rounded-lg hover:bg-slate-100 text-slate-700 font-medium">
                            <i class="fa-solid fa-print text-slate-500 w-4"></i>
                            <span>Print Docket</span>
                        </a>
                        <div class="border-t border-slate-100 my-1"></div>
                        <div class="px-2.5 py-1 text-[10px] font-bold uppercase tracking-wider text-slate-400">KP Legal Forms</div>
                        <a href="{{ route('admin.cases.printables.blotter-extract', $case) }}" target="_blank" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700">
                            <i class="fa-solid fa-file-lines text-slate-400 w-4"></i>
                            <span>Blotter Certified Extract</span>
                        </a>
                        <a href="{{ route('admin.cases.printables.complaint', $case) }}" target="_blank" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700">
                            <i class="fa-solid fa-scale-balanced text-slate-400 w-4"></i>
                            <span>Formal Complaint (KP Form 1)</span>
                        </a>
                        <a href="{{ route('admin.cases.printables.summons', $case) }}" target="_blank" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700">
                            <i class="fa-solid fa-envelope-open-text text-slate-400 w-4"></i>
                            <span>Summons Notice (KP Form 7)</span>
                        </a>
                        <a href="{{ route('admin.cases.printables.conciliation-notice', $case) }}" target="_blank" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700">
                            <i class="fa-solid fa-calendar-check text-slate-400 w-4"></i>
                            <span>Notice of Hearing (KP 8/9)</span>
                        </a>
                        <a href="{{ route('admin.cases.printables.settlement', $case) }}" target="_blank" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700">
                            <i class="fa-solid fa-handshake text-slate-400 w-4"></i>
                            <span>Amicable Settlement (KP 16)</span>
                        </a>
                        <a href="{{ route('admin.cases.printables.cfa', $case) }}" target="_blank" class="flex items-center gap-2 px-2.5 py-1.5 rounded-lg hover:bg-slate-100 text-slate-700">
                            <i class="fa-solid fa-certificate text-slate-400 w-4"></i>
                            <span>Certification to File Action (KP 20)</span>
                        </a>
                    </div>
                </details>
            </div>
        </div>

        <!-- Navigation Tabs Bar -->
        <nav class="flex space-x-6 -mb-px mt-3 overflow-x-auto" aria-label="Case Navigation Tabs">
            <button type="button" onclick="switchCaseTab('overview')" id="tab-btn-overview" class="tab-btn border-b-2 border-blue-600 py-2.5 px-1 text-xs font-semibold text-blue-600 whitespace-nowrap">
                Overview
            </button>
            <button type="button" onclick="switchCaseTab('parties')" id="tab-btn-parties" class="tab-btn border-b-2 border-transparent py-2.5 px-1 text-xs font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap">
                Parties ({{ $case->caseParties->count() }})
            </button>
            <button type="button" onclick="switchCaseTab('summons')" id="tab-btn-summons" class="tab-btn border-b-2 border-transparent py-2.5 px-1 text-xs font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap">
                Summons ({{ $case->activeSummons->count() }})
            </button>
            <button type="button" onclick="switchCaseTab('hearings')" id="tab-btn-hearings" class="tab-btn border-b-2 border-transparent py-2.5 px-1 text-xs font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap">
                Hearings ({{ $case->hearings->count() }})
            </button>
            <button type="button" onclick="switchCaseTab('escalation')" id="tab-btn-escalation" class="tab-btn border-b-2 border-transparent py-2.5 px-1 text-xs font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap">
                Escalation
            </button>
            <button type="button" onclick="switchCaseTab('notes')" id="tab-btn-notes" class="tab-btn border-b-2 border-transparent py-2.5 px-1 text-xs font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap">
                Notes ({{ $case->notes->count() }})
            </button>
            <button type="button" onclick="switchCaseTab('timeline')" id="tab-btn-timeline" class="tab-btn border-b-2 border-transparent py-2.5 px-1 text-xs font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap">
                Timeline & Logs
            </button>
        </nav>
    </div>

    <!-- Toast Notification for Action Confirmation -->
    @if (session('success'))
        <div role="status" class="fixed bottom-6 right-6 z-50 p-4 bg-emerald-600 text-white font-medium text-xs rounded-xl shadow-xl flex items-center gap-3 transition-opacity duration-300" data-toast>
            <i class="fa-solid fa-circle-check text-base"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div role="alert" class="fixed bottom-6 right-6 z-50 p-4 bg-red-600 text-white font-medium text-xs rounded-xl shadow-xl flex items-center gap-3 transition-opacity duration-300" data-toast>
            <i class="fa-solid fa-triangle-exclamation text-base"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif
    @if (session('warning_message'))
        <div role="status" class="fixed bottom-6 right-6 z-50 p-4 bg-amber-600 text-white font-medium text-xs rounded-xl shadow-xl flex items-center gap-3 transition-opacity duration-300" data-toast>
            <i class="fa-solid fa-circle-info text-base"></i>
            <span>{{ session('warning_message') }}</span>
        </div>
    @endif
    @if ($errors->any())
        <div role="alert" class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-900 text-xs shadow-xs space-y-1">
            <div class="font-bold flex items-center gap-2 text-red-800">
                <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                <span>Could not save changes — please review the required fields:</span>
            </div>
            <ul class="list-disc pl-5 space-y-0.5 text-red-700">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Main Content Two-Column Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Left 2 Columns: Active Tab Content -->
        <main class="lg:col-span-2 space-y-6">

            <!-- Tab 1: Overview -->
            <div id="tab-pane-overview" class="tab-pane space-y-6">
                <!-- Guided Post-Docket Workflow & Stepper -->
                @if(isset($nextStep))
                    @include('admin.cases.partials.next-step-panel', ['case' => $case, 'nextStep' => $nextStep])
                @endif

                <!-- Structured Facts & Narrative Card -->
                <x-ui.card class="p-6 border border-slate-200 bg-white shadow-sm space-y-4 font-sans">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-file-lines text-blue-600"></i>
                            <span>Complaint Narrative & Facts</span>
                        </h2>
                        @if($sourceIntake && isset($sourceIntake->field_provenance['what_happened']))
                            <x-ui.provenance-badge :provenance="$sourceIntake->field_provenance['what_happened']" />
                        @endif
                    </div>

                    <div class="space-y-4 text-xs font-sans">
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="block font-semibold text-slate-500 uppercase tracking-wider text-[11px]">What Happened (Factual Account)</span>
                                @if($sourceIntake && isset($sourceIntake->field_provenance['what_happened']))
                                    <x-ui.provenance-badge :provenance="$sourceIntake->field_provenance['what_happened']" />
                                @endif
                            </div>
                            <p class="text-sm text-slate-800 leading-relaxed whitespace-pre-wrap bg-slate-50 p-3.5 rounded-lg border border-slate-200/80">{{ $parsedWhat ?: 'Not stated' }}</p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 pt-2">
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200/80">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="block font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Circumstances / Context</span>
                                    @if($sourceIntake && isset($sourceIntake->field_provenance['why_how_happened']))
                                        <x-ui.provenance-badge :provenance="$sourceIntake->field_provenance['why_how_happened']" />
                                    @endif
                                </div>
                                <p class="text-xs text-slate-800 leading-relaxed whitespace-pre-wrap {{ empty($parsedCircumstances) ? 'text-slate-400 italic' : '' }}">{{ $parsedCircumstances ?: 'Not stated' }}</p>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200/80">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="block font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Rights Violated</span>
                                    @if($sourceIntake && isset($sourceIntake->field_provenance['rights_violated']))
                                        <x-ui.provenance-badge :provenance="$sourceIntake->field_provenance['rights_violated']" />
                                    @endif
                                </div>
                                <p class="text-xs text-slate-800 leading-relaxed whitespace-pre-wrap {{ empty($parsedRights) ? 'text-slate-400 italic' : '' }}">{{ $parsedRights ?: 'Not stated' }}</p>
                            </div>
                            <div class="bg-slate-50 p-3 rounded-lg border border-slate-200/80">
                                <div class="flex items-center justify-between mb-1">
                                    <span class="block font-semibold text-slate-500 uppercase tracking-wider text-[10px]">Relief Requested</span>
                                    @if($sourceIntake && isset($sourceIntake->field_provenance['relief_requested']))
                                        <x-ui.provenance-badge :provenance="$sourceIntake->field_provenance['relief_requested']" />
                                    @endif
                                </div>
                                <p class="text-xs text-slate-800 leading-relaxed whitespace-pre-wrap {{ empty($parsedRelief) ? 'text-slate-400 italic' : '' }}">{{ $parsedRelief ?: 'Not stated' }}</p>
                            </div>
                        </div>

                        @if($sourceIntake && ($sourceIntake->incident_narrative || $sourceIntake->rights_and_relief))
                            <div x-data="{ openOrig: false }" class="pt-3 border-t border-slate-100">
                                <button type="button" @click="openOrig = !openOrig" class="inline-flex items-center gap-1.5 text-xs font-semibold text-slate-600 hover:text-slate-900 transition-colors">
                                    <i class="fa-solid" :class="openOrig ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                    <span>Original text submitted by complainant</span>
                                </button>
                                <div x-show="openOrig" x-cloak class="mt-3 p-3.5 bg-slate-50 rounded-lg border border-slate-200 space-y-3">
                                    @if($sourceIntake->incident_narrative)
                                        <div>
                                            <span class="block font-bold text-slate-600 text-[10px] uppercase">Incident Narrative (Verbatim)</span>
                                            <p class="text-xs text-slate-700 whitespace-pre-wrap leading-relaxed mt-0.5">{{ $sourceIntake->incident_narrative }}</p>
                                        </div>
                                    @endif
                                    @if($sourceIntake->rights_and_relief)
                                        <div>
                                            <span class="block font-bold text-slate-600 text-[10px] uppercase">Rights Violated & Relief Requested (Verbatim)</span>
                                            <p class="text-xs text-slate-700 whitespace-pre-wrap leading-relaxed mt-0.5">{{ $sourceIntake->rights_and_relief }}</p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </x-ui.card>

                <!-- Incident Particulars Card -->
                <x-ui.card class="p-6 border border-slate-200 bg-white shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-location-dot text-indigo-600"></i>
                        <span>Incident Particulars</span>
                    </h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs">
                        <div>
                            <dt class="font-semibold text-slate-500">Legal Classification</dt>
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
                                <dd class="mt-1 text-blue-800 flex items-center justify-between flex-wrap gap-2">
                                    <span>Tracking Code: <strong class="font-mono">{{ $case->sourceReport->tracking_code }}</strong></span>
                                    <a href="{{ route('admin.reports.review', $case->sourceReport) }}" class="underline text-xs font-semibold">View Original Review</a>
                                </dd>
                            </div>
                        @endif
                    </dl>
                </x-ui.card>

                <!-- Full Screening Checklist Inline Card -->
                <x-ui.card id="screening-checklist-card" class="p-6 border border-slate-200 bg-white shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3 flex-wrap gap-2">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                                <i class="fa-solid fa-scale-balanced text-sm"></i>
                            </span>
                            <div>
                                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                                    Pre-Summons KP Eligibility Screening
                                </h3>
                                <p class="text-[11px] text-slate-500">
                                    Statutory screening under RA 7160 (Katarungang Pambarangay)
                                </p>
                            </div>
                        </div>

                        @if($isAllChecked)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Eligible ✓
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Pending Review
                            </span>
                        @endif
                    </div>

                    @if($isAllChecked)
                        <!-- Collapsible State for Complete Checklist -->
                        <details class="text-xs">
                            <summary class="cursor-pointer font-semibold text-blue-700 hover:text-blue-900 py-1 flex items-center gap-1.5">
                                <span>View / Edit Statutory Criteria</span>
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </summary>
                            <form method="POST" action="{{ route('admin.cases.eligibility', $case) }}" class="space-y-2.5 text-xs mt-3 pt-3 border-t border-slate-100">
                                @csrf
                                <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
                                    <input type="checkbox" name="same_barangay" value="1" @checked(!empty($checklist['same_barangay'])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <div class="space-y-0.5">
                                        <span class="font-medium text-slate-900">Territorial Jurisdiction</span>
                                        <p class="text-[11px] text-slate-500">Parties reside in Barangay Calarian (or adjacent barangays per RA 7160 Sec. 408).</p>
                                    </div>
                                </label>
                                <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
                                    <input type="checkbox" name="kp_matter" value="1" @checked(!empty($checklist['kp_matter'])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <div class="space-y-0.5">
                                        <span class="font-medium text-slate-900">KP Covered Subject Matter</span>
                                        <p class="text-[11px] text-slate-500">Civil dispute or petty offense punishable by &le; 1 year imprisonment or &le; 5,000 PHP fine.</p>
                                    </div>
                                </label>
                                <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
                                    <input type="checkbox" name="non_confidential" value="1" @checked(!empty($checklist['non_confidential'])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <div class="space-y-0.5">
                                        <span class="font-medium text-slate-900">Non-Confidential Matter</span>
                                        <p class="text-[11px] text-slate-500">Matter does NOT involve domestic violence (VAWC), child abuse, or statutory exclusions.</p>
                                    </div>
                                </label>
                                <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
                                    <input type="checkbox" name="residency_verified" value="1" @checked(!empty($checklist['residency_verified'])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <div class="space-y-0.5">
                                        <span class="font-medium text-slate-900">Parties' Residency Verified</span>
                                        <p class="text-[11px] text-slate-500">Parties' identities and barangay residence checked against registry records.</p>
                                    </div>
                                </label>
                                <div class="flex justify-end pt-2 border-t border-slate-100">
                                    <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">
                                        Update Screening
                                    </button>
                                </div>
                            </form>
                        </details>
                    @else
                        <!-- Form Open for Pending Screening -->
                        <form method="POST" action="{{ route('admin.cases.eligibility', $case) }}" class="space-y-2.5 text-xs">
                            @csrf
                            <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
                                <input type="checkbox" name="same_barangay" value="1" @checked(!empty($checklist['same_barangay'])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <div class="space-y-0.5">
                                    <span class="font-medium text-slate-900">Territorial Jurisdiction</span>
                                    <p class="text-[11px] text-slate-500">Parties reside in Barangay Calarian (or adjacent barangays per RA 7160 Sec. 408).</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
                                <input type="checkbox" name="kp_matter" value="1" @checked(!empty($checklist['kp_matter'])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <div class="space-y-0.5">
                                    <span class="font-medium text-slate-900">KP Covered Subject Matter</span>
                                    <p class="text-[11px] text-slate-500">Civil dispute or petty offense punishable by &le; 1 year imprisonment or &le; 5,000 PHP fine.</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
                                <input type="checkbox" name="non_confidential" value="1" @checked(!empty($checklist['non_confidential'])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <div class="space-y-0.5">
                                    <span class="font-medium text-slate-900">Non-Confidential Matter</span>
                                    <p class="text-[11px] text-slate-500">Matter does NOT involve domestic violence (VAWC), child abuse, or statutory exclusions.</p>
                                </div>
                            </label>
                            <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
                                <input type="checkbox" name="residency_verified" value="1" @checked(!empty($checklist['residency_verified'])) class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                <div class="space-y-0.5">
                                    <span class="font-medium text-slate-900">Parties' Residency Verified</span>
                                    <p class="text-[11px] text-slate-500">Parties' identities and barangay residence checked against registry records.</p>
                                </div>
                            </label>
                            <div class="flex justify-end pt-2 border-t border-slate-100">
                                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-800">
                                    Save Screening Checklist
                                </button>
                            </div>
                        </form>
                    @endif
                </x-ui.card>
            </div>

            <!-- Tab 2: Parties -->
            <div id="tab-pane-parties" class="tab-pane hidden space-y-6">
                <div class="flex items-center justify-between pb-1">
                    <h3 class="text-sm font-bold uppercase tracking-wider text-slate-800">Dispute Parties Directory</h3>
                    <a href="{{ route('admin.cases.edit', $case) }}#parties" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800">
                        <i class="fa-solid fa-user-plus"></i>
                        <span>Add / Edit Parties</span>
                    </a>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Complainants Section -->
                    <x-ui.card class="p-5 border border-slate-200 bg-white shadow-sm space-y-3">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-user text-emerald-600"></i>
                                <span>Complainant(s)</span>
                            </span>
                            <span class="text-[11px] font-semibold text-slate-400 font-mono">{{ $case->caseParties->where('role', 'complainant')->count() }}</span>
                        </h4>
                        <div class="divide-y divide-slate-100">
                            @forelse($case->caseParties->where('role', 'complainant') as $cp)
                                <div class="py-3 flex items-start justify-between text-xs gap-3">
                                    <div>
                                        <a href="{{ route('admin.parties.show', $cp->party) }}" class="font-bold text-slate-900 hover:text-blue-600 hover:underline">
                                            {{ $cp->party->full_name }}
                                        </a>
                                        <div class="text-slate-500 mt-0.5 text-[11px]">
                                            {{ $cp->party->address_line ?? $cp->party->purok?->name ?? 'Address not specified' }}
                                            · {{ $cp->party->contact_number ?? 'No contact' }}
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider flex-shrink-0 {{ $cp->residency_at_filing === 'resident' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($cp->residency_at_filing) }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 italic py-3">No complainant recorded.</p>
                            @endforelse
                        </div>
                    </x-ui.card>

                    <!-- Respondents Section -->
                    <x-ui.card class="p-5 border border-slate-200 bg-white shadow-sm space-y-3">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center justify-between border-b border-slate-100 pb-2">
                            <span class="flex items-center gap-1.5">
                                <i class="fa-solid fa-user-shield text-amber-600"></i>
                                <span>Respondent(s)</span>
                            </span>
                            <span class="text-[11px] font-semibold text-slate-400 font-mono">{{ $case->caseParties->where('role', 'respondent')->count() }}</span>
                        </h4>
                        <div class="divide-y divide-slate-100">
                            @forelse($case->caseParties->where('role', 'respondent') as $cp)
                                <div class="py-3 flex items-start justify-between text-xs gap-3">
                                    <div>
                                        <a href="{{ route('admin.parties.show', $cp->party) }}" class="font-bold text-slate-900 hover:text-blue-600 hover:underline">
                                            {{ $cp->party->full_name }}
                                        </a>
                                        <div class="text-slate-500 mt-0.5 text-[11px]">
                                            {{ $cp->party->address_line ?? $cp->party->home_barangay ?? 'Address not specified' }}
                                            · {{ $cp->party->contact_number ?? 'No contact' }}
                                        </div>
                                    </div>
                                    <span class="px-2 py-0.5 rounded text-[10px] font-semibold uppercase tracking-wider flex-shrink-0 {{ $cp->residency_at_filing === 'resident' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700' }}">
                                        {{ ucfirst($cp->residency_at_filing) }}
                                    </span>
                                </div>
                            @empty
                                <p class="text-xs text-slate-400 italic py-3">No respondent recorded.</p>
                            @endforelse
                        </div>
                    </x-ui.card>
                </div>

                <!-- Witnesses Section (Full width if present) -->
                @if($case->caseParties->where('role', 'witness')->isNotEmpty())
                    <x-ui.card class="p-5 border border-slate-200 bg-white shadow-sm space-y-3">
                        <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-1.5 border-b border-slate-100 pb-2">
                            <i class="fa-solid fa-users text-purple-600"></i>
                            <span>Witnesses</span>
                        </h4>
                        <div class="divide-y divide-slate-100">
                            @foreach($case->caseParties->where('role', 'witness') as $cp)
                                <div class="py-2.5 flex items-center justify-between text-xs">
                                    <a href="{{ route('admin.parties.show', $cp->party) }}" class="font-bold text-slate-900 hover:underline">
                                        {{ $cp->party->full_name }}
                                    </a>
                                    <span class="text-slate-500">{{ $cp->party->contact_number ?? 'No contact specified' }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endif
            </div>

            <!-- Tab 3: Summons -->
            <div id="tab-pane-summons" class="tab-pane hidden space-y-6">
                @includeIf('admin.case-actions.partials.summons-tab', ['case' => $case])
            </div>

            <!-- Tab 4: Hearings -->
            <div id="tab-pane-hearings" class="tab-pane hidden space-y-6">
                @includeIf('admin.case-actions.partials.hearings-tab', ['case' => $case])
            </div>

            <!-- Tab 5: Escalation -->
            <div id="tab-pane-escalation" class="tab-pane hidden space-y-6">
                @includeIf('admin.case-actions.partials.escalation-tab', ['case' => $case])
            </div>

            <!-- Tab 6: Notes -->
            <div id="tab-pane-notes" class="tab-pane hidden space-y-6">
                <x-ui.card class="p-6 border border-slate-200 bg-white shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-comment-dots text-blue-600"></i>
                        <span>Internal Staff Notes</span>
                    </h2>

                    <!-- Note Form at Top -->
                    <form method="POST" action="{{ route('admin.cases.notes.store', $case) }}" class="space-y-3">
                        @csrf
                        <textarea
                            name="body"
                            rows="3"
                            required
                            minlength="3"
                            placeholder="Add confidential investigative note, observation, or staff instruction..."
                            class="w-full text-xs border border-slate-300 rounded-lg p-2.5 focus:ring-1 focus:ring-blue-500 focus:border-blue-500"
                        ></textarea>
                        <div class="flex justify-end">
                            <button type="submit" class="px-3.5 py-1.5 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
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
                            <div class="text-center py-6 text-slate-400 italic text-xs">
                                <i class="fa-solid fa-note-sticky text-2xl text-slate-300 mb-1.5 block"></i>
                                <span>No internal staff notes recorded yet.</span>
                            </div>
                        @endforelse
                    </div>
                </x-ui.card>
            </div>

            <!-- Tab 7: Timeline & Logs -->
            <div id="tab-pane-timeline" class="tab-pane hidden space-y-6">
                <!-- Full-Width Vertical Timeline Card -->
                <x-ui.card class="p-6 border border-slate-200 bg-white shadow-sm space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left text-blue-600"></i>
                        <span>Official Status Transition History</span>
                    </h2>
                    <div class="flow-root mt-4">
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
                                                    <p class="font-medium text-slate-900 leading-snug">
                                                        Status transitioned to <strong class="text-blue-700 font-semibold">{{ \App\Support\CaseStatus::label($log->to_status) }}</strong>
                                                        @if($log->from_status)
                                                            <span class="text-slate-500">(from {{ \App\Support\CaseStatus::label($log->from_status) }})</span>
                                                        @endif
                                                    </p>
                                                    @if($log->note)
                                                        <p class="text-slate-600 italic mt-1 bg-slate-50 p-2 rounded border border-slate-100 leading-relaxed">{{ $log->note }}</p>
                                                    @endif
                                                </div>
                                                <div class="text-right whitespace-nowrap text-slate-500 flex-shrink-0">
                                                    <div class="font-medium">{{ $log->created_at?->format('M d, Y') ?? '—' }}</div>
                                                    <div class="text-[10px] text-slate-400 mt-0.5">{{ $log->user?->name ?? 'System' }}</div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </x-ui.card>

                <!-- RA 10173 Confidential Access Log -->
                @if($case->is_confidential && auth()->user()->isAdmin())
                    <details class="rounded-xl border border-red-200 bg-red-50/20 p-5 space-y-2">
                        <summary class="cursor-pointer text-xs font-bold text-red-900 uppercase tracking-wider flex items-center gap-2 list-none">
                            <i class="fa-solid fa-shield-halved text-red-600"></i>
                            <span>RA 10173 Confidential Access Log (Administrator Audit)</span>
                            <i class="fa-solid fa-chevron-down text-[10px] text-red-500 ml-auto"></i>
                        </summary>
                        <div class="pt-3 border-t border-red-100 text-xs text-slate-600 space-y-2">
                            <p>Recorded access audit entries for this confidential record per Data Privacy Act regulations.</p>
                            <p class="text-[11px] text-slate-500">
                                Audited view activities are automatically recorded in central <a href="{{ route('admin.audit-log.index') }}" class="text-blue-600 underline font-semibold">Audit Trail</a>.
                            </p>
                        </div>
                    </details>
                @endif
            </div>

        </main>

        <!-- Right 1 Column: "Case at a glance" (Sticky on every tab) -->
        <aside class="lg:col-span-1 lg:sticky lg:top-24 self-start space-y-6">

            <!-- Case at a glance Container -->
            <x-ui.card class="p-5 border border-slate-200 bg-white shadow-sm space-y-5 text-xs">
                <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center gap-1.5">
                    <i class="fa-solid fa-gauge text-blue-600"></i>
                    <span>Case at a Glance</span>
                </h3>

                <!-- Screening Status -->
                <div class="space-y-1.5 pb-3 border-b border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Statutory Screening</span>
                    <div class="flex items-center justify-between pt-0.5">
                        @if($isAllChecked)
                            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                                <i class="fa-solid fa-circle-check text-emerald-600"></i> Eligible ✓
                            </span>
                            <button type="button" onclick="switchCaseTab('overview'); document.getElementById('screening-checklist-card')?.scrollIntoView({behavior:'smooth'});" class="text-xs font-medium text-blue-600 hover:underline">
                                Edit
                            </button>
                        @else
                            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">
                                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Pending Review
                            </span>
                            <button type="button" onclick="switchCaseTab('overview'); document.getElementById('screening-checklist-card')?.scrollIntoView({behavior:'smooth'});" class="text-xs font-medium text-blue-600 hover:underline">
                                View checklist
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Working-Day Mediation Clock (Sec. 410b RA 7160) -->
                <div class="space-y-1.5 pb-3 border-b border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Mediation Clock (15 Working Days)</span>
                    @php
                        $clock = $nextStep['mediation_clock'] ?? null;
                    @endphp
                    @if($clock && $clock['is_started'])
                        <div class="pt-0.5 space-y-1">
                            <div class="flex items-center justify-between text-xs font-semibold">
                                <span class="{{ $clock['is_overdue'] ? 'text-red-700' : ($clock['is_warning'] ? 'text-amber-700' : 'text-blue-900') }}">
                                    {{ $clock['display'] }}
                                </span>
                            </div>
                            @if(!$clock['is_overdue'])
                                @php
                                    $periodDays = $clock['period'] ?? config('katarungang_pambarangay.mediation_period_days', 15);
                                    $dayNum = $clock['day_current'] ?? 1;
                                    $pct = min(100, round(($dayNum / max(1, $periodDays)) * 100));
                                @endphp
                                <div class="w-full bg-slate-100 rounded-full h-2 overflow-hidden">
                                    <div class="h-2 rounded-full transition-all {{ $clock['is_warning'] ? 'bg-amber-500' : 'bg-blue-600' }}" style="width: {{ $pct }}%"></div>
                                </div>
                            @endif
                        </div>
                    @else
                        <div class="pt-1 text-slate-500 text-xs">
                            {{ $clock['display'] ?? 'Starts at first scheduled hearing (TBD)' }}
                        </div>
                    @endif
                </div>

                <!-- Next Hearing -->
                <div class="space-y-1.5 pb-3 border-b border-slate-100">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider">Next Hearing</span>
                    <div class="pt-0.5">
                        @if($nextHearing)
                            <div class="bg-blue-50/60 p-2.5 rounded-lg border border-blue-100 text-xs space-y-0.5">
                                <div class="font-bold text-blue-950 flex items-center gap-1">
                                    <i class="fa-solid fa-calendar-day text-blue-600"></i>
                                    <span>{{ $nextHearing->scheduled_at->format('M d, Y · h:i A') }}</span>
                                </div>
                                <p class="text-[11px] text-blue-800">{{ $nextHearing->venue ?? 'Barangay Hall' }}</p>
                            </div>
                        @else
                            <p class="text-xs text-slate-400 italic">No hearing scheduled.</p>
                        @endif
                    </div>
                </div>

                <!-- Docket Details -->
                <div class="space-y-2">
                    <span class="text-[11px] font-bold text-slate-500 uppercase tracking-wider block">Docket Details</span>

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

                    <div class="flex justify-between items-center py-1 border-b border-slate-50">
                        <span class="text-slate-500">Assigned Conciliator</span>
                        <div class="flex items-center gap-1">
                            <span class="font-bold text-blue-700">{{ $case->assignedTo?->name ?? 'Unassigned' }}</span>
                            @if(!$case->assignedTo)
                                <button type="button" onclick="openAssignModal()" class="text-blue-600 hover:underline text-[11px] font-semibold ml-1">
                                    Assign
                                </button>
                            @endif
                        </div>
                    </div>

                    @if($case->settled_on)
                        @php
                            $repEnds = $case->repudiation_ends_at ?? $case->settled_on->copy()->addDays((int) config('katarungang_pambarangay.repudiation_period_days', 10));
                            $isRepActive = empty($case->repudiated_at) && now()->lt($repEnds);
                        @endphp
                        <div class="space-y-1.5 py-1.5 border-b border-slate-50 {{ $case->repudiated_at ? 'bg-red-50/70 border-red-200' : 'bg-emerald-50/70 border-emerald-200' }} p-2.5 rounded border mt-2">
                            <div class="flex justify-between items-center text-xs">
                                <span class="{{ $case->repudiated_at ? 'text-red-800' : 'text-emerald-800' }} font-semibold">Settlement Status</span>
                                @if($case->repudiated_at)
                                    <span class="font-bold text-red-700">Repudiated</span>
                                @elseif($case->court_transmitted_at)
                                    <span class="font-bold text-emerald-800">Final · Transmitted to MTC</span>
                                @elseif($isRepActive)
                                    <span class="font-bold text-emerald-800">Signed (10-Day Window)</span>
                                @else
                                    <span class="font-bold text-emerald-800">Final &amp; Executory</span>
                                @endif
                            </div>
                            <div class="flex justify-between items-center text-xs">
                                <span class="text-slate-600 font-medium">Signed On</span>
                                <span class="font-bold text-slate-900">{{ $case->settled_on?->format('M d, Y') ?? '—' }}</span>
                            </div>
                            @if($isRepActive)
                                <div class="flex justify-between items-center text-[11px] text-emerald-800">
                                    <span>Repudiation Ends</span>
                                    <span class="font-semibold">{{ $repEnds->format('M d, Y') }}</span>
                                </div>
                            @endif
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
                </div>
            </x-ui.card>

        </aside>

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
            <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                <i class="fa-solid fa-triangle-exclamation mr-1 text-amber-600"></i>
                <strong>Notice:</strong> Changing status manually bypasses the guided milestone checks. A formal administrative justification is mandatory.
            </div>
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
                <label class="block font-semibold text-slate-700 mb-1">Official Transition Reason & Justification <span class="text-red-500">*</span></label>
                <textarea
                    name="note"
                    rows="3"
                    required
                    minlength="5"
                    maxlength="1000"
                    placeholder="State reason or official order for bypassing guided steps..."
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

<!-- Modal: Start 15-Day Mediation Clock -->
<div id="start-clock-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-stopwatch text-blue-600"></i> Initiate 15-Day Mediation Clock
            </h3>
            <button type="button" onclick="closeStartClockModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.cases.first-meeting', $case) }}" class="space-y-4 text-xs">
            @csrf
            <div class="bg-amber-50 p-3 rounded-lg border border-amber-200 text-amber-900 space-y-1">
                <p class="font-bold flex items-center gap-1.5">
                    <i class="fa-solid fa-scale-balanced text-amber-700"></i>
                    <span>Statutory Legal Timer</span>
                </p>
                <p class="text-[11px] leading-relaxed text-amber-800">
                    Recording the first appearance date formally starts the statutory 15-working-day mediation period before the Punong Barangay (RA 7160 Sec. 410b).
                </p>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">First Personal Confrontation Date <span class="text-red-500">*</span></label>
                <input
                    type="date"
                    name="first_meeting_on"
                    value="{{ old('first_meeting_on', today()->toDateString()) }}"
                    required
                    class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500 bg-white"
                >
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeStartClockModal()" class="px-3 py-1.5 text-slate-600 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-1.5 font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm">Confirm & Start Clock</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Escalate Case Confirmation -->
<div id="escalate-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-red-900 flex items-center gap-2">
                <i class="fa-solid fa-triangle-exclamation text-red-600"></i> Confirm Statutory Escalation & CFA
            </h3>
            <button type="button" onclick="closeEscalateModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="space-y-3 text-xs text-slate-700">
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-red-900 space-y-1">
                <p class="font-bold">Legal Consequences under Katarungang Pambarangay:</p>
                <ul class="list-disc pl-4 space-y-1 text-[11px] text-red-800">
                    <li>Issuing a Certification to File Action (CFA) certifies that barangay conciliation has terminated without settlement.</li>
                    <li>This enables the complainant to proceed with formal court, prosecutor, or police action.</li>
                    <li>Requires confirmation by the Punong Barangay or Pangkat Chairman.</li>
                </ul>
            </div>
            <p>Do you wish to proceed to the official Escalation & CFA filing form?</p>
        </div>

        <div class="flex justify-end gap-2 pt-3 border-t border-slate-100">
            <button type="button" onclick="closeEscalateModal()" class="px-3 py-1.5 text-slate-600 font-medium">Cancel</button>
            <a href="{{ route('admin.cases.escalations.create', $case) }}" class="px-4 py-1.5 font-bold text-white bg-red-600 rounded-lg hover:bg-red-700 shadow-sm">
                Proceed to CFA Filing
            </a>
        </div>
    </div>
</div>

@include('admin.cases.partials.workflow-modals', ['case' => $case, 'staffMembers' => $staffMembers])

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
    function openStartClockModal() { document.getElementById('start-clock-modal').classList.remove('hidden'); }
    function closeStartClockModal() { document.getElementById('start-clock-modal').classList.add('hidden'); }
    function openEscalateModal() { document.getElementById('escalate-modal').classList.remove('hidden'); }
    function closeEscalateModal() { document.getElementById('escalate-modal').classList.add('hidden'); }

    const urlParams = new URLSearchParams(window.location.search);
    const initialTab = urlParams.get('tab');
    if (initialTab) {
        switchCaseTab(initialTab);
    }
</script>
@endsection
