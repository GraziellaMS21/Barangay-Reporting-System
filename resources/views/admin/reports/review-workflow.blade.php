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
  $residentCategory = $report->residentCategory ?? (! $alreadyReviewed ? $report->category : null);
  $residentPurok = $report->residentPurok ?? (! $alreadyReviewed ? $report->purok : null);
  $residentLandmark = $report->resident_landmark ?? (! $alreadyReviewed ? $report->landmark : null);
  $residentCategoryId = $residentCategory?->id;
  $residentDescription = $report->description;
  $answers = is_array($report->answers) ? $report->answers : [];
  $questionSchema = $residentCategory?->question_schema ?? [];
  $finalCategoryId = old('category_id', $alreadyReviewed
      ? $report->category_id
      : ($aiReady && $aiCategoryId ? $aiCategoryId : $report->category_id));
  $finalUrgency = old('urgency', $alreadyReviewed
      ? $report->urgency
      : ($aiReady && $aiUrgencyPresent ? $aiUrgency : $report->urgency));
  $finalDescription = old('staff_description', $alreadyReviewed
      ? ($report->staff_description ?? $report->description)
      : ($aiReady && $aiDescriptionPresent ? $aiDescription : ($report->staff_description ?? $report->description)));
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
  $categoryReviewRequired = $aiReady && $aiCategoryPresent;
  $urgencyReviewRequired = $aiReady && $aiUrgencyPresent;
  $descriptionReviewRequired = $aiReady && $aiDescriptionPresent;
  $requiredFieldsCount = ($categoryReviewRequired ? 1 : 0) + ($urgencyReviewRequired ? 1 : 0) + ($descriptionReviewRequired ? 1 : 0);
  $reviewedInitialCount = $alreadyReviewed ? $requiredFieldsCount : 0;
  $selectedCategoryId = (int) $finalCategoryId;
  $routingDecision = $routingDecisions[$selectedCategoryId] ?? null;
  $candidateOfficeDefault = $alreadyReviewed
      ? ($report->office_id ?? $routingDecision['office_id'] ?? '')
      : ($routingDecision['office_id'] ?? $report->office_id ?? '');
  $selectedInternalOffice = $offices->firstWhere('id', (int) $candidateOfficeDefault);
  $officeDefault = old('office_id', $selectedInternalOffice ? $selectedInternalOffice->id : '');
  $assignedDefault = old('assigned_to', $report->assigned_to ?? $selectedInternalOffice?->default_assignee_id ?? '');
  // The Message to the resident starts empty; guidance lives in the placeholder
  // so response templates never push stub text like "Template: needs more info"
  // into an outgoing message.
  $defaultMessage = old('review_note', '');
  $isOverdue = $report->due_at !== null
      && $report->due_at->isPast()
      && ! in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true);
  $hasEmergencyFlag = $report->is_emergency
      || $report->status === 'emergency_flagged'
      || ($aiReady && (bool) $aiResult->emergency_flag && ! $emergencyFlagReviewed);
  $emergencyActionOnly = $hasEmergencyFlag;
  $canResolveEmergency = ! in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true);
  $pendingDuplicate = $report->duplicateSuggestions->firstWhere('decision', 'pending');
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
  $statusStep = match ($report->status) {
      'submitted', 'under_review', 'reopened' => 0,
      'routed' => 1,
      'in_progress' => 2,
      'needs_info' => (($report->statusLogs->where('to_status', 'needs_info')->last()?->from_status) === 'in_progress' ? 2 : 1),
      'awaiting_confirmation', 'resolved', 'referred', 'merged', 'no_action' => 3,
      default => 0,
  };
  $isCompleted = in_array($report->status, ['resolved', 'no_action'], true);
  $oneLineSummary = \Illuminate\Support\Str::limit($report->description, 110);
  $priorityLabel = $report->urgency ?: 'not set';
  $googleMapsUrl = $report->lat !== null && $report->lng !== null
      ? 'https://www.google.com/maps/search/?api=1&query='.$report->lat.','.$report->lng
      : null;
@endphp

