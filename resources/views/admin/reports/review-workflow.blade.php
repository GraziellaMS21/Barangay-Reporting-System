@php
  $aiResult = $report->aiResult;
  $aiReady = $report->ai_status === 'done' && $aiResult !== null && $aiResult->error === null;
  $aiPending = in_array($report->ai_status, ['pending', 'processing'], true);
  $aiUnavailable = ! $aiReady && ! $aiPending;
  $confidence = $aiReady && $aiResult->confidence !== null ? (float) $aiResult->confidence : null;
  $highConfidenceThreshold = max(0.6, min(1, $aiConfidenceThreshold));
  $lowConfidenceThreshold = max(0, min($highConfidenceThreshold, $aiConfidenceLowThreshold));
  $lowConfidence = $confidence !== null && $confidence < $lowConfidenceThreshold;
  $suggestedCategory = $aiReady
      ? $categories->firstWhere('slug', $aiResult->suggested_category)
      : null;
  $aiCategoryId = $suggestedCategory?->id;
  $aiCategoryLabel = $suggestedCategory?->name
      ?? ($aiReady && $aiResult->suggested_category === 'unclassified' ? 'Unclassified' : null);
  $aiCategoryPresent = $aiReady && filled($aiResult->suggested_category);
  $aiUrgency = $aiReady ? $aiResult->suggested_urgency : null;
  $aiDescription = $aiReady ? $aiResult->summary : null;
  $aiUrgencyPresent = $aiUrgency !== null && $aiUrgency !== '';
  $aiDescriptionPresent = $aiDescription !== null && trim($aiDescription) !== '';
  $alreadyReviewed = $report->reviewed_at !== null;
  $voiceAudio = $report->audio;
  $voiceProvenance = is_array($voiceAudio?->provenance) ? $voiceAudio->provenance : [];
  $residentCategory = $report->residentCategory ?? (! $alreadyReviewed ? $report->category : null);
  $residentPurok = $report->residentPurok ?? (! $alreadyReviewed ? $report->purok : null);
  $residentLandmark = $report->resident_landmark ?? (! $alreadyReviewed ? $report->landmark : null);
  $residentCategoryId = $residentCategory?->id;
  $residentDescription = $report->description;
  $answers = is_array($report->answers) ? $report->answers : [];
  $questionSchema = $residentCategory?->question_schema ?? [];
  $finalCategoryId = old('category_id', $report->category_id);
  $finalUrgency = old('urgency', $report->urgency);
  $finalDescription = old('staff_description', $report->staff_description ?? $report->description);
  $finalPurokId = old('purok_id', $report->purok_id);
  $finalLandmark = old('landmark', $report->landmark);
  $categoryFinalState = $alreadyReviewed
      ? ((string) $report->category_id === (string) $aiCategoryId && $aiCategoryPresent ? 'Accepted' : ($aiReady ? 'Edited' : 'Resident'))
      : ($aiCategoryPresent ? 'AI suggested' : 'Resident');
  $urgencyFinalState = $alreadyReviewed
      ? ($aiUrgencyPresent && $report->urgency === $aiUrgency ? 'Accepted' : ($aiReady ? 'Edited' : 'Resident'))
      : ($aiUrgencyPresent ? 'AI suggested' : 'Resident');
  $descriptionFinalState = $alreadyReviewed
      ? ($aiDescriptionPresent && $report->staff_description === $aiDescription ? 'Accepted' : ($aiReady ? 'Edited' : 'Resident'))
      : ($aiDescriptionPresent ? 'AI suggested' : 'Resident');
  $aiConfigured = filled(config('services.gemini.key'));
  $aiFailed = $report->ai_status === 'failed';
  $categoryReviewRequired = true;
  $urgencyReviewRequired = true;
  $descriptionReviewRequired = true;
  $requiredFieldsCount = 3;
  $descriptionReviewed = $alreadyReviewed || ! $aiDescriptionPresent || $descriptionFinalState === 'Accepted' || $descriptionFinalState === 'Edited';
  $reviewedInitialCount = ($finalCategoryId ? 1 : 0) + ($finalUrgency ? 1 : 0) + ($descriptionReviewed ? 1 : 0);
  $selectedCategoryId = (int) $finalCategoryId;
  $routingDecision = $routingDecisions[$selectedCategoryId] ?? null;
  $candidateOfficeDefault = $alreadyReviewed
      ? ($report->office_id ?? $routingDecision['office_id'] ?? '')
      : ($routingDecision['office_id'] ?? $report->office_id ?? '');
  $selectedInternalOffice = $offices->firstWhere('id', (int) $candidateOfficeDefault);
  $officeDefault = old('office_id', $selectedInternalOffice ? $selectedInternalOffice->id : '');
  $assignedDefault = old('assigned_to', $report->assigned_to ?? $selectedInternalOffice?->default_assignee_id ?? '');
  $defaultMessage = old('review_note', '');
  $isOverdue = $report->due_at !== null
      && $report->due_at->isPast()
      && ! in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true);
  $hasEmergencyFlag = $report->is_emergency
      || $report->status === 'emergency_flagged'
      || ($aiReady && (bool) $aiResult->emergency_flag && ! $emergencyFlagReviewed);
  $emergencyActionOnly = $hasEmergencyFlag;
  $canResolveEmergency = ! in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true);
  $actionableDuplicates = ($actionableDuplicateSuggestions ?? $report->duplicateSuggestions)
      ->filter(fn ($suggestion) => $suggestion->decision === 'pending'
          && $suggestion->candidate !== null
          && $suggestion->candidate->status !== 'merged')
      ->sortByDesc('score')
      ->values();
  $pendingDuplicate = $actionableDuplicates->first();
  $canMergeDuplicate = ! $report->is_emergency
      && $report->status !== 'emergency_flagged'
      && in_array($report->status, ['submitted', 'under_review', 'reopened'], true);
  $hasPhotoMismatch = ! $hasEmergencyFlag
      && ! $photoMismatchReviewed
      && ($report->photo_mismatch || ($aiReady && (bool) $aiResult->photo_mismatch));
  $isAfterRoute = in_array($report->status, [
      'routed',
      'in_progress',
      'needs_info',
      'awaiting_confirmation',
      'resolved',
      'referred',
      'merged',
      'no_action',
  ], true);
  $isRouteable = in_array($report->status, ['submitted', 'under_review', 'reopened'], true)
      && ! $emergencyActionOnly;
  $defaultWorkspaceStep = in_array($report->status, ['routed', 'in_progress', 'referred', 'awaiting_confirmation', 'resolved', 'reopened', 'merged', 'no_action'], true)
      && ! $errors->has('category_id')
      && ! $errors->has('urgency')
      && ! $errors->has('staff_description')
      ? 3
      : 1;
  $isCompleted = in_array($report->status, ['resolved', 'no_action'], true);
  $priorityLabel = $report->urgency ?: 'not set';
  $googleMapsUrl = $report->lat !== null && $report->lng !== null
      ? 'https://www.google.com/maps/search/?api=1&query='.$report->lat.','.$report->lng
      : null;
  $isVoiceOrPhone = $report->source === \App\Models\Report::SOURCE_VOICE || $report->source === \App\Models\Report::SOURCE_PHONE_LOG || $report->audio;
  $hasInfoRequests = $report->infoRequests->isNotEmpty();
  $hasResidentReply = $report->infoRequests->whereNotNull('responded_at')->isNotEmpty();
  $unreadReplies = $infoThreadSummary['unread'] ?? 0;
  $canOpenInfoChat = $hasInfoRequests || in_array($report->status, ['submitted', 'under_review', 'reopened', 'routed', 'in_progress', 'needs_info'], true);
  $canTakeNoAction = app(\App\Services\ReportStatusService::class)->canTransition($report->status, \App\Support\ReportStatus::NO_ACTION);
