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

<div class="max-w-7xl mx-auto space-y-6 pb-24">

    <!-- Top Navigation & Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <nav class="flex text-xs text-slate-500 mb-1.5" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="{{ route('admin.cases.index', ['tab' => 'pending_intake']) }}" data-turbo="false" class="hover:text-slate-800 transition-colors font-medium">← Back to Pending Intakes</a></li>
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
            @if($aiReady)
                <button type="button" onclick="acceptAllAiSuggestions()" class="px-3.5 py-2 text-xs font-semibold text-purple-800 bg-purple-50 hover:bg-purple-100 border border-purple-200 rounded-lg transition-colors shadow-xs">
                    <i class="fa-solid fa-wand-magic-sparkles mr-1 text-purple-600"></i> Accept All AI
                </button>
            @endif
            <button type="button" onclick="openRejectIntakeModal()" class="px-3 py-2 text-xs font-semibold text-red-700 bg-red-50 border border-red-200 rounded-lg hover:bg-red-100 transition-colors">
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
            <form action="{{ route('admin.cases.intakes.retry-ai', $intake) }}" method="POST" data-turbo="false">
                @csrf
                <button type="submit" class="px-3 py-1.5 rounded-lg text-xs font-bold text-amber-800 bg-amber-100 hover:bg-amber-200 border border-amber-300 transition-colors">
                    <i class="fa-solid fa-arrows-rotate mr-1"></i> Retry AI Analysis
                </button>
            </form>
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-red-50 border border-red-200 text-red-800 text-xs rounded-xl space-y-1">
            <div class="font-bold flex items-center gap-1.5 text-sm">
                <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                <span>Unable to docket case. Please correct the following:</span>
            </div>
            <ul class="list-disc list-inside space-y-0.5 ml-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Form for Review & Docketing -->
    <form action="{{ route('admin.cases.intakes.docket', $intake) }}" method="POST" id="docketForm" data-turbo="false" onsubmit="return validateDocketSubmission(event)">
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
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                                <i class="fa-solid fa-wand-magic-sparkles text-purple-600"></i>
                                <span>3. AI-Assisted Intake Review & Facts</span>
                            </h2>
                            <p class="text-xs text-slate-500 mt-0.5">
                                @if($intake->ai_status === 'failed')
                                    <span class="font-semibold text-amber-700">AI extraction unavailable. Fields below must be completed manually.</span>
                                @else
                                    Review, edit, or accept dispute particulars before generating the case docket.
                                @endif
                            </p>
                        </div>

                        <div class="flex items-center gap-2">
                            <button type="button" 
                                    onclick="acceptAllAiSuggestions()" 
                                    @if($intake->ai_status === 'failed' || empty($aiSuggestions)) disabled @endif
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-bold transition-colors flex items-center gap-1.5 {{ ($intake->ai_status === 'failed' || empty($aiSuggestions)) ? 'bg-slate-100 text-slate-400 border border-slate-200 cursor-not-allowed opacity-60' : 'bg-purple-100 text-purple-800 hover:bg-purple-200 border border-purple-300' }}">
                                <i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i>
                                <span>Accept All AI</span>
                            </button>
                            <button type="button" 
                                    onclick="resetAllToResident()"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-700 bg-slate-100 hover:bg-slate-200 border border-slate-200 transition-colors">
                                <span>Reset All</span>
                            </button>
                        </div>
                    </div>

                    <!-- Short Fields Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <x-review.short-field-card
                            label="Incident Location"
                            field-name="where_happened"
                            :resident-value="$intake->where_happened"
                            :ai-value="$aiSuggestions['incident_location'] ?? null"
                            :current-value="$intake->where_happened"
                            :provenance="$intake->field_provenance['where_happened'] ?? null"
                            :ai-status="$intake->ai_status ?? 'completed'"
                        />

                        @php
                            $validWhenDate = $intake->when_happened?->format('Y-m-d\TH:i');
                            $aiDateRaw = $aiSuggestions['incident_datetime'] ?? null;
                            $cleanAiDate = ($aiDateRaw && $aiDateRaw !== 'Not specified in narrative') ? $aiDateRaw : null;
                            if (!$validWhenDate && $cleanAiDate) {
                                $ts = strtotime($cleanAiDate);
                                if ($ts !== false && $ts > 0) {
                                    $validWhenDate = date('Y-m-d\TH:i', $ts);
                                }
                            }
                        @endphp
                        <x-review.short-field-card
                            label="Incident Date & Time"
                            field-name="when_happened"
                            :resident-value="$intake->when_happened?->format('Y-m-d H:i') ?? null"
                            :ai-value="$cleanAiDate"
                            :current-value="$validWhenDate"
                            :provenance="$intake->field_provenance['when_happened'] ?? null"
                            :ai-status="$intake->ai_status ?? 'completed'"
                        />

                        <div class="md:col-span-2">
                            <x-review.short-field-card
                                label="Persons Involved / Witnesses"
                                field-name="who_involved"
                                :resident-value="$intake->who_involved"
                                :ai-value="$aiSuggestions['who_involved'] ?? null"
                                :current-value="$intake->who_involved"
                                :provenance="$intake->field_provenance['who_involved'] ?? null"
                                :ai-status="$intake->ai_status ?? 'completed'"
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
                            :provenance="$intake->field_provenance['what_happened'] ?? null"
                            :ai-status="$intake->ai_status ?? 'completed'"
                            :required="true"
                        />

                        <x-review.diff-field-block
                            label="Circumstances & Context (Why / How)"
                            field-name="why_how_happened"
                            :resident-text="$intake->incident_narrative ?? $intake->why_how_happened"
                            :ai-text="$aiSuggestions['circumstances'] ?? null"
                            :current-value="$intake->why_how_happened"
                            :provenance="$intake->field_provenance['why_how_happened'] ?? null"
                            :ai-status="$intake->ai_status ?? 'completed'"
                        />

                        <x-review.diff-field-block
                            label="Rights Violated / Injury Suffered"
                            field-name="rights_violated"
                            :resident-text="$intake->rights_and_relief ?? $intake->rights_violated"
                            :ai-text="$aiSuggestions['rights_violated'] ?? null"
                            :current-value="$intake->rights_violated"
                            :provenance="$intake->field_provenance['rights_violated'] ?? null"
                            :ai-status="$intake->ai_status ?? 'completed'"
                        />

                        <x-review.diff-field-block
                            label="Relief Requested / Settlement Terms"
                            field-name="relief_requested"
                            :resident-text="$intake->rights_and_relief ?? $intake->relief_requested"
                            :ai-text="$aiSuggestions['relief_requested'] ?? null"
                            :current-value="$intake->relief_requested"
                            :provenance="$intake->field_provenance['relief_requested'] ?? null"
                            :ai-status="$intake->ai_status ?? 'completed'"
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
                            <input type="checkbox" name="kp_eligibility[same_barangay]" id="kp_same_barangay" value="1" onchange="checkDocketReady()" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-slate-800 font-medium">Parties reside in Barangay Calarian (or adjacent barangays without territorial conflict).</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="kp_eligibility[kp_matter]" id="kp_matter" value="1" onchange="checkDocketReady()" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-slate-800 font-medium">Dispute falls under Lupon jurisdiction (punishable by imprisonment not exceeding 1 year or fine not exceeding ₱5,000).</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="kp_eligibility[non_confidential]" id="kp_non_confidential" value="1" onchange="checkDocketReady()" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-slate-800 font-medium">Screened against exempt matters (does NOT involve VAWC RA 9262, Child Abuse RA 7610, or urgent police matters).</span>
                        </label>
                        <label class="flex items-start gap-2.5 cursor-pointer">
                            <input type="checkbox" name="kp_eligibility[residency_verified]" id="kp_residency_verified" value="1" onchange="checkDocketReady()" class="mt-0.5 rounded text-blue-600 focus:ring-blue-500">
                            <span class="text-slate-800 font-medium">Complainant residency status confirmed by official records or verified declaration.</span>
                        </label>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label for="case_type_id" class="block text-xs font-bold text-slate-700 mb-1">Official Case Type <span class="text-red-600">*</span></label>
                            <select name="case_type_id" id="case_type_id" required onchange="checkDocketReady()" class="w-full text-xs rounded-lg border-slate-300 focus:ring-blue-500 p-2.5 bg-white border">
                                <option value="">-- Select case type --</option>
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
                            <button type="button" onclick="applyTemplate('Your complaint has been formally docketed. Please visit Barangay Calarian Hall for summons signing.')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium border border-slate-200">
                                In-person signing chip
                            </button>
                            <button type="button" onclick="applyTemplate('Your complaint was reviewed and accepted. An initial mediation hearing is being scheduled.')" class="px-2 py-0.5 rounded text-[11px] bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium border border-slate-200">
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

                    <button type="submit" 
                            id="btn-confirm-docket"
                            class="w-full py-3 px-4 rounded-xl font-bold text-xs text-white bg-blue-600 hover:bg-blue-700 shadow-sm transition-all flex items-center justify-center gap-2 cursor-pointer">
                        <i class="fa-solid fa-gavel"></i>
                        <span>Confirm and Docket Case</span>
                    </button>
                    <div id="docket-validation-hint" class="hidden p-2 rounded bg-amber-50 border border-amber-200 text-[11px] text-amber-800 space-y-1">
                        <div class="font-bold flex items-center gap-1">
                            <i class="fa-solid fa-circle-info"></i>
                            <span>Required before docketing:</span>
                        </div>
                        <ul class="list-disc list-inside text-[10px] space-y-0.5 ml-1">
                            <li>Factual Summary must not be empty</li>
                            <li>Select an official Case Type</li>
                            <li>Check all 4 KP Eligibility criteria</li>
                        </ul>
                    </div>
                    <p class="text-[11px] text-slate-500 text-center">
                        Generates official Case {{ $caseNumberPreview }} and moves intake to active docket.
                    </p>
                </div>

            </div>

        </div>
    </form>

    <!-- Reject Intake Modal -->
    <div id="reject-intake-modal-wrapper" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div onclick="closeRejectIntakeModal()" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>

            <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                        <i class="fa-solid fa-ban text-red-600"></i>
                        <span>Reject Citizen Complaint Intake</span>
                    </h3>
                    <button type="button" onclick="closeRejectIntakeModal()" class="text-slate-400 hover:text-slate-600">
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
                        <button type="button" onclick="closeRejectIntakeModal()" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">
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