<div class="review-workflow" data-review-workflow
     data-ai-state="{{ $aiReady ? 'ready' : ($aiPending ? 'pending' : 'unavailable') }}"
     data-ai-poll-url="{{ route('admin.reports.review-ai', $report) }}"
     data-confidence-high="{{ $highConfidenceThreshold }}"
     data-confidence-low="{{ $lowConfidenceThreshold }}">
  <a href="{{ route('admin.reports.index') }}" class="review-back-link">
    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
    Back to report queue
  </a>

  @if(session('success'))
    <div role="status" class="review-flash review-flash-success">{{ session('success') }}</div>
  @endif
  @if(session('error'))
    <div role="alert" class="review-flash review-flash-error">{{ session('error') }}</div>
  @endif
  @if($errors->any())
    <div role="alert" class="review-flash review-flash-error">{{ $errors->first() }}</div>
  @endif

  <header class="review-page-header">
    <div class="review-page-title">
      <h1>{{ $report->tracking_code }}</h1>
      <x-status-badge :status="$report->status" />
      @if($isOverdue)
        <span class="review-badge review-badge-red" title="SLA due {{ $report->due_at->format('M d, Y h:i A') }}">
          <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Overdue
        </span>
      @endif
      @if($report->escalated_at)
        <span class="review-badge review-badge-red">
          <i class="fa-solid fa-fire" aria-hidden="true"></i> Escalated
        </span>
      @endif
    </div>
    <p class="review-submitted">
      Submitted {{ $report->created_at->format('M d, Y \a\t h:i A') }}
      @if($report->due_at)
        <span aria-hidden="true">·</span>
        SLA due {{ $report->due_at->format('M d, Y h:i A') }}
      @endif
    </p>
  </header>

  <ol class="review-stepper" aria-label="Report progress">
    @foreach(['Review', 'Route', 'Work', 'Resolve'] as $stepIndex => $stepLabel)
      @php
        $stepDone = $stepIndex < $statusStep || ($isCompleted && $stepIndex === 3);
        $stepCurrent = ! $isCompleted && $stepIndex === $statusStep;
        $stepWaiting = $stepCurrent && in_array($report->status, ['needs_info', 'awaiting_confirmation'], true);
      @endphp
      <li class="review-step {{ $stepDone ? 'is-complete' : '' }} {{ $stepCurrent ? 'is-current' : '' }} {{ $stepWaiting ? 'is-waiting' : '' }}"
          @if($stepCurrent) aria-current="step" @endif>
        <span class="review-step-circle" aria-hidden="true">
          @if($stepDone)
            <i class="fa-solid fa-check"></i>
          @else
            {{ $stepIndex + 1 }}
          @endif
        </span>
        <span class="review-step-label">{{ $stepLabel }}</span>
        @if($stepWaiting)
          <span class="review-badge review-badge-amber">Waiting</span>
        @endif
      </li>
    @endforeach
  </ol>

  @if($hasEmergencyFlag)
    <section class="review-flag review-flag-emergency" aria-label="Emergency review required">
      <div>
        <strong><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Possible emergency</strong>
        <p>{{ $report->is_emergency ? 'This report is currently flagged as an emergency.' : 'The AI or report details indicate a possible emergency. Confirm before continuing.' }}</p>
      </div>
      <div class="review-flag-actions">
        @if(! $report->is_emergency && $canResolveEmergency)
          <form method="POST" action="{{ route('admin.reports.emergency', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="review-button review-button-outline">Confirm emergency</button>
          </form>
        @endif
        @if($canResolveEmergency)
          <form method="POST" action="{{ route('admin.reports.emergency', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="confirm" value="0">
            <button type="submit" class="review-button review-button-outline">Not an emergency</button>
          </form>
        @else
          <span class="review-field-hint">This report is closed; its emergency state cannot be changed here.</span>
        @endif
      </div>
    </section>
  @elseif($pendingDuplicate)
    <section class="review-flag review-flag-amber">
      <div>
        <strong><i class="fa-solid fa-copy" aria-hidden="true"></i> Possible duplicate</strong>
        <p>
          @if($pendingDuplicate->candidate)
            Similar to {{ $pendingDuplicate->candidate->tracking_code }} ({{ number_format((float) $pendingDuplicate->score, 2) }} match score).
          @else
            A possible duplicate was detected.
          @endif
        </p>
      </div>
      <button type="button" class="review-text-link" data-open-dialog="duplicate-modal">Review duplicate options</button>
    </section>
  @endif

  <section class="review-workflow-card">
    <details id="resident-report-card" class="review-collapsible" @if(! $isAfterRoute) open @endif>
      <summary class="review-card-heading">
        <span class="review-card-number" aria-hidden="true">1</span>
        <span class="review-card-heading-copy">
          <span class="review-card-title">Resident report</span>
          @unless($isAfterRoute)
            <span class="review-card-instruction">Read what the resident sent.</span>
          @endunless
        </span>
        @if($isAfterRoute)
          <span class="review-collapsed-summary">{{ $oneLineSummary }}</span>
          <a class="review-edit-link" href="#resident-report-card" data-expand-review>Edit</a>
        @endif
      </summary>
      <div class="review-card-content">
        <div class="review-reported-by">
          <span class="review-reported-label">Reported by</span>
          @if($report->is_anonymous || ! filled($report->reporter_name))
            <span class="review-not-provided"><i class="fa-solid fa-user" aria-hidden="true"></i> Not stated (anonymous report)</span>
          @else
            <span class="review-reported-identity">
              <span><i class="fa-solid fa-user" aria-hidden="true"></i> {{ $report->reporter_name }}</span>
              @if($report->reporter_contact || $report->reporter_email)
                <span class="review-reported-contact">
                  @if($report->reporter_contact)
                    <span><i class="fa-solid fa-phone" aria-hidden="true"></i> {{ $report->reporter_contact }}</span>
                  @endif
                  @if($report->reporter_email)
                    <span><i class="fa-solid fa-envelope" aria-hidden="true"></i> {{ $report->reporter_email }}</span>
                  @endif
                </span>
              @endif
            </span>
          @endif
        </div>

        <div class="review-section">
          <h3 class="review-field-label">Original description</h3>
          <p class="review-source-description">{{ $report->description }}</p>
        </div>

        @if($report->people_at_risk || $report->is_ongoing || $report->is_recurring || count($answers) > 0)
          <div class="review-section">
            <h3 class="review-field-label">Resident's answers</h3>
            <div class="review-answer-chips">
              @if($report->people_at_risk)
                <span class="review-answer-chip"><i class="fa-solid fa-person-circle-exclamation" aria-hidden="true"></i> Someone in danger</span>
              @endif
              @if($report->is_ongoing)
                <span class="review-answer-chip"><i class="fa-solid fa-clock" aria-hidden="true"></i> Happening now</span>
              @endif
              @if($report->is_recurring)
                <span class="review-answer-chip"><i class="fa-solid fa-repeat" aria-hidden="true"></i> Recurring</span>
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
                  <span class="review-answer-chip">
                    {{ $question['label'] ?? $questionKey }}: {{ $answerLabel }}
                  </span>
                @endif
              @endforeach
              @foreach($answers as $answerKey => $answerValue)
                @php
                  $hasQuestionLabel = collect($questionSchema)->contains(
                      fn ($question) => ($question['key'] ?? null) === $answerKey
                  );
                  $fallbackAnswerExists = ! $hasQuestionLabel && $answerValue !== null && $answerValue !== '';
                  $fallbackAnswerLabel = is_array($answerValue)
                      ? implode(', ', array_filter($answerValue, fn ($value) => $value !== null && $value !== ''))
                      : (in_array((string) $answerValue, ['0', '1'], true)
                          ? ((string) $answerValue === '1' ? 'Yes' : 'No')
                          : $answerValue);
                @endphp
                @if($fallbackAnswerExists)
                  <span class="review-answer-chip">{{ str($answerKey)->replace('_', ' ')->title() }}: {{ $fallbackAnswerLabel }}</span>
                @endif
              @endforeach
            </div>
          </div>
        @endif

        <div class="review-section">
          <h3 class="review-field-label">Photo and location</h3>
          <div class="review-media-grid">
            <div class="review-photo-panel">
              @if($report->photos->isNotEmpty())
                @foreach($report->photos as $photo)
                  <a href="{{ route('admin.reports.photos.show', [$report, $photo]) }}" target="_blank" rel="noopener">
                    <img src="{{ route('admin.reports.photos.show', [$report, $photo]) }}" alt="Resident's report photo">
                  </a>
                @endforeach
              @elseif($report->photo_path)
                <a href="{{ route('admin.reports.photo', $report) }}" target="_blank" rel="noopener">
                  <img src="{{ route('admin.reports.photo', $report) }}" alt="Resident's report photo">
                </a>
              @else
                <p class="review-empty-media">No photo attached.</p>
              @endif
            </div>
            <div>
              @if($report->lat !== null && $report->lng !== null)
                <div id="report-location-map" class="review-location-map map-frame"
                     data-lat="{{ $report->lat }}" data-lng="{{ $report->lng }}"
                     role="img" aria-label="Map showing the reported location"></div>
              @else
                <div class="review-location-map review-map-empty">No map pin shared.</div>
              @endif
            </div>
          </div>
          <div class="review-location-details">
            @if($residentPurok)
              <span><strong>Purok:</strong> {{ $residentPurok->name }}</span>
            @endif
            @if($residentLandmark)
              <span><strong>Landmark:</strong> {{ $residentLandmark }}</span>
            @endif
            @if($googleMapsUrl)
              <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener">Open in Google Maps</a>
            @endif
          </div>
        </div>
      </div>
    </details>
  </section>

  @if($hasPhotoMismatch)
    <section class="review-flag review-flag-amber" aria-label="Possible photo mismatch">
      <div>
        <strong><i class="fa-solid fa-image" aria-hidden="true"></i> Possible photo mismatch</strong>
        <p>Check whether the attached photo matches the resident's description.</p>
      </div>
      <div class="review-flag-actions">
        <form method="POST" action="{{ route('admin.reports.photo-mismatch', $report) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="confirm" value="1">
          <button type="submit" class="review-button review-button-outline">Confirm mismatch</button>
        </form>
        <form method="POST" action="{{ route('admin.reports.photo-mismatch', $report) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="confirm" value="0">
          <button type="submit" class="review-button review-button-outline">Not a mismatch</button>
        </form>
      </div>
    </section>
  @endif

  @if(! $emergencyActionOnly)
    <form id="review-form" method="POST" action="{{ route('admin.reports.review', $report) }}">
      @csrf
      @method('PATCH')
  @endif

  <section class="review-workflow-card" id="review-comparison">
    <details id="ai-review-card" class="review-collapsible" @if(! $isAfterRoute) open @endif>
      <summary class="review-card-heading">
        <span class="review-card-number" aria-hidden="true">2</span>
        <span class="review-card-heading-copy">
          <span class="review-card-title">Review AI suggestions</span>
          @unless($isAfterRoute)
            <span class="review-card-instruction">Compare each field, then accept, keep the resident's value, or edit.</span>
          @endunless
        </span>
        @if(! $isAfterRoute && ! $emergencyActionOnly)
          <span class="review-progress-pill" data-reviewed-count>{{ $reviewedInitialCount }} of {{ $requiredFieldsCount }} reviewed</span>
        @endif
        @if($isAfterRoute)
          <span class="review-collapsed-summary">{{ $report->category?->name ?? 'Category not assigned' }} · {{ $report->urgency ?? 'Urgency not assigned' }}</span>
          <a class="review-edit-link" href="#ai-review-card" data-expand-review>Edit</a>
        @endif
      </summary>
      <div class="review-card-content">
        <div class="review-ai-header">
          <div class="review-ai-status">
            @if($aiReady)
              <span class="review-badge review-badge-blue" data-ai-state-badge>
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> AI ready
              </span>
            @elseif($aiPending)
              <span class="review-badge review-badge-gray" data-ai-state-badge>
                <span class="review-spinner" aria-hidden="true"></span> AI analyzing
              </span>
            @else
              <span class="review-badge review-badge-gray" data-ai-state-badge>
                <i class="fa-solid fa-circle-minus" aria-hidden="true"></i> AI unavailable
              </span>
            @endif
            <span class="review-ai-confidence" data-ai-confidence-wrap @if(! $aiReady) hidden @endif>
              <span class="review-badge {{ $confidence !== null && $confidence >= $highConfidenceThreshold ? 'review-badge-green' : ($confidence !== null && $confidence >= $lowConfidenceThreshold ? 'review-badge-amber' : 'review-badge-red') }}"
                    data-ai-confidence>
                Confidence {{ $confidence !== null ? number_format($confidence * 100) : '—' }}%
              </span>
            </span>
            @if($lowConfidence)
              <span class="review-badge review-badge-red" data-low-confidence>Check carefully</span>
            @else
              <span class="review-badge review-badge-red" data-low-confidence hidden>Check carefully</span>
            @endif
          </div>
          <div class="review-header-actions">
            @php
              $hasInfoRequests = $report->infoRequests->isNotEmpty();
              $hasResidentReply = $report->infoRequests->whereNotNull('responded_at')->isNotEmpty();
              $unreadReplies = $infoThreadSummary['unread'] ?? 0;
            @endphp
            @if($hasInfoRequests || in_array($report->status, ['submitted', 'under_review', 'reopened', 'routed', 'in_progress', 'needs_info'], true))
              <button type="button" class="review-button review-button-outline"
                      data-chat-open data-report-id="{{ $report->id }}"
                      data-chat-tracking="{{ $report->tracking_code }}"
                      data-chat-category="{{ $report->category?->name ?? 'Category not assigned' }}"
                      data-chat-status="{{ \App\Models\Report::STATUSES[$report->status] ?? $report->status }}"
                      data-chat-summary="{{ $report->description }}{{ $report->purok ? ' — '.$report->purok->name : '' }}">
                <i class="fa-solid fa-comment-dots" aria-hidden="true"></i>
                <span>Ask Reporter for More Info</span>
                @if($unreadReplies > 0 || ($hasResidentReply && !session('info_thread_seen.'.$report->id)))
                  <span class="chat-notif-circle" data-chat-notif-circle title="New reply from resident">
                    {{ $unreadReplies > 0 ? $unreadReplies : '' }}
                  </span>
                @else
                  <span class="chat-notif-circle" data-chat-notif-circle style="display:none;"></span>
                @endif
              </button>
            @endif
            @if(in_array($report->status, ['submitted', 'under_review', 'reopened'], true))
              <button type="button" class="review-button review-button-outline" data-open-dialog="duplicate-modal">
                <i class="fa-solid fa-copy" aria-hidden="true"></i> Mark duplicate
              </button>
              <button type="button" class="review-button review-button-outline" data-open-dialog="no-action-modal">
                <i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> No action
              </button>
            @endif
          </div>
        </div>

        @if($report->infoRequests->isNotEmpty() && isset($infoThreadSummary))
          <div class="review-info-summary" data-chat-summary-strip data-report-id="{{ $report->id }}">
            <p class="review-info-summary-text">
              <i class="fa-solid fa-comments" aria-hidden="true"></i>
              <span data-chat-message-count>{{ $infoThreadSummary['count'] }} message{{ $infoThreadSummary['count'] === 1 ? '' : 's' }} sent</span>
              <span class="review-info-summary-dot" aria-hidden="true">·</span>
              <span data-chat-awaiting @if(! $infoThreadSummary['awaiting_reply']) hidden @endif>Awaiting resident reply</span>
            </p>
          </div>
        @endif
        <p class="review-ai-description" data-ai-state-message aria-live="polite">
          @if($aiReady)
            AI suggestions are ready. Verify each field before confirming.
            @if($lowConfidence) Check carefully because confidence is low. @endif
          @elseif($aiPending)
            AI is analyzing this report. Your final values will not be changed when suggestions arrive.
          @else
            AI suggestions are not available. Enter or confirm Category and Urgency manually.
          @endif
        </p>
        @if($aiUnavailable && $report->ai_status === 'failed' && ! $emergencyActionOnly)
          <form method="POST" action="{{ route('admin.reports.retry-ai', $report) }}" class="review-retry-form">
            @csrf
            <button type="submit" class="review-text-link">Retry AI analysis</button>
          </form>
        @endif

        <div class="review-table-wrap">
          <table class="review-comparison-table">
            <thead>
              <tr>
                <th scope="col">Field</th>
                <th scope="col">Resident</th>
                <th scope="col">AI suggestion</th>
                <th scope="col">Final</th>
              </tr>
            </thead>
            <tbody>
              <tr data-review-row data-field="category"
                  data-resident-value="{{ $residentCategoryId ?? '' }}"
                  data-resident-label="{{ $residentCategory?->name ?? '' }}"
                  data-ai-value="{{ $aiCategoryId ?? '' }}"
                  data-ai-label="{{ $aiCategoryLabel ?? '' }}"
                  data-ai-present="{{ $aiCategoryPresent ? 'true' : 'false' }}"
                  data-review-required="{{ $categoryReviewRequired ? 'true' : 'false' }}"
                  data-reviewed="{{ $alreadyReviewed || ! $categoryReviewRequired ? 'true' : 'false' }}">
                <th scope="row" data-column-label="Field">Category</th>
                <td data-column-label="Resident">
                  @if($residentCategory)
                    <span data-resident-display>{{ $residentCategory->name }}</span>
                  @else
                    <span class="review-not-provided">Not provided</span>
                  @endif
                </td>
                <td data-column-label="AI suggestion">
                  <span data-ai-value-display @if($aiReady && ! $aiCategoryLabel) class="review-not-provided" @endif>
                    @if($aiReady){{ $aiCategoryLabel ?? 'Not provided' }}
                    @elseif($aiPending)Waiting for suggestion
                    @else Not available
                    @endif
                  </span>
                  <span class="review-badge review-badge-outline-green" data-comparison-badge hidden></span>
                </td>
                <td data-column-label="Final">
                  <label class="sr-only" for="final-category">Final category</label>
                  <select id="final-category" name="category_id" data-final-control data-required-field required @disabled($emergencyActionOnly)>
                    <option value="">Select category</option>
                    @foreach($categories as $category)
                      <option value="{{ $category->id }}" @selected((string) $finalCategoryId === (string) $category->id)>{{ $category->name }}</option>
                    @endforeach
                  </select>
                  <div class="review-final-meta">
                    <span class="review-badge {{ $categoryFinalState === 'Accepted' ? 'review-badge-green' : ($categoryFinalState === 'Edited' ? 'review-badge-amber' : ($categoryFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                          data-final-state>{{ $categoryFinalState }}</span>
                    <div class="review-row-actions">
                      <button type="button" class="review-text-link" data-use-ai @disabled($emergencyActionOnly) @if(! $aiCategoryId || ! $aiCategoryPresent || $aiCategoryId === $residentCategoryId) hidden @endif>Use AI</button>
                      <button type="button" class="review-text-link" data-use-resident @disabled($emergencyActionOnly) @if($residentCategoryId === null || $residentCategoryId === $aiCategoryId) hidden @endif>Use resident's</button>
                      <button type="button" class="review-text-link" data-accept-row @disabled($emergencyActionOnly) @if(! $aiCategoryId || ! $aiCategoryPresent) hidden @endif>Accept</button>
                      <button type="button" class="review-text-link" data-undo-row hidden>Undo</button>
                    </div>
                  </div>
                  @if($residentCategory && $aiCategoryPresent && (string) $residentCategoryId !== (string) $aiCategoryId)
                    <small class="review-resident-choice">Resident chose: {{ $residentCategory->name }}</small>
                  @endif
                </td>
              </tr>

              <tr data-review-row data-field="urgency"
                  data-resident-value=""
                  data-resident-label=""
                  data-ai-value="{{ $aiUrgency ?? '' }}"
                  data-ai-label="{{ $aiUrgency ?? '' }}"
                  data-ai-present="{{ $aiUrgencyPresent ? 'true' : 'false' }}"
                  data-review-required="{{ $urgencyReviewRequired ? 'true' : 'false' }}"
                  data-reviewed="{{ $alreadyReviewed || ! $urgencyReviewRequired ? 'true' : 'false' }}">
                <th scope="row" data-column-label="Field">Urgency</th>
                <td data-column-label="Resident"><span class="review-not-provided">Not provided</span></td>
                <td data-column-label="AI suggestion">
                  <span data-ai-value-display @if($aiReady && ! $aiUrgencyPresent) class="review-not-provided" @endif>
                    @if($aiReady){{ $aiUrgency ?? 'Not provided' }}
                    @elseif($aiPending)Waiting for suggestion
                    @else Not available
                    @endif
                  </span>
                  <span class="review-badge review-badge-outline-blue" data-comparison-badge hidden></span>
                </td>
                <td data-column-label="Final">
                  <label class="sr-only" for="final-urgency">Final urgency</label>
                  <select id="final-urgency" name="urgency" data-final-control data-required-field required @disabled($emergencyActionOnly)>
                    <option value="">Select urgency</option>
                    @foreach(['Low', 'Medium', 'High', 'Critical'] as $urgency)
                      <option value="{{ $urgency }}" @selected($finalUrgency === $urgency)>{{ $urgency }}</option>
                    @endforeach
                  </select>
                  <div class="review-final-meta">
                    <span class="review-badge {{ $urgencyFinalState === 'Accepted' ? 'review-badge-green' : ($urgencyFinalState === 'Edited' ? 'review-badge-amber' : ($urgencyFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                          data-final-state>{{ $urgencyFinalState }}</span>
                    <div class="review-row-actions">
                      <button type="button" class="review-text-link" data-use-ai @disabled($emergencyActionOnly) @if(! $aiUrgencyPresent) hidden @endif>Use AI</button>
                      <button type="button" class="review-text-link" data-use-resident @disabled($emergencyActionOnly) hidden>Use resident's</button>
                      <button type="button" class="review-text-link" data-accept-row @disabled($emergencyActionOnly) @if(! $aiUrgencyPresent) hidden @endif>Accept</button>
                      <button type="button" class="review-text-link" data-undo-row hidden>Undo</button>
                    </div>
                  </div>
                </td>
              </tr>

              <tr data-review-row data-field="description"
                  data-resident-value="{{ $residentDescription }}"
                  data-resident-label="{{ $residentDescription }}"
                  data-ai-value="{{ $aiDescription ?? '' }}"
                  data-ai-label="{{ $aiDescription ?? '' }}"
                  data-ai-present="{{ $aiDescriptionPresent ? 'true' : 'false' }}"
                  data-review-required="{{ $descriptionReviewRequired ? 'true' : 'false' }}"
                  data-reviewed="{{ $alreadyReviewed || ! $descriptionReviewRequired ? 'true' : 'false' }}">
                <th scope="row" data-column-label="Field">Description</th>
                <td data-column-label="Resident">
                  @if(trim((string) $residentDescription) !== '')
                    <div class="review-clamp-wrap">
                      <div class="review-clamp" data-clamp><span data-resident-display>{{ $residentDescription }}</span></div>
                    </div>
                  @else
                    <span class="review-not-provided">Not provided</span>
                  @endif
                </td>
                <td data-column-label="AI suggestion">
                  @if($aiReady && $aiDescriptionPresent)
                    <div class="review-clamp-wrap">
                      <div class="review-description-diff review-clamp" data-description-diff
                           data-original="{{ $residentDescription }}" data-ai="{{ $aiDescription }}"></div>
                    </div>
                    <span data-ai-value-display class="sr-only">{{ $aiDescription }}</span>
                  @else
                    <span data-ai-value-display>
                      @if($aiPending)Waiting for suggestion
                      @elseif($aiUnavailable)Not available
                      @else
                        <span class="review-not-provided">Not provided</span>
                      @endif
                    </span>
                    <div class="review-description-diff" data-description-diff data-original="{{ $residentDescription }}" data-ai=""></div>
                  @endif
                  <span class="review-badge review-badge-outline-green" data-comparison-badge hidden></span>
                </td>
                <td data-column-label="Final">
                  <label class="sr-only" for="final-description">Final description</label>
                  <textarea id="final-description" name="staff_description" rows="4" maxlength="2000"
                            data-final-control data-required-field required @disabled($emergencyActionOnly)>{{ $finalDescription }}</textarea>
                  <div class="review-final-meta">
                    <span class="review-badge {{ $descriptionFinalState === 'Accepted' ? 'review-badge-green' : ($descriptionFinalState === 'Edited' ? 'review-badge-amber' : ($descriptionFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                          data-final-state>{{ $descriptionFinalState }}</span>
                    <div class="review-row-actions">
                      <button type="button" class="review-text-link" data-use-ai @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>Use AI</button>
                      <button type="button" class="review-text-link" data-use-resident @disabled($emergencyActionOnly) @if(! $residentDescription || $residentDescription === $aiDescription) hidden @endif>Use resident's</button>
                      <button type="button" class="review-text-link" data-accept-row @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>Accept</button>
                      <button type="button" class="review-text-link" data-use-original @disabled($emergencyActionOnly)>Use original</button>
                      <button type="button" class="review-text-link" data-append-ai @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>Append AI summary</button>
                      <button type="button" class="review-text-link" data-undo-row hidden>Undo</button>
                    </div>
                  </div>
                </td>
              </tr>

              <tr data-review-row data-field="purok"
                  data-resident-value="{{ $residentPurok?->id ?? '' }}"
                  data-resident-label="{{ $residentPurok?->name ?? '' }}"
                  data-ai-value="" data-ai-label="" data-ai-present="false"
                  data-review-required="false" data-reviewed="true">
                <th scope="row" data-column-label="Field">Purok</th>
                <td data-column-label="Resident">
                  @if($residentPurok)
                    <span data-resident-display>{{ $residentPurok->name }}</span>
                  @else
                    <span class="review-not-provided">Not provided</span>
                  @endif
                </td>
                <td data-column-label="AI suggestion">
                  <span data-ai-value-display @if(! $aiPending && ! $aiUnavailable) class="review-not-provided" @endif>
                    @if($aiPending)Waiting for suggestion
                    @elseif($aiUnavailable)Not available
                    @else Not provided
                    @endif
                  </span>
                  <span class="review-badge review-badge-outline-blue" data-comparison-badge hidden></span>
                </td>
                <td data-column-label="Final">
                  <label class="sr-only" for="final-purok">Final purok</label>
                  <select id="final-purok" name="purok_id" data-final-control @disabled($emergencyActionOnly)>
                    <option value="">Select purok</option>
                    @foreach($puroks as $purok)
                      <option value="{{ $purok->id }}" @selected((string) $finalPurokId === (string) $purok->id)>{{ $purok->name }}</option>
                    @endforeach
                  </select>
                  <span class="review-badge review-badge-gray" data-final-state>Resident</span>
                </td>
              </tr>

              <tr data-review-row data-field="landmark"
                  data-resident-value="{{ $residentLandmark ?? '' }}"
                  data-resident-label="{{ $residentLandmark ?? '' }}"
                  data-ai-value="" data-ai-label="" data-ai-present="false"
                  data-review-required="false" data-reviewed="true">
                <th scope="row" data-column-label="Field">Landmark</th>
                <td data-column-label="Resident">
                  @if(trim((string) $residentLandmark) !== '')
                    <div class="review-clamp-wrap">
                      <div class="review-clamp" data-clamp><span data-resident-display>{{ $residentLandmark }}</span></div>
                    </div>
                  @else
                    <span class="review-not-provided">Not provided</span>
                  @endif
                </td>
                <td data-column-label="AI suggestion">
                  <span data-ai-value-display @if(! $aiPending && ! $aiUnavailable) class="review-not-provided" @endif>
                    @if($aiPending)Waiting for suggestion
                    @elseif($aiUnavailable)Not available
                    @else Not provided
                    @endif
                  </span>
                  <span class="review-badge review-badge-outline-blue" data-comparison-badge hidden></span>
                </td>
                <td data-column-label="Final">
                  <label class="sr-only" for="final-landmark">Final landmark</label>
                  <input id="final-landmark" type="text" name="landmark" maxlength="255"
                         value="{{ $finalLandmark }}" data-final-control @disabled($emergencyActionOnly)>
                  <span class="review-badge review-badge-gray" data-final-state>Resident</span>
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        @if($aiReady && ($aiResult->photo_description || $aiResult->key_facts))
          <details class="review-ai-details">
            <summary>AI details</summary>
            @if($aiResult->photo_description)
              <p><strong>Photo description:</strong> {{ $aiResult->photo_description }}</p>
            @endif
            @if($aiResult->key_facts)
              <ul>
                @foreach($aiResult->key_facts as $fact)
                  <li>{{ $fact }}</li>
                @endforeach
              </ul>
            @endif
          </details>
        @endif

        @if(! $emergencyActionOnly)
          <div class="review-actions-row">
            @if($aiReady)
              <button type="button" class="review-button review-button-blue" data-accept-all>
                <i class="fa-solid fa-check-double" aria-hidden="true"></i> Accept all AI suggestions
              </button>
            @else
              <button type="button" class="review-button review-button-blue" data-accept-all hidden disabled>
                Accept all AI suggestions
              </button>
            @endif

            <!-- <button type="submit" name="intent" value="save" class="review-button review-button-blue">
              Confirm review
            </button> -->
          </div>
        @endif
      </div>
    </details>
  </section>

  @if(! $isAfterRoute && ! $emergencyActionOnly)
    <section class="review-workflow-card">
      <div class="review-card-heading review-card-heading-static">
        <span class="review-card-number" aria-hidden="true">3</span>
        <span class="review-card-heading-copy">
          <span class="review-card-title">Route and assign</span>
          <span class="review-card-instruction">Confirm who should handle it.</span>
        </span>
      </div>
      <div class="review-card-content">
        @php
          $ruleWithin = (bool) ($jurisdiction['within_authority'] ?? true);
          $initialMode = $ruleWithin ? 'within' : 'beyond';
          $defaultAgency = $externalOffices->firstWhere('id', $jurisdiction['office']?->id) ?? $externalOffices->first();
          $defaultLetter = old('referral_letter', $letterTemplates[$defaultAgency?->id]['letter'] ?? '');
          $defaultResidentMessage = old('resident_message', $letterTemplates[$defaultAgency?->id]['resident_message'] ?? '');
          $defaultAgencyId = $defaultAgency?->id;
        @endphp

        <div class="review-mode-banner {{ $ruleWithin ? 'review-mode-banner-within' : 'review-mode-banner-beyond' }}"
             data-mode-banner data-rule-mode="{{ $initialMode }}">
          <div>
            <span class="review-mode-badge" data-mode-badge>{{ $ruleWithin ? 'Within barangay authority' : 'Beyond barangay authority' }}</span>
            <p class="review-mode-note" data-mode-note>{{ $jurisdiction['jurisdiction_note'] ?? 'Assigned to the category default office.' }}</p>
          </div>
          <button type="button" class="review-text-link" data-mode-toggle data-other-mode="{{ $ruleWithin ? 'beyond' : 'within' }}">
            {{ $ruleWithin ? "This isn't barangay authority" : 'Handle within the barangay instead' }}
          </button>
        </div>
        <input type="hidden" name="authority_mode" value="{{ $initialMode }}" data-authority-mode-input>
        <script type="application/json" data-routing-map>@json($routingDecisions)</script>
        <script type="application/json" data-letter-templates>@json($letterTemplates)</script>

        <p class="review-priority-line">
          Urgency:
          <span class="review-badge review-badge-blue" title="Priority score: {{ $report->priority_score ?? 0 }}">{{ $priorityLabel }}</span>
          @if($report->due_at)
            <span>Due {{ $report->due_at->format('M d, Y h:i A') }}</span>
          @endif
        </p>

        {{-- Mode A: within barangay authority --}}
        <div class="review-mode-block" data-mode-block="within">
          <div class="review-form-grid">
            <div class="review-form-field">
              <label for="office_id">Office</label>
              <select id="office_id" name="office_id" data-office-select>
                <option value="">Select an office</option>
                @foreach($offices as $office)
                  <option value="{{ $office->id }}"
                          data-default-assignee="{{ $office->default_assignee_id ?? '' }}"
                          @selected((string) $officeDefault === (string) $office->id)>{{ $office->name }}</option>
                @endforeach
              </select>
              <p class="review-field-hint">The department that handles this report</p>
              <p class="review-field-hint" data-routing-reason>
                @if($routingDecision)
                  Rule: {{ $categories->firstWhere('id', $selectedCategoryId)?->name }} → {{ $routingDecision['office_name'] ?? 'No office' }}.
                @else
                  Select a category to see the routing recommendation.
                @endif
              </p>
            </div>
            <div class="review-form-field">
              <label for="assigned_to">Assigned to</label>
              <select id="assigned_to" name="assigned_to" data-assigned-select>
                <option value="">Unassigned</option>
                @foreach($staffUsers as $staffUser)
                  <option value="{{ $staffUser->id }}" @selected((string) $assignedDefault === (string) $staffUser->id)>{{ $staffUser->name }}</option>
                @endforeach
              </select>
              <p class="review-field-hint">The staff member responsible for follow-through</p>
            </div>
            <div class="review-form-field review-form-field-wide">
              <label for="review_note">Message to the resident <span class="review-optional">(optional)</span></label>
              <p class="review-field-hint">
                This message is sent to the reporter when you click <strong>Confirm and route</strong> below.
                Use it to tell them what happens next — for example, which office will respond and when.
                Leave it blank if there is nothing new to tell them.
              </p>
              <textarea id="review_note" name="review_note" rows="3" maxlength="1000" placeholder="Example: The CENRO drainage team will inspect your report within 3 working days.">{{ $defaultMessage }}</textarea>
            </div>
          </div>

          <div class="review-actions-row">
            <span class="review-gate-hint" data-gate-reason aria-live="polite"></span>
            @if($isRouteable)
              <button type="submit" name="intent" value="route" class="review-button review-button-primary" data-route-submit>
                Confirm and route
              </button>
            @endif
          </div>
        </div>

        {{-- Mode B: beyond barangay authority (refer to an external agency) --}}
        <div class="review-mode-block" data-mode-block="beyond" @if($initialMode === 'within') hidden @endif>
          <div class="review-form-grid">
            <div class="review-form-field">
              <label for="agency_id">Agency</label>
              <select id="agency_id" name="office_id" data-agency-select @disabled($initialMode === 'within')>
                <option value="">Select an agency</option>
                @foreach($externalOffices as $externalOffice)
                  <option value="{{ $externalOffice->id }}" @selected((string) $defaultAgencyId === (string) $externalOffice->id)>{{ $externalOffice->name }}</option>
                @endforeach
              </select>
              <p class="review-field-hint" data-agency-info>
                @if($defaultAgency)
                  Contact: {{ $defaultAgency->contact_person ?? 'Not provided' }}
                  @if($defaultAgency->contact_email)
                    · {{ $defaultAgency->contact_email }}
                  @endif
                  @if($defaultAgency->contact_phone)
                    · {{ $defaultAgency->contact_phone }}
                  @endif
                  @if($defaultAgency->address)
                    · {{ $defaultAgency->address }}
                  @endif
                @else
                  No external agencies are configured yet. An administrator can add them under offices.
                @endif
              </p>
            </div>
            <div class="review-form-field">
              <label for="follow_up_owner_id">Follow-up owner</label>
              <select id="follow_up_owner_id" name="follow_up_owner_id" @disabled($initialMode === 'within')>
                <option value="">Unassigned</option>
                @foreach($staffUsers as $staffUser)
                  <option value="{{ $staffUser->id }}" @selected((string) old('follow_up_owner_id', $currentUserId) === (string) $staffUser->id)>{{ $staffUser->name }}</option>
                @endforeach
              </select>
              <p class="review-field-hint">The staff member responsible for following up with the agency.</p>
            </div>
          </div>
          <div class="review-form-field review-form-field-wide">
            <label for="referral_letter">Referral letter</label>
            <p class="review-field-hint">Editable letter sent to the agency. It includes the tracking code, location and report details.</p>
            <textarea id="referral_letter" name="referral_letter" rows="16" maxlength="20000"
                      data-referral-letter @disabled($initialMode === 'within')>{{ $defaultLetter }}</textarea>
          </div>
          <div class="review-form-field review-form-field-wide">
            <label for="resident_message">Message to the resident</label>
            <p class="review-field-hint">Shown to the resident on the tracking page when the referral is prepared.</p>
            <textarea id="resident_message" name="resident_message" rows="3" maxlength="1000"
                      data-resident-message @disabled($initialMode === 'within')>{{ $defaultResidentMessage }}</textarea>
          </div>

          <div class="review-actions-row">
            <button type="submit" name="intent" value="referral" class="review-button review-button-primary" data-referral-submit>
              Prepare referral
            </button>
          </div>
        </div>

        @if($isOverdue && ! $report->escalated_at)
          <form method="POST" action="{{ route('admin.reports.escalate', $report) }}" class="review-escalate-form">
            @csrf
            <button type="submit" class="review-button review-button-outline">Escalate overdue report</button>
          </form>
        @endif
      </div>
    </section>
  @elseif($emergencyActionOnly)
    <section class="review-workflow-card">
      <div class="review-card-heading review-card-heading-static">
        <span class="review-card-number" aria-hidden="true">3</span>
        <span class="review-card-heading-copy">
          <span class="review-card-title">Route and assign</span>
          <span class="review-card-instruction">Confirm who should handle it.</span>
        </span>
      </div>
      <div class="review-card-content">
        <p class="review-field-hint">Resolve the emergency concern above before reviewing or routing this report.</p>
      </div>
    </section>
  @endif

  @if(! $emergencyActionOnly)
    </form>

    @if(in_array($report->status, ['submitted', 'under_review', 'reopened'], true))
      <dialog id="duplicate-modal" class="review-modal">
        <form method="POST" action="{{ route('admin.reports.duplicate', $report) }}">
          @csrf
          @method('PATCH')
          <header class="review-modal-header">
            <strong><i class="fa-solid fa-copy" aria-hidden="true"></i> Mark duplicate</strong>
            <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
          </header>
          <div class="review-modal-body">
            <p class="review-field-hint">
              Merge this report into the one it duplicates. The report is hidden from the queue and its reporter
              is pointed to the kept report.
            </p>
            <label for="duplicate-candidate">Duplicate of</label>
            <select id="duplicate-candidate" name="candidate_id" required>
              <option value="">Select a report</option>
              @foreach($duplicateCandidates as $candidate)
                <option value="{{ $candidate->id }}"
                        @selected($pendingDuplicate && (string) $pendingDuplicate->candidate_id === (string) $candidate->id)>
                  {{ $candidate->tracking_code }} — {{ \Illuminate\Support\Str::limit($candidate->description, 70) }}
                </option>
              @endforeach
            </select>
            @if($pendingDuplicate && $pendingDuplicate->candidate)
              <p class="review-field-hint">Possible match: {{ $pendingDuplicate->candidate->tracking_code }} ({{ number_format((float) $pendingDuplicate->score, 2) }} match score).</p>
            @endif
            <label for="duplicate-note">Merge note</label>
            <textarea id="duplicate-note" name="note" rows="2" required maxlength="500" placeholder="Why these are the same report."></textarea>
          </div>
          <footer class="review-modal-footer">
            <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
            <button type="submit" class="review-button review-button-primary" @disabled($duplicateCandidates->isEmpty())>Mark duplicate</button>
          </footer>
        </form>
      </dialog>

      <dialog id="no-action-modal" class="review-modal">
        <form method="POST" action="{{ route('admin.reports.status', $report) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="status" value="no_action">
          <header class="review-modal-header">
            <strong><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Take no action</strong>
            <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
          </header>
          <div class="review-modal-body">
            <p class="review-field-hint">
              Use when the barangay will take no further action (outside authority, spam, cannot resolve, or resident unreachable).
              If another government office should handle it, use <strong>Prepare referral</strong> in Route and assign.
            </p>
            <div style="margin-bottom: 0.75rem;">
              <label for="no-action-reason" style="font-weight: 600; font-size: 0.85rem; display: block; margin-bottom: 0.35rem;">Reason code</label>
              <select id="no-action-reason" name="no_action_reason" required style="width: 100%; padding: 0.5rem; border: 1px solid var(--line); border-radius: 6px;">
                <option value="">Select a reason code...</option>
                @foreach(\App\Models\Report::NO_ACTION_REASONS as $code => $label)
                  <option value="{{ $code }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
            <label for="no-action-note" style="font-weight: 600; font-size: 0.85rem; display: block; margin-bottom: 0.35rem;">Explanation shown to resident</label>
            <textarea id="no-action-note" name="note" rows="3" required maxlength="500" placeholder="Provide a clear, respectful explanation for why no further action will be taken."></textarea>
          </div>
          <footer class="review-modal-footer">
            <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
            <button type="submit" class="review-button review-button-primary">Confirm No Action</button>
          </footer>
        </form>
      </dialog>
    @endif
  @endif

  @if($isAfterRoute && ! $emergencyActionOnly)
    <section class="review-workflow-card">
      <div class="review-card-heading review-card-heading-static">
        <span class="review-card-number" aria-hidden="true">3</span>
        <span class="review-card-heading-copy">
          <span class="review-card-title">Next step</span>
          <span class="review-card-instruction">Continue the report through its current status.</span>
        </span>
      </div>
      <div class="review-card-content">
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
          <p class="review-next-summary">{{ $report->category?->name ?? 'Category not assigned' }} · {{ $report->urgency ?? 'Urgency not assigned' }} · {{ $report->office?->name ?? 'Office not assigned' }} · {{ $report->assignee?->name ?? 'Unassigned' }}</p>
          @if($report->status === 'needs_info')
            <p class="review-next-summary">Waiting for the resident's reply. The conversation is in the <strong>chat modal</strong> you can open from Review AI suggestions above, or you can post a progress update below.</p>
          @endif

          <form method="POST" action="{{ route('admin.reports.updates.store', $report) }}" enctype="multipart/form-data" class="review-update-form" data-prevent-double-submit>
            @csrf
            <div class="review-form-grid">
              <div class="review-form-field">
                <label for="update-type">Update type</label>
                <select id="update-type" name="update_type" required data-update-type-select>
                  @foreach($updateTypes as $typeKey => $typeMeta)
                    <option value="{{ $typeKey }}"
                            data-template="{{ $typeMeta['template'] }}"
                            data-message-required="{{ $typeMeta['message_required'] ? '1' : '0' }}"
                            @selected($selectedUpdateType === $typeKey)>
                      {{ $typeMeta['label'] }} ({{ \App\Models\Report::STATUSES[$typeMeta['status']] ?? $typeMeta['status'] }})
                    </option>
                  @endforeach
                </select>
                @error('update_type')<span class="review-field-error">{{ $message }}</span>@enderror
              </div>

              <div class="review-form-field">
                <label for="expected-completion-date">Expected completion date <span class="review-optional">(optional)</span></label>
                <input id="expected-completion-date" type="date" name="expected_completion_date" min="{{ now()->toDateString() }}" value="{{ old('expected_completion_date') }}">
                @error('expected_completion_date')<span class="review-field-error">{{ $message }}</span>@enderror
              </div>

              <div class="review-form-field review-form-field-wide">
                <label for="update-message">
                  Message to resident
                  <span class="review-optional" data-update-message-optional>{{ $selectedUpdateMeta['message_required'] ? '(required)' : '(optional)' }}</span>
                </label>
                <textarea id="update-message" name="message" rows="3" maxlength="2000" data-update-message @if($selectedUpdateMeta['message_required']) required @endif>{{ $defaultUpdateMessage }}</textarea>
                @error('message')<span class="review-field-error">{{ $message }}</span>@enderror
              </div>

              <div class="review-form-field review-form-field-wide">
                <label for="update-photos">Progress photos <span class="review-optional">(optional)</span></label>
                <p class="review-field-hint">
                  Up to {{ $proofConfig['max_files'] }} images, {{ round($proofConfig['max_size_kb'] / 1024) }} MB each. JPG, PNG or WebP.
                </p>
                <input id="update-photos" type="file" name="photos[]" multiple accept="{{ $proofConfig['accept'] }}"
                       data-proof-input
                       data-max-files="{{ $proofConfig['max_files'] }}"
                       data-max-size-kb="{{ $proofConfig['max_size_kb'] }}">
                <ul class="review-proof-errors" data-proof-errors hidden></ul>
                <div class="review-proof-preview" data-proof-preview></div>
                @error('photos')<span class="review-field-error">{{ $message }}</span>@enderror
                @error('photos.*')<span class="review-field-error">{{ $message }}</span>@enderror
              </div>

              <div class="review-form-field review-form-field-wide">
                <input type="hidden" name="visible_to_resident" value="0">
                <label class="review-checkbox-label">
                  <input type="checkbox" name="visible_to_resident" value="1" @checked(old('visible_to_resident', '1') == '1')>
                  <span>Visible to resident</span>
                </label>
                <p class="review-field-hint">Uncheck to save as an internal staff note hidden from the resident page.</p>
              </div>
            </div>

            <div class="review-actions-row">
              <button type="submit" class="review-button review-button-primary" data-submit-once>Post update</button>
              <button type="button" class="review-button review-button-outline" data-toggle-resolution aria-expanded="{{ $openResolutionForm ? 'true' : 'false' }}" aria-controls="resolution-form-panel">
                Mark resolved
              </button>
            </div>
          </form>

          <div id="resolution-form-panel" class="review-resolution-panel" data-resolution-panel @if(! $openResolutionForm) hidden @endif>
            <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" enctype="multipart/form-data" class="review-resolution-form" data-prevent-double-submit>
              @csrf
              <div class="review-form-field">
                <label for="resolution-note">Resolution note</label>
                <textarea id="resolution-note" name="note" rows="4" required maxlength="2000">{{ old('note') }}</textarea>
                @error('note')<span class="review-field-error">{{ $message }}</span>@enderror
              </div>
              <div class="review-form-field">
                <label for="proof-photos">Proof photos <span class="review-optional">(optional)</span></label>
                <p class="review-field-hint">
                  Up to {{ $proofConfig['max_files'] }} images, {{ round($proofConfig['max_size_kb'] / 1024) }} MB each. JPG, PNG or WebP.
                </p>
                <input id="proof-photos" type="file" name="proof_photos[]" multiple accept="{{ $proofConfig['accept'] }}"
                       data-proof-input
                       data-max-files="{{ $proofConfig['max_files'] }}"
                       data-max-size-kb="{{ $proofConfig['max_size_kb'] }}">
                <ul class="review-proof-errors" data-proof-errors hidden></ul>
                <div class="review-proof-preview" data-proof-preview></div>
              </div>
              <button type="submit" class="review-button review-button-primary" data-submit-once>Mark resolved</button>
            </form>

            @if($report->proofPhotos->isNotEmpty())
              <div class="review-saved-proofs">
                <h3 class="review-field-label">Saved proof photos</h3>
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
          <p class="review-next-summary">Waiting for the resident to confirm the resolution.</p>
          @if($report->due_at && $report->due_at->copy()->addDays($autoCloseDays)->isPast())
            <form method="POST" action="{{ route('admin.reports.close', $report) }}" data-prevent-double-submit>
              @csrf
              <button type="submit" class="review-button review-button-outline" data-submit-once>Close case</button>
            </form>
          @endif
        @elseif(in_array($report->status, ['resolved', 'no_action'], true))
          <p class="review-next-summary">
            This report has been {{ $report->status === 'no_action' ? 'marked as no action' : 'resolved' }}. If further work is needed, you can reopen it back to In Progress.
          </p>
          <form method="POST" action="{{ route('admin.reports.reopen', $report) }}" class="review-reopen-form" data-prevent-double-submit>
            @csrf
            <div class="review-form-field">
              <label for="reopen-reason">Reason for reopening</label>
              <textarea id="reopen-reason" name="reason" rows="2" required minlength="5" maxlength="1000" placeholder="Explain why this report is being reopened...">{{ old('reason') }}</textarea>
              @error('reason')<span class="review-field-error">{{ $message }}</span>@enderror
            </div>
            <button type="submit" class="review-button review-button-outline" data-submit-once>Reopen report</button>
          </form>
        @elseif($report->status === 'referred')
          @php
            $referral = $report->activeReferral;
            $referralOffice = $referral?->office;
            $referralBadgeClass = match ($referral?->status) {
              'prepared' => 'review-badge-amber',
              'sent' => 'review-badge-blue',
              'acknowledged' => 'review-badge-blue',
              'action_taken' => 'review-badge-green',
              'closed' => 'review-badge-gray',
              default => 'review-badge-gray',
            };
          @endphp
          @if($referral)
            <div class="referral-panel" data-referral-panel data-letter="{{ $referral->referral_text }}">
              <div class="referral-panel-header">
                <span class="review-badge review-badge-blue">{{ $referral->reference_no }}</span>
                <strong>{{ $referralOffice?->name ?? 'Unknown agency' }}</strong>
                <span class="review-badge {{ $referralBadgeClass }}">{{ \App\Models\Referral::STATUSES[$referral->status] ?? $referral->status }}</span>
                @if($referral->isOverdue())
                  <span class="review-badge review-badge-red">Follow-up overdue</span>
                @endif
              </div>
              <p class="review-next-summary">
                @if($referral->sent_at)
                  Sent {{ $referral->sent_at->format('M d, Y') }}@if($referral->sent_via) ({{ \App\Models\Referral::SENT_VIA[$referral->sent_via] ?? $referral->sent_via }})@endif.
                @endif
                Follow up @if($referral->follow_up_at){{ $referral->follow_up_at->format('M d, Y') }}@else not scheduled @endif.
              </p>

              <div class="referral-panel-actions">
                <a class="review-button review-button-outline" href="{{ route('admin.referrals.letter', $referral) }}" target="_blank" rel="noopener">
                  <i class="fa-solid fa-print" aria-hidden="true"></i> Print / Save as PDF
                </a>
                <button type="button" class="review-button review-button-outline" data-copy-letter>
                  <i class="fa-solid fa-copy" aria-hidden="true"></i> Copy letter text
                </button>
                @if($referralOffice?->contact_email)
                  <a class="review-button review-button-outline"
                     href="mailto:{{ $referralOffice->contact_email }}?subject={{ rawurlencode('Referral '.$referral->reference_no.' — '.$report->tracking_code) }}&body={{ rawurlencode($referral->referral_text) }}">
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i> Open email
                  </a>
                @endif
              </div>

              @if($referral->status === 'prepared')
                <form method="POST" action="{{ route('admin.referrals.mark-sent', $referral) }}" class="referral-panel-form">
                  @csrf
                  <div class="referral-panel-form-grid">
                    <div class="review-form-field">
                      <label for="referral-sent-at">Sent date</label>
                      <input id="referral-sent-at" type="date" name="sent_at" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="review-form-field">
                      <label for="referral-sent-via">Sent via</label>
                      <select id="referral-sent-via" name="sent_via">
                        <option value="">Not recorded</option>
                        @foreach(\App\Models\Referral::SENT_VIA as $sentViaKey => $sentViaLabel)
                          <option value="{{ $sentViaKey }}">{{ $sentViaLabel }}</option>
                        @endforeach
                      </select>
                    </div>
                  </div>
                  <button type="submit" class="review-button review-button-primary">Mark as sent</button>
                  <p class="review-field-hint">Marks the referral as sent and schedules a follow-up in {{ \App\Models\Setting::value('referral_follow_up_days', $referralConfig['follow_up_days']) }} days.</p>
                </form>
              @endif

              @if($referral->isOpen())
                <form method="POST" action="{{ route('admin.referrals.updates.store', $referral) }}" enctype="multipart/form-data" class="referral-panel-form">
                  @csrf
                  <h3 class="review-field-label">Log agency update</h3>
                  <div class="referral-panel-form-grid">
                    <div class="review-form-field review-form-field-wide">
                      <label for="referral-update-note">What the agency reported</label>
                      <textarea id="referral-update-note" name="note" rows="3" required maxlength="2000"></textarea>
                    </div>
                    <div class="review-form-field">
                      <label for="referral-update-channel">Received via</label>
                      <select id="referral-update-channel" name="channel" required>
                        @foreach(\App\Models\Referral::CHANNELS as $channelKey => $channelLabel)
                          <option value="{{ $channelKey }}">{{ $channelLabel }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="review-form-field">
                      <label for="referral-update-status">Agency status</label>
                      <select id="referral-update-status" name="status">
                        <option value="">Keep current</option>
                        @foreach(\App\Models\Referral::UPDATE_TARGET_STATUSES as $updateStatus)
                          <option value="{{ $updateStatus }}">{{ \App\Models\Referral::STATUSES[$updateStatus] ?? $updateStatus }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="review-form-field">
                      <label for="referral-update-date">Update date</label>
                      <input id="referral-update-date" type="date" name="occurred_at" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="review-form-field">
                      <label for="referral-update-reference">Agency reference no. <span class="review-optional">(optional)</span></label>
                      <input id="referral-update-reference" type="text" name="agency_reference_no" maxlength="100">
                    </div>
                    <div class="review-form-field">
                      <label for="referral-update-photo">Photo <span class="review-optional">(optional)</span></label>
                      <input id="referral-update-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
                    </div>
                    <div class="review-form-field review-form-field-wide">
                      <label class="review-checkbox-label">
                        <input type="checkbox" name="visible_to_resident" value="1" checked>
                        <span>Show this update to the resident</span>
                      </label>
                    </div>
                  </div>
                  <button type="submit" class="review-button review-button-primary">Log update</button>
                </form>

                <form method="POST" action="{{ route('admin.referrals.close', $referral) }}" class="referral-panel-form">
                  @csrf
                  <h3 class="review-field-label">Close referral</h3>
                  <div class="referral-panel-form-grid">
                    <div class="review-form-field">
                      <label for="referral-outcome">Outcome</label>
                      <select id="referral-outcome" name="outcome" required>
                        @foreach(\App\Models\Referral::OUTCOMES as $outcomeKey => $outcomeLabel)
                          <option value="{{ $outcomeKey }}">{{ $outcomeLabel }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="review-form-field review-form-field-wide">
                      <label for="referral-close-note">Closing note</label>
                      <textarea id="referral-close-note" name="note" rows="2" required maxlength="2000"></textarea>
                    </div>
                  </div>
                  <button type="submit" class="review-button review-button-outline">Close referral</button>
                  <p class="review-field-hint">Resolved closes the report. Declined or no response keeps the report open for the next action.</p>
                </form>
              @endif

              @if($referral->updates->isNotEmpty())
                <ol class="review-activity-list referral-update-list">
                  @foreach($referral->updates as $referralUpdate)
                    <li>
                      <div class="review-activity-heading">
                        <strong>{{ \App\Models\Referral::CHANNELS[$referralUpdate->channel] ?? $referralUpdate->channel }}</strong>
                        <time datetime="{{ ($referralUpdate->occurred_at ?? $referralUpdate->created_at)?->toIso8601String() }}">{{ ($referralUpdate->occurred_at ?? $referralUpdate->created_at)?->format('M d, Y h:i A') }}</time>
                      </div>
                      <p class="review-activity-body">{{ $referralUpdate->note }}</p>
                      @if($referralUpdate->agency_reference_no)
                        <p class="review-activity-user">Agency reference: {{ $referralUpdate->agency_reference_no }}</p>
                      @endif
                      @if($referralUpdate->photo_path)
                        <a href="{{ route('admin.referrals.updates.photo', [$referral, $referralUpdate]) }}" target="_blank" rel="noopener">View update photo</a>
                      @endif
                    </li>
                  @endforeach
                </ol>
              @endif
            </div>
          @else
            <p class="review-next-summary">This report was referred to {{ $report->office?->name ?? 'the appropriate office' }}, but no open referral is tracked. Choose the next action:</p>
            <div class="review-actions-row">
              <form method="POST" action="{{ route('admin.reports.status', $report) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="routed">
                <input type="hidden" name="note" value="Handled within the barangay after the referral was closed.">
                <button type="submit" class="review-button review-button-outline">Route internally</button>
              </form>
              <form method="POST" action="{{ route('admin.reports.status', $report) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="no_action">
                <input type="hidden" name="no_action_reason" value="cannot_resolve">
                <input type="hidden" name="note" value="No agency can act on this report.">
                <button type="submit" class="review-button review-button-outline">Take no action</button>
              </form>
            </div>
          @endif
        @elseif($report->status === 'merged')
          <p class="review-next-summary">This report was merged as a duplicate.</p>
        @else
          <p class="review-next-summary">No further action is being taken on this report.</p>
        @endif
      </div>
    </section>
  @endif

  <section class="review-workflow-card review-activity-card">
    <div class="review-card-heading review-card-heading-static">
      <span class="review-card-title">Activity</span>
      <span class="review-card-instruction">Newest first.</span>
    </div>
    <div class="review-card-content">
      <ol class="review-activity-list">
        @forelse($activityEntries as $activity)
          <li>
            <div class="review-activity-heading">
              <strong>{{ $activity['title'] }}</strong>
              <time datetime="{{ $activity['at']?->toIso8601String() }}">{{ $activity['at']?->format('M d, Y h:i A') }}</time>
            </div>
            @if($activity['user'])
              <p class="review-activity-user">{{ $activity['user'] }}</p>
            @endif
            @if($activity['body'])
              <p class="review-activity-body">{{ $activity['body'] }}</p>
            @endif
          </li>
        @empty
          <li class="review-activity-empty">No activity yet.</li>
        @endforelse
      </ol>
    </div>
  </section>

  <section class="review-workflow-card review-notes-card">
    <div class="review-card-heading review-card-heading-static">
      <span class="review-card-title">Internal notes</span>
    </div>
    <div class="review-card-content">
      @unless($emergencyActionOnly)
        <form method="POST" action="{{ route('admin.reports.internal-notes.store', $report) }}" class="review-note-form">
          @csrf
          <div class="review-form-field">
            <label for="internal-note">Add an internal note</label>
            <textarea id="internal-note" name="body" rows="3" maxlength="3000">{{ old('body') }}</textarea>
          </div>
          <button type="submit" class="review-button review-button-outline">Add note</button>
        </form>
      @endunless
      @if($report->internalNotes->isNotEmpty())
        <ul class="review-internal-notes">
          @foreach($report->internalNotes->sortByDesc('created_at') as $note)
            <li>
              <div><strong>{{ $note->user?->name ?? 'Staff' }}</strong><time>{{ $note->created_at->format('M d, Y h:i A') }}</time></div>
              <p>{{ $note->body }}</p>
            </li>
          @endforeach
        </ul>
      @endif
    </div>
  </section>
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
  const countLabel = root.querySelector('[data-reviewed-count]');
  const gateReason = root.querySelector('[data-gate-reason]');
  const pollUrl = root.dataset.aiPollUrl;
  let aiState = root.dataset.aiState;
  let pollAttempts = 0;
  let officeTouched = false;

  const normalize = (value) => String(value ?? '').trim().toLocaleLowerCase();
  const rows = [...root.querySelectorAll('[data-review-row]')];

  const stateClass = (state) => ({
    'AI suggested': 'review-badge-blue',
    Accepted: 'review-badge-green',
    Edited: 'review-badge-amber',
    Resident: 'review-badge-gray',
  }[state] || 'review-badge-gray');

  // A row is "resolved" once staff picked a value (AI, resident's, or their own).
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

  // Remember the pre-resolution value so "Undo" can restore it.
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

  const tokenize = (value) => value.match(/\s+|[\p{L}\p{M}\p{N}'’-]+|[^\s]/gu) || [];
  const renderDescriptionDiff = (row) => {
    const container = row.querySelector('[data-description-diff]');
    if (!container) return;
    const original = row.dataset.residentValue || '';
    const suggestion = row.dataset.aiValue || '';
    container.replaceChildren();
    if (!suggestion) return;

    const before = tokenize(original);
    const after = tokenize(suggestion);
    const lcs = Array.from({ length: before.length + 1 }, () => new Uint16Array(after.length + 1));
    for (let i = before.length - 1; i >= 0; i -= 1) {
      for (let j = after.length - 1; j >= 0; j -= 1) {
        lcs[i][j] = before[i] === after[j]
          ? lcs[i + 1][j + 1] + 1
          : Math.max(lcs[i + 1][j], lcs[i][j + 1]);
      }
    }

    const operations = [];
    let i = 0;
    let j = 0;
    while (i < before.length || j < after.length) {
      if (i < before.length && j < after.length && before[i] === after[j]) {
        operations.push(['same', before[i]]);
        i += 1;
        j += 1;
      } else if (i < before.length && (j === after.length || lcs[i + 1][j] >= lcs[i][j + 1])) {
        operations.push(['removed', before[i]]);
        i += 1;
      } else {
        operations.push(['added', after[j]]);
        j += 1;
      }
    }

    let currentType = null;
    let currentText = '';
    const flush = () => {
      if (!currentText) return;
      if (currentType === 'same') {
        container.append(document.createTextNode(currentText));
      } else {
        const span = document.createElement(currentType === 'removed' ? 'del' : 'ins');
        span.className = currentType === 'removed' ? 'review-diff-removed' : 'review-diff-added';
        span.textContent = currentText;
        container.append(span);
      }
      currentText = '';
    };
    operations.forEach(([type, text]) => {
      if (type !== currentType) {
        flush();
        currentType = type;
      }
      currentText += text;
    });
    flush();
  };

  const syncClamp = (wrap) => {
    const clampEl = wrap?.querySelector('.review-clamp');
    if (!clampEl) return;
    const expanded = clampEl.classList.contains('is-expanded');
    const truncated = clampEl.scrollHeight > clampEl.clientHeight + 1;
    let link = wrap.querySelector('.review-view-more');
    if (!truncated && !expanded) {
      link?.remove();
      return;
    }
    if (!link) {
      link = document.createElement('button');
      link.type = 'button';
      link.className = 'review-view-more';
      link.addEventListener('click', () => {
        const isExpanded = clampEl.classList.toggle('is-expanded');
        link.textContent = isExpanded ? 'View Less' : 'View More';
        syncClamp(wrap);
      });
      wrap.append(link);
    }
    link.hidden = false;
    link.textContent = expanded ? 'View Less' : 'View More';
  };

  const syncAllClamps = () => {
    root.querySelectorAll('.review-clamp-wrap').forEach(syncClamp);
  };

  const setAiDisplay = (display, label) => {
    if (!display) return;
    const empty = label === null || label === undefined || String(label).trim() === '';
    display.textContent = empty ? 'Not provided' : label;
    display.classList.toggle('review-not-provided', empty);
  };

  root.querySelectorAll('[data-open-dialog]').forEach((button) => {
    button.addEventListener('click', () => {
      const dialog = document.getElementById(button.dataset.openDialog);
      if (dialog) {
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

  root.querySelectorAll('form[data-confirm-delete]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirmDelete)) event.preventDefault();
    });
  });

  const referralPanel = root.querySelector('[data-referral-panel]');
  const copyLetterButton = root.querySelector('[data-copy-letter]');
  copyLetterButton?.addEventListener('click', async () => {
    const text = referralPanel?.dataset.letter || '';
    let copied = false;
    try {
      await navigator.clipboard.writeText(text);
      copied = true;
    } catch {
      const helper = document.createElement('textarea');
      helper.value = text;
      document.body.append(helper);
      helper.select();
      try {
        copied = document.execCommand('copy');
      } catch {
        copied = false;
      }
      helper.remove();
    }
    if (!copied) return;
    copyLetterButton.textContent = 'Copied';
    window.setTimeout(() => {
      copyLetterButton.innerHTML = '<i class="fa-solid fa-copy" aria-hidden="true"></i> Copy letter text';
    }, 1500);
  });

  root.querySelectorAll('[data-proof-input]').forEach((proofInput) => {
    const fieldScope = proofInput.closest('.review-form-field') || root;
    const preview = fieldScope.querySelector('[data-proof-preview]');
    const errorList = fieldScope.querySelector('[data-proof-errors]');
    const maxFiles = Number(proofInput.dataset.maxFiles || 5);
    const maxSizeKb = Number(proofInput.dataset.maxSizeKb || 5120);
    const acceptTypes = (proofInput.accept || '').split(',').map((type) => type.trim()).filter(Boolean);
    const acceptExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    let pendingFiles = [];

    const syncInput = () => {
      const transfer = new DataTransfer();
      pendingFiles.forEach((file) => transfer.items.add(file));
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
        const extension = file.name.includes('.')
          ? file.name.split('.').pop().toLowerCase()
          : '';
        const typeOk = (file.type && acceptTypes.includes(file.type)) || acceptExtensions.includes(extension);
        if (!typeOk) {
          errors.push(`${file.name} is not a JPG, PNG, or WebP image.`);
          return;
        }
        if (file.size > maxSizeKb * 1024) {
          errors.push(`${file.name} is larger than ${Math.round(maxSizeKb / 1024)} MB.`);
          return;
        }
        accepted.push(file);
      });

      const room = Math.max(0, maxFiles - pendingFiles.length);
      if (accepted.length > room) {
        errors.push(`Up to ${maxFiles} photos can be uploaded at a time.`);
      }
      pendingFiles = pendingFiles.concat(accepted.slice(0, room));

      if (errorList) {
        errorList.replaceChildren(...errors.map((message) => {
          const item = document.createElement('li');
          item.textContent = message;
          return item;
        }));
        errorList.hidden = errors.length === 0;
      }
      syncInput();
      renderPending();
    });
  });

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
      if (updateMessageOptional) {
        updateMessageOptional.textContent = isRequired ? '(required)' : '(optional)';
      }
    });
  }

  const toggleResolutionBtn = root.querySelector('[data-toggle-resolution]');
  const resolutionPanel = root.querySelector('[data-resolution-panel]');
  if (toggleResolutionBtn && resolutionPanel) {
    toggleResolutionBtn.addEventListener('click', () => {
      const willOpen = resolutionPanel.hidden;
      resolutionPanel.hidden = !willOpen;
      toggleResolutionBtn.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      if (willOpen) {
        resolutionPanel.querySelector('textarea')?.focus();
      }
    });
  }

  root.querySelectorAll('form[data-prevent-double-submit]').forEach((protectedForm) => {
    protectedForm.addEventListener('submit', (event) => {
      const submitBtn = event.submitter || protectedForm.querySelector('[data-submit-once], button[type="submit"]');
      if (submitBtn) {
        if (submitBtn.dataset.submitting === 'true') {
          event.preventDefault();
          return;
        }
        submitBtn.dataset.submitting = 'true';
        submitBtn.disabled = true;
      }
    });
  });

  const modeBanner = root.querySelector('[data-mode-banner]');
  const modeBadge = root.querySelector('[data-mode-badge]');
  const modeNote = root.querySelector('[data-mode-note]');
  const modeToggle = root.querySelector('[data-mode-toggle]');
  const authorityModeInput = root.querySelector('[data-authority-mode-input]');
  const blockWithin = root.querySelector('[data-mode-block="within"]');
  const blockBeyond = root.querySelector('[data-mode-block="beyond"]');
  const agencySelect = root.querySelector('[data-agency-select]');
  const agencyInfo = root.querySelector('[data-agency-info]');
  const letterTextarea = root.querySelector('[data-referral-letter]');
  const residentMessageTextarea = root.querySelector('[data-resident-message]');
  const referralSubmit = root.querySelector('[data-referral-submit]');
  const letterTemplatesElement = root.querySelector('[data-letter-templates]');
  let letterTemplates = {};
  try {
    if (letterTemplatesElement) letterTemplates = JSON.parse(letterTemplatesElement.textContent);
  } catch {
    letterTemplates = {};
  }
  let currentMode = modeBanner?.dataset.ruleMode === 'beyond' ? 'beyond' : 'within';
  let modeOverridden = false;

  // Hide "Accept all AI suggestions" once every AI-suggested field is resolved.
  const updateAcceptAllVisibility = () => {
    if (!acceptAllButton) return;
    const aiRows = rows.filter((row) => row.dataset.aiPresent === 'true' && row.dataset.reviewRequired === 'true');
    acceptAllButton.hidden = aiRows.length === 0 || aiRows.every((row) => isRowResolved(row));
  };

  const updateGate = () => {
    const requiredRows = rows.filter((row) => row.dataset.reviewRequired === 'true');
    const reviewedCount = requiredRows.filter((row) => row.dataset.reviewed === 'true').length;
    const remaining = requiredRows.length - reviewedCount;
    if (countLabel) countLabel.textContent = `${reviewedCount} of ${requiredRows.length} reviewed`;

    updateAcceptAllVisibility();

    const category = root.querySelector('[name="category_id"]');
    const urgency = root.querySelector('[name="urgency"]');
    const missingCategory = !category?.value;
    const missingUrgency = !urgency?.value;
    const reasons = [];
    if (remaining > 0) reasons.push(`Review ${remaining} more field${remaining === 1 ? '' : 's'}.`);
    if (missingCategory || missingUrgency) {
      reasons.push(`Select ${missingCategory && missingUrgency ? 'Category and Urgency' : (missingCategory ? 'Category' : 'Urgency')}.`);
    }

    if (currentMode === 'beyond') {
      if (routeButton) routeButton.disabled = true;
      const missingAgency = !agencySelect?.value;
      if (referralSubmit) {
        referralSubmit.disabled = remaining > 0 || missingCategory || missingUrgency || missingAgency;
      }
      if (missingAgency) reasons.push('Choose an agency.');
    } else {
      if (referralSubmit) referralSubmit.disabled = true;
      const missingOffice = Boolean(routeButton && !officeSelect?.value);
      if (missingOffice) reasons.push('Choose an office.');
      if (routeButton) {
        routeButton.disabled = remaining > 0 || missingCategory || missingUrgency || missingOffice;
      }
    }

    if (gateReason) gateReason.textContent = reasons.join(' ');
  };

  rows.forEach((row) => {
    updateRow(row);
    renderDescriptionDiff(row);
    const control = row.querySelector('[data-final-control]');
    if (control) {
      const markEdited = () => {
        row.dataset.finalTouched = 'true';
        snapshotUndo(row);
        setFinalState(row, 'Edited');
        updateGate();
      };
      control.addEventListener('input', markEdited);
      control.addEventListener('change', markEdited);
    }

    row.querySelector('[data-accept-row]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
      snapshotUndo(row);
      if (control) {
        control.value = row.dataset.aiValue || '';
        control.dispatchEvent(new Event('change', { bubbles: true }));
        setFinalState(row, 'Accepted');
        row.dataset.finalTouched = 'false';
      }
      updateGate();
    });
    row.querySelector('[data-use-ai]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
      if (!control || !row.dataset.aiValue) return;
      snapshotUndo(row);
      control.value = row.dataset.aiValue;
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Accepted');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-use-resident]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
      if (!control) return;
      snapshotUndo(row);
      control.value = row.dataset.residentValue || '';
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Resident');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-use-original]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
      if (!control) return;
      snapshotUndo(row);
      control.value = row.dataset.residentValue || '';
      control.dispatchEvent(new Event('input', { bubbles: true }));
      setFinalState(row, 'Resident');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-undo-row]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
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
      setResolvedUi(row);
      updateGate();
    });
    row.querySelector('[data-append-ai]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
      const suggestion = row.dataset.aiValue || '';
      if (!control || !suggestion) return;
      control.value = [control.value.trim(), suggestion.trim()].filter(Boolean).join('\n\n');
      control.dispatchEvent(new Event('input', { bubbles: true }));
      setFinalState(row, 'Edited');
      updateGate();
    });
  });

  syncAllClamps();

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
    updateGate();
  });

  const routingMapElement = root.querySelector('[data-routing-map]');
  const routingReason = root.querySelector('[data-routing-reason]');
  const officeSelect = root.querySelector('[data-office-select]');
  let routingMap = {};
  try {
    if (routingMapElement) routingMap = JSON.parse(routingMapElement.textContent);
  } catch {
    if (routingReason) routingReason.textContent = 'Routing recommendations could not be loaded.';
  }

  const applyLetterTemplate = (officeId) => {
    const template = letterTemplates[officeId];
    if (!template) return;
    if (letterTextarea && letterTextarea.dataset.touched !== 'true') {
      letterTextarea.value = template.letter || '';
    }
    if (residentMessageTextarea && residentMessageTextarea.dataset.touched !== 'true') {
      residentMessageTextarea.value = template.resident_message || '';
    }
    if (agencyInfo) {
      const parts = [];
      if (template.contact_person) parts.push(`Contact: ${template.contact_person}`);
      if (template.contact_email) parts.push(template.contact_email);
      if (template.contact_phone) parts.push(template.contact_phone);
      if (template.address) parts.push(template.address);
      agencyInfo.textContent = parts.length ? parts.join(' · ') : 'Contact details not provided.';
    }
  };

  const setMode = (mode, { fromUser = false } = {}) => {
    currentMode = mode === 'beyond' ? 'beyond' : 'within';
    if (fromUser) modeOverridden = true;
    if (authorityModeInput) authorityModeInput.value = currentMode;
    const within = currentMode === 'within';
    if (modeBanner) {
      modeBanner.classList.toggle('review-mode-banner-within', within);
      modeBanner.classList.toggle('review-mode-banner-beyond', !within);
    }
    if (modeBadge) modeBadge.textContent = within ? 'Within barangay authority' : 'Beyond barangay authority';
    if (modeToggle) {
      modeToggle.dataset.otherMode = within ? 'beyond' : 'within';
      modeToggle.textContent = within ? "This isn't barangay authority" : 'Handle within the barangay instead';
    }
    if (blockWithin) blockWithin.hidden = !within;
    if (blockBeyond) blockBeyond.hidden = within;
    if (officeSelect) officeSelect.disabled = !within;
    if (agencySelect) agencySelect.disabled = within;
    const followUpOwnerSelect = root.querySelector('#follow_up_owner_id');
    if (followUpOwnerSelect) followUpOwnerSelect.disabled = within;
    if (letterTextarea) letterTextarea.disabled = within;
    if (residentMessageTextarea) residentMessageTextarea.disabled = within;
    if (!within && agencySelect) applyLetterTemplate(agencySelect.value);
    updateGate();
  };

  const assignedSelect = root.querySelector('[data-assigned-select]');
  let assignedTouched = false;
  assignedSelect?.addEventListener('change', () => {
    assignedTouched = true;
  });

  const syncDefaultAssignee = () => {
    if (!officeSelect || !assignedSelect || assignedTouched) return;
    const opt = officeSelect.selectedOptions[0];
    if (opt && opt.dataset.defaultAssignee !== undefined) {
      assignedSelect.value = opt.dataset.defaultAssignee || '';
    }
  };

  modeToggle?.addEventListener('click', () => {
    setMode(modeToggle.dataset.otherMode === 'beyond' ? 'beyond' : 'within', { fromUser: true });
  });
  agencySelect?.addEventListener('change', () => {
    agencySelect.dataset.touched = 'true';
    applyLetterTemplate(agencySelect.value);
    updateGate();
  });
  letterTextarea?.addEventListener('input', () => {
    letterTextarea.dataset.touched = 'true';
  });
  residentMessageTextarea?.addEventListener('input', () => {
    residentMessageTextarea.dataset.touched = 'true';
  });
  officeSelect?.addEventListener('change', () => {
    officeTouched = true;
    syncDefaultAssignee();
    updateGate();
  });
  root.querySelector('[name="category_id"]')?.addEventListener('change', (event) => {
    const decision = routingMap[event.target.value];
    if (!decision) return;
    const ruleMode = decision.within_authority ? 'within' : 'beyond';
    if (routingReason) {
      routingReason.textContent = `Rule: ${decision.office_name || 'No office'} — ${decision.reason}`;
    }
    if (modeNote && !modeOverridden) modeNote.textContent = decision.reason || modeNote.textContent;
    if (officeSelect && !officeTouched && decision.office_id) {
      officeSelect.value = String(decision.office_id);
      syncDefaultAssignee();
    }
    if (!modeOverridden) setMode(ruleMode);
    updateGate();
  });

  setMode(currentMode);

  const applyAiResult = (result) => {
    aiState = result.status;
    root.dataset.aiState = aiState;
    if (result.status === 'ready') {
      if (aiStateBadge) {
        aiStateBadge.className = 'review-badge review-badge-blue';
        aiStateBadge.innerHTML = '<i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> AI suggestions ready';
      }
      const confidence = Number(result.confidence);
      const high = Number(root.dataset.confidenceHigh);
      const low = Number(root.dataset.confidenceLow);
      if (confidenceWrap) confidenceWrap.hidden = false;
      if (confidenceBadge) {
        confidenceBadge.textContent = `Confidence ${Number.isFinite(confidence) ? Math.round(confidence * 100) : '—'}%`;
        confidenceBadge.className = `review-badge ${confidence >= high ? 'review-badge-green' : (confidence >= low ? 'review-badge-amber' : 'review-badge-red')}`;
      }
      if (lowConfidenceBadge) lowConfidenceBadge.hidden = !(Number.isFinite(confidence) && confidence < low);
      if (stateMessage) stateMessage.textContent = 'AI suggestions are ready. Your final values were not changed; choose Use AI or Accept for each suggestion.';
      if (acceptAllButton) acceptAllButton.hidden = false;
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
      syncAllClamps();
      updateGate();
      return;
    }

    if (result.status === 'unavailable') {
      if (aiStateBadge) {
        aiStateBadge.className = 'review-badge review-badge-gray';
        aiStateBadge.textContent = 'AI unavailable';
      }
      if (stateMessage) stateMessage.textContent = 'AI suggestions are not available. Your final values were not changed; confirm Category and Urgency manually.';
      if (acceptAllButton) acceptAllButton.hidden = true;
      rows.forEach(updateRow);
      updateGate();
    }
  };

  const pollAi = async () => {
    if (aiState !== 'pending' || pollAttempts >= 24) return;
    pollAttempts += 1;
    try {
      const response = await fetch(pollUrl, { headers: { Accept: 'application/json' } });
      if (!response.ok) throw new Error(`AI status request failed (${response.status}).`);
      const result = await response.json();
      if (result.status === 'pending') {
        window.setTimeout(pollAi, 5000);
        return;
      }
      applyAiResult(result);
    } catch (error) {
      if (stateMessage) stateMessage.textContent = `${error.message} Your final values are unchanged. Retrying shortly.`;
      window.setTimeout(pollAi, 5000);
    }
  };

  root.querySelectorAll('[data-expand-review]').forEach((link) => {
    link.addEventListener('click', (event) => {
      event.preventDefault();
      const details = link.closest('details');
      if (details) details.open = true;
    });
  });

  form?.addEventListener('submit', (event) => {
    if (event.submitter?.value !== 'route' && event.submitter?.value !== 'referral') return;
    updateGate();
    const submitterDisabled = event.submitter.value === 'route'
      ? routeButton?.disabled
      : referralSubmit?.disabled;
    if (submitterDisabled) event.preventDefault();
  });

  updateGate();
  if (aiState === 'pending') window.setTimeout(pollAi, 1500);
})();
</script>
@endpush