@endphp

<style>
  .rw-fade-in {
    animation: rwFadeSlideIn 0.22s ease-out;
  }
  @keyframes rwFadeSlideIn {
    from { opacity: 0; transform: translateY(6px); }
    to { opacity: 1; transform: translateY(0); }
  }
  @keyframes rwShakeX {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-4px); }
    75% { transform: translateX(4px); }
  }
  .rw-shake {
    animation: rwShakeX 0.25s ease-in-out 2;
  }
  .rw-clamp {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }
  .rw-clamp.is-expanded {
    display: block;
    -webkit-line-clamp: unset;
  }
  .review-activity-list {
    margin: 0;
    padding: 0;
    list-style: none;
    max-height: 420px;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  @media (prefers-reduced-motion: reduce) {
    .rw-fade-in, .rw-shake { animation: none !important; }
  }
</style>

<div class="review-workflow space-y-4" data-review-workflow
     data-is-after-route="{{ $isAfterRoute ? 'true' : 'false' }}"
     data-has-unverified-audio="{{ $report->hasUnverifiedAudio() ? 'true' : 'false' }}"
     data-ai-state="{{ $aiReady ? 'ready' : ($aiPending ? 'pending' : 'unavailable') }}"
     data-ai-poll-url="{{ route('admin.reports.review-ai', $report) }}"
     data-confidence-high="{{ $highConfidenceThreshold }}"
     data-confidence-low="{{ $lowConfidenceThreshold }}"
     data-default-step="{{ $defaultWorkspaceStep }}">

  @if(session('success'))
    <div role="status" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-xs font-semibold text-emerald-900">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-semibold text-rose-900">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div role="alert" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-2.5 text-xs font-semibold text-rose-900">{{ $errors->first() }}</div>
  @endif

  @include('admin.reports.partials.review.review-header')
  @include('admin.reports.partials.review.review-referral-banner')

  {{-- 2. Main 2-Column Split Workspace --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">
    {{-- =============================================================== --}}
    {{-- LEFT COLUMN: STICKY REFERENCE DOSSIER                           --}}
    {{-- =============================================================== --}}
    <aside class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-20 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      {{-- Dossier Tab Bar (3 Tabs) --}}
      <div class="grid grid-cols-3 border-b border-slate-200 bg-slate-50/70 text-xs font-bold" role="tablist" aria-label="Reference Dossier Tabs">
        <button type="button" class="py-3 px-2 border-b-2 border-blue-600 text-blue-700 bg-white flex items-center justify-center gap-1.5 transition"
                data-dossier-tab="evidence" role="tab" aria-selected="true">
          <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
          <span>Evidence</span>
        </button>
        <button type="button" class="py-3 px-2 border-b-2 border-transparent text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5 transition"
                data-dossier-tab="timeline" role="tab" aria-selected="false">
          <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
          <span>Timeline</span>
          @if(count($activityEntries) > 0)
            <span class="rounded-full bg-blue-100 text-blue-700 px-1.5 py-0.2 text-[10px] font-bold">{{ count($activityEntries) }}</span>
          @endif
        </button>
        <button type="button" class="py-3 px-2 border-b-2 border-transparent text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5 transition"
                data-dossier-tab="duplicates" role="tab" aria-selected="false">
          <i class="fa-solid fa-copy" aria-hidden="true"></i>
          <span>Duplicates</span>
          @if($actionableDuplicates->isNotEmpty() && $report->status !== 'merged')
            <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold bg-amber-100 text-amber-800">{{ $actionableDuplicates->count() }}</span>
          @endif
        </button>
      </div>

      {{-- TAB 1: EVIDENCE --}}
      <div class="dossier-panel rw-fade-in p-4 space-y-4" data-dossier-panel="evidence">
        @include('admin.reports.partials.review.review-reporter')
        @include('admin.reports.partials.review.review-overview')
        @include('admin.reports.partials.review.review-location')
      </div>

      {{-- TAB 2: TIMELINE & NOTES --}}
      @include('admin.reports.partials.review.review-timeline')

      {{-- TAB 3: DUPLICATES --}}
      @include('admin.reports.partials.review.review-duplicates')
    </aside>

    {{-- =============================================================== --}}
    {{-- RIGHT COLUMN: GUIDED 3-STEP PROGRESSIVE ACTION WORKSPACE        --}}
    {{-- =============================================================== --}}
    <section class="lg:col-span-7 xl:col-span-8 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      {{-- Interactive 3-Step Progress Header --}}
      <div class="px-5 py-3.5 border-b border-slate-200 bg-slate-50/70">
        <div class="flex items-center justify-between max-w-2xl mx-auto">
          <button type="button" class="rw-stepper-btn flex items-center gap-2 focus:outline-none group" data-workspace-step="1">
            <span class="rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-200">1</span>
            <span class="text-left leading-tight">
              <span class="block text-xs sm:text-sm font-bold text-slate-900">Review &amp; Triage</span>
              <span class="hidden sm:block text-[10px] text-slate-500">Verify category, urgency &amp; details</span>
            </span>
          </button>

          <div class="flex-1 h-0.5 bg-slate-200 mx-2 sm:mx-4 rounded-full overflow-hidden">
            <div id="rw-connector-1" class="h-full bg-blue-600 transition-all duration-300" style="width:0%"></div>
          </div>

          <button type="button" class="rw-stepper-btn flex items-center gap-2 focus:outline-none group" data-workspace-step="2">
            <span class="rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-200">2</span>
            <span class="text-left leading-tight">
              <span class="block text-xs sm:text-sm font-bold text-slate-600">Route &amp; Assign</span>
              <span class="hidden sm:block text-[10px] text-slate-500">Choose office or external agency</span>
            </span>
          </button>

          <div class="flex-1 h-0.5 bg-slate-200 mx-2 sm:mx-4 rounded-full overflow-hidden">
            <div id="rw-connector-2" class="h-full bg-blue-600 transition-all duration-300" style="width:0%"></div>
          </div>

          <button type="button" class="rw-stepper-btn flex items-center gap-2 focus:outline-none group" data-workspace-step="3">
            <span class="rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold transition-all duration-200">3</span>
            <span class="text-left leading-tight">
              <span class="block text-xs sm:text-sm font-bold text-slate-600">Work &amp; Resolve</span>
              <span class="hidden sm:block text-[10px] text-slate-500">Log progress &amp; resolution proof</span>
            </span>
          </button>
        </div>
      </div>

      {{-- Main Review & Route Form (wraps Steps 1 & 2) --}}
      @if(! $emergencyActionOnly)
        <form id="review-form" method="POST" action="{{ route('admin.reports.review', $report) }}">
          @csrf
          @method('PATCH')
      @endif

      {{-- STEP 1 PANEL: REVIEW & TRIAGE --}}
      @include('admin.reports.partials.review.review-triage')

      {{-- STEP 2 PANEL: ROUTE & ASSIGN --}}
      @include('admin.reports.partials.review.review-dispatch')

      @if(! $emergencyActionOnly)
        </form>
      @endif

      {{-- STEP 3 PANEL: WORK & RESOLVE --}}
      @include('admin.reports.partials.review.review-resolution')
    </section>
  </div>

  {{-- Auxiliary action forms & Modals --}}
  @include('admin.reports.partials.review.review-modals')
</div>

{{-- Scripts --}}
@include('admin.reports.partials.review.review-scripts')