<script>
    const aiData = @json($aiSuggestions);
    const residentData = {
        where_happened: @json($intake->where_happened),
        when_happened: @json($intake->when_happened?->format('Y-m-d\TH:i')),
        who_involved: @json($intake->who_involved),
        what_happened: @json($intake->incident_narrative ?? $intake->what_happened),
        why_how_happened: @json($intake->incident_narrative ?? $intake->why_how_happened),
        rights_violated: @json($intake->rights_and_relief ?? $intake->rights_violated),
        relief_requested: @json($intake->rights_and_relief ?? $intake->relief_requested)
    };

    function setFieldValue(fieldName, val, provenance) {
        const input = document.getElementById('input_' + fieldName);
        const textarea = document.getElementById('textarea_' + fieldName);
        const target = input || textarea;
        if (target) {
            target.value = val || '';
        }
        updateProvenance(fieldName, provenance);
        checkDocketReady();
    }

    function updateProvenance(fieldName, provenance) {
        const hiddenProv = document.getElementById('prov_' + fieldName);
        if (hiddenProv) {
            hiddenProv.value = provenance;
        }
        const badge = document.getElementById('badge_' + fieldName);
        if (badge) {
            if (provenance === 'ai') {
                badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border bg-purple-50 text-purple-700 border-purple-200"><i class="fa-solid fa-wand-magic-sparkles text-[10px]"></i><span>AI Suggested</span></span>';
            } else if (provenance === 'staff_edited') {
                badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border bg-amber-50 text-amber-700 border-amber-200"><i class="fa-solid fa-user-pen text-[10px]"></i><span>Staff Verified</span></span>';
            } else if (provenance === 'not_provided') {
                badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border bg-slate-100 text-slate-500 border-slate-200"><i class="fa-solid fa-minus text-[10px]"></i><span>Not provided</span></span>';
            } else {
                badge.innerHTML = '<span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border bg-blue-50 text-blue-700 border-blue-200"><i class="fa-solid fa-user text-[10px]"></i><span>Resident Original</span></span>';
            }
        }
    }

    function toggleDiff(drawerId) {
        const el = document.getElementById(drawerId);
        if (el) {
            el.classList.toggle('hidden');
        }
    }

    function applyTemplate(text) {
        const input = document.getElementById('complainant_message');
        if (input) {
            input.value = text;
        }
    }

    function acceptAllAiSuggestions() {
        if (!aiData) return;
        if (aiData.incident_location) setFieldValue('where_happened', aiData.incident_location, 'ai');
        if (aiData.incident_datetime && aiData.incident_datetime !== 'Not specified in narrative') {
            setFieldValue('when_happened', aiData.incident_datetime, 'ai');
        }
        if (aiData.who_involved) setFieldValue('who_involved', aiData.who_involved, 'ai');
        if (aiData.what_happened) setFieldValue('what_happened', aiData.what_happened, 'ai');
        if (aiData.circumstances) setFieldValue('why_how_happened', aiData.circumstances, 'ai');
        if (aiData.rights_violated) setFieldValue('rights_violated', aiData.rights_violated, 'ai');
        if (aiData.relief_requested) setFieldValue('relief_requested', aiData.relief_requested, 'ai');
    }

    function resetAllToResident() {
        setFieldValue('where_happened', residentData.where_happened, 'resident');
        setFieldValue('when_happened', residentData.when_happened, 'resident');
        setFieldValue('who_involved', residentData.who_involved, 'resident');
        setFieldValue('what_happened', residentData.what_happened, 'resident');
        setFieldValue('why_how_happened', residentData.why_how_happened, 'resident');
        setFieldValue('rights_violated', residentData.rights_violated, 'resident');
        setFieldValue('relief_requested', residentData.relief_requested, 'resident');
    }

    function checkDocketReady() {
        const checkboxes = ['kp_same_barangay', 'kp_matter', 'kp_non_confidential', 'kp_residency_verified'];
        const allChecked = checkboxes.every(id => {
            const el = document.getElementById(id);
            return el && el.checked;
        });
        
        const caseTypeSelect = document.getElementById('case_type_id');
        const hasCaseType = caseTypeSelect && caseTypeSelect.value !== '';

        const factualSummary = document.getElementById('textarea_what_happened');
        const hasSummary = factualSummary && factualSummary.value.trim() !== '';

        const btn = document.getElementById('btn-confirm-docket');
        const hint = document.getElementById('docket-validation-hint');

        if (btn) {
            if (allChecked && hasCaseType && hasSummary) {
                btn.removeAttribute('disabled');
                btn.classList.remove('opacity-50', 'cursor-not-allowed', 'bg-slate-400');
                btn.classList.add('bg-blue-600', 'hover:bg-blue-700', 'cursor-pointer');
                if (hint) hint.classList.add('hidden');
            } else {
                btn.setAttribute('disabled', 'disabled');
                btn.classList.add('opacity-50', 'cursor-not-allowed', 'bg-slate-400');
                btn.classList.remove('bg-blue-600', 'hover:bg-blue-700', 'cursor-pointer');
                if (hint) hint.classList.remove('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', checkDocketReady);
    // Also trigger immediately in case DOMContentLoaded already fired
    setTimeout(checkDocketReady, 50);

    function openRejectIntakeModal() {
        const modal = document.getElementById('reject-intake-modal-wrapper');
        if (modal) modal.classList.remove('hidden');
    }

    function closeRejectIntakeModal() {
        const modal = document.getElementById('reject-intake-modal-wrapper');
        if (modal) modal.classList.add('hidden');
    }

    function validateDocketSubmission(e) {
        const checkboxes = ['kp_same_barangay', 'kp_matter', 'kp_non_confidential', 'kp_residency_verified'];
        for (const id of checkboxes) {
            const el = document.getElementById(id);
            if (el && !el.checked) {
                alert('All 4 Katarungang Pambarangay eligibility criteria must be checked before docketing.');
                el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                if (e) e.preventDefault();
                return false;
            }
        }
        const caseTypeSelect = document.getElementById('case_type_id');
        if (!caseTypeSelect || !caseTypeSelect.value) {
            alert('Please select an official case type.');
            caseTypeSelect.focus();
            if (e) e.preventDefault();
            return false;
        }
        const factualSummary = document.getElementById('textarea_what_happened');
        if (!factualSummary || !factualSummary.value.trim()) {
            alert('Factual Summary cannot be empty.');
            factualSummary.focus();
            if (e) e.preventDefault();
            return false;
        }

        const btn = document.getElementById('btn-confirm-docket');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin mr-1"></i> Docketing Case...';
            btn.classList.add('opacity-75', 'cursor-not-allowed');
        }
        return true;
    }
</script>
@endsection
