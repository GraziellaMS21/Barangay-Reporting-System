@extends('layouts.admin')

@section('title', 'Review Complaint Intake #' . $intake->id)

@section('content')
@php
    $aiSuggestions = $intake->ai_suggestions ?? [];
    $aiStatus = $intake->ai_status ?? 'pending';
    $aiReady = $aiStatus === 'done' && !empty($aiSuggestions);
    $detectedLang = $intake->detected_language ?? $aiSuggestions['detected_language'] ?? 'English';
    $aiConfidence = $intake->ai_confidence ?? $aiSuggestions['confidence'] ?? null;
    $referenceCode = 'CMP-' . str_pad($intake->id, 5, '0', STR_PAD_LEFT);

    // Initial matched category
    $suggestedCatName = $aiSuggestions['suggested_category'] ?? $intake->case_type_suggestion ?? '';
    $matchedCaseType = $caseTypes->first(function ($ct) use ($suggestedCatName) {
        return stripos($ct->name, $suggestedCatName) !== false || stripos($suggestedCatName, $ct->name) !== false;
    }) ?? $caseTypes->first();
@endphp

<div x-data="{
    reviewedFields: {
        what_happened: true,
        who_involved: true,
        where_happened: true,
        why_how_happened: true,
        rights_violated: true,
        relief_requested: true,
    },
    get reviewedCount() {
        return Object.values(this.reviewedFields).filter(Boolean).length;
    },
    totalFields: 6,
    sameBarangay: true,
    kpMatter: true,
    nonConfidential: true,
    residencyVerified: true,
    get canDocket() {
        return this.reviewedCount >= this.totalFields && 
               this.sameBarangay && 
               this.kpMatter && 
               this.nonConfidential && 
               this.residencyVerified;
    },
    rejectModalOpen: false,
    selectedTemplate: '',
    applyTemplate(text) {
        const input = document.getElementById('complainant_message');
        if (input) {
            input.value = text;
        }
    }
}" 
@field-reviewed.window="reviewedFields[$event.detail.field] = true"
class="max-w-7xl mx-auto space-y-6 pb-24">

    <!-- Top Navigation & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <nav class="flex text-xs text-slate-500 mb-1.5" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="{{ route('admin.cases.index', ['tab' => 'pending_intake']) }}" class="hover:text-slate-800 transition-colors font-medium">← Back to Pending Intakes</a></li>
                    <li><span class="text-slate-400">/</span></li>
                    <li class="font-semibold text-slate-800">{{ $referenceCode }}</li>
                </ol>
            </nav>
            <div class="flex items-center flex-wrap gap-2.5">
                <h1 class="text-2xl font-bold font-mono text-slate-900">Intake {{ $referenceCode }}</h1>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                    <span>Submitted / Pending Intake</span>
                </span>
                @if($detectedLang)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200">
                        <i class="fa-solid fa-language text-slate-500"></i>
                        <span>{{ $detectedLang }}</span>
                    </span>
                @endif
                @if($aiConfidence)
                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium bg-purple-50 text-purple-700 border border-purple-200">
                        <i class="fa-solid fa-sparkles text-purple-500"></i>
                        <span>AI Confidence: {{ (float)$aiConfidence > 1 ? $aiConfidence : round((float)$aiConfidence * 100) }}%</span>
                    </span>
                @endif
            </div>
            <p class="text-xs text-slate-600 mt-1">Submitted {{ $intake->submitted_at?->format('F j, Y \a\t g:i A') }} ({{ $intake->submitted_at?->diffForHumans() }})</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" @click="rejectModalOpen = true" class="px-3 py-2 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
                <i class="fa-solid fa-ban mr-1"></i> Reject Intake
            </button>
        </div>
    </div>

    <!-- 4-Step Katarungang Pambarangay Progress Tracker -->
    <div class="bg-white p-4 rounded-xl border border-slate-200 shadow-xs">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-blue-600 text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-sm">1</div>
                <div>
                    <span class="block text-xs font-bold text-blue-700">Step 1: Review</span>
                    <span class="block text-[11px] text-slate-500">AI Extraction & Facts</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-300 text-slate-700 font-bold text-xs flex items-center justify-center shrink-0">2</div>
                <div>
                    <span class="block text-xs font-semibold text-slate-800">Step 2: Docket</span>
                    <span class="block text-[11px] text-slate-500">Blotter & Case Entry</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-300 text-slate-400 font-bold text-xs flex items-center justify-center shrink-0">3</div>
                <div>
                    <span class="block text-xs font-medium text-slate-500">Step 3: Mediation</span>
                    <span class="block text-[11px] text-slate-400">Summons & Appearance</span>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-full bg-slate-100 border border-slate-300 text-slate-400 font-bold text-xs flex items-center justify-center shrink-0">4</div>
                <div>
                    <span class="block text-xs font-medium text-slate-500">Step 4: Resolve</span>
                    <span class="block text-[11px] text-slate-400">Settlement / Escalation</span>
                </div>
            </div>
        </div>
    </div>

    @if (session('status'))
        <div class="p-3.5 bg-blue-50 border border-blue-200 text-blue-800 text-xs rounded-xl flex items-center gap-2">
            <i class="fa-solid fa-circle-info text-blue-600"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if($aiStatus === 'failed')
        <div class="p-4 bg-amber-50 border border-amber-200 text-amber-900 text-xs rounded-xl flex items-center justify-between gap-3">
            <div class="flex items-center gap-2.5">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-base"></i>
                <span>The AI assistant was temporarily unavailable. You can review the raw narratives and structure the case particulars manually, or retry analysis.</span>
            </div>
            <form action="{{ route('admin.cases.intakes.retry-ai', $intake) }}" method="POST">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-300 transition-colors">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Retry AI Analysis
                </button>
            </form>
        </div>
    @endif

    <!-- Form for Review & Docketing -->
    <form action="{{ route('admin.cases.intakes.docket', $intake) }}" method="POST" id="docketForm">
        @csrf

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

            <!-- Left 2 Columns: Step 1 & Step 2 Review Blocks -->
            <div class="lg:col-span-2 space-y-6">

                <!-- 1. Parties Information (Read-Only) -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-users text-blue-600"></i>
                        <span>1. Dispute Parties (Citizen Submission)</span>
                    </h2>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <!-- Complainant -->
                        <div class="p-3.5 rounded-lg bg-blue-50/50 border border-blue-200/80 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-blue-900 uppercase tracking-wider text-[10px]">Complainant (Aggrieved Party)</span>
                                <x-ui.provenance-badge provenance="resident" />
                            </div>
                            <div class="font-bold text-slate-900 text-sm">{{ $intake->complainant_name }}</div>
                            @if($intake->complainant_alias)
                                <div class="text-slate-600"><span class="font-semibold text-slate-500">Alias:</span> {{ $intake->complainant_alias }}</div>
                            @endif
                            <div class="text-slate-600"><span class="font-semibold text-slate-500">Contact:</span> {{ $intake->complainant_contact }}</div>
                            <div class="text-slate-600"><span class="font-semibold text-slate-500">Address:</span> {{ $intake->complainant_address }}</div>
                            <div>
                                <span class="font-semibold text-slate-500">Status:</span>
                                <span class="font-bold text-blue-700 capitalize">{{ str_replace('_', ' ', $intake->complainant_residency_claim) }}</span>
                            </div>
                        </div>

                        <!-- Respondent -->
                        <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-slate-800 uppercase tracking-wider text-[10px]">Respondent (Summons Recipient)</span>
                                <x-ui.provenance-badge provenance="resident" />
                            </div>
                            <div class="font-bold text-slate-900 text-sm">{{ $intake->respondent_name }}</div>
                            @if($intake->respondent_alias)
                                <div class="text-slate-600"><span class="font-semibold text-slate-500">Alias:</span> {{ $intake->respondent_alias }}</div>
                            @endif
                            <div class="text-slate-600"><span class="font-semibold text-slate-500">Contact:</span> {{ $intake->respondent_contact ?: 'Not provided' }}</div>
                            <div class="text-slate-600"><span class="font-semibold text-slate-500">Address:</span> {{ $intake->respondent_address }}</div>
                            <div class="text-[11px] text-amber-700">
                                <i class="fa-solid fa-location-crosshairs mr-1"></i> Target for summons delivery
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Verbatim Raw Submissions (Read-Only) -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                        <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                            <i class="fa-solid fa-file-lines text-indigo-600"></i>
                            <span>2. Resident Verbatim Narratives</span>
                        </h2>
                        <x-ui.provenance-badge provenance="resident" />
                    </div>

                    <div class="space-y-4 text-xs font-sans">
                        <div>
                            <span class="block font-semibold text-slate-600 uppercase tracking-wider text-[11px] mb-1">Raw Section 3: Incident Narrative</span>
                            <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $intake->incident_narrative ?? $intake->what_happened }}</div>
                        </div>

                        <div>
                            <span class="block font-semibold text-slate-600 uppercase tracking-wider text-[11px] mb-1">Raw Section 4: Rights Violated & Relief Requested</span>
                            <div class="p-3.5 rounded-lg bg-slate-50 border border-slate-200 text-slate-800 whitespace-pre-wrap leading-relaxed">{{ $intake->rights_and_relief ?? ($intake->rights_violated . ' ' . $intake->relief_requested) }}</div>
                        </div>

                        @if($intake->case_type_suggestion)
                            <div>
                                <span class="font-semibold text-slate-500">Complainant Suggested Type:</span>
                                <span class="font-bold text-slate-800 ml-1">{{ $intake->case_type_suggestion }}</span>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- 3. Review AI Extraction & Structure Particulars -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-5">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i>
                                <span>3. AI-Assisted Intake Review & Facts</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">Review and confirm extracted dispute particulars prior to docketing.</p>
                        </div>
                        <div class="text-right">
                            <span class="text-xs font-bold text-blue-700 bg-blue-50 px-2.5 py-1 rounded-full border border-blue-200">
                                <span x-text="reviewedCount"></span> of <span x-text="totalFields"></span> reviewed
                            </span>
                        </div>
                    </div>

                    <!-- Short Fields Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-review.short-field-card
                            label="Incident Location"
                            field-name="where_happened"
                            :resident-value="$intake->where_happened ?? 'Barangay Calarian'"
                            :ai-value="$aiSuggestions['incident_location'] ?? null"
                            :current-value="$intake->where_happened"
                            :provenance="$intake->field_provenance['where_happened'] ?? 'ai'"
                        />

                        <x-review.short-field-card
                            label="Incident Date & Time"
                            field-name="when_happened"
                            :resident-value="$intake->when_happened?->format('Y-m-d H:i') ?? null"
                            :ai-value="$aiSuggestions['incident_datetime'] ?? null"
                            :current-value="$intake->when_happened?->format('Y-m-d\TH:i')"
                            :provenance="$intake->field_provenance['when_happened'] ?? 'ai'"
                        />

                        <div class="md:col-span-2">
                            <x-review.short-field-card
                                label="Persons Involved / Witnesses"
                                field-name="who_involved"
                                :resident-value="$intake->who_involved"
                                :ai-value="$aiSuggestions['who_involved'] ?? null"
                                :current-value="$intake->who_involved"
                                :provenance="$intake->field_provenance['who_involved'] ?? 'ai'"
                            />
                        </div>
                    </div>

                    <!-- Long Structured Narrative Blocks -->
                    <div class="space-y-4">
                        <x-review.diff-field-block
                            label="Factual Summary (What Happened)"
                            field-name="what_happened"
                            :resident-text="$intake->incident_narrative ?? $intake->what_happened"
                            :ai-text="$aiSuggestions['what_happened'] ?? null"
                            :current-value="$intake->what_happened"
                            :provenance="$intake->field_provenance['what_happened'] ?? 'ai'"
                        />

                        <x-review.diff-field-block
                            label="Circumstances & Context (Why / How)"
                            field-name="why_how_happened"
                            :resident-text="$intake->incident_narrative ?? $intake->why_how_happened"
                            :ai-text="$aiSuggestions['circumstances'] ?? null"
                            :current-value="$intake->why_how_happened"
                            :provenance="$intake->field_provenance['why_how_happened'] ?? 'ai'"
                        />

                        <x-review.diff-field-block
                            label="Rights Violated / Injury Suffered"
                            field-name="rights_violated"
                            :resident-text="$intake->rights_and_relief ?? $intake->rights_violated"
                            :ai-text="$aiSuggestions['rights_violated'] ?? null"
                            :current-value="$intake->rights_violated"
                            :provenance="$intake->field_provenance['rights_violated'] ?? 'ai'"
                        />

                        <x-review.diff-field-block
                            label="Relief Requested / Settlement Terms"
                            field-name="relief_requested"
                            :resident-text="$intake->rights_and_relief ?? $intake->relief_requested"
                            :ai-text="$aiSuggestions['relief_requested'] ?? null"
                            :current-value="$intake->relief_requested"
                            :provenance="$intake->field_provenance['relief_requested'] ?? 'ai'"
                        />
                    </div>
                </div>

                <!-- 4. KP Eligibility Checklist & Docketing Settings -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-4">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-clipboard-check text-emerald-600"></i>
                        <span>4. Katarungang Pambarangay (KP) Eligibility Screening</span>
                    </h2>

                    <div class="space-y-3 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="kp_eligibility[same_barangay]" value="1" x-model="sameBarangay" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-slate-800 font-medium">Parties reside in Barangay Calarian (or adjacent barangays without territorial conflict).</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="kp_eligibility[kp_matter]" value="1" x-model="kpMatter" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-slate-800 font-medium">Dispute falls under Lupon jurisdiction (punishable by imprisonment not exceeding 1 year or fine not exceeding ₱5,000).</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="kp_eligibility[non_confidential]" value="1" x-model="nonConfidential" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-slate-800 font-medium">Screened against exempt matters (does NOT involve VAWC RA 9262, Child Abuse RA 7610, or urgent police matters).</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="kp_eligibility[residency_verified]" value="1" x-model="residencyVerified" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-slate-800 font-medium">Complainant residency status confirmed by official records or verified declaration.</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label for="case_type_id" class="block text-xs font-bold text-slate-700 mb-1">Official Case Type <span class="text-red-600">*</span></label>
                            <select name="case_type_id" id="case_type_id" required class="w-full text-xs rounded-lg border-slate-300 focus:ring-blue-500 p-2.5 bg-white border">
                                @foreach($caseTypes as $type)
                                    <option value="{{ $type->id }}" @selected($matchedCaseType && $matchedCaseType->id === $type->id)>
                                        {{ $type->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="purok_id" class="block text-xs font-bold text-slate-700 mb-1">Purok Area</label>
                            <select name="purok_id" id="purok_id" class="w-full text-xs rounded-lg border-slate-300 focus:ring-blue-500 p-2.5 bg-white border">
                                <option value="">-- Barangay-wide / Not specified --</option>
                                @foreach($puroks as $purok)
                                    <option value="{{ $purok->id }}">{{ $purok->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="assigned_to" class="block text-xs font-bold text-slate-700 mb-1">Assigned Conciliator / Officer</label>
                            <select name="assigned_to" id="assigned_to" class="w-full text-xs rounded-lg border-slate-300 focus:ring-blue-500 p-2.5 bg-white border">
                                <option value="">-- Select Lupon Conciliator --</option>
                                @foreach($staffUsers as $staff)
                                    <option value="{{ $staff->id }}" @selected(auth()->id() === $staff->id)>{{ $staff->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Message to Complainant -->
                    <div class="pt-2">
                        <label for="complainant_message" class="block text-xs font-bold text-slate-700 mb-1">Message to Complainant (Optional Notification Note)</label>
                        <div class="flex items-center flex-wrap gap-1.5 mb-2">
                            <button type="button" @click="applyTemplate('Your complaint has been formally docketed. Please visit Barangay Calarian Hall for summons signing.')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium border border-slate-200">
                                In-person signing chip
                            </button>
                            <button type="button" @click="applyTemplate('Your complaint was reviewed and accepted. An initial mediation hearing is being scheduled.')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium border border-slate-200">
                                Mediation scheduled chip
                            </button>
                        </div>
                        <input type="text" name="message_to_complainant" id="complainant_message" placeholder="Optional brief update for complainant records..." class="w-full text-xs rounded-lg border-slate-300 focus:ring-blue-500 p-2.5 bg-white border">
                    </div>
                </div>

            </div>

            <!-- Right Column: Case Number Preview & Sticky Docket Bar -->
            <div class="lg:col-span-1 space-y-6 lg:sticky lg:top-20">

                <!-- Docket & Blotter Preview Card -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-4">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2 flex items-center gap-2">
                        <i class="fa-solid fa-stamp text-blue-600"></i>
                        <span>Docket Identification Preview</span>
                    </h3>

                    <div class="space-y-3 text-xs">
                        <div class="p-3 bg-blue-50/50 rounded-lg border border-blue-200">
                            <span class="block text-[10px] font-bold uppercase text-blue-900">Case Docket Number</span>
                            <span class="font-mono font-bold text-base text-blue-700">{{ $caseNumberPreview }}</span>
                        </div>
                        <div class="p-3 bg-slate-50 rounded-lg border border-slate-200">
                            <span class="block text-[10px] font-bold uppercase text-slate-600">Blotter Reference Number</span>
                            <span class="font-mono font-bold text-sm text-slate-800">{{ $blotterPreview }}</span>
                        </div>
                    </div>
                </div>

                <!-- Legal Compliance Card -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-xs p-5 space-y-3 text-xs text-slate-600">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider flex items-center gap-1.5">
                        <i class="fa-solid fa-shield-halved text-emerald-600"></i>
                        <span>Legal Authority</span>
                    </h3>
                    <p class="leading-relaxed">
                        Republic Act No. 7160 (Local Government Code), Chapter 7 — Katarungang Pambarangay. Conciliation proceedings are mandatory prior to court filing.
                    </p>
                    <div class="pt-2 border-t border-slate-100 flex items-center gap-1.5 text-[11px] text-slate-500">
                        <i class="fa-solid fa-lock text-slate-400"></i>
                        <span>RA 10173 Data Privacy consent recorded on {{ $intake->consent_at?->format('M d, Y') ?? 'submission' }}.</span>
                    </div>
                </div>

                <!-- Sticky Docket Action Card -->
                <div class="bg-white rounded-xl border border-slate-200 shadow-md p-5 space-y-4">
                    <h3 class="text-xs font-bold text-slate-900 uppercase tracking-wider">
                        Intake Decision
                    </h3>

                    <div class="text-xs space-y-2">
                        <div class="flex items-center justify-between text-slate-700">
                            <span>Review Gate:</span>
                            <span class="font-bold" :class="reviewedCount >= totalFields ? 'text-emerald-600' : 'text-amber-600'">
                                <span x-text="reviewedCount"></span> / <span x-text="totalFields"></span> resolved
                            </span>
                        </div>
                        <div class="flex items-center justify-between text-slate-700">
                            <span>KP Eligibility:</span>
                            <span class="font-bold" :class="canDocket ? 'text-emerald-600' : 'text-amber-600'">
                                <span x-text="canDocket ? 'Passed' : 'Incomplete'"></span>
                            </span>
                        </div>
                    </div>

                    <button type="submit" 
                            :disabled="!canDocket"
                            class="w-full py-3 px-4 rounded-xl font-bold text-xs text-white shadow-sm transition-all flex items-center justify-center gap-2"
                            :class="canDocket ? 'bg-blue-600 hover:bg-blue-700 cursor-pointer' : 'bg-slate-300 cursor-not-allowed opacity-60'">
                        <i class="fa-solid fa-gavel"></i>
                        <span>Confirm and Docket Case</span>
                    </button>
                    <p class="text-[11px] text-slate-500 text-center">
                        Generates official Case {{ $caseNumberPreview }} and moves intake to docket.
                    </p>
                </div>

            </div>

        </div>
    </form>

    <!-- Reject Intake Modal -->
    <div x-show="rejectModalOpen" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="rejectModalOpen" @click="rejectModalOpen = false" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div x-show="rejectModalOpen" class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-ban text-red-600"></i>
                        <span>Reject Citizen Complaint Intake</span>
                    </h3>
                    <button type="button" @click="rejectModalOpen = false" class="text-slate-400 hover:text-slate-600">
                        <i class="fa-solid fa-xmark"></i>
                    </button>
                </div>

                <form action="{{ route('admin.cases.intakes.reject', $intake) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label for="outcome_reason" class="block text-xs font-bold text-slate-700 mb-1">Reason for Rejection <span class="text-red-600">*</span></label>
                        <select name="outcome_reason" id="outcome_reason" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                            <option value="Non-resident / Lack of jurisdiction">Non-resident / Lack of jurisdiction</option>
                            <option value="Exempt matter / VAWC / Child Protection">Exempt matter / VAWC / Child Protection (Refer to PNP/DSWD)</option>
                            <option value="Criminal offense beyond Lupon jurisdiction">Criminal offense beyond Lupon jurisdiction</option>
                            <option value="Incomplete or unverifiable information">Incomplete or unverifiable information</option>
                            <option value="Withdrawn by complainant">Withdrawn by complainant</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div>
                        <label for="explanation_note" class="block text-xs font-bold text-slate-700 mb-1">Detailed Explanation Note <span class="text-red-600">*</span></label>
                        <textarea name="explanation_note" id="explanation_note" rows="3" required placeholder="Explain why this complaint request was rejected..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border font-sans"></textarea>
                    </div>

                    <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                        <button type="button" @click="rejectModalOpen = false" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
                            Cancel
                        </button>
                        <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700">
                            Confirm Rejection
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

</div>
@endsection
