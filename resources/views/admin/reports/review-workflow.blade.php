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

  {{-- 1A. Sticky Report Sub-Header --}}
  <header class="bg-white/95 backdrop-blur border border-slate-200 rounded-2xl px-4 sm:px-5 py-3 shadow-xs">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
            <i class="fa-solid fa-arrow-left text-[11px]" aria-hidden="true"></i>
            <span>Back to report queue</span>
          </a>
          <span class="text-slate-300">|</span>
          <h1 class="font-mono text-base sm:text-lg font-extrabold text-slate-900 tracking-tight m-0">{{ $report->tracking_code }}</h1>
          <x-status-badge :status="$report->status" />
          @if($report->is_emergency)
            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 text-rose-800 border border-rose-200 px-2.5 py-0.5 text-[11px] font-bold">
              <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i> Emergency
            </span>
          @endif
          @if($report->source === \App\Models\Report::SOURCE_VOICE)
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 text-[11px] font-bold" title="Voice report">
              <i class="fa-solid fa-microphone text-[10px]" aria-hidden="true"></i> Voice report
            </span>
          @elseif($report->source === \App\Models\Report::SOURCE_PHONE_LOG)
            <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 text-teal-800 border border-teal-200 px-2.5 py-0.5 text-[11px] font-bold" title="Phone hotline report">
              <i class="fa-solid fa-phone text-[10px]" aria-hidden="true"></i> Phone intake
            </span>
          @endif
          @if($isOverdue)
            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 text-[11px] font-bold" title="SLA due {{ $report->due_at->format('M d, Y h:i A') }}">
              <i class="fa-solid fa-clock-rotate-left text-[10px]" aria-hidden="true"></i> Overdue
            </span>
          @endif
          @if($report->escalated_at)
            <span class="inline-flex items-center gap-1 rounded-full bg-rose-600 text-white px-2.5 py-0.5 text-[11px] font-bold">
              <i class="fa-solid fa-fire text-[10px]" aria-hidden="true"></i> Escalated
            </span>
          @endif
        </div>
        <p class="text-xs text-slate-500 mt-1 mb-0">
          Submitted {{ $report->created_at->format('M j, Y \a\t g:i A') }} ({{ $report->created_at->diffForHumans() }})
          @if($report->due_at)
            <span aria-hidden="true">·</span>
            SLA due {{ $report->due_at->format('M j, Y g:i A') }}
          @endif
        </p>
      </div>

      {{-- + Other actions dropdown --}}
      <div class="flex items-center gap-2">
        @if($isOverdue && ! $report->escalated_at)
          <button type="submit" form="review-escalate-form" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold px-3 py-1.5 transition">
            <i class="fa-solid fa-fire text-[10px]" aria-hidden="true"></i> Escalate overdue
          </button>
        @endif

        <div class="kebab relative">
          <button type="button" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold px-3 py-1.5 rounded-lg shadow-xs transition"
                  data-kebab-toggle aria-haspopup="true" aria-expanded="false" aria-label="More actions">
            <i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i>
            <span>Other actions</span>
            @if($unreadReplies > 0 || ($hasResidentReply && !session('info_thread_seen.'.$report->id)))
              <span class="chat-notif-circle" data-chat-notif-circle title="New reply from resident">{{ $unreadReplies > 0 ? $unreadReplies : '!' }}</span>
            @endif
            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400" aria-hidden="true"></i>
          </button>
          <div class="kebab-menu absolute right-0 mt-1.5 w-60 bg-white rounded-xl border border-slate-200 shadow-xl py-1.5 z-40 text-xs" hidden>
            @if($canOpenInfoChat)
              <button type="button" class="w-full px-3.5 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 font-medium"
                      aria-label="Ask Reporter for Info"
                      data-chat-open data-report-id="{{ $report->id }}"
                      data-chat-tracking="{{ $report->tracking_code }}"
                      data-chat-category="{{ $report->category?->name ?? 'Category not assigned' }}"
                      data-chat-status="{{ \App\Models\Report::STATUSES[$report->status] ?? $report->status }}"
                      data-chat-summary="{{ $report->description }}{{ $report->purok ? ' — '.$report->purok->name : '' }}">
                <i class="fa-solid fa-comment-dots text-blue-600 w-4 text-center" aria-hidden="true"></i>
                <span>Ask Reporter for More Info</span>
              </button>
            @endif
            @if(filter_var(\App\Models\Setting::value('cases.enabled') ?? config('cases.enabled', true), FILTER_VALIDATE_BOOLEAN) && auth()->user()?->hasPermission('cases.create'))
              @includeIf('admin.cases.partials.convert-button', ['report' => $report, 'formId' => 'kebab-convert-case-form'])
            @endif
            @if($canMergeDuplicate)
              <button type="button" class="w-full px-3.5 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 font-medium"
                      data-open-dialog="duplicate-modal" title="Mark as duplicate">
                <i class="fa-solid fa-code-merge text-amber-600 w-4 text-center" aria-hidden="true"></i>
                <span>Manual Duplicate Merge</span>
              </button>
            @endif
            @if($report->status === 'merged')
              <button type="button" class="w-full px-3.5 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 font-medium" data-open-dialog="unmerge-modal">
                <i class="fa-solid fa-code-branch text-blue-600 w-4 text-center" aria-hidden="true"></i>
                <span>Unmerge Report</span>
              </button>
            @endif
            @if(!$emergencyActionOnly && $isRouteable)
              <button type="submit" form="review-form" name="intent" value="save" class="w-full px-3.5 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 font-medium">
                <i class="fa-regular fa-floppy-disk text-slate-500 w-4 text-center" aria-hidden="true"></i>
                <span>Save review without routing</span>
              </button>
            @endif
            @if(!$report->is_emergency && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true))
              <button type="submit" form="kebab-emergency-form" class="w-full px-3.5 py-2 text-left text-rose-700 hover:bg-rose-50 flex items-center gap-2.5 font-medium">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 w-4 text-center" aria-hidden="true"></i>
                <span>Flag as emergency</span>
              </button>
            @endif
            @if($canTakeNoAction)
              <div class="my-1 border-t border-slate-100"></div>
              <button type="button" class="w-full px-3.5 py-2 text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2.5 font-semibold" data-open-dialog="no-action-modal">
                <i class="fa-solid fa-ban w-4 text-center" aria-hidden="true"></i>
                <span>Take No Action</span>
              </button>
            @endif
          </div>
        </div>
      </div>
    </div>
  </header>

  {{-- 1B. Full-Width Conditional Alert Banners --}}
  @if($hasEmergencyFlag)
    <section class="rounded-xl border border-rose-300 bg-rose-50 p-4 flex flex-wrap items-center justify-between gap-3" aria-label="Emergency review required">
      <div class="min-w-0">
        <strong class="text-sm font-extrabold text-rose-900 flex items-center gap-2">
          <i class="fa-solid fa-triangle-exclamation text-rose-600" aria-hidden="true"></i>
          <span>Possible emergency</span>
        </strong>
        <p class="text-xs text-rose-800 mt-1 mb-0">{{ $report->is_emergency ? 'This report is currently flagged as an emergency.' : 'The AI or report details indicate a possible emergency. Confirm before continuing.' }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        @if(! $report->is_emergency && $canResolveEmergency)
          <form method="POST" action="{{ route('admin.reports.emergency', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="rounded-lg border border-rose-300 bg-white hover:bg-rose-100 text-rose-800 text-xs font-bold px-3 py-1.5 transition">Confirm emergency</button>
          </form>
        @endif
        @if($report->is_emergency && $report->status === 'emergency_flagged')
          <button type="button" class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 transition" data-open-dialog="emergency-handled-modal">
            <i class="fa-solid fa-person-digging mr-1" aria-hidden="true"></i> Handle Locally (Tanod Response)
          </button>
        @endif
        @if($canResolveEmergency)
          <form method="POST" action="{{ route('admin.reports.emergency', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="confirm" value="0">
            <button type="submit" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3 py-1.5 transition">Not an emergency</button>
          </form>
        @else
          <span class="text-xs text-slate-600">This report is in a final status; its emergency state cannot be changed here.</span>
        @endif
      </div>
    </section>
  @endif

  @if($report->hasUnverifiedAudio())
    <section id="voice-audio-verification-banner" class="bg-amber-50 border border-amber-300 rounded-xl p-4 space-y-3" aria-label="Voice audio verification required">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="flex items-start gap-3">
          <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-microphone-slash" aria-hidden="true"></i>
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <strong class="text-sm font-extrabold text-amber-900">Voice Audio Needs Verification</strong>
              <span class="inline-flex items-center rounded-full bg-amber-200/80 text-amber-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">Unverified draft</span>
            </div>
            <p class="text-xs text-amber-800 mt-0.5 mb-0">
              AI transcription is unclear or unverified. Listen to the recording and confirm or update the report description to unblock routing.
            </p>
          </div>
        </div>
      </div>
      <audio controls src="{{ route('admin.reports.audio', $report) }}" preload="metadata" class="w-full max-w-lg h-10"></audio>
      <form method="POST" action="{{ route('admin.reports.audio.verify', $report) }}" class="space-y-2 pt-1">
        @csrf
        <label for="verified_description" class="block text-xs font-bold text-amber-900">
          Staff Verified Description <span class="text-rose-600">*</span>
        </label>
        <textarea id="verified_description" name="description" rows="2" required minlength="5"
                  class="w-full rounded-lg border border-amber-400 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500/30">{{ old('description', $report->description) }}</textarea>
        <div class="flex justify-end">
          <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3.5 py-2 transition">
            <i class="fa-solid fa-check-double" aria-hidden="true"></i> Verify and Update Description
          </button>
        </div>
      </form>
    </section>
  @endif

  @if($hasPhotoMismatch)
    <section class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 flex flex-wrap items-center justify-between gap-3" aria-label="Possible photo mismatch">
      <div class="flex items-center gap-2.5 text-xs text-amber-900">
        <i class="fa-solid fa-image text-amber-600 text-sm" aria-hidden="true"></i>
        <span><strong>Possible photo mismatch:</strong> Check whether the attached photo matches the resident's description.</span>
      </div>
      <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('admin.reports.photo-mismatch', $report) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="confirm" value="1">
          <button type="submit" class="rounded-lg border border-amber-300 bg-white hover:bg-amber-100 text-amber-900 text-xs font-bold px-3 py-1.5 transition">Confirm mismatch</button>
        </form>
        <form method="POST" action="{{ route('admin.reports.photo-mismatch', $report) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="confirm" value="0">
          <button type="submit" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3 py-1.5 transition">Not a mismatch</button>
        </form>
      </div>
    </section>
  @endif

  {{-- 2. Main 2-Column Split Workspace --}}
  <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

    {{-- =============================================================== --}}
    {{-- LEFT COLUMN: STICKY REFERENCE DOSSIER                           --}}
    {{-- =============================================================== --}}
    <aside class="lg:col-span-5 xl:col-span-4 lg:sticky lg:top-20 rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">
      {{-- Dossier Tab Bar (3 Tabs) --}}
      <div class="grid grid-cols-3 border-b border-slate-200 bg-slate-50/70 text-xs font-bold" role="tablist" aria-label="Dossier tabs">
        <button type="button" role="tab" aria-selected="true" data-dossier-tab="evidence"
                class="py-3 px-2 border-b-2 border-blue-600 text-blue-700 bg-white flex items-center justify-center gap-1.5 transition">
          <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
          <span>Evidence</span>
        </button>
        <button type="button" role="tab" aria-selected="false" data-dossier-tab="timeline"
                class="py-3 px-2 border-b-2 border-transparent text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5 transition">
          <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
          <span>Timeline &amp; Notes</span>
          <span class="rounded-full bg-blue-100 text-blue-700 px-1.5 py-0.2 text-[10px] font-bold">{{ count($activityEntries) }}</span>
        </button>
        <button type="button" role="tab" aria-selected="false" data-dossier-tab="duplicates"
                class="py-3 px-2 border-b-2 border-transparent text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5 transition">
          <i class="fa-solid fa-copy" aria-hidden="true"></i>
          <span>Duplicates</span>
          <span class="rounded-full px-1.5 py-0.2 text-[10px] font-bold {{ $actionableDuplicates->isNotEmpty() ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-600' }}">{{ $actionableDuplicates->count() }}</span>
        </button>
      </div>

      {{-- TAB 1: EVIDENCE --}}
      <div class="dossier-panel rw-fade-in p-4 space-y-4" data-dossier-panel="evidence">
        {{-- Reporter Strip --}}
        <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3 flex items-start gap-3">
          <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
            <i class="fa-solid fa-user" aria-hidden="true"></i>
          </div>
          <div class="min-w-0 flex-1 text-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Reported by</div>
            @if($report->is_anonymous || (! $isVoiceOrPhone && ! filled($report->reporter_name)))
              <div class="font-semibold text-slate-600 italic mt-0.5">Not stated (anonymous report)</div>
            @else
              <div class="font-bold text-slate-900 truncate mt-0.5">
                @if(filled($report->reporter_name))
                  {{ $report->reporter_name }}
                @else
                  <span class="italic font-normal text-slate-500">Not provided</span>
                @endif
                @if(!empty($voiceProvenance['reporter_name']['edited']))
                  <span class="ml-1 inline-flex items-center rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.2 text-[10px] font-bold">Edited after recording</span>
                @elseif(!empty($voiceProvenance['reporter_name']['filled']))
                  <span class="ml-1 inline-flex items-center rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-1.5 py-0.2 text-[10px] font-bold">From recording</span>
                @endif
              </div>
              <div class="text-slate-600 mt-1 space-y-0.5">
                <div class="flex items-center gap-1.5">
                  <i class="fa-solid fa-phone text-[10px] text-slate-400 w-3" aria-hidden="true"></i>
                  @if(filled($report->reporter_contact))
                    <span>{{ $report->reporter_contact }}</span>
                  @else
                    <span class="italic text-slate-400">Not provided</span>
                  @endif
                </div>
                <div class="flex items-center gap-1.5 truncate">
                  <i class="fa-regular fa-envelope text-[10px] text-slate-400 w-3" aria-hidden="true"></i>
                  @if(filled($report->reporter_email))
                    <span class="truncate">{{ $report->reporter_email }}</span>
                  @else
                    <span class="italic text-slate-400">Not provided</span>
                  @endif
                </div>
              </div>
            @endif
          </div>
        </div>

        {{-- Original Resident Description --}}
        <div>
          <div class="flex items-center justify-between gap-2 mb-1.5">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Original description</span>
            @if($voiceAudio && $voiceAudio->ai_description && trim($voiceAudio->ai_description) !== trim($residentDescription))
              <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 text-[10px] font-bold">
                <i class="fa-solid fa-user-pen" aria-hidden="true"></i> Edited by resident
              </span>
            @endif
          </div>
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
            <p id="dossier-orig-desc" class="rw-clamp text-xs sm:text-sm text-slate-800 leading-relaxed m-0">{{ $report->description }}</p>
            <button type="button" id="dossier-desc-toggle" class="hidden mt-1.5 text-xs font-bold text-blue-600 hover:underline">View more</button>
          </div>

          @if($report->people_at_risk || $report->is_ongoing || $report->is_recurring || count($answers) > 0)
            <div class="flex flex-wrap gap-1.5 mt-2.5">
              @if($report->people_at_risk)
                <span class="inline-flex items-center gap-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 px-2 py-1 text-[11px] font-semibold">
                  <i class="fa-solid fa-person-circle-exclamation" aria-hidden="true"></i> Someone in danger
                </span>
              @endif
              @if($report->is_ongoing)
                <span class="inline-flex items-center gap-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 text-[11px] font-semibold">
                  <i class="fa-solid fa-clock" aria-hidden="true"></i> Happening now
                </span>
              @endif
              @if($report->is_recurring)
                <span class="inline-flex items-center gap-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 text-[11px] font-semibold">
                  <i class="fa-solid fa-repeat" aria-hidden="true"></i> Recurring
                </span>
              @endif
              @foreach($questionSchema as $question)
                @php
                  $questionKey = $question['key'] ?? null;
                  $answerExists = $questionKey !== null && array_key_exists($questionKey, $answers) && $answers[$questionKey] !== null && $answers[$questionKey] !== '';
                  $answerValue = $answerExists ? $answers[$questionKey] : null;
                  $answerLabel = is_array($answerValue)
                      ? implode(', ', array_filter($answerValue, fn ($value) => $value !== null && $value !== ''))
                      : (in_array((string) $answerValue, ['0', '1'], true)
                          ? ((string) $answerValue === '1' ? 'Yes' : 'No')
                          : $answerValue);
                @endphp
                @if($answerExists)
                  <span class="inline-flex items-center rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 text-[11px] font-semibold">
                    {{ $question['label'] ?? $questionKey }}: {{ $answerLabel }}
                  </span>
                @endif
              @endforeach
              @foreach($answers as $answerKey => $answerValue)
                @php
                  $hasQuestionLabel = collect($questionSchema)->contains(fn ($q) => ($q['key'] ?? null) === $answerKey);
                  $fallbackAnswerExists = ! $hasQuestionLabel && $answerValue !== null && $answerValue !== '';
                  $fallbackAnswerLabel = is_array($answerValue)
                      ? implode(', ', array_filter($answerValue, fn ($value) => $value !== null && $value !== ''))
                      : (in_array((string) $answerValue, ['0', '1'], true)
                          ? ((string) $answerValue === '1' ? 'Yes' : 'No')
                          : $answerValue);
                @endphp
                @if($fallbackAnswerExists)
                  <span class="inline-flex items-center rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 text-[11px] font-semibold">
                    {{ str($answerKey)->replace('_', ' ')->title() }}: {{ $fallbackAnswerLabel }}
                  </span>
                @endif
              @endforeach
            </div>
          @endif
        </div>

        {{-- Attached Audio Player (if voice/phone report) --}}
        @if($report->audio)
          <div id="voice-audio-section" class="pt-3 border-t border-slate-100 space-y-2">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <i class="fa-solid fa-microphone-lines text-blue-600" aria-hidden="true"></i>
                <span>{{ $report->audio->origin === 'staff' ? 'Hotline Phone Recording' : 'Resident Voice Recording' }}</span>
              </span>
              @if($report->audio->isVerified())
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold">
                  <i class="fa-solid fa-check" aria-hidden="true"></i> Verified
                </span>
              @endif
            </div>
            <audio controls src="{{ route('admin.reports.audio', $report) }}" preload="metadata" class="w-full h-9"></audio>
            @if($report->audio->transcript)
              <details class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                <summary class="font-bold text-slate-700 cursor-pointer">What was said (AI transcript)</summary>
                <p class="mt-1.5 text-slate-700 leading-relaxed mb-0">{{ $report->audio->transcript }}</p>
              </details>
            @endif
          </div>
        @endif

        {{-- Photo & Location Section --}}
        <div class="pt-3 border-t border-slate-100">
          <span class="block text-[11px] font-bold uppercase tracking-wider text-slate-400 mb-2">Photo &amp; Location</span>
          <div class="grid grid-cols-2 gap-3">
            <div>
              @if($report->photos->isNotEmpty())
                @php $firstPhoto = $report->photos->first(); @endphp
                <div class="relative group">
                  <img src="{{ route('admin.reports.photos.show', [$report, $firstPhoto]) }}"
                       alt="Resident report photo"
                       data-lightbox-src="{{ route('admin.reports.photos.show', [$report, $firstPhoto]) }}"
                       class="rounded-xl object-cover h-36 w-full cursor-zoom-in border border-slate-200">
                  @if($report->photos->count() > 1)
                    <span class="absolute bottom-1.5 left-1.5 rounded-md bg-slate-900/75 text-white text-[10px] font-bold px-2 py-0.5">
                      +{{ $report->photos->count() - 1 }} more
                    </span>
                  @endif
                </div>
                @if($report->photos->count() > 1)
                  <div class="flex gap-1.5 mt-1.5 overflow-x-auto">
                    @foreach($report->photos->skip(1) as $extraPhoto)
                      <img src="{{ route('admin.reports.photos.show', [$report, $extraPhoto]) }}"
                           alt="Additional resident report photo"
                           data-lightbox-src="{{ route('admin.reports.photos.show', [$report, $extraPhoto]) }}"
                           class="h-10 w-10 rounded-lg object-cover cursor-zoom-in border border-slate-200 flex-shrink-0">
                    @endforeach
                  </div>
                @endif
              @elseif($report->photo_path)
                <img src="{{ route('admin.reports.photo', $report) }}"
                     alt="Resident report photo"
                     data-lightbox-src="{{ route('admin.reports.photo', $report) }}"
                     class="rounded-xl object-cover h-36 w-full cursor-zoom-in border border-slate-200">
              @else
                <div class="h-36 rounded-xl border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-xs text-slate-400 italic text-center px-2">
                  No photo attached
                </div>
              @endif
            </div>

            <div>
              @if($report->lat !== null && $report->lng !== null)
                <div id="report-location-map" class="relative z-0 isolate h-36 rounded-xl overflow-hidden border border-slate-200"
                     data-lat="{{ $report->lat }}" data-lng="{{ $report->lng }}"
                     role="img" aria-label="Map showing the reported location"></div>
              @else
                <div class="h-36 rounded-xl border border-dashed border-slate-200 bg-slate-50 flex items-center justify-center text-xs text-slate-400 italic text-center px-2">
                  No map pin shared
                </div>
              @endif
            </div>
          </div>

          <div class="mt-2.5 flex flex-wrap items-center justify-between gap-2 text-xs text-slate-600">
            <div>
              <strong>Purok:</strong>
              @if($residentPurok)
                <span>{{ $residentPurok->name }}</span>
              @else
                <span class="italic text-slate-400">Not provided</span>
              @endif
              <span class="mx-1 text-slate-300">·</span>
              <strong>Landmark:</strong>
              @if(trim((string) $residentLandmark) !== '')
                <span>{{ $residentLandmark }}</span>
              @else
                <span class="italic text-slate-400">Not provided</span>
              @endif
            </div>
            @if($googleMapsUrl)
              <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener" class="text-blue-600 hover:underline font-semibold inline-flex items-center gap-1">
                Open in Google Maps <i class="fa-solid fa-arrow-up-right-from-square text-[10px]" aria-hidden="true"></i>
              </a>
            @endif
          </div>
        </div>
      </div>

      {{-- TAB 2: TIMELINE & NOTES --}}
      <div class="dossier-panel rw-fade-in p-4 space-y-4 hidden" data-dossier-panel="timeline">
        @unless($isCompleted || $emergencyActionOnly)
          <form method="POST" action="{{ route('admin.reports.internal-notes.store', $report) }}" class="rounded-xl bg-slate-50 border border-slate-200 p-3 space-y-2" data-prevent-double-submit>
            @csrf
            <label for="timeline-internal-note" class="block text-[11px] font-bold text-slate-700">
              <i class="fa-solid fa-lock text-slate-400 mr-1" aria-hidden="true"></i> Add internal note · Visible to staff only
            </label>
            <textarea id="timeline-internal-note" name="body" rows="2" maxlength="3000" required
                      placeholder="Record staff observations, coordination, or call notes..."
                      class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30"></textarea>
            <div class="flex justify-end">
              <button type="submit" class="rounded-lg bg-[#0b1f4b] hover:bg-[#172e63] text-white text-xs font-bold px-3 py-1.5 transition" data-submit-once>
                Save note
              </button>
            </div>
          </form>
        @endunless

        <ol class="review-activity-list">
          @forelse($activityEntries as $activity)
            @php
              $isInternal = isset($activity['visible_to_resident']) ? !$activity['visible_to_resident'] : str_contains($activity['title'], '(Internal)');
              $isInfoReq = str_contains(strtolower($activity['title']), 'info');
            @endphp
            <li class="relative pl-4 border-l-2 {{ $isInternal ? 'border-slate-300' : ($isInfoReq ? 'border-amber-400' : 'border-blue-500') }}">
              <span class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full {{ $isInternal ? 'bg-slate-400' : ($isInfoReq ? 'bg-amber-500' : 'bg-blue-600') }}"></span>
              <div class="flex flex-wrap items-center justify-between gap-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <strong class="text-xs text-slate-900">{{ $activity['title'] }}</strong>
                  @if($isInternal)
                    <span class="rounded px-1.5 py-0.2 text-[10px] font-bold bg-slate-100 text-slate-600">Staff only</span>
                  @elseif($isInfoReq)
                    <span class="rounded px-1.5 py-0.2 text-[10px] font-bold bg-amber-50 text-amber-800">Info request</span>
                  @else
                    <span class="rounded px-1.5 py-0.2 text-[10px] font-bold bg-blue-50 text-blue-700">Public</span>
                  @endif
                </div>
                <time datetime="{{ $activity['at']?->toIso8601String() }}" class="text-[10px] text-slate-400">{{ $activity['at']?->format('M d, Y h:i A') }}</time>
              </div>
              @if($activity['user'])
                <div class="text-[11px] text-slate-500 mt-0.5">{{ $activity['user'] }}</div>
              @endif
              @if($activity['body'])
                <p class="text-xs text-slate-700 mt-1 bg-slate-50 border border-slate-100 rounded-lg px-2.5 py-1.5 leading-relaxed mb-0">{{ $activity['body'] }}</p>
              @endif
            </li>
          @empty
            <li class="text-xs text-slate-500 italic">No activity recorded yet.</li>
          @endforelse
        </ol>
      </div>

      {{-- TAB 3: DUPLICATES --}}
      <div class="dossier-panel rw-fade-in p-4 space-y-3 hidden" id="duplicate-suggestions-section" data-dossier-panel="duplicates">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-xs font-bold text-slate-900 m-0">Possible Duplicate Reports</h2>
          @if($canMergeDuplicate)
            <button type="button" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-2.5 py-1 transition" data-open-dialog="duplicate-modal">
              <i class="fa-solid fa-code-merge mr-1" aria-hidden="true"></i> Manual merge
            </button>
          @endif
        </div>

        @if($actionableDuplicates->isEmpty() || $report->status === 'merged')
          <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-xs text-slate-500">
            No pending duplicate suggestions for <strong>{{ $report->tracking_code }}</strong>.
          </div>
        @else
          <div class="space-y-3">
            @foreach($actionableDuplicates as $index => $suggestion)
              @php
                $candidate = $suggestion->candidate;
                $similarityPct = (int) round((float) $suggestion->score * 100);
                $candidateCategory = $candidate->category?->name ?? $candidate->residentCategory?->name ?? 'Uncategorized';
                $candidatePurok = $candidate->purok?->name ?? $candidate->residentPurok?->name;
                $candidateIsEmergency = $candidate->is_emergency || $candidate->status === 'emergency_flagged';
              @endphp
              <article class="rounded-xl border border-slate-200 bg-white p-3 space-y-2" data-duplicate-card="{{ $suggestion->id }}">
                <div class="flex flex-wrap items-center justify-between gap-1.5">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="rounded-full bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 text-[10px] font-bold">{{ $similarityPct }}% match</span>
                    <a href="{{ route('admin.reports.show', $candidate) }}" target="_blank" rel="noopener noreferrer" class="font-mono text-xs font-bold text-blue-700 hover:underline">
                      {{ $candidate->tracking_code }}
                    </a>
                    <x-status-badge :status="$candidate->status" class="text-[10px]" />
                  </div>
                </div>
                <div class="text-[11px] text-slate-600">
                  <strong>{{ $candidateCategory }}</strong> · {{ $candidatePurok ?? 'No purok' }}@if($candidate->landmark) · {{ \Illuminate\Support\Str::limit($candidate->landmark, 45) }}@endif
                </div>
                <p class="text-xs text-slate-700 bg-slate-50 rounded-lg p-2 border border-slate-100 m-0">{{ \Illuminate\Support\Str::limit($candidate->description, 140) }}</p>
                <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                  <form method="POST" action="{{ route('admin.reports.duplicate.dismiss', [$report, $suggestion]) }}" class="flex items-center gap-1.5 flex-1">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="note" maxlength="500" placeholder="Dismissal note..." class="text-xs border border-slate-300 rounded-lg px-2 py-1 flex-1 min-w-[100px] bg-white">
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-2.5 py-1">Dismiss</button>
                  </form>
                  @if($canMergeDuplicate && ! $candidateIsEmergency)
                    <button type="button" class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-2.5 py-1"
                            data-open-dialog="duplicate-modal" data-duplicate-candidate-id="{{ $candidate->id }}">
                      Merge
                    </button>
                  @endif
                </div>
              </article>
            @endforeach
          </div>
        @endif
      </div>
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

      {{-- ============================================================= --}}
      {{-- STEP 1 PANEL: REVIEW & TRIAGE                                 --}}
      {{-- ============================================================= --}}
      <div class="rw-step-panel rw-fade-in p-5 space-y-4" data-workspace-panel="1" aria-label="Review AI suggestions" @if($defaultWorkspaceStep !== 1) hidden @endif>
        {{-- Panel Header Strip --}}
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
          <div class="flex flex-wrap items-center gap-2">
            @if($aiReady)
              <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 text-xs font-bold" data-ai-state-badge>
                <i class="fa-solid fa-wand-magic-sparkles text-[10px]" aria-hidden="true"></i> AI ready
              </span>
            @elseif($aiPending)
              <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-0.5 text-xs font-bold" data-ai-state-badge>
                <span class="review-spinner" aria-hidden="true"></span> AI analyzing...
              </span>
            @elseif($aiFailed && $aiConfigured)
              <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 text-xs font-bold" data-ai-state-badge>
                <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i> AI failed
              </span>
            @else
              <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-bold" data-ai-state-badge>
                <i class="fa-solid fa-circle-minus text-[10px]" aria-hidden="true"></i> AI unavailable
              </span>
            @endif

            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $confidence !== null && $confidence >= $highConfidenceThreshold ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($confidence !== null && $confidence >= $lowConfidenceThreshold ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}"
                  data-ai-confidence-wrap @if(! $aiReady) hidden @endif>
              <span data-ai-confidence>Confidence {{ $confidence !== null ? number_format($confidence * 100) : '—' }}%</span>
            </span>

            <span class="inline-flex items-center rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 text-xs font-bold" data-low-confidence @if(! $lowConfidence) hidden @endif>
              Low confidence
            </span>

            @if(! $emergencyActionOnly)
              <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full" data-reviewed-count>
                {{ $reviewedInitialCount }} of {{ $requiredFieldsCount }} reviewed
              </span>
            @endif
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <span class="review-gate-hint review-card2-gate-summary text-xs font-bold" data-gate-reason-card2 aria-live="polite"></span>
            @if(! $emergencyActionOnly)
              @if($aiPending)
                <button type="submit" form="review-stop-ai-form" data-stop-ai
                        class="inline-flex items-center gap-1.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-3 py-1.5 shadow-xs transition">
                  <i class="fa-solid fa-stop" aria-hidden="true"></i> Stop
                </button>
              @endif
              @if($aiFailed && $aiConfigured)
                <button type="submit" form="review-retry-ai-form" aria-label="Retry AI analysis" title="Retry AI analysis"
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3 py-1.5 shadow-xs transition">
                  <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Try again
                </button>
              @endif
              <button type="button" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-1.5 shadow-xs transition"
                      data-accept-all @if(! $aiReady) hidden disabled @endif>
                <i class="fa-solid fa-check-double" aria-hidden="true"></i> Accept all AI suggestions
              </button>
            @endif
          </div>
        </div>

        <p class="text-xs text-slate-600 m-0" data-ai-state-message aria-live="polite">
          @if($aiReady)
            AI suggestions are ready. Click <strong>Accept all AI suggestions</strong> or apply individual fields below.
            @if($lowConfidence) Check carefully because confidence is low. @endif
          @elseif($aiPending)
            AI is analyzing this report. Your final values will not be changed when suggestions arrive.
          @elseif($aiFailed && $aiConfigured)
            AI suggestions are not available. Enter or confirm Category and Urgency manually. Click <strong>Try again</strong> if needed.
          @else
            AI suggestions are not available. Enter or confirm Category and Urgency manually.
          @endif
        </p>

        @if($report->infoRequests->isNotEmpty() && isset($infoThreadSummary))
          <div class="rounded-xl border border-blue-200 bg-blue-50/70 px-3.5 py-2 text-xs text-blue-900 flex items-center gap-2 flex-wrap" data-chat-summary-strip data-report-id="{{ $report->id }}">
            <i class="fa-solid fa-comments text-blue-600" aria-hidden="true"></i>
            <span class="font-bold" data-chat-message-count>{{ $infoThreadSummary['count'] }} message{{ $infoThreadSummary['count'] === 1 ? '' : 's' }} sent</span>
            <span aria-hidden="true">·</span>
            <span data-chat-awaiting @if(! $infoThreadSummary['awaiting_reply']) hidden @endif>Awaiting resident reply</span>
          </div>
        @endif

        {{-- Compact 2x2 Triage Field Grid --}}
        <div id="review-triage-grid" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
          {{-- Card 1: Category --}}
          <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-2.5" data-review-row data-field="category"
               data-resident-value="{{ $residentCategoryId ?? '' }}"
               data-resident-label="{{ $residentCategory?->name ?? '' }}"
               data-ai-value="{{ $aiCategoryId ?? '' }}"
               data-ai-label="{{ $aiCategoryLabel ?? '' }}"
               data-ai-present="{{ $aiCategoryPresent ? 'true' : 'false' }}"
               data-review-required="{{ $categoryReviewRequired ? 'true' : 'false' }}"
               data-reviewed="{{ $alreadyReviewed || ! $categoryReviewRequired ? 'true' : 'false' }}">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <label for="final-category" class="text-xs font-extrabold text-slate-900">Category <span class="text-rose-600">*</span></label>
              <div class="flex items-center gap-1.5 flex-wrap">
                @if(!empty($voiceProvenance['category']['filled']))
                  <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ !empty($voiceProvenance['category']['edited']) ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                    {{ !empty($voiceProvenance['category']['edited']) ? 'Edited after recording' : 'From recording' }}
                  </span>
                @endif
                <span class="review-badge review-badge-outline-green" data-comparison-badge hidden></span>
                <span class="review-badge {{ $categoryFinalState === 'Accepted' ? 'review-badge-green' : ($categoryFinalState === 'Edited' ? 'review-badge-amber' : ($categoryFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                      data-final-state>{{ $categoryFinalState }}</span>
              </div>
            </div>

            <div class="text-[11px] text-slate-600 space-y-0.5">
              <div>Resident: @if($residentCategory)<strong class="text-slate-800" data-resident-display>{{ $residentCategory->name }}</strong>@else<span class="italic text-slate-400">Not chosen by resident</span>@endif</div>
              <div>AI: <strong class="text-slate-800" data-ai-value-display>@if($aiReady){{ $aiCategoryLabel ?? 'Not provided' }}@elseif($aiPending)Waiting...@else Not available @endif</strong></div>
            </div>

            <select id="final-category" name="category_id" data-final-control data-required-field required @disabled($emergencyActionOnly)
                    class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
              <option value="">Select category</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $finalCategoryId === (string) $category->id)>{{ $category->name }}</option>
              @endforeach
            </select>

            <div class="flex items-center justify-between gap-2 pt-0.5 flex-wrap">
              <span class="review-gate-field-hint {{ $finalCategoryId ? 'is-ready' : 'is-missing' }}" data-gate-hint="category">
                <i class="fa-solid {{ $finalCategoryId ? 'fa-check' : 'fa-lock' }}" aria-hidden="true"></i>
                <span>{{ $finalCategoryId ? 'Ready to route' : 'Required to route' }}</span>
              </span>
              <div class="flex items-center gap-2 text-[11px] font-bold">
                <button type="button" class="text-blue-600 hover:underline" data-use-ai @disabled($emergencyActionOnly) @if(! $aiCategoryId || ! $aiCategoryPresent || $aiCategoryId === $residentCategoryId) hidden @endif>Use AI</button>
                <button type="button" class="text-blue-600 hover:underline" data-use-resident @disabled($emergencyActionOnly) @if($residentCategoryId === null || $residentCategoryId === $aiCategoryId) hidden @endif>Use resident's</button>
                <button type="button" class="text-emerald-600 hover:underline" data-accept-row @disabled($emergencyActionOnly) @if(! $aiCategoryId || ! $aiCategoryPresent) hidden @endif>Accept</button>
                <button type="button" class="text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
              </div>
            </div>
          </div>

          {{-- Card 2: Urgency (1-Tap Pill Selector) --}}
          <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-2.5" data-review-row data-field="urgency"
               data-resident-value=""
               data-resident-label=""
               data-ai-value="{{ $aiUrgency ?? '' }}"
               data-ai-label="{{ $aiUrgency ?? '' }}"
               data-ai-present="{{ $aiUrgencyPresent ? 'true' : 'false' }}"
               data-review-required="{{ $urgencyReviewRequired ? 'true' : 'false' }}"
               data-reviewed="{{ $alreadyReviewed || ! $urgencyReviewRequired ? 'true' : 'false' }}">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <label for="final-urgency" class="text-xs font-extrabold text-slate-900">Urgency <span class="text-rose-600">*</span></label>
              <div class="flex items-center gap-1.5">
                <span class="review-badge review-badge-outline-blue" data-comparison-badge hidden></span>
                <span class="review-badge {{ $urgencyFinalState === 'Accepted' ? 'review-badge-green' : ($urgencyFinalState === 'Edited' ? 'review-badge-amber' : ($urgencyFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                      data-final-state>{{ $urgencyFinalState }}</span>
              </div>
            </div>

            <div class="text-[11px] text-slate-600 space-y-0.5">
              <div>Resident: <span class="italic text-slate-400" data-resident-display>Not provided</span></div>
              <div>AI: <strong class="text-slate-800" data-ai-value-display>@if($aiReady){{ $aiUrgency ?? 'Not provided' }}@elseif($aiPending)Waiting...@else Not available @endif</strong></div>
            </div>

            {{-- Underlying form control synced with 1-tap pills --}}
            <select id="final-urgency" name="urgency" class="sr-only" tabindex="-1" aria-hidden="true" data-final-control data-required-field required @disabled($emergencyActionOnly)>
              <option value="">Select urgency</option>
              @foreach(['Low', 'Medium', 'High', 'Critical'] as $urgency)
                <option value="{{ $urgency }}" @selected($finalUrgency === $urgency)>{{ $urgency }}</option>
              @endforeach
            </select>

            <div class="grid grid-cols-4 gap-1 rounded-lg border border-slate-200 bg-slate-100 p-1" role="group" aria-label="Urgency level">
              @foreach(['Low', 'Medium', 'High', 'Critical'] as $urgency)
                <button type="button" data-urgency-pill-btn="{{ $urgency }}" @disabled($emergencyActionOnly)
                        class="py-1.5 px-1 rounded-md text-xs font-bold transition text-center {{ $finalUrgency === $urgency ? 'is-selected' : 'text-slate-600 hover:text-slate-900' }}">
                  {{ $urgency }}
                </button>
              @endforeach
            </div>

            <div class="flex items-center justify-between gap-2 pt-0.5 flex-wrap">
              <span class="review-gate-field-hint {{ $finalUrgency ? 'is-ready' : 'is-missing' }}" data-gate-hint="urgency">
                <i class="fa-solid {{ $finalUrgency ? 'fa-check' : 'fa-lock' }}" aria-hidden="true"></i>
                <span>{{ $finalUrgency ? 'Ready to route' : 'Required to route' }}</span>
              </span>
              <div class="flex items-center gap-2 text-[11px] font-bold">
                <button type="button" class="text-blue-600 hover:underline" data-use-ai @disabled($emergencyActionOnly) @if(! $aiUrgencyPresent) hidden @endif>Use AI</button>
                <button type="button" class="text-emerald-600 hover:underline" data-accept-row @disabled($emergencyActionOnly) @if(! $aiUrgencyPresent) hidden @endif>Accept</button>
                <button type="button" class="text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
              </div>
            </div>
          </div>

          {{-- Card 3: Purok --}}
          <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-2" data-review-row data-field="purok"
               data-resident-value="{{ $residentPurok?->id ?? '' }}"
               data-resident-label="{{ $residentPurok?->name ?? '' }}"
               data-ai-value="" data-ai-label="" data-ai-present="false"
               data-review-required="false" data-reviewed="true">
            <div class="flex items-center justify-between gap-2">
              <label for="final-purok" class="text-xs font-extrabold text-slate-900">Purok</label>
              <div class="flex items-center gap-1.5">
                @if(!empty($voiceProvenance['purok']['filled']))
                  <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ !empty($voiceProvenance['purok']['edited']) ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                    {{ !empty($voiceProvenance['purok']['edited']) ? 'Edited after recording' : 'From recording' }}
                  </span>
                @endif
                <span class="review-badge review-badge-gray" data-final-state>Resident</span>
              </div>
            </div>
            <div class="text-[11px] text-slate-600">
              Resident: @if($residentPurok)<strong class="text-slate-800" data-resident-display>{{ $residentPurok->name }}</strong>@else<span class="italic text-slate-400">Not provided</span>@endif
              <span data-ai-value-display class="sr-only"></span>
              <span data-comparison-badge hidden class="sr-only"></span>
            </div>
            <select id="final-purok" name="purok_id" data-final-control @disabled($emergencyActionOnly)
                    class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
              <option value="">Select purok</option>
              @foreach($puroks as $purok)
                <option value="{{ $purok->id }}" @selected((string) $finalPurokId === (string) $purok->id)>{{ $purok->name }}</option>
              @endforeach
            </select>
            <div class="flex justify-end">
              <button type="button" class="text-[11px] font-bold text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
            </div>
          </div>

          {{-- Card 4: Landmark --}}
          <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-2" data-review-row data-field="landmark"
               data-resident-value="{{ $residentLandmark ?? '' }}"
               data-resident-label="{{ $residentLandmark ?? '' }}"
               data-ai-value="" data-ai-label="" data-ai-present="false"
               data-review-required="false" data-reviewed="true">
            <div class="flex items-center justify-between gap-2">
              <label for="final-landmark" class="text-xs font-extrabold text-slate-900">Landmark</label>
              <div class="flex items-center gap-1.5">
                @if(!empty($voiceProvenance['landmark']['filled']))
                  <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ !empty($voiceProvenance['landmark']['edited']) ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                    {{ !empty($voiceProvenance['landmark']['edited']) ? 'Edited after recording' : 'From recording' }}
                  </span>
                @endif
                <span class="review-badge review-badge-gray" data-final-state>Resident</span>
              </div>
            </div>
            <div class="text-[11px] text-slate-600 truncate">
              Resident: @if(trim((string) $residentLandmark) !== '')<strong class="text-slate-800" data-resident-display>{{ $residentLandmark }}</strong>@else<span class="italic text-slate-400">Not provided</span>@endif
              <span data-ai-value-display class="sr-only"></span>
              <span data-comparison-badge hidden class="sr-only"></span>
            </div>
            <input id="final-landmark" type="text" name="landmark" maxlength="255"
                   value="{{ $finalLandmark }}" placeholder="Enter landmark or nearby reference" data-final-control @disabled($emergencyActionOnly)
                   class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
            <div class="flex justify-end">
              <button type="button" class="text-[11px] font-bold text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
            </div>
          </div>
        </div>

        {{-- Incident Description Comparison Block --}}
        <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-3" data-review-row data-field="description"
             data-resident-value="{{ $residentDescription }}"
             data-resident-label="{{ $residentDescription }}"
             data-ai-value="{{ $aiDescription ?? '' }}"
             data-ai-label="{{ $aiDescription ?? '' }}"
             data-ai-present="{{ $aiDescriptionPresent ? 'true' : 'false' }}"
             data-review-required="true"
             data-reviewed="{{ $descriptionReviewed ? 'true' : 'false' }}">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="text-xs font-extrabold text-slate-900">Incident Description</span>
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="review-badge review-badge-outline-green" data-comparison-badge hidden></span>
              <span class="review-badge {{ $descriptionFinalState === 'Accepted' ? 'review-badge-green' : ($descriptionFinalState === 'Edited' ? 'review-badge-amber' : ($descriptionFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                    data-final-state>{{ $descriptionFinalState }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="rounded-lg bg-slate-50 border border-slate-200 p-2.5">
              <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Original Resident Text</div>
              <div class="text-xs text-slate-700 leading-relaxed max-h-24 overflow-y-auto">
                @if(trim((string) $residentDescription) !== '')
                  <span data-resident-display>{{ $residentDescription }}</span>
                @else
                  <span class="italic text-slate-400">Not provided</span>
                @endif
              </div>
            </div>

            <div class="rounded-lg bg-blue-50/70 border border-blue-200 p-2.5">
              <div class="text-[10px] font-bold uppercase tracking-wider text-blue-700 mb-1">AI Summary / Suggestion</div>
              <div class="text-xs text-blue-950 leading-relaxed max-h-24 overflow-y-auto">
                @if($aiReady && $aiDescriptionPresent)
                  <div class="review-description-diff" data-description-diff data-original="{{ $residentDescription }}" data-ai="{{ $aiDescription }}"></div>
                  <span data-ai-value-display class="sr-only">{{ $aiDescription }}</span>
                @else
                  <span data-ai-value-display>
                    @if($aiPending)Waiting for suggestion
                    @elseif($aiUnavailable)Not available
                    @else <span class="italic text-slate-400">Not provided</span>
                    @endif
                  </span>
                  <div class="review-description-diff" data-description-diff data-original="{{ $residentDescription }}" data-ai=""></div>
                @endif
              </div>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3 text-xs font-bold">
            <button type="button" class="text-slate-600 hover:text-blue-700" data-use-original @disabled($emergencyActionOnly)>Use original</button>
            <button type="button" class="text-blue-600 hover:underline" data-use-ai @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>Use AI suggestion</button>
            <button type="button" class="text-emerald-600 hover:underline" data-accept-row @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>Accept</button>
            <button type="button" class="text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
          </div>

          <div>
            <label for="final-description" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
              Final Description (Official Record)
            </label>
            <textarea id="final-description" name="staff_description" rows="3" maxlength="2000"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/30 resize-none"
                      data-final-control @disabled($emergencyActionOnly)>{{ $finalDescription }}</textarea>
          </div>
        </div>

        @if($aiReady && ($aiResult->photo_description || $aiResult->key_facts))
          <details class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs">
            <summary class="font-bold text-slate-700 cursor-pointer">AI details</summary>
            @if($aiResult->photo_description)
              <p class="mt-2 mb-1 text-slate-700"><strong>Photo description:</strong> {{ $aiResult->photo_description }}</p>
            @endif
            @if($aiResult->key_facts)
              <ul class="list-disc pl-4 mt-1 mb-0 text-slate-700 space-y-0.5">
                @foreach($aiResult->key_facts as $fact)
                  <li>{{ $fact }}</li>
                @endforeach
              </ul>
            @endif
          </details>
        @endif

        {{-- Step 1 Footer Action Bar --}}
        <div class="border-t border-slate-100 pt-4 mt-2 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <span class="review-gate-hint text-xs" data-gate-reason aria-live="polite"></span>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            @if($canOpenInfoChat)
              <button type="button" class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2 transition"
                      data-chat-open data-report-id="{{ $report->id }}"
                      data-chat-tracking="{{ $report->tracking_code }}"
                      data-chat-category="{{ $report->category?->name ?? 'Category not assigned' }}"
                      data-chat-status="{{ \App\Models\Report::STATUSES[$report->status] ?? $report->status }}"
                      data-chat-summary="{{ $report->description }}{{ $report->purok ? ' — '.$report->purok->name : '' }}">
                Ask Reporter for More Info
              </button>
            @endif
            <button type="button" id="rw-btn-to-step2"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold px-4 py-2.5 shadow-xs transition">
              <span>Continue to Route &amp; Assign</span>
              <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </div>

      {{-- ============================================================= --}}
      {{-- STEP 2 PANEL: ROUTE & ASSIGN                                  --}}
      {{-- ============================================================= --}}
      <div class="rw-step-panel rw-fade-in p-5 space-y-4" data-workspace-panel="2" aria-label="Route and assign" @if($defaultWorkspaceStep !== 2) hidden @endif>
        @if(! $isAfterRoute && ! $emergencyActionOnly)
          @php
            $ruleWithin = (bool) ($routingDecision['within_authority'] ?? ($jurisdiction['within_authority'] ?? true));
            $initialMode = in_array(old('authority_mode'), ['within', 'beyond'], true)
                ? old('authority_mode')
                : ($ruleWithin ? 'within' : 'beyond');
            $isInitiallyWithin = $initialMode === 'within';
            $preferredExternalId = (old('office_id') && $externalOffices->contains('id', (int) old('office_id')))
                ? (int) old('office_id')
                : (int) ($routingDecision['office_id'] ?? $jurisdiction['office']?->id ?? 0);
            $defaultAgency = $externalOffices->firstWhere('id', $preferredExternalId) ?? $externalOffices->first();
            $defaultLetter = old('referral_letter', $letterTemplates[$defaultAgency?->id]['letter'] ?? '');
            $defaultResidentMessage = old('resident_message', $letterTemplates[$defaultAgency?->id]['resident_message'] ?? '');
            $defaultAgencyId = $defaultAgency?->id;
            $matchedRuleName = $routingDecision['reason'] ?? ($jurisdiction['jurisdiction_note'] ?? 'Category default');
            $isSuggestedMatch = $routingDecision && (string) $officeDefault === (string) ($routingDecision['office_id'] ?? '');
            $residentMessageChips = [
              'within' => [
                'The assigned office will inspect within 3 working days.',
                'Your report has been routed to the department in charge.',
                'Staff will assess the site and schedule necessary action.',
                'Work is being scheduled for this report.',
              ],
              'beyond' => [
                'We have referred this report to the appropriate agency.',
                'We will monitor this external referral and follow up.',
                'Your report is under review with the agency.',
                'We will update you as soon as the agency responds.',
              ],
            ];
          @endphp

          <input type="hidden" name="authority_mode" value="{{ $initialMode }}" data-authority-mode-input>
          <input type="hidden" name="jurisdiction_mode_override" value="0" data-jurisdiction-override-input>
          <input type="hidden" name="agency_office_id" value="{{ $defaultAgencyId }}" data-agency-hidden-input>
          <script type="application/json" data-routing-map>@json($routingDecisions)</script>
          <script type="application/json" data-letter-templates>@json($letterTemplates)</script>

          {{-- Jurisdiction Banner & Mode Switcher --}}
          <div class="review-mode-banner rounded-xl border border-l-[3px] p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 transition-all {{ $isInitiallyWithin ? 'border-l-emerald-600 bg-emerald-50/60 border-emerald-200' : 'border-l-indigo-600 bg-indigo-50/60 border-indigo-200' }}"
               data-mode-banner data-rule-mode="{{ $ruleWithin ? 'within' : 'beyond' }}">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <strong class="text-sm font-bold text-slate-900" data-mode-badge>{{ $isInitiallyWithin ? 'Within barangay authority' : 'Beyond barangay authority' }}</strong>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border bg-white text-slate-700 border-slate-200" data-urgency-pill>
                  <span class="w-2 h-2 rounded-full bg-slate-400" data-urgency-dot></span>
                  <span>Urgency: <span data-urgency-label>{{ $priorityLabel }}</span></span>
                </span>
              </div>
              <p class="text-xs text-slate-600 mt-1 mb-0" data-mode-note>
                @if($isInitiallyWithin)
                  Matched rule: <span data-matched-rule-text>{{ $matchedRuleName }}</span>. Assign it to an office and staff member.
                @else
                  External referral required. A referral letter is prepared and 3-day follow-up is tracked.
                @endif
              </p>
            </div>

            <div class="inline-flex p-1 bg-slate-200/70 rounded-lg text-xs self-start md:self-center flex-shrink-0" role="tablist" aria-label="Authority mode toggle">
              <button type="button" class="mode-seg-btn px-3 py-1.5 rounded-md font-bold transition {{ $isInitiallyWithin ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600' }}" data-mode-btn="within">
                Within barangay
              </button>
              <button type="button" class="mode-seg-btn px-3 py-1.5 rounded-md font-bold transition {{ !$isInitiallyWithin ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600' }}" data-mode-btn="beyond">
                Refer to agency
              </button>
            </div>
            <button type="button" class="sr-only" data-mode-toggle data-other-mode="{{ $isInitiallyWithin ? 'beyond' : 'within' }}">Toggle mode</button>
          </div>

          {{-- Mode A: Internal Dispatch --}}
          <div class="review-mode-block space-y-4" data-mode-block="within" @if(!$isInitiallyWithin) hidden @endif>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="office_id" class="text-xs font-bold text-slate-700 flex items-center gap-2 mb-1">
                  <span>Office</span>
                  <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200" data-suggested-badge @if(!$isSuggestedMatch) style="display:none;" @endif>
                    <i class="fa-solid fa-check text-[9px]" aria-hidden="true"></i> Suggested
                  </span>
                </label>
                <select id="office_id" name="office_id" class="w-full h-11 text-xs rounded-lg border border-slate-300 bg-white px-2.5" data-office-select @disabled(!$isInitiallyWithin)>
                  <option value="">Select an office</option>
                  @foreach($offices as $office)
                    <option value="{{ $office->id }}" data-default-assignee="{{ $office->default_assignee_id ?? '' }}" @selected((string) $officeDefault === (string) $office->id)>{{ $office->name }}</option>
                  @endforeach
                </select>
                <div class="text-[11px] text-blue-700 font-medium mt-1.5" data-routing-reason>
                  @if($routingDecision)
                    Rule: {{ $categories->firstWhere('id', $selectedCategoryId)?->name }} → {{ $routingDecision['office_name'] ?? 'No office' }}
                  @else
                    Select a category to see the routing recommendation.
                  @endif
                </div>
              </div>

              <div>
                <label for="assigned_to" class="block text-xs font-bold text-slate-700 mb-1">Assigned to</label>
                <select id="assigned_to" name="assigned_to" class="w-full h-11 text-xs rounded-lg border border-slate-300 bg-white px-2.5" data-assigned-select>
                  <option value="">Unassigned</option>
                  @foreach($staffUsers as $staffUser)
                    <option value="{{ $staffUser->id }}" @selected((string) $assignedDefault === (string) $staffUser->id)>{{ $staffUser->name }}</option>
                  @endforeach
                </select>
                <div class="flex items-center gap-1.5 text-[11px] text-amber-700 font-medium mt-1.5" data-unassigned-note @if($assignedDefault) style="display:none;" @endif>
                  <i class="fa-solid fa-circle-exclamation text-amber-600" aria-hidden="true"></i>
                  <span>Optional — leaving Unassigned will not block routing.</span>
                </div>
              </div>
            </div>

            <div>
              <label for="review_note" class="block text-xs font-bold text-slate-700 mb-1">Message to resident <span class="text-slate-400 font-normal">(optional)</span></label>
              <div class="flex items-center gap-1.5 flex-wrap mb-2">
                @foreach($residentMessageChips['within'] as $chip)
                  <button type="button" class="btn-quick-fill text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 text-slate-700 border border-slate-200 transition" data-chip-target="review_note" data-chip-text="{{ $chip }}">
                    {{ $chip }}
                  </button>
                @endforeach
              </div>
              <textarea id="review_note" name="review_note" rows="2" maxlength="1000" class="w-full text-xs rounded-lg border border-slate-300 p-2.5" placeholder="e.g. The assigned office will inspect within 3 working days.">{{ $defaultMessage }}</textarea>
            </div>
          </div>

          {{-- Mode B: External Agency Referral --}}
          <div class="review-mode-block space-y-4" data-mode-block="beyond" @if($isInitiallyWithin) hidden @endif>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label for="agency_id" class="text-xs font-bold text-slate-700">External Agency</label>
                  <span class="rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 text-[10px] font-bold">3-day follow-up</span>
                </div>
                <select id="agency_id" name="office_id" class="w-full h-11 text-xs rounded-lg border border-slate-300 bg-white px-2.5" data-agency-select @disabled($isInitiallyWithin)>
                  <option value="">Select an agency</option>
                  @foreach($externalOffices as $externalOffice)
                    <option value="{{ $externalOffice->id }}" @selected((string) $defaultAgencyId === (string) $externalOffice->id)>{{ $externalOffice->name }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label for="follow_up_owner_id" class="block text-xs font-bold text-slate-700 mb-1">Follow-up owner</label>
                <select id="follow_up_owner_id" name="follow_up_owner_id" class="w-full h-11 text-xs rounded-lg border border-slate-300 bg-white px-2.5" @disabled($isInitiallyWithin)>
                  <option value="">Unassigned</option>
                  @foreach($staffUsers as $staffUser)
                    <option value="{{ $staffUser->id }}" @selected((string) old('follow_up_owner_id', $currentUserId) === (string) $staffUser->id)>{{ $staffUser->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div>
              <label for="referral_letter" class="block text-xs font-bold text-slate-700 mb-1">Editable Referral Endorsement Letter</label>
              <textarea id="referral_letter" name="referral_letter" rows="7" maxlength="20000"
                        class="w-full text-xs font-mono p-3 rounded-lg border border-slate-300"
                        data-referral-letter @disabled($isInitiallyWithin)>{{ $defaultLetter }}</textarea>
            </div>

            <div>
              <label for="resident_message" class="block text-xs font-bold text-slate-700 mb-1">Message to resident <span class="text-slate-400 font-normal">(optional)</span></label>
              <div class="flex items-center gap-1.5 flex-wrap mb-2">
                @foreach($residentMessageChips['beyond'] as $chip)
                  <button type="button" class="btn-quick-fill text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 text-slate-700 border border-slate-200 transition" data-chip-target="resident_message" data-chip-text="{{ $chip }}">
                    {{ $chip }}
                  </button>
                @endforeach
              </div>
              <textarea id="resident_message" name="resident_message" rows="2" maxlength="1000" class="w-full text-xs rounded-lg border border-slate-300 p-2.5" data-resident-message @disabled($isInitiallyWithin)>{{ $defaultResidentMessage }}</textarea>
            </div>
          </div>

          {{-- Step 2 Footer Action Bar --}}
          <div class="border-t border-slate-100 pt-4 mt-2 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
              <button type="button" id="rw-btn-back-step1" class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2 transition">
                &larr; Back to Review
              </button>
              <span class="rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-xs font-bold" data-action-summary-text>
                @if($isInitiallyWithin)
                  Route to: {{ $officeDefault ? ($offices->firstWhere('id', $officeDefault)?->name ?? 'selected office') : 'selected office' }} · {{ $priorityLabel }}
                @else
                  Refer to: {{ $defaultAgency?->name ?? 'external agency' }} · {{ $priorityLabel }}
                @endif
              </span>
            </div>

            <div class="flex items-center gap-2">
              @if($isRouteable)
                <button type="submit" form="review-form" name="intent" value="route"
                        class="btn btn-gold text-xs font-extrabold py-2.5 px-4 rounded-xl inline-flex items-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        data-route-submit @if(!$isInitiallyWithin) style="display:none;" @endif>
                  <i class="fa-solid fa-check" aria-hidden="true"></i>
                  <span>Confirm and route</span>
                </button>
              @endif
              <button type="submit" form="review-form" name="intent" value="referral"
                      class="btn btn-gold text-xs font-extrabold py-2.5 px-4 rounded-xl inline-flex items-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                      data-referral-submit @if($isInitiallyWithin) style="display:none;" @endif>
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                <span>Prepare referral</span>
              </button>
            </div>
          </div>
        @else
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700 space-y-2">
            <div><strong>Current Routing:</strong> {{ $report->office?->name ?? 'Not assigned' }} · <strong>Assigned to:</strong> {{ $report->assignee?->name ?? 'Unassigned' }}</div>
            @if(! $emergencyActionOnly)
              <button type="button" class="rounded-lg bg-blue-600 text-white text-xs font-bold px-3 py-1.5" onclick="document.querySelector('[data-workspace-step=\'3\']').click()">
                Go to Work &amp; Resolve &rarr;
              </button>
            @endif
          </div>
        @endif
      </div>

      @if(! $emergencyActionOnly)
        </form>
      @endif

      {{-- ============================================================= --}}
      {{-- STEP 3 PANEL: WORK & RESOLVE                                  --}}
      {{-- ============================================================= --}}
      <div class="rw-step-panel rw-fade-in p-5 space-y-4" data-workspace-panel="3" @if($defaultWorkspaceStep !== 3) hidden @endif>
        @if(! $isAfterRoute && ! $emergencyActionOnly)
          <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center space-y-2.5">
            <div class="text-sm font-bold text-slate-800">Report Not Yet Routed</div>
            <p class="text-xs text-slate-600 max-w-md mx-auto m-0">
              Complete Step 1 (Review &amp; Triage) and Step 2 (Route &amp; Assign) first before logging field progress or resolution proof.
            </p>
            <button type="button" class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 transition"
                    onclick="document.querySelector('[data-workspace-step=\'2\']').click()">
              Go to Route &amp; Assign
            </button>
          </div>
        @elseif($emergencyActionOnly)
          <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-900 space-y-2">
            <strong>Emergency Handling Required</strong>
            <p class="m-0">Use the emergency banner above to either mark as <em>Not an emergency</em>, <em>Handle Locally (Tanod Response)</em>, or redirect to 911 via <em>Take No Action</em>.</p>
          </div>
        @else
          {{-- Active Work Header Strip --}}
          <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-3.5 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs text-slate-800">
              <div class="font-bold text-sm text-slate-900">{{ $report->office?->name ?? 'Office not assigned' }}</div>
              <div>Assigned to: <strong>{{ $report->assignee?->name ?? 'Unassigned' }}</strong> · {{ $report->category?->name ?? 'Uncategorized' }} · {{ $report->urgency ?? 'Normal' }}</div>
            </div>
            <div class="flex items-center gap-2">
              <x-status-badge :status="$report->status" />
              @if($report->due_at)
                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $isOverdue ? 'bg-rose-100 text-rose-800' : 'bg-white text-blue-700 border border-blue-200' }}">
                  {{ $isOverdue ? 'Overdue' : 'Due '.$report->due_at->format('M d') }}
                </span>
              @endif
            </div>
          </div>

          @if(in_array($report->status, ['routed', 'in_progress', 'needs_info'], true))
            @php
              $updateTypes = \App\Models\Report::UPDATE_TYPES;
              $defaultUpdateType = array_key_first($updateTypes);
              $selectedUpdateType = old('update_type', $defaultUpdateType);
              if (! isset($updateTypes[$selectedUpdateType])) {
                  $selectedUpdateType = $defaultUpdateType;
              }
              $selectedUpdateMeta = $updateTypes[$selectedUpdateType];
              $defaultUpdateMessage = old('message', $selectedUpdateMeta['template'] ?? '');
              $openResolutionForm = $errors->has('note') || $errors->has('proof_photos') || $errors->has('proof_photos.*');
            @endphp

            {{-- Log Progress Update --}}
            <form method="POST" action="{{ route('admin.reports.updates.store', $report) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 p-4 space-y-3" data-prevent-double-submit>
              @csrf
              <div class="flex items-center justify-between gap-2 flex-wrap">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 m-0">Log Progress Update</h3>
                <div class="flex flex-wrap gap-1.5">
                  @foreach(['Work started' => 'Field crew on-site; work has started.', 'Inspection scheduled' => 'Site inspection has been scheduled.', 'Waiting on materials' => 'Waiting on required materials for repair.'] as $chipLabel => $chipMsg)
                    <button type="button" class="btn-quick-fill text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 text-slate-700 border border-slate-200 transition"
                            data-chip-target="update-message" data-chip-text="{{ $chipMsg }}">
                      {{ $chipLabel }}
                    </button>
                  @endforeach
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label for="update-type" class="block text-xs font-bold text-slate-700 mb-1">Update type</label>
                  <select id="update-type" name="update_type" required data-update-type-select class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                    @foreach($updateTypes as $typeKey => $typeMeta)
                      <option value="{{ $typeKey }}" data-template="{{ $typeMeta['template'] }}" data-message-required="{{ $typeMeta['message_required'] ? '1' : '0' }}" @selected($selectedUpdateType === $typeKey)>
                        {{ $typeMeta['label'] }} ({{ \App\Models\Report::STATUSES[$typeMeta['status']] ?? $typeMeta['status'] }})
                      </option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label for="expected-completion-date" class="block text-xs font-bold text-slate-700 mb-1">Expected completion date <span class="text-slate-400 font-normal">(optional)</span></label>
                  <input id="expected-completion-date" type="date" name="expected_completion_date" min="{{ now()->toDateString() }}" value="{{ old('expected_completion_date') }}" class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                </div>
              </div>

              <div>
                <label for="update-message" class="block text-xs font-bold text-slate-700 mb-1">
                  Message to resident <span class="text-slate-400 font-normal" data-update-message-optional>{{ $selectedUpdateMeta['message_required'] ? '(required)' : '(optional)' }}</span>
                </label>
                <textarea id="update-message" name="message" rows="2" maxlength="2000" data-update-message @if($selectedUpdateMeta['message_required']) required @endif
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ $defaultUpdateMessage }}</textarea>
              </div>

              <div class="review-form-field">
                <label for="update-photos" class="block text-xs font-bold text-slate-700 mb-1">Progress photos <span class="text-slate-400 font-normal">(optional · max {{ $proofConfig['max_files'] }}, {{ round($proofConfig['max_size_kb'] / 1024) }} MB each)</span></label>
                <input id="update-photos" type="file" name="photos[]" multiple accept="{{ $proofConfig['accept'] }}"
                       data-proof-input data-max-files="{{ $proofConfig['max_files'] }}" data-max-size-kb="{{ $proofConfig['max_size_kb'] }}" class="text-xs">
                <ul class="review-proof-errors" data-proof-errors hidden></ul>
                <div class="review-proof-preview" data-proof-preview></div>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <div>
                  <input type="hidden" name="visible_to_resident" value="0">
                  <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="visible_to_resident" value="1" @checked(old('visible_to_resident', '1') == '1')>
                    <span>Visible to resident</span>
                  </label>
                </div>
                <div class="flex items-center gap-2">
                  <button type="submit" class="rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3.5 py-2 transition" data-submit-once>Post update</button>
                  <button type="button" class="rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold px-3.5 py-2 transition"
                          data-toggle-resolution aria-expanded="{{ $openResolutionForm ? 'true' : 'false' }}" aria-controls="resolution-form-panel">
                    Mark resolved
                  </button>
                </div>
              </div>
            </form>

            {{-- Upload Proof Photos & Propose Resolution --}}
            <div id="resolution-form-panel" class="rounded-xl border border-emerald-200 bg-emerald-50/30 p-4 space-y-3" data-resolution-panel @if(! $openResolutionForm) hidden @endif>
              <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" enctype="multipart/form-data" class="space-y-3" data-prevent-double-submit>
                @csrf
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-900 m-0">Upload Proof Photos &amp; Propose Resolution</h3>
                <div>
                  <label for="resolution-note" class="block text-xs font-bold text-slate-700 mb-1">Resolution note <span class="text-rose-600">*</span></label>
                  <textarea id="resolution-note" name="note" rows="3" required maxlength="2000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">{{ old('note') }}</textarea>
                  @error('note')<span class="review-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="review-form-field">
                  <label for="proof-photos" class="block text-xs font-bold text-slate-700 mb-1">Proof photos <span class="text-slate-400 font-normal">(up to {{ $proofConfig['max_files'] }} images, {{ round($proofConfig['max_size_kb'] / 1024) }} MB each)</span></label>
                  <input id="proof-photos" type="file" name="proof_photos[]" multiple accept="{{ $proofConfig['accept'] }}"
                         data-proof-input data-max-files="{{ $proofConfig['max_files'] }}" data-max-size-kb="{{ $proofConfig['max_size_kb'] }}" class="text-xs">
                  <ul class="review-proof-errors" data-proof-errors hidden></ul>
                  <div class="review-proof-preview" data-proof-preview></div>
                </div>
                <div class="flex justify-end">
                  <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 transition" data-submit-once>
                    <i class="fa-solid fa-check-circle mr-1" aria-hidden="true"></i> Submit resolution proof
                  </button>
                </div>
              </form>

              @if($report->proofPhotos->isNotEmpty())
                <div class="pt-3 border-t border-emerald-200">
                  <h4 class="text-xs font-bold text-slate-700 mb-2">Saved proof photos</h4>
                  <div class="review-proof-preview">
                    @foreach($report->proofPhotos as $proofPhoto)
                      <div class="review-proof-item">
                        <a href="{{ route('admin.reports.proof-photos.show', [$report, $proofPhoto]) }}" target="_blank" rel="noopener">
                          <img src="{{ route('admin.reports.proof-photos.show', [$report, $proofPhoto]) }}" alt="Saved proof photo">
                        </a>
                        <form method="POST" action="{{ route('admin.reports.proof-photos.destroy', [$report, $proofPhoto]) }}"
                              data-confirm-delete="Delete this proof photo? This cannot be undone.">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="review-proof-delete" aria-label="Delete proof photo">&times;</button>
                        </form>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>

          @elseif($report->status === 'awaiting_confirmation')
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
              Waiting for the resident to confirm the resolution on the tracking page.
            </div>
            @if($report->due_at && $report->due_at->copy()->addDays($autoCloseDays)->isPast() && auth()->user()?->role === 'admin')
              <form method="POST" action="{{ route('admin.reports.close', $report) }}" class="rounded-xl border border-slate-200 p-4 space-y-2" data-prevent-double-submit>
                @csrf
                <label for="close-justification" class="block text-xs font-bold text-slate-700">Manual Resolution Justification <span class="text-rose-600">*</span></label>
                <textarea id="close-justification" name="justification" rows="2" required minlength="10" maxlength="1000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ old('justification') }}</textarea>
                <button type="submit" class="rounded-xl bg-slate-800 text-white text-xs font-bold px-3.5 py-2" data-submit-once>Finalize resolution</button>
              </form>
            @endif

          @elseif($report->status === 'referred')
            @php $referral = $report->activeReferral; $referralOffice = $referral?->office; @endphp
            @if($referral)
              <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4 space-y-3" data-referral-panel data-letter="{{ $referral->referral_text }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs font-bold text-indigo-800 bg-white border border-indigo-200 px-2 py-0.5 rounded">{{ $referral->reference_no }}</span>
                    <strong class="text-xs text-slate-900">{{ $referralOffice?->name ?? 'Unknown agency' }}</strong>
                  </div>
                  <a class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3 py-1.5" href="{{ route('admin.referrals.letter', $referral) }}" target="_blank" rel="noopener" data-turbo="false">
                    <i class="fa-solid fa-print mr-1" aria-hidden="true"></i> Print / Save as PDF
                  </a>
                </div>
                @if($referral->status === 'prepared')
                  <form method="POST" action="{{ route('admin.referrals.mark-sent', $referral) }}" class="space-y-2" data-prevent-double-submit>
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                      <input type="date" name="sent_at" value="{{ now()->toDateString() }}" class="h-9 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                      <select name="sent_via" class="h-9 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                        <option value="">Sent via (optional)</option>
                        @foreach(\App\Models\Referral::SENT_VIA as $sentViaKey => $sentViaLabel)
                          <option value="{{ $sentViaKey }}">{{ $sentViaLabel }}</option>
                        @endforeach
                      </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-blue-600 text-white text-xs font-bold px-3.5 py-1.5" data-submit-once>Mark as sent</button>
                  </form>
                @elseif($referral->isOpen())
                  <form method="POST" action="{{ route('admin.referrals.updates.store', $referral) }}" enctype="multipart/form-data" class="space-y-2" data-prevent-double-submit>
                    @csrf
                    <textarea name="note" rows="2" required maxlength="2000" placeholder="What the agency reported..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs"></textarea>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                      <select name="channel" required class="h-9 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                        @foreach(\App\Models\Referral::CHANNELS as $channelKey => $channelLabel)
                          <option value="{{ $channelKey }}">{{ $channelLabel }}</option>
                        @endforeach
                      </select>
                      <select name="status" class="h-9 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                        <option value="">Keep current status</option>
                        @foreach(\App\Models\Referral::UPDATE_TARGET_STATUSES as $updateStatus)
                          <option value="{{ $updateStatus }}">{{ \App\Models\Referral::STATUSES[$updateStatus] ?? $updateStatus }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <button type="submit" class="rounded-lg bg-blue-600 text-white text-xs font-bold px-3 py-1.5" data-submit-once>Log update</button>
                      <button type="button" class="rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold px-3 py-1.5" data-open-dialog="close-referral-modal">Close referral</button>
                    </div>
                  </form>

                  <dialog id="close-referral-modal" class="review-modal" @if($errors->has('outcome')) data-open-on-load @endif>
                    <form method="POST" action="{{ route('admin.referrals.close', $referral) }}" data-prevent-double-submit>
                      @csrf
                      <header class="review-modal-header">
                        <strong><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Close referral</strong>
                        <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
                      </header>
                      <div class="review-modal-body space-y-2">
                        <select name="outcome" required class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs">
                          @foreach(\App\Models\Referral::OUTCOMES as $outcomeKey => $outcomeLabel)
                            <option value="{{ $outcomeKey }}">{{ $outcomeLabel }}</option>
                          @endforeach
                        </select>
                        <textarea name="note" rows="3" required maxlength="2000" placeholder="Summarize the final outcome..." class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></textarea>
                      </div>
                      <footer class="review-modal-footer">
                        <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
                        <button type="submit" class="review-button review-button-primary" data-submit-once>Close referral</button>
                      </footer>
                    </form>
                  </dialog>
                @endif
              </div>
            @endif

          @elseif(in_array($report->status, ['resolved', 'no_action'], true))
            @if($report->status === 'resolved')
              <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs text-emerald-900 space-y-1">
                <div class="font-bold">Resolved @if($report->resolved_at) on {{ $report->resolved_at->format('M j, Y g:i A') }} @endif</div>
                @if($report->satisfaction_rating)
                  <div>Resident rating: <strong>{{ $report->satisfaction_rating }} / 5</strong> @if($report->satisfaction_comment) — “{{ $report->satisfaction_comment }}” @endif</div>
                @endif
              </div>
            @endif
            <form method="POST" action="{{ route('admin.reports.reopen', $report) }}" class="rounded-xl border border-slate-200 p-4 space-y-2" data-prevent-double-submit>
              @csrf
              <label for="reopen-reason" class="block text-xs font-bold text-slate-700">Reason for reopening</label>
              <textarea id="reopen-reason" name="reason" rows="2" required minlength="5" maxlength="1000" placeholder="Explain why this report is being reopened..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ old('reason') }}</textarea>
              <button type="submit" class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2" data-submit-once>Reopen report</button>
            </form>
          @elseif($report->status === 'merged')
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700 space-y-2">
              <p class="m-0">This report was merged as a duplicate.</p>
              <button type="button" class="rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold px-3 py-1.5" data-open-dialog="unmerge-modal">Unmerge report</button>
            </div>
          @endif
        @endif
      </div>
    </section>
  </div>

  {{-- Auxiliary action forms outside #review-form --}}
  @if(auth()->user()?->hasPermission('cases.create'))
    <form id="kebab-convert-case-form" method="POST" action="{{ route('admin.reports.convert-to-case', $report) }}" class="hidden">@csrf</form>
  @endif
  @if(!$report->is_emergency && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true))
    <form id="kebab-emergency-form" method="POST" action="{{ route('admin.reports.emergency', $report) }}" data-confirm="Flag this report as an emergency concern?" class="hidden">
      @csrf
      @method('PATCH')
      <input type="hidden" name="confirm" value="1">
    </form>
  @endif
  @if($aiUnavailable && $report->ai_status === 'failed' && ! $emergencyActionOnly)
    <form id="review-retry-ai-form" method="POST" action="{{ route('admin.reports.retry-ai', $report) }}" class="hidden">@csrf</form>
  @endif
  @if($aiPending && ! $emergencyActionOnly)
    <form id="review-stop-ai-form" method="POST" action="{{ route('admin.reports.cancel-ai', $report) }}" class="hidden">@csrf</form>
  @endif
  @if($isOverdue && ! $report->escalated_at)
    <form id="review-escalate-form" method="POST" action="{{ route('admin.reports.escalate', $report) }}" class="hidden">@csrf</form>
  @endif

  {{-- Modals --}}
  @if($canMergeDuplicate)
    <dialog id="duplicate-modal" class="review-modal">
      <form method="POST" action="{{ route('admin.reports.duplicate', $report) }}">
        @csrf
        @method('PATCH')
        <header class="review-modal-header">
          <strong><i class="fa-solid fa-copy" aria-hidden="true"></i> Mark duplicate</strong>
          <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <div class="review-modal-body">
          @php
            $mergeSelectableCandidates = $actionableDuplicates->pluck('candidate')
                ->filter(fn ($c) => $c !== null && ! $c->is_emergency && $c->status !== 'emergency_flagged')
                ->concat($duplicateCandidates)->unique('id')->values();
          @endphp
          <label for="duplicate-candidate">Duplicate of</label>
          <select id="duplicate-candidate" name="candidate_id" required>
            <option value="">Select a report</option>
            @foreach($mergeSelectableCandidates as $candidate)
              <option value="{{ $candidate->id }}" @selected($pendingDuplicate && ! $pendingDuplicate->candidate->is_emergency && $pendingDuplicate->candidate->status !== 'emergency_flagged' && (string) $pendingDuplicate->candidate_id === (string) $candidate->id)>
                {{ $candidate->tracking_code }} — {{ \Illuminate\Support\Str::limit($candidate->description, 70) }}
              </option>
            @endforeach
          </select>
          <label for="duplicate-note">Merge note</label>
          <textarea id="duplicate-note" name="note" rows="2" required maxlength="500" placeholder="Why these are the same report."></textarea>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary" @disabled($mergeSelectableCandidates->isEmpty())>Mark duplicate</button>
        </footer>
      </form>
    </dialog>
  @endif

  @if($canTakeNoAction)
    <dialog id="no-action-modal" class="review-modal">
      <form method="POST" action="{{ route('admin.reports.status', $report) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="no_action">
        <header class="review-modal-header">
          <strong><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Take no action</strong>
          <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <div class="review-modal-body space-y-3">
          <div>
            <label for="no-action-reason" class="block text-xs font-bold mb-1">Reason code</label>
            <select id="no-action-reason" name="no_action_reason" required class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs">
              <option value="">Select a reason code...</option>
              @foreach(\App\Models\Report::NO_ACTION_REASONS as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label for="no-action-note" class="block text-xs font-bold mb-1">Explanation shown to resident</label>
            <textarea id="no-action-note" name="note" rows="3" required maxlength="500" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></textarea>
          </div>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary">Confirm No Action</button>
        </footer>
      </form>
    </dialog>
  @endif

  @if($report->status === 'merged')
    <dialog id="unmerge-modal" class="review-modal">
      <form method="POST" action="{{ route('admin.reports.unmerge', $report) }}">
        @csrf
        @method('PATCH')
        <header class="review-modal-header">
          <strong><i class="fa-solid fa-code-merge" aria-hidden="true"></i> Unmerge report</strong>
          <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <div class="review-modal-body">
          <label for="unmerge-note" class="block text-xs font-bold mb-1">Reason for unmerging</label>
          <textarea id="unmerge-note" name="note" rows="3" required minlength="5" maxlength="500" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></textarea>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary">Confirm Unmerge</button>
        </footer>
      </form>
    </dialog>
  @endif

  @if($report->is_emergency)
    <dialog id="emergency-handled-modal" class="review-modal">
      <form method="POST" action="{{ route('admin.reports.status', $report) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="in_progress">
        <header class="review-modal-header">
          <strong><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Emergency Handled by Barangay</strong>
          <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <div class="review-modal-body">
          <label for="emergency-handled-note" class="block text-xs font-bold mb-1">Action taken note</label>
          <textarea id="emergency-handled-note" name="note" rows="3" required minlength="5" maxlength="500" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></textarea>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary">Move to In Progress</button>
        </footer>
      </form>
    </dialog>
  @endif

  <dialog id="photo-lightbox-modal" class="review-modal">
    <div class="bg-white rounded-2xl max-w-2xl w-full overflow-hidden">
      <header class="review-modal-header">
        <strong>Incident Photo — {{ $report->tracking_code }}</strong>
        <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
      </header>
      <div class="p-3 bg-slate-100 flex justify-center">
        <img id="photo-lightbox-img" src="" alt="Enlarged resident photo" class="max-h-[75vh] rounded-xl object-contain">
      </div>
    </div>
  </dialog>

  @include('admin.reports.partials.info-thread-modal')
</div>

@push('scripts')
<script>
(() => {
  const root = document.querySelector('[data-review-workflow]');
  if (!root) return;

  const form = document.getElementById('review-form');
  const stateMessage = root.querySelector('[data-ai-state-message]');
  const aiStateBadge = root.querySelector('[data-ai-state-badge]');
  const confidenceWrap = root.querySelector('[data-ai-confidence-wrap]');
  const confidenceBadge = root.querySelector('[data-ai-confidence]');
  const lowConfidenceBadge = root.querySelector('[data-low-confidence]');
  const acceptAllButton = root.querySelector('[data-accept-all]');
  const routeButton = root.querySelector('[data-route-submit]');
  const referralSubmit = root.querySelector('[data-referral-submit]');
  const countLabel = root.querySelector('[data-reviewed-count]');
  const gateReason = root.querySelector('[data-gate-reason]');
  const pollUrl = root.dataset.aiPollUrl;
  let aiState = root.dataset.aiState;
  let pollAttempts = 0;
  let officeTouched = false;
  let currentWorkspaceStep = Number(root.dataset.defaultStep || 1);

  const normalize = (value) => String(value ?? '').trim().toLocaleLowerCase();
  const rows = [...root.querySelectorAll('[data-review-row]')];

  // Dossier Tabs
  root.querySelectorAll('[data-dossier-tab]').forEach((tab) => {
    tab.addEventListener('click', () => {
      const target = tab.dataset.dossierTab;
      root.querySelectorAll('[data-dossier-tab]').forEach((t) => {
        const active = t === tab;
        t.setAttribute('aria-selected', active ? 'true' : 'false');
        t.className = active
          ? 'py-3 px-2 border-b-2 border-blue-600 text-blue-700 bg-white flex items-center justify-center gap-1.5 transition'
          : 'py-3 px-2 border-b-2 border-transparent text-slate-600 hover:text-slate-900 flex items-center justify-center gap-1.5 transition';
      });
      root.querySelectorAll('[data-dossier-panel]').forEach((panel) => {
        panel.classList.toggle('hidden', panel.dataset.dossierPanel !== target);
      });
      if (target === 'evidence') {
        window.setTimeout(() => window.dispatchEvent(new Event('resize')), 60);
      }
    });
  });

  // Dossier Original Description 2-line Clamp Toggle
  const origDesc = document.getElementById('dossier-orig-desc');
  const origToggle = document.getElementById('dossier-desc-toggle');
  if (origDesc && origToggle) {
    if (origDesc.scrollHeight > origDesc.clientHeight + 2) {
      origToggle.classList.remove('hidden');
      origToggle.addEventListener('click', () => {
        const expanded = origDesc.classList.toggle('is-expanded');
        origToggle.textContent = expanded ? 'View less' : 'View more';
      });
    }
  }

  // Photo Lightbox
  const lightboxModal = document.getElementById('photo-lightbox-modal');
  const lightboxImg = document.getElementById('photo-lightbox-img');
  root.querySelectorAll('[data-lightbox-src]').forEach((img) => {
    img.addEventListener('click', () => {
      if (lightboxModal && lightboxImg) {
        lightboxImg.src = img.dataset.lightboxSrc;
        lightboxModal.showModal();
      }
    });
  });

  // 3-Step Workspace Stepper
  const setWorkspaceStep = (step) => {
    currentWorkspaceStep = step;
    root.querySelectorAll('[data-workspace-panel]').forEach((panel) => {
      panel.hidden = Number(panel.dataset.workspacePanel) !== step;
    });
    root.querySelectorAll('[data-workspace-step]').forEach((btn) => {
      const s = Number(btn.dataset.workspaceStep);
      const dot = btn.querySelector('.rw-step-dot');
      const title = btn.querySelector('span > span:first-child');
      if (s < step || (root.dataset.isAfterRoute === 'true' && s < 3 && s !== step)) {
        dot.className = 'rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold bg-emerald-600 text-white transition-all duration-200';
        dot.innerHTML = '<i class="fa-solid fa-check text-[10px]" aria-hidden="true"></i>';
        if (title) title.className = 'block text-xs sm:text-sm font-bold text-slate-800';
      } else if (s === step) {
        dot.className = 'rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold bg-blue-600 text-white ring-4 ring-blue-100 transition-all duration-200';
        dot.textContent = String(s);
        if (title) title.className = 'block text-xs sm:text-sm font-bold text-slate-900';
      } else {
        dot.className = 'rw-step-dot w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold bg-white text-slate-500 border border-slate-300 transition-all duration-200';
        dot.textContent = String(s);
        if (title) title.className = 'block text-xs sm:text-sm font-bold text-slate-500';
      }
    });
    const c1 = document.getElementById('rw-connector-1');
    const c2 = document.getElementById('rw-connector-2');
    if (c1) c1.style.width = step >= 2 ? '100%' : '0%';
    if (c2) c2.style.width = step >= 3 ? '100%' : '0%';
  };

  // Urgency 1-Tap Pill Sync
  const urgencySelect = document.getElementById('final-urgency');
  const syncUrgencyPills = () => {
    const val = urgencySelect?.value || '';
    root.querySelectorAll('[data-urgency-pill-btn]').forEach((btn) => {
      const level = btn.dataset.urgencyPillBtn;
      const active = val === level;
      let activeCls = 'bg-slate-700 text-white shadow-xs';
      if (level === 'Medium') activeCls = 'bg-blue-600 text-white shadow-xs';
      if (level === 'High') activeCls = 'bg-amber-500 text-white shadow-xs';
      if (level === 'Critical') activeCls = 'bg-rose-600 text-white shadow-xs';
      btn.className = active
        ? `py-1.5 px-1 rounded-md text-xs font-bold transition text-center ${activeCls}`
        : 'py-1.5 px-1 rounded-md text-xs font-bold transition text-center text-slate-600 hover:text-slate-900';
    });
  };
  root.querySelectorAll('[data-urgency-pill-btn]').forEach((btn) => {
    btn.addEventListener('click', () => {
      if (!urgencySelect || urgencySelect.disabled) return;
      urgencySelect.value = btn.dataset.urgencyPillBtn;
      urgencySelect.dispatchEvent(new Event('change', { bubbles: true }));
      syncUrgencyPills();
    });
  });

  const stateClass = (state) => ({
    'AI suggested': 'review-badge-blue',
    Accepted: 'review-badge-green',
    Edited: 'review-badge-amber',
    Resident: 'review-badge-gray',
  }[state] || 'review-badge-gray');

  const RESOLVED_STATES = ['Accepted', 'Resident', 'Edited'];
  const rowState = (row) => row.querySelector('[data-final-state]')?.textContent.trim() || '';
  const isRowResolved = (row) => RESOLVED_STATES.includes(rowState(row));

  const setResolvedUi = (row) => {
    const resolved = isRowResolved(row);
    const undo = row.querySelector('[data-undo-row]');
    if (undo) undo.hidden = !resolved;
    if (!resolved) return false;
    const comparisonBadge = row.querySelector('[data-comparison-badge]');
    const useAi = row.querySelector('[data-use-ai]');
    const accept = row.querySelector('[data-accept-row]');
    const useResident = row.querySelector('[data-use-resident]');
    if (comparisonBadge) comparisonBadge.hidden = true;
    if (useAi) useAi.hidden = true;
    if (accept) accept.hidden = true;
    if (useResident) useResident.hidden = true;
    return true;
  };

  const snapshotUndo = (row) => {
    if (isRowResolved(row)) return;
    const control = row.querySelector('[data-final-control]');
    row.dataset.undoValue = control ? control.value : '';
    row.dataset.undoState = rowState(row) || 'AI suggested';
  };

  const setFinalState = (row, state) => {
    const badge = row.querySelector('[data-final-state]');
    if (!badge) return;
    badge.textContent = state;
    badge.className = `review-badge ${stateClass(state)}`;
    row.dataset.reviewed = 'true';
    setResolvedUi(row);
  };

  const updateRow = (row) => {
    const aiPresent = row.dataset.aiPresent === 'true';
    if (setResolvedUi(row)) return;
    const comparisonBadge = row.querySelector('[data-comparison-badge]');
    const aiDisplay = row.querySelector('[data-ai-value-display]');
    const useAi = row.querySelector('[data-use-ai]');
    const accept = row.querySelector('[data-accept-row]');
    const useResident = row.querySelector('[data-use-resident]');

    if (aiState === 'pending') {
      if (aiDisplay) aiDisplay.textContent = 'Waiting for suggestion';
      if (comparisonBadge) comparisonBadge.hidden = true;
      if (useAi) useAi.hidden = true;
      if (accept) accept.hidden = true;
      if (useResident) useResident.hidden = true;
      return;
    }
    if (aiState === 'unavailable') {
      if (aiDisplay) aiDisplay.textContent = 'Not available';
      if (comparisonBadge) comparisonBadge.hidden = true;
      if (useAi) useAi.hidden = true;
      if (accept) accept.hidden = true;
      if (useResident) useResident.hidden = true;
      row.dataset.reviewRequired = 'false';
      return;
    }
    if (!aiPresent) {
      if (comparisonBadge) comparisonBadge.hidden = true;
      if (useAi) useAi.hidden = true;
      if (accept) accept.hidden = true;
      if (useResident) useResident.hidden = true;
      return;
    }

    const residentValue = row.dataset.residentValue || '';
    const aiValue = row.dataset.aiValue || '';
    const differs = residentValue === '' || normalize(residentValue) !== normalize(aiValue);
    if (comparisonBadge) {
      comparisonBadge.hidden = false;
      comparisonBadge.textContent = residentValue === '' ? 'AI only' : (differs ? 'Differs' : 'Matches');
      comparisonBadge.className = `review-badge ${residentValue === '' ? 'review-badge-outline-blue' : (differs ? 'review-badge-outline-amber' : 'review-badge-outline-green')}`;
    }
    if (useAi) useAi.hidden = !differs || aiValue === '';
    if (accept) accept.hidden = aiValue === '';
    if (useResident) useResident.hidden = residentValue === '' || !differs;
  };

  const renderDescriptionDiff = (row) => {
    const container = row.querySelector('[data-description-diff]');
    if (!container) return;
    const suggestion = row.dataset.aiValue || '';
    container.textContent = suggestion;
  };

  const setAiDisplay = (display, label) => {
    if (!display) return;
    const empty = label === null || label === undefined || String(label).trim() === '';
    display.textContent = empty ? 'Not provided' : label;
  };

  // Dialog helpers
  root.querySelectorAll('[data-open-dialog]').forEach((button) => {
    button.addEventListener('click', () => {
      const dialog = document.getElementById(button.dataset.openDialog);
      if (dialog) {
        if (button.dataset.duplicateCandidateId) {
          const candidateSelect = dialog.querySelector('#duplicate-candidate');
          if (candidateSelect) candidateSelect.value = button.dataset.duplicateCandidateId;
        }
        dialog.showModal();
        dialog.querySelector('textarea, select, input')?.focus();
      }
    });
  });
  root.querySelectorAll('dialog').forEach((dialog) => {
    dialog.querySelectorAll('[data-close-modal]').forEach((button) => {
      button.addEventListener('click', () => dialog.close());
    });
    dialog.addEventListener('click', (event) => {
      if (event.target === dialog) dialog.close();
    });
  });

  // Kebab dropdown
  const closeAllKebabs = (except) => {
    document.querySelectorAll('.kebab.open').forEach((kebab) => {
      if (kebab === except) return;
      kebab.classList.remove('open');
      const menu = kebab.querySelector('.kebab-menu');
      if (menu) menu.hidden = true;
      const toggle = kebab.querySelector('[data-kebab-toggle]');
      if (toggle) toggle.setAttribute('aria-expanded', 'false');
    });
  };
  document.querySelectorAll('[data-kebab-toggle]').forEach((toggle) => {
    toggle.addEventListener('click', (event) => {
      event.preventDefault();
      event.stopPropagation();
      const kebab = toggle.closest('.kebab');
      if (!kebab) return;
      const menu = kebab.querySelector('.kebab-menu');
      const isOpen = kebab.classList.contains('open');
      closeAllKebabs(kebab);
      kebab.classList.toggle('open', !isOpen);
      if (menu) menu.hidden = isOpen;
      toggle.setAttribute('aria-expanded', isOpen ? 'false' : 'true');
    });
  });
  document.addEventListener('click', (event) => {
    if (!event.target.closest('.kebab')) closeAllKebabs();
  });

  // Proof input previews
  root.querySelectorAll('[data-proof-input]').forEach((proofInput) => {
    const fieldScope = proofInput.closest('.review-form-field') || root;
    const preview = fieldScope.querySelector('[data-proof-preview]');
    const errorList = fieldScope.querySelector('[data-proof-errors]');
    const maxFiles = Number(proofInput.dataset.maxFiles || 5);
    const maxSizeKb = Number(proofInput.dataset.maxSizeKb || 5120);
    const acceptTypes = (proofInput.accept || '').split(',').map((t) => t.trim()).filter(Boolean);
    const acceptExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    let pendingFiles = [];

    const syncInput = () => {
      const transfer = new DataTransfer();
      pendingFiles.forEach((f) => transfer.items.add(f));
      proofInput.files = transfer.files;
    };
    const renderPending = () => {
      if (!preview) return;
      preview.replaceChildren();
      pendingFiles.forEach((file, index) => {
        const item = document.createElement('div');
        item.className = 'review-proof-item';
        const image = document.createElement('img');
        image.alt = file.name;
        image.src = URL.createObjectURL(file);
        item.append(image);
        const remove = document.createElement('button');
        remove.type = 'button';
        remove.className = 'review-proof-delete';
        remove.setAttribute('aria-label', `Remove ${file.name}`);
        remove.textContent = '\u00d7';
        remove.addEventListener('click', () => {
          URL.revokeObjectURL(image.src);
          pendingFiles.splice(index, 1);
          syncInput();
          renderPending();
        });
        item.append(remove);
        preview.append(item);
      });
    };

    proofInput.addEventListener('change', () => {
      const errors = [];
      const accepted = [];
      [...(proofInput.files || [])].forEach((file) => {
        const ext = file.name.includes('.') ? file.name.split('.').pop().toLowerCase() : '';
        const typeOk = (file.type && acceptTypes.includes(file.type)) || acceptExtensions.includes(ext);
        if (!typeOk) { errors.push(`${file.name} is not a JPG, PNG, or WebP image.`); return; }
        if (file.size > maxSizeKb * 1024) { errors.push(`${file.name} is larger than ${Math.round(maxSizeKb / 1024)} MB.`); return; }
        accepted.push(file);
      });
      const room = Math.max(0, maxFiles - pendingFiles.length);
      if (accepted.length > room) errors.push(`Up to ${maxFiles} photos can be uploaded at a time.`);
      pendingFiles = pendingFiles.concat(accepted.slice(0, room));
      if (errorList) {
        errorList.replaceChildren(...errors.map((m) => { const li = document.createElement('li'); li.textContent = m; return li; }));
        errorList.hidden = errors.length === 0;
      }
      syncInput();
      renderPending();
    });
  });

  // Update type template sync
  const updateTypeSelect = root.querySelector('[data-update-type-select]');
  const updateMessageTextarea = root.querySelector('[data-update-message]');
  const updateMessageOptional = root.querySelector('[data-update-message-optional]');
  if (updateTypeSelect && updateMessageTextarea) {
    let lastPrefilledTemplate = updateTypeSelect.selectedOptions[0]?.dataset.template || '';
    updateTypeSelect.addEventListener('change', () => {
      const selectedOption = updateTypeSelect.selectedOptions[0];
      if (!selectedOption) return;
      const newTemplate = selectedOption.dataset.template || '';
      const isRequired = selectedOption.dataset.messageRequired === '1';
      if (updateMessageTextarea.value.trim() === '' || updateMessageTextarea.value === lastPrefilledTemplate) {
        updateMessageTextarea.value = newTemplate;
      }
      lastPrefilledTemplate = newTemplate;
      updateMessageTextarea.required = isRequired;
      if (updateMessageOptional) updateMessageOptional.textContent = isRequired ? '(required)' : '(optional)';
    });
  }

  const toggleResolutionBtn = root.querySelector('[data-toggle-resolution]');
  const resolutionPanel = root.querySelector('[data-resolution-panel]');
  if (toggleResolutionBtn && resolutionPanel) {
    toggleResolutionBtn.addEventListener('click', () => {
      const willOpen = resolutionPanel.hidden;
      resolutionPanel.hidden = !willOpen;
      toggleResolutionBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      if (willOpen) resolutionPanel.querySelector('textarea')?.focus();
    });
  }

  // Review Gate & Mode Logic
  const modeBanner = root.querySelector('[data-mode-banner]');
  const modeBadge = root.querySelector('[data-mode-badge]');
  const modeNote = root.querySelector('[data-mode-note]');
  const authorityModeInput = root.querySelector('[data-authority-mode-input]');
  const jurisdictionOverrideInput = root.querySelector('[data-jurisdiction-override-input]');
  const agencyHiddenInput = root.querySelector('[data-agency-hidden-input]');
  const blockWithin = root.querySelector('[data-mode-block="within"]');
  const blockBeyond = root.querySelector('[data-mode-block="beyond"]');
  const officeSelect = root.querySelector('[data-office-select]');
  const agencySelect = root.querySelector('[data-agency-select]');
  const letterTextarea = root.querySelector('[data-referral-letter]');
  const residentMessageTextarea = root.querySelector('[data-resident-message]');
  const letterTemplatesElement = root.querySelector('[data-letter-templates]');
  let letterTemplates = {};
  try { if (letterTemplatesElement) letterTemplates = JSON.parse(letterTemplatesElement.textContent); } catch {}
  let currentMode = authorityModeInput?.value === 'beyond' ? 'beyond' : 'within';
  let modeOverridden = false;

  const updateAcceptAllVisibility = () => {
    if (!acceptAllButton) return;
    const aiRows = rows.filter((r) => r.dataset.aiPresent === 'true' && r.dataset.reviewRequired === 'true');
    acceptAllButton.hidden = aiRows.length === 0 || aiRows.every((r) => isRowResolved(r));
  };

  const updateGate = () => {
    const requiredRows = rows.filter((r) => r.dataset.reviewRequired === 'true');
    const reviewedCount = requiredRows.filter((r) => r.dataset.reviewed === 'true').length;
    const remaining = requiredRows.length - reviewedCount;
    if (countLabel) countLabel.textContent = `${reviewedCount} of ${requiredRows.length} reviewed`;
    updateAcceptAllVisibility();

    const category = root.querySelector('[name="category_id"]');
    const urgency = root.querySelector('[name="urgency"]');
    const missingCategory = !category?.value;
    const missingUrgency = !urgency?.value;

    const categoryGateHint = root.querySelector('[data-gate-hint="category"]');
    if (categoryGateHint) {
      categoryGateHint.className = `review-gate-field-hint ${missingCategory ? 'is-missing' : 'is-ready'}`;
      categoryGateHint.innerHTML = `<i class="fa-solid ${missingCategory ? 'fa-lock' : 'fa-check'}" aria-hidden="true"></i> <span>${missingCategory ? 'Required to route' : 'Ready to route'}</span>`;
    }
    const urgencyGateHint = root.querySelector('[data-gate-hint="urgency"]');
    if (urgencyGateHint) {
      urgencyGateHint.className = `review-gate-field-hint ${missingUrgency ? 'is-missing' : 'is-ready'}`;
      urgencyGateHint.innerHTML = `<i class="fa-solid ${missingUrgency ? 'fa-lock' : 'fa-check'}" aria-hidden="true"></i> <span>${missingUrgency ? 'Required to route' : 'Ready to route'}</span>`;
    }

    const hasUnverifiedAudio = root.dataset.hasUnverifiedAudio === 'true';
    const reasons = [];
    if (hasUnverifiedAudio) reasons.push('Audio verification required before routing.');
    if (remaining > 0) reasons.push(`Review ${remaining} more field${remaining === 1 ? '' : 's'}.`);
    if (missingCategory || missingUrgency) {
      reasons.push(`Select ${missingCategory && missingUrgency ? 'Category and Urgency' : (missingCategory ? 'Category' : 'Urgency')}.`);
    }

    if (currentMode === 'beyond') {
      if (routeButton) routeButton.disabled = true;
      const missingAgency = !agencySelect?.value;
      if (referralSubmit) referralSubmit.disabled = hasUnverifiedAudio || missingCategory || missingUrgency || missingAgency;
      if (missingAgency) reasons.push('Choose an agency.');
    } else {
      if (referralSubmit) referralSubmit.disabled = true;
      const missingOffice = Boolean(routeButton && !officeSelect?.value);
      if (missingOffice) reasons.push('Choose an office.');
      if (routeButton) routeButton.disabled = hasUnverifiedAudio || missingCategory || missingUrgency || missingOffice;
    }

    if (gateReason) {
      if (reasons.length > 0) {
        gateReason.innerHTML = `<i class="fa-solid fa-circle-exclamation text-amber-500 mr-1" aria-hidden="true"></i> <span>${reasons.join(' ')}</span>`;
        gateReason.className = 'review-gate-hint text-xs font-medium text-amber-700 bg-amber-50 px-2.5 py-1 rounded-lg border border-amber-200 inline-flex items-center';
      } else {
        gateReason.innerHTML = '<i class="fa-solid fa-check text-emerald-600 mr-1" aria-hidden="true"></i> <span>Ready to route</span>';
        gateReason.className = 'review-gate-hint text-xs font-medium text-emerald-700 bg-emerald-50 px-2.5 py-1 rounded-lg border border-emerald-200 inline-flex items-center';
      }
    }

    const card2GateReason = root.querySelector('[data-gate-reason-card2]');
    if (card2GateReason) {
      if (root.dataset.isAfterRoute === 'true') {
        card2GateReason.hidden = true;
      } else if (hasUnverifiedAudio) {
        card2GateReason.textContent = 'Route gate: Audio verification required before routing';
        card2GateReason.className = 'review-gate-hint review-card2-gate-summary is-missing';
        card2GateReason.hidden = false;
      } else if (missingCategory || missingUrgency) {
        card2GateReason.textContent = `Route gate: Select ${missingCategory && missingUrgency ? 'Category & Urgency' : (missingCategory ? 'Category' : 'Urgency')} to route`;
        card2GateReason.className = 'review-gate-hint review-card2-gate-summary is-missing';
        card2GateReason.hidden = false;
      } else if (remaining > 0) {
        card2GateReason.textContent = `Route gate: ${remaining} field${remaining === 1 ? '' : 's'} pending review`;
        card2GateReason.className = 'review-gate-hint review-card2-gate-summary';
        card2GateReason.hidden = false;
      } else {
        card2GateReason.textContent = 'Route gate: Review requirements met';
        card2GateReason.className = 'review-gate-hint review-card2-gate-summary is-ready';
        card2GateReason.hidden = false;
      }
    }
  };

  // Step navigation listeners
  const attemptStep2 = () => {
    const category = root.querySelector('[name="category_id"]');
    const urgency = root.querySelector('[name="urgency"]');
    const hasUnverifiedAudio = root.dataset.hasUnverifiedAudio === 'true';
    if (hasUnverifiedAudio || !category?.value || !urgency?.value) {
      const card2 = root.querySelector('[data-gate-reason-card2]');
      if (card2) {
        card2.classList.remove('rw-shake');
        void card2.offsetWidth;
        card2.classList.add('rw-shake');
      }
      if (!category?.value) category?.focus();
      else if (!urgency?.value) root.querySelector('[data-urgency-pill-btn]')?.focus();
      return false;
    }
    setWorkspaceStep(2);
    return true;
  };

  root.querySelector('#rw-btn-to-step2')?.addEventListener('click', attemptStep2);
  root.querySelector('#rw-btn-back-step1')?.addEventListener('click', () => setWorkspaceStep(1));
  root.querySelectorAll('[data-workspace-step]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const target = Number(btn.dataset.workspaceStep);
      if (target === 2 && root.dataset.isAfterRoute !== 'true') {
        attemptStep2();
        return;
      }
      setWorkspaceStep(target);
    });
  });

  rows.forEach((row) => {
    updateRow(row);
    renderDescriptionDiff(row);
    const control = row.querySelector('[data-final-control]');
    if (control) {
      const markEdited = () => {
        row.dataset.finalTouched = 'true';
        snapshotUndo(row);
        setFinalState(row, 'Edited');
        if (row.dataset.field === 'urgency') syncUrgencyPills();
        updateGate();
      };
      control.addEventListener('input', markEdited);
      control.addEventListener('change', markEdited);
    }

    row.querySelector('[data-accept-row]')?.addEventListener('click', () => {
      snapshotUndo(row);
      if (control) {
        control.value = row.dataset.aiValue || '';
        control.dispatchEvent(new Event('change', { bubbles: true }));
        setFinalState(row, 'Accepted');
        row.dataset.finalTouched = 'false';
        if (row.dataset.field === 'urgency') syncUrgencyPills();
      }
      updateGate();
    });
    row.querySelector('[data-use-ai]')?.addEventListener('click', () => {
      if (!control || !row.dataset.aiValue) return;
      snapshotUndo(row);
      control.value = row.dataset.aiValue;
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Accepted');
      row.dataset.finalTouched = 'false';
      if (row.dataset.field === 'urgency') syncUrgencyPills();
      updateGate();
    });
    row.querySelector('[data-use-resident]')?.addEventListener('click', () => {
      if (!control) return;
      snapshotUndo(row);
      control.value = row.dataset.residentValue || '';
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Resident');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-use-original]')?.addEventListener('click', () => {
      if (!control) return;
      snapshotUndo(row);
      control.value = row.dataset.residentValue || '';
      control.dispatchEvent(new Event('input', { bubbles: true }));
      setFinalState(row, 'Resident');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-undo-row]')?.addEventListener('click', () => {
      const state = row.dataset.undoState || 'AI suggested';
      if (control && row.dataset.undoValue !== undefined) {
        control.value = row.dataset.undoValue;
        control.dispatchEvent(new Event('change', { bubbles: true }));
      }
      const badge = row.querySelector('[data-final-state]');
      if (badge) {
        badge.textContent = state;
        badge.className = `review-badge ${stateClass(state)}`;
      }
      row.dataset.reviewed = state === 'AI suggested' ? 'false' : 'true';
      if (row.dataset.field === 'urgency') syncUrgencyPills();
      setResolvedUi(row);
      updateGate();
    });
  });

  acceptAllButton?.addEventListener('click', () => {
    rows.forEach((row) => {
      if (row.dataset.aiPresent !== 'true' || !row.dataset.aiValue) return;
      snapshotUndo(row);
      const control = row.querySelector('[data-final-control]');
      if (!control) return;
      control.value = row.dataset.aiValue;
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Accepted');
      row.dataset.finalTouched = 'false';
    });
    syncUrgencyPills();
    updateGate();
  });

  const routingMapElement = root.querySelector('[data-routing-map]');
  const routingReason = root.querySelector('[data-routing-reason]');
  let routingMap = {};
  try { if (routingMapElement) routingMap = JSON.parse(routingMapElement.textContent); } catch {}
  let currentRuleText = modeNote?.querySelector('[data-matched-rule-text]')?.textContent.trim() || 'Category default';

  const updateStickySummary = () => {
    const summaryTextEl = root.querySelector('[data-action-summary-text]');
    if (!summaryTextEl) return;
    const urgency = root.querySelector('[name="urgency"]')?.value || 'not set';
    if (currentMode === 'within') {
      const officeName = officeSelect?.selectedOptions[0]?.text || 'selected office';
      summaryTextEl.textContent = `Route to: ${officeName} · ${urgency}`;
    } else {
      const agencyName = agencySelect?.selectedOptions[0]?.text || 'external agency';
      summaryTextEl.textContent = `Refer to: ${agencyName} · ${urgency}`;
    }
  };

  const applyLetterTemplate = (officeId) => {
    const template = letterTemplates[officeId];
    if (!template) return;
    if (letterTextarea && letterTextarea.dataset.touched !== 'true') letterTextarea.value = template.letter || '';
    if (residentMessageTextarea && residentMessageTextarea.dataset.touched !== 'true') residentMessageTextarea.value = template.resident_message || '';
    updateStickySummary();
  };

  const setMode = (mode, { fromUser = false } = {}) => {
    currentMode = mode === 'beyond' ? 'beyond' : 'within';
    if (fromUser) modeOverridden = true;
    if (authorityModeInput) authorityModeInput.value = currentMode;
    const ruleDefaultMode = modeBanner?.dataset.ruleMode || 'within';
    if (jurisdictionOverrideInput) {
      jurisdictionOverrideInput.value = (fromUser && currentMode !== ruleDefaultMode) ? '1' : '0';
    }
    const within = currentMode === 'within';

    root.querySelectorAll('.mode-seg-btn').forEach((btn) => {
      const isTarget = btn.dataset.modeBtn === currentMode;
      btn.className = `mode-seg-btn px-3 py-1.5 rounded-md font-bold transition ${isTarget ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600'}`;
    });
    if (modeBanner) {
      modeBanner.className = `review-mode-banner rounded-xl border border-l-[3px] p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 transition-all ${within ? 'border-l-emerald-600 bg-emerald-50/60 border-emerald-200' : 'border-l-indigo-600 bg-indigo-50/60 border-indigo-200'}`;
    }
    if (modeBadge) modeBadge.textContent = within ? 'Within barangay authority' : 'Beyond barangay authority';
    if (modeNote) {
      modeNote.innerHTML = within
        ? `Matched rule: <span data-matched-rule-text>${currentRuleText}</span>. Assign it to an office and staff member.`
        : 'External referral required. A referral letter is prepared and 3-day follow-up is tracked.';
    }
    if (blockWithin) blockWithin.hidden = !within;
    if (blockBeyond) blockBeyond.hidden = within;
    if (officeSelect) officeSelect.disabled = !within;
    if (agencySelect) {
      agencySelect.disabled = within;
      if (!within && !agencySelect.value && agencySelect.options.length > 1) agencySelect.selectedIndex = 1;
      if (agencyHiddenInput) agencyHiddenInput.value = agencySelect.value || '';
    }
    const followUpOwnerSelect = root.querySelector('#follow_up_owner_id');
    if (followUpOwnerSelect) followUpOwnerSelect.disabled = within;
    if (letterTextarea) letterTextarea.disabled = within;
    if (residentMessageTextarea) residentMessageTextarea.disabled = within;
    if (routeButton) routeButton.style.display = within ? '' : 'none';
    if (referralSubmit) referralSubmit.style.display = within ? 'none' : '';
    if (!within && agencySelect) applyLetterTemplate(agencySelect.value);
    updateStickySummary();
    updateGate();
  };

  const assignedSelect = root.querySelector('[data-assigned-select]');
  let assignedTouched = false;
  assignedSelect?.addEventListener('change', () => {
    assignedTouched = true;
    const note = root.querySelector('[data-unassigned-note]');
    if (note) note.style.display = assignedSelect.value === '' ? 'flex' : 'none';
    updateStickySummary();
  });

  root.querySelectorAll('.mode-seg-btn').forEach((btn) => {
    btn.addEventListener('click', () => setMode(btn.dataset.modeBtn, { fromUser: true }));
  });
  agencySelect?.addEventListener('change', () => {
    agencySelect.dataset.touched = 'true';
    if (agencyHiddenInput) agencyHiddenInput.value = agencySelect.value || '';
    applyLetterTemplate(agencySelect.value);
    updateGate();
  });
  officeSelect?.addEventListener('change', () => {
    officeTouched = true;
    updateStickySummary();
    updateGate();
  });
  root.querySelectorAll('.btn-quick-fill').forEach((btn) => {
    btn.addEventListener('click', () => {
      const targetArea = root.querySelector('#' + btn.dataset.chipTarget);
      if (targetArea && btn.dataset.chipText) {
        targetArea.value = btn.dataset.chipText;
        targetArea.dispatchEvent(new Event('input', { bubbles: true }));
        targetArea.focus();
      }
    });
  });

  root.querySelector('[name="urgency"]')?.addEventListener('change', (e) => {
    const label = root.querySelector('[data-urgency-label]');
    if (label) label.textContent = e.target.value || 'not set';
    updateStickySummary();
  });

  root.querySelector('[name="category_id"]')?.addEventListener('change', (event) => {
    const decision = routingMap[event.target.value];
    if (!decision) return;
    const ruleMode = decision.within_authority ? 'within' : 'beyond';
    currentRuleText = decision.reason || `${decision.office_name || 'No office'}`;
    if (routingReason) routingReason.textContent = `Rule: ${decision.office_name || 'No office'} — ${decision.reason}`;
    if (decision.within_authority && officeSelect && !officeTouched && decision.office_id) {
      officeSelect.value = String(decision.office_id);
    } else if (!decision.within_authority && agencySelect && agencySelect.dataset.touched !== 'true' && decision.office_id) {
      agencySelect.value = String(decision.office_id);
      if (agencyHiddenInput) agencyHiddenInput.value = agencySelect.value;
      applyLetterTemplate(agencySelect.value);
    }
    if (!modeOverridden) setMode(ruleMode);
    updateStickySummary();
    updateGate();
  });

  // AI Polling (stops after 24 attempts; never overwrites manual edits)
  const applyAiResult = (result) => {
    aiState = result.status;
    root.dataset.aiState = aiState;
    if (result.status === 'ready') {
      if (aiStateBadge) {
        aiStateBadge.className = 'inline-flex items-center gap-1.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 text-xs font-bold';
        aiStateBadge.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles text-[10px]" aria-hidden="true"></i> AI ready';
      }
      const confidence = Number(result.confidence);
      if (confidenceWrap) confidenceWrap.hidden = false;
      if (confidenceBadge) confidenceBadge.textContent = `Confidence ${Number.isFinite(confidence) ? Math.round(confidence * 100) : '—'}%`;
      if (stateMessage) stateMessage.textContent = 'AI suggestions are ready. Your final values were not changed; choose Use AI or Accept for each suggestion.';
      if (acceptAllButton) { acceptAllButton.hidden = false; acceptAllButton.disabled = false; }
      rows.forEach((row) => {
        const value = result.values[row.dataset.field] || { value: null, label: null };
        row.dataset.aiValue = value.value ?? '';
        row.dataset.aiLabel = value.label ?? '';
        row.dataset.aiPresent = value.value !== null && value.value !== '' ? 'true' : 'false';
        row.dataset.reviewRequired = row.dataset.aiPresent;
        const display = row.querySelector('[data-ai-value-display]');
        if (display && row.dataset.field !== 'description') setAiDisplay(display, value.label);
        renderDescriptionDiff(row);
        if (row.dataset.finalTouched !== 'true') {
          setFinalState(row, row.dataset.aiPresent === 'true' ? 'AI suggested' : 'Resident');
          row.dataset.reviewed = row.dataset.aiPresent === 'true' ? 'false' : 'true';
        }
        updateRow(row);
      });
      updateGate();
      return;
    }
    if (result.status === 'unavailable') {
      if (aiStateBadge) aiStateBadge.textContent = 'AI unavailable';
      if (stateMessage) stateMessage.textContent = 'AI suggestions are not available. Confirm Category and Urgency manually.';
      if (acceptAllButton) acceptAllButton.hidden = true;
      rows.forEach(updateRow);
      updateGate();
    }
  };

  let aiPollController = null;
  let aiPollTimer = null;

  root.querySelector('[data-stop-ai]')?.addEventListener('click', () => {
    aiState = 'unavailable';
    if (aiPollTimer) {
      window.clearTimeout(aiPollTimer);
      aiPollTimer = null;
    }
    if (aiPollController) {
      aiPollController.abort();
      aiPollController = null;
    }
  });

  const pollAi = async () => {
    if (aiState !== 'pending' || pollAttempts >= 24) return;
    pollAttempts += 1;
    aiPollController = new AbortController();
    try {
      const response = await fetch(pollUrl, {
        headers: { Accept: 'application/json' },
        signal: aiPollController.signal,
      });
      if (!response.ok) throw new Error(`AI status request failed (${response.status}).`);
      const result = await response.json();
      if (result.status === 'pending') {
        aiPollTimer = window.setTimeout(pollAi, 5000);
        return;
      }
      applyAiResult(result);
    } catch (err) {
      if (err && err.name === 'AbortError') return;
      aiPollTimer = window.setTimeout(pollAi, 5000);
    }
  };

  form?.addEventListener('submit', (event) => {
    if (event.submitter?.value !== 'route' && event.submitter?.value !== 'referral') return;
    rows.forEach((row) => { row.dataset.reviewed = 'true'; });
    updateGate();
    const submitterDisabled = event.submitter.value === 'route' ? routeButton?.disabled : referralSubmit?.disabled;
    if (submitterDisabled) event.preventDefault();
  });

  setWorkspaceStep(currentWorkspaceStep);
  syncUrgencyPills();
  setMode(currentMode);
  updateStickySummary();
  updateGate();
  if (aiState === 'pending') window.setTimeout(pollAi, 1500);
})();
</script>
@endpush
