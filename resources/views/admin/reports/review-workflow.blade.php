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
  $officeDefault = old('office_id', $alreadyReviewed
      ? ($report->office_id ?? $routingDecision['office_id'] ?? '')
      : ($routingDecision['office_id'] ?? $report->office_id ?? ''));
  $assignedDefault = old('assigned_to', $report->assigned_to ?? $currentUserId);
  $defaultMessage = old(
      'review_note',
      $responseTemplates->firstWhere('category_id', $selectedCategoryId)?->body
          ?? $responseTemplates->first()?->body
          ?? ''
  );
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
      'out_of_scope',
  ], true);
  $isRouteable = in_array($report->status, ['submitted', 'under_review', 'reopened'], true)
      && ! $emergencyActionOnly;
  $statusStep = match ($report->status) {
      'submitted', 'under_review', 'reopened' => 0,
      'routed' => 1,
      'in_progress' => 2,
      'needs_info' => (($report->statusLogs->where('to_status', 'needs_info')->last()?->from_status) === 'in_progress' ? 2 : 1),
      'awaiting_confirmation', 'resolved', 'referred', 'merged', 'out_of_scope' => 3,
      default => 0,
  };
  $isCompleted = $report->status === 'resolved';
  $oneLineSummary = \Illuminate\Support\Str::limit($report->description, 110);
  $priorityLabel = $report->urgency ?: 'Unassigned';
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
      <a class="review-text-link" href="#review-other-actions">Review duplicate options</a>
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
            <span><i class="fa-solid fa-user" aria-hidden="true"></i> {{ $report->reporter_name }}</span>
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

        <details class="review-reporter">
          <summary>Reporter</summary>
          <div class="review-reporter-details">
            @if($report->is_anonymous)
              <span>Anonymous report</span>
            @else
              @if($report->reporter_name)<span><strong>Name:</strong> {{ $report->reporter_name }}</span>@endif
              @if($report->reporter_contact)<span><strong>Phone:</strong> {{ $report->reporter_contact }}</span>@endif
              @if($report->reporter_email)<span><strong>Email:</strong> {{ $report->reporter_email }}</span>@endif
            @endif
          </div>
        </details>
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
        </div>
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
            @if(in_array($report->status, ['submitted', 'under_review', 'reopened', 'routed', 'in_progress'], true))
              <button type="button" class="review-button review-button-outline" data-open-info-request>
                <i class="fa-solid fa-comment-dots" aria-hidden="true"></i> Ask Reporter for More Info
              </button>
            @endif

            @if($aiReady)
              <button type="button" class="review-button review-button-blue" data-accept-all>
                <i class="fa-solid fa-check-double" aria-hidden="true"></i> Accept all AI suggestions
              </button>
            @else
              <button type="button" class="review-button review-button-blue" data-accept-all hidden disabled>
                Accept all AI suggestions
              </button>
            @endif

            <span class="review-field-hint">Step 2 of 3: confirm the reviewed values before routing.</span>

            <button type="submit" name="intent" value="save" class="review-button review-button-blue">
              Confirm review
            </button>
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
        <div class="review-form-grid">
          <div class="review-form-field">
            <label for="office_id">Office</label>
            <select id="office_id" name="office_id" data-office-select>
              <option value="">Select an office</option>
              @foreach($offices as $office)
                <option value="{{ $office->id }}" @selected((string) $officeDefault === (string) $office->id)>{{ $office->name }}</option>
              @endforeach
            </select>
            <p class="review-field-hint" data-routing-reason>
              @if($routingDecision)
                Rule: {{ $categories->firstWhere('id', $selectedCategoryId)?->name }} → {{ $routingDecision['office_name'] ?? 'No office' }}. {{ $routingDecision['reason'] }}
                @if(! $routingDecision['within_authority'] && $routingDecision['office_name'])
                  Refer to {{ $routingDecision['office_name'] }} using the referral flow.
                @endif
              @else
                Select a category to see the routing recommendation.
              @endif
            </p>
            <script type="application/json" data-routing-map>@json($routingDecisions)</script>
          </div>
          <div class="review-form-field">
            <label for="assigned_to">Assigned to</label>
            <select id="assigned_to" name="assigned_to">
              <option value="">Unassigned</option>
              @foreach($staffUsers as $staffUser)
                <option value="{{ $staffUser->id }}" @selected((string) $assignedDefault === (string) $staffUser->id)>{{ $staffUser->name }}</option>
              @endforeach
            </select>
            <p class="review-field-hint">Defaults to the selected office's assignee, or you when none is configured.</p>
          </div>
          <div class="review-form-field review-form-field-wide">
            <label for="review_note">Message to the resident <span class="review-optional">(optional)</span></label>
            <p class="review-field-hint">
              This message is sent to the reporter when you click <strong>Confirm and route</strong> below.
              Use it to tell them what happens next — for example, which office will respond and when.
              The text below is pre-filled from the standard response for the selected category; edit it freely.
            </p>
            <textarea id="review_note" name="review_note" rows="3" maxlength="1000" placeholder="Example: The CENRO drainage team will inspect your report within 3 working days.">{{ $defaultMessage }}</textarea>
          </div>
        </div>
        <p class="review-priority-line">
          Priority:
          <span class="review-badge review-badge-blue" title="Priority score: {{ $report->priority_score ?? 0 }}">{{ $priorityLabel }}</span>
          @if($report->due_at)
            <span>Due {{ $report->due_at->format('M d, Y h:i A') }}</span>
          @endif
        </p>

        <details class="review-other-actions" id="review-other-actions">
          <summary><i class="fa-solid fa-ellipsis" aria-hidden="true"></i> Other actions (refer, duplicate, out of scope)</summary>
          <div class="review-other-actions-list">
            @if(in_array($report->status, ['submitted', 'under_review', 'reopened', 'needs_info', 'routed', 'in_progress'], true))
              <form method="POST" action="{{ route('admin.reports.refer', $report) }}">
                @csrf
                @method('PATCH')
                <label for="refer-office">Refer to office</label>
                <select id="refer-office" name="office_id" required>
                  <option value="">Select an office</option>
                  @foreach($offices as $office)
                    <option value="{{ $office->id }}" @selected((string) $officeDefault === (string) $office->id)>{{ $office->name }}</option>
                  @endforeach
                </select>
                <label for="refer-note">Reason for referral</label>
                <textarea id="refer-note" name="note" rows="2" required maxlength="500"></textarea>
                <button type="submit" class="review-button review-button-outline">Refer report</button>
              </form>
            @endif

            @if(in_array($report->status, ['submitted', 'under_review', 'reopened'], true))
              <form method="POST" action="{{ route('admin.reports.duplicate', $report) }}">
                @csrf
                @method('PATCH')
                <label for="duplicate-candidate">Mark duplicate of</label>
                <select id="duplicate-candidate" name="candidate_id" required>
                  <option value="">Select a report</option>
                  @foreach($duplicateCandidates as $candidate)
                    <option value="{{ $candidate->id }}">{{ $candidate->tracking_code }} — {{ \Illuminate\Support\Str::limit($candidate->description, 70) }}</option>
                  @endforeach
                </select>
                <label for="duplicate-note">Merge note</label>
                <textarea id="duplicate-note" name="note" rows="2" required maxlength="500"></textarea>
                <button type="submit" class="review-button review-button-outline" @disabled($duplicateCandidates->isEmpty())>Mark duplicate</button>
                @if($duplicateCandidates->isEmpty())<small>No other reports are available to merge.</small>@endif
              </form>
            @endif

            @if(in_array($report->status, ['submitted', 'under_review', 'reopened'], true))
              <form method="POST" action="{{ route('admin.reports.status', $report) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="out_of_scope">
                <label for="out-of-scope-note">Out-of-scope reason</label>
                <textarea id="out-of-scope-note" name="note" rows="2" required maxlength="500"></textarea>
                <button type="submit" class="review-button review-button-outline">Mark out of scope</button>
              </form>
            @endif

            @if($isOverdue && ! $report->escalated_at)
              <form method="POST" action="{{ route('admin.reports.escalate', $report) }}">
                @csrf
                <button type="submit" class="review-button review-button-outline">Escalate overdue report</button>
              </form>
            @endif
          </div>
        </details>

        <div class="review-actions-row">
          <span class="review-gate-hint" data-gate-reason aria-live="polite"></span>
          @if($isRouteable)
            <button type="submit" name="intent" value="route" class="review-button review-button-primary" data-route-submit>
              Confirm and route
            </button>
          @endif
        </div>
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

    @if(in_array($report->status, ['submitted', 'under_review', 'reopened', 'routed', 'in_progress'], true))
      <dialog id="info-request-modal" class="review-modal">
        <form id="more-info-request-form" method="POST" action="{{ route('admin.reports.info-requests.store', $report) }}">
          @csrf
          <header class="review-modal-header">
            <strong><i class="fa-solid fa-comment-dots" aria-hidden="true"></i> Ask the reporter for more info</strong>
            <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
          </header>
          <div class="review-modal-body">
            <p class="review-field-hint">
              The report moves to the <strong>Needs info</strong> state and the reporter is notified.
              Their reply appears on this page, and you can resume reviewing afterwards.
            </p>
            <label for="more-question">Your question</label>
            <textarea id="more-question" name="question" rows="4" required maxlength="2000" placeholder="Example: What time did you first notice this issue?"></textarea>
          </div>
          <footer class="review-modal-footer">
            <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
            <button type="submit" class="review-button review-button-primary">Send request</button>
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
        @if($report->status === 'routed')
          <p class="review-next-summary">{{ $report->category?->name ?? 'Category not assigned' }} · {{ $report->urgency ?? 'Urgency not assigned' }} · {{ $report->office?->name ?? 'Office not assigned' }} · {{ $report->assignee?->name ?? 'Unassigned' }}</p>
          <form method="POST" action="{{ route('admin.reports.status', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" value="in_progress">
            <button type="submit" class="review-button review-button-outline">Start work</button>
          </form>
        @elseif($report->status === 'in_progress')
          <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" enctype="multipart/form-data" class="review-resolution-form">
            @csrf
            <div class="review-form-field">
              <label for="resolution-note">Resolution note</label>
              <textarea id="resolution-note" name="note" rows="4" required maxlength="2000">{{ old('note') }}</textarea>
              @error('note')<span class="review-field-error">{{ $message }}</span>@enderror
            </div>
            <div class="review-form-field">
              <label for="resolution-photo">Proof photo <span class="review-optional">(optional)</span></label>
              <input id="resolution-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp">
            </div>
            <button type="submit" class="review-button review-button-outline">Mark resolved</button>
          </form>
        @elseif($report->status === 'needs_info')
          <p class="review-next-summary">Waiting for the resident's reply.</p>
          @foreach($report->infoRequests as $infoRequest)
            @if($infoRequest->responded_at && $infoRequest->response)
              <p class="review-resident-reply"><strong>Resident replied:</strong> {{ $infoRequest->response }}</p>
            @endif
          @endforeach
        @elseif($report->status === 'awaiting_confirmation')
          <p class="review-next-summary">Waiting for the resident to confirm the resolution.</p>
          @if($report->due_at && $report->due_at->copy()->addDays($autoCloseDays)->isPast())
            <form method="POST" action="{{ route('admin.reports.close', $report) }}">
              @csrf
              <button type="submit" class="review-button review-button-outline">Close case</button>
            </form>
          @endif
        @elseif($report->status === 'resolved')
          <p class="review-next-summary">This report has been resolved.</p>
        @elseif($report->status === 'referred')
          <p class="review-next-summary">This report was referred to {{ $report->office?->name ?? 'the appropriate office' }}.</p>
        @elseif($report->status === 'merged')
          <p class="review-next-summary">This report was merged as a duplicate.</p>
        @else
          <p class="review-next-summary">This report is out of scope.</p>
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

  const setFinalState = (row, state) => {
    const badge = row.querySelector('[data-final-state]');
    if (!badge) return;
    badge.textContent = state;
    badge.className = `review-badge ${stateClass(state)}`;
    row.dataset.reviewed = 'true';
  };

  const updateRow = (row) => {
    const aiPresent = row.dataset.aiPresent === 'true';
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

  const infoRequestModal = root.querySelector('#info-request-modal');
  if (infoRequestModal) {
    root.querySelector('[data-open-info-request]')?.addEventListener('click', () => {
      infoRequestModal.showModal();
      infoRequestModal.querySelector('textarea')?.focus();
    });
    infoRequestModal.querySelectorAll('[data-close-modal]').forEach((button) => {
      button.addEventListener('click', () => infoRequestModal.close());
    });
    infoRequestModal.addEventListener('click', (event) => {
      if (event.target === infoRequestModal) infoRequestModal.close();
    });
  }

  const updateGate = () => {
    const requiredRows = rows.filter((row) => row.dataset.reviewRequired === 'true');
    const reviewedCount = requiredRows.filter((row) => row.dataset.reviewed === 'true').length;
    const remaining = requiredRows.length - reviewedCount;
    if (countLabel) countLabel.textContent = `${reviewedCount} of ${requiredRows.length} reviewed`;

    const category = root.querySelector('[name="category_id"]');
    const urgency = root.querySelector('[name="urgency"]');
    const office = root.querySelector('[name="office_id"]');
    const missingCategory = !category?.value;
    const missingUrgency = !urgency?.value;
    const missingOffice = Boolean(routeButton && !office?.value);
    const reasons = [];
    if (remaining > 0) reasons.push(`Review ${remaining} more field${remaining === 1 ? '' : 's'}.`);
    if (missingCategory || missingUrgency) {
      reasons.push(`Select ${missingCategory && missingUrgency ? 'Category and Urgency' : (missingCategory ? 'Category' : 'Urgency')}.`);
    }
    if (missingOffice) reasons.push('Choose an office.');
    if (gateReason) gateReason.textContent = reasons.join(' ');
    if (routeButton) routeButton.disabled = remaining > 0 || missingCategory || missingUrgency || missingOffice;
  };

  rows.forEach((row) => {
    updateRow(row);
    renderDescriptionDiff(row);
    const control = row.querySelector('[data-final-control]');
    if (control) {
      const markEdited = () => {
        row.dataset.finalTouched = 'true';
        setFinalState(row, 'Edited');
        updateGate();
      };
      control.addEventListener('input', markEdited);
      control.addEventListener('change', markEdited);
    }

    row.querySelector('[data-accept-row]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
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
      control.value = row.dataset.aiValue;
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Accepted');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-use-resident]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
      if (!control) return;
      control.value = row.dataset.residentValue || '';
      control.dispatchEvent(new Event('change', { bubbles: true }));
      setFinalState(row, 'Resident');
      row.dataset.finalTouched = 'false';
      updateGate();
    });
    row.querySelector('[data-use-original]')?.addEventListener('click', () => {
      const control = row.querySelector('[data-final-control]');
      if (!control) return;
      control.value = row.dataset.residentValue || '';
      control.dispatchEvent(new Event('input', { bubbles: true }));
      setFinalState(row, 'Resident');
      row.dataset.finalTouched = 'false';
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
  officeSelect?.addEventListener('change', () => {
    officeTouched = true;
    updateGate();
  });
  root.querySelector('[name="category_id"]')?.addEventListener('change', (event) => {
    const decision = routingMap[event.target.value];
    if (!decision) return;
    if (routingReason) {
      routingReason.textContent = `Rule: ${decision.office_name || 'No office'} — ${decision.reason}${decision.within_authority ? '' : ` Refer to ${decision.office_name} using the referral flow.`}`;
    }
    if (officeSelect && !officeTouched && decision.office_id) {
      officeSelect.value = String(decision.office_id);
    }
    updateGate();
  });

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
    if (event.submitter?.value !== 'route') return;
    updateGate();
    if (routeButton?.disabled) event.preventDefault();
  });

  updateGate();
  if (aiState === 'pending') window.setTimeout(pollAi, 1500);
})();
</script>
@endpush
