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
     data-is-after-route="{{ $isAfterRoute ? 'true' : 'false' }}"
     data-has-unverified-audio="{{ $report->hasUnverifiedAudio() ? 'true' : 'false' }}"
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
      @if($report->source === \App\Models\Report::SOURCE_VOICE)
        <span class="review-badge review-badge-blue" title="Voice report">
          <i class="fa-solid fa-microphone" aria-hidden="true"></i> Voice report
        </span>
      @elseif($report->source === \App\Models\Report::SOURCE_PHONE_LOG)
        <span class="review-badge" style="background:#ccfbf1;color:#0f766e;border-color:#99f6e4;" title="Phone hotline report">
          <i class="fa-solid fa-phone" aria-hidden="true"></i> Phone intake
        </span>
      @endif
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
        @if($report->is_emergency && $report->status === 'emergency_flagged')
          <button type="button" class="review-button review-button-primary" data-open-dialog="emergency-handled-modal">
            <i class="fa-solid fa-person-digging" aria-hidden="true"></i> Handle internally
          </button>
        @endif
        @if($canResolveEmergency)
          <form method="POST" action="{{ route('admin.reports.emergency', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="confirm" value="0">
            <button type="submit" class="review-button review-button-outline">Not an emergency</button>
          </form>
        @else
          <span class="review-field-hint">This report is in a final or non-actionable status; its emergency state cannot be changed here.</span>
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

  @if($report->hasUnverifiedAudio())
    <section class="review-flag review-flag-amber" style="background-color:#fffbeb;border:1px solid #fcd34d;color:#92400e;padding:1rem;border-radius:0.5rem;margin-bottom:1rem;display:flex;align-items:flex-start;gap:1rem;">
      <div style="font-size:1.5rem;line-height:1;color:#d97706;">
        <i class="fa-solid fa-microphone-slash"></i>
      </div>
      <div style="flex:1;">
        <strong style="display:block;font-size:0.95rem;font-weight:700;color:#92400e;">Voice Audio Needs Verification</strong>
        <p style="margin:0.25rem 0 0.5rem;font-size:0.875rem;color:#78350f;">
          This report was recorded by voice or phone hotline, but AI transcription is unclear or unverified.
          Routing is blocked until staff listens to the recording and confirms or updates the report description.
        </p>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
          <a href="#voice-audio-section" class="review-button review-button-primary" style="padding:0.4rem 0.8rem;font-size:0.85rem;background:#d97706;border-color:#b45309;color:#fff;text-decoration:none;">
            <i class="fa-solid fa-headphones"></i> Listen & Verify Audio
          </a>
        </div>
      </div>
    </section>
  @endif

  {{-- Top Row: Resident report on the left, Case timeline & notes on the right --}}
  <div class="review-top-grid">
    {{-- Left: Card 1 — Resident report --}}
    <div class="review-col-resident">
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
          @php
            $isVoiceOrPhone = $report->source === \App\Models\Report::SOURCE_VOICE || $report->source === \App\Models\Report::SOURCE_PHONE_LOG || $report->audio;
          @endphp
          @if($report->is_anonymous)
            <span class="review-not-provided"><i class="fa-solid fa-user" aria-hidden="true"></i> Not stated (anonymous report)</span>
          @elseif(! $isVoiceOrPhone && ! filled($report->reporter_name))
            <span class="review-not-provided"><i class="fa-solid fa-user" aria-hidden="true"></i> Not stated (anonymous report)</span>
          @else
            <span class="review-reported-identity">
              <span>
                <i class="fa-solid fa-user" aria-hidden="true"></i>
                @if(filled($report->reporter_name))
                  {{ $report->reporter_name }}
                @else
                  <span class="review-not-provided">Not provided</span>
                @endif
              </span>
              @if($isVoiceOrPhone)
                <span class="review-reported-contact">
                  <span>
                    <i class="fa-solid fa-phone" aria-hidden="true"></i>
                    @if(filled($report->reporter_contact))
                      {{ $report->reporter_contact }}
                    @else
                      <span class="review-not-provided">Not provided</span>
                    @endif
                  </span>
                  <span>
                    <i class="fa-solid fa-envelope" aria-hidden="true"></i>
                    @if(filled($report->reporter_email))
                      {{ $report->reporter_email }}
                    @else
                      <span class="review-not-provided">Not provided</span>
                    @endif
                  </span>
                </span>
              @elseif($report->reporter_contact || $report->reporter_email)
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
                <div id="report-location-map" class="review-location-map map-frame z-0 isolate"
                     style="position:relative;z-index:0;isolation:isolate;"
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
            @elseif($report->source === \App\Models\Report::SOURCE_VOICE || $report->source === \App\Models\Report::SOURCE_PHONE_LOG || $report->audio)
              <span><strong>Purok:</strong> <span class="review-not-provided">Not provided</span></span>
            @endif
            @if($residentLandmark)
              <span><strong>Landmark:</strong> {{ $residentLandmark }}</span>
            @elseif($report->source === \App\Models\Report::SOURCE_VOICE || $report->source === \App\Models\Report::SOURCE_PHONE_LOG || $report->audio)
              <span><strong>Landmark:</strong> <span class="review-not-provided">Not provided</span></span>
            @endif
            @if($googleMapsUrl)
              <a href="{{ $googleMapsUrl }}" target="_blank" rel="noopener">Open in Google Maps</a>
            @endif
          </div>
        </div>

        @if($report->audio)
          <div id="voice-audio-section" class="review-section" style="margin-top:1rem;padding-top:1rem;border-top:1px solid var(--line);">
            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:0.5rem;">
              <h3 class="review-field-label" style="margin:0;display:flex;align-items:center;gap:0.5rem;">
                <i class="fa-solid fa-microphone-lines" style="color:var(--blue);"></i>
                <span>{{ $report->audio->origin === 'staff' ? 'Hotline Phone Recording' : 'Resident Voice Recording' }}</span>
              </h3>
              @if($report->audio->isVerified())
                <span class="review-badge review-badge-green" style="display:inline-flex;align-items:center;gap:0.3rem;">
                  <i class="fa-solid fa-check"></i> Verified by {{ $report->audio->verifiedBy?->name ?? 'Staff' }} on {{ $report->audio->verified_at->format('M d, Y h:i A') }}
                </span>
              @elseif($report->hasUnverifiedAudio())
                <span class="review-badge review-badge-amber" style="display:inline-flex;align-items:center;gap:0.3rem;">
                  <i class="fa-solid fa-triangle-exclamation"></i> Needs verification
                </span>
              @endif
            </div>

            <audio controls src="{{ route('admin.reports.audio', $report) }}" preload="metadata" style="width:100%;max-width:500px;height:40px;outline:none;margin-bottom:0.5rem;"></audio>

            @if($report->audio->transcript)
              <div style="margin-top:0.5rem;padding:0.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.375rem;font-size:0.875rem;">
                <div style="font-size:0.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.25rem;">
                  AI Machine Transcript (Verbatim)
                  @if($report->audio->transcript_language)
                    <span style="font-weight:400;text-transform:none;">({{ strtoupper($report->audio->transcript_language) }})</span>
                  @endif
                </div>
                <div style="color:#1e293b;line-height:1.4;">{{ $report->audio->transcript }}</div>
              </div>
            @endif

            @if($report->hasUnverifiedAudio())
              <form method="POST" action="{{ route('admin.reports.audio.verify', $report) }}" style="margin-top:0.75rem;padding:1rem;background:#fefce8;border:1px solid #fef08a;border-radius:0.5rem;">
                @csrf
                <label for="verified_description" style="display:block;font-size:0.875rem;font-weight:700;color:#854d0e;margin-bottom:0.35rem;">
                  Staff Verified Description <span style="color:var(--red);">*</span>
                </label>
                <p style="margin:0 0 0.5rem;font-size:0.8rem;color:#713f12;">
                  Listen to the audio recording above and confirm or transcribe what was reported. This will update the report description and unblock routing.
                </p>
                <textarea id="verified_description" name="description" rows="3" required minlength="5"
                          style="width:100%;padding:0.6rem;border:1px solid #ca8a04;border-radius:0.375rem;font-size:0.875rem;background:#fff;">{{ old('description', $report->description) }}</textarea>
                <div style="margin-top:0.5rem;display:flex;justify-content:flex-end;">
                  <button type="submit" class="review-button review-button-primary" style="background:#ca8a04;border-color:#a16207;color:#fff;">
                    <i class="fa-solid fa-check-double"></i> Verify and Update Description
                  </button>
                </div>
              </form>
            @endif
          </div>
        @endif
      </div>
    </details>
  </section>

  @if($hasPhotoMismatch)
    <section class="review-flag review-flag-amber" aria-label="Possible photo mismatch" style="margin-top: 0.75rem;">
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
  </div> {{-- End review-col-resident --}}

  {{-- Right: Case timeline & notes --}}
  <div class="review-col-timeline">
    <section class="review-workflow-card review-timeline-card">
      <div class="review-card-heading review-card-heading-static" style="padding-bottom:0.75rem; border-bottom: 1px solid var(--line);">
        <div style="display:flex;align-items:center;justify-content:space-between;width:100%;">
          <div>
            <span class="review-card-title">Case timeline &amp; notes</span>
            <span class="review-card-instruction">Audit trail, citizen interactions, and internal notes</span>
          </div>
          <span class="review-badge review-badge-blue">{{ count($activityEntries) }} event{{ count($activityEntries) === 1 ? '' : 's' }}</span>
        </div>
      </div>

      {{-- Internal Note Box --}}
      @unless($isCompleted || $emergencyActionOnly)
        <div class="review-composer-box" style="padding: 0.85rem 1.25rem; background: #f8fafc; border-bottom: 1px solid var(--line);">
          <form method="POST" action="{{ route('admin.reports.internal-notes.store', $report) }}" data-prevent-double-submit>
            @csrf
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-bottom:0.35rem;">
              <label for="timeline-internal-note" style="font-size:0.8rem;font-weight:700;color:#475569;">
                <i class="fa-solid fa-lock text-slate-500 mr-1" aria-hidden="true"></i> Add internal note <span style="font-weight:normal;color:#64748b;font-size:0.75rem;">(visible to staff only)</span>
              </label>
            </div>
            <div style="display:flex;gap:0.5rem;align-items:flex-end;">
              <textarea id="timeline-internal-note" name="body" rows="2" maxlength="3000" required placeholder="Record staff observations, internal coordination, or phone call notes..." style="flex:1;font-size:0.84rem;padding:0.5rem 0.65rem;border:1px solid var(--line);border-radius:8px;background:#fff;"></textarea>
              <button type="submit" class="btn btn-navy btn-small" style="padding:0.5rem 0.85rem;font-size:0.8rem;white-space:nowrap;font-weight:700;">
                Save note
              </button>
            </div>
          </form>
        </div>
      @endunless

      {{-- Unified Descending Event Feed --}}
      <div class="review-timeline-feed" style="padding:1.25rem;">
        <ol class="review-activity-list">
          @forelse($activityEntries as $activity)
            @php
              $isInternal = isset($activity['visible_to_resident']) ? !$activity['visible_to_resident'] : str_contains($activity['title'], '(Internal)');
            @endphp
            <li style="border-left: 3px solid {{ $isInternal ? '#94a3b8' : '#2563eb' }}; padding-left: 0.85rem; margin-bottom: 1.15rem; position: relative;">
              <div class="review-activity-heading" style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;">
                <div style="display:inline-flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
                  <strong>{{ $activity['title'] }}</strong>
                  @if($isInternal)
                    <span class="review-badge review-badge-gray" style="font-size:0.68rem;padding:0.1rem 0.35rem;"><i class="fa-solid fa-lock" aria-hidden="true"></i> Internal</span>
                  @else
                    <span class="review-badge review-badge-blue" style="font-size:0.68rem;padding:0.1rem 0.35rem;"><i class="fa-solid fa-eye" aria-hidden="true"></i> Public</span>
                  @endif
                </div>
                <time datetime="{{ $activity['at']?->toIso8601String() }}" style="font-size:0.75rem;color:#64748b;">{{ $activity['at']?->format('M d, Y h:i A') }}</time>
              </div>
              @if($activity['user'])
                <p class="review-activity-user" style="font-size:0.76rem;color:#475569;margin-top:0.15rem;"><i class="fa-solid fa-user-check" style="font-size:0.7rem;color:#94a3b8;margin-right:0.25rem;" aria-hidden="true"></i>{{ $activity['user'] }}</p>
              @endif
              @if($activity['body'])
                <p class="review-activity-body" style="font-size:0.84rem;color:#334155;margin-top:0.35rem;line-height:1.45;background:#fff;padding:0.5rem 0.75rem;border-radius:6px;border:1px solid #f1f5f9;">{{ $activity['body'] }}</p>
              @endif
            </li>
          @empty
            <li class="review-activity-empty">No activity recorded yet.</li>
          @endforelse
        </ol>
      </div>
    </section>
  </div> {{-- End review-col-timeline --}}
  </div> {{-- End review-top-grid --}}

  {{-- Bottom Stack: Review AI Suggestions below, and Route & Assign below that --}}
  <div class="review-bottom-stack space-y-6" style="display:flex;flex-direction:column;gap:1.5rem;">

  @if(! $emergencyActionOnly)
    <form id="review-form" method="POST" action="{{ route('admin.reports.review', $report) }}" class="space-y-6" style="display:flex;flex-direction:column;gap:1.25rem;">
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
            <div class="kebab" style="position:relative;">
              <button type="button" class="btn btn-outline btn-small" data-kebab-toggle aria-haspopup="true" aria-expanded="false" style="display:inline-flex;align-items:center;gap:0.4rem;font-size:0.8rem;font-weight:600;">
                <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
                <span>More actions</span>
                @if($unreadReplies > 0 || ($hasResidentReply && !session('info_thread_seen.'.$report->id)))
                  <span class="chat-notif-circle" data-chat-notif-circle title="New reply from resident">{{ $unreadReplies > 0 ? $unreadReplies : '!' }}</span>
                @endif
              </button>
              <div class="kebab-menu" hidden style="position:absolute;right:0;top:calc(100% + 4px);z-index:30;min-width:210px;background:#fff;border:1px solid var(--line);border-radius:10px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);padding:0.4rem 0;">
                @if($hasInfoRequests || in_array($report->status, ['submitted', 'under_review', 'reopened', 'routed', 'in_progress', 'needs_info'], true))
                  <button type="button" class="kebab-item"
                          data-chat-open data-report-id="{{ $report->id }}"
                          data-chat-tracking="{{ $report->tracking_code }}"
                          data-chat-category="{{ $report->category?->name ?? 'Category not assigned' }}"
                          data-chat-status="{{ \App\Models\Report::STATUSES[$report->status] ?? $report->status }}"
                          data-chat-summary="{{ $report->description }}{{ $report->purok ? ' — '.$report->purok->name : '' }}"
                          style="display:flex;align-items:center;gap:0.5rem;width:100%;padding:0.5rem 0.85rem;font-size:0.82rem;text-align:left;border:none;background:transparent;cursor:pointer;">
                    <i class="fa-solid fa-comment-dots text-blue-600" aria-hidden="true"></i>
                    <span>Ask Reporter for Info</span>
                  </button>
                @endif
                @if(in_array($report->status, ['submitted', 'under_review', 'reopened'], true))
                  <button type="button" class="kebab-item" data-open-dialog="duplicate-modal"
                          style="display:flex;align-items:center;gap:0.5rem;width:100%;padding:0.5rem 0.85rem;font-size:0.82rem;text-align:left;border:none;background:transparent;cursor:pointer;">
                    <i class="fa-solid fa-copy text-amber-600" aria-hidden="true"></i>
                    <span>Mark as duplicate</span>
                  </button>
                @endif
                @if(app(\App\Services\ReportStatusService::class)->canTransition($report->status, \App\Support\ReportStatus::NO_ACTION))
                  <button type="button" class="kebab-item" data-open-dialog="no-action-modal"
                          style="display:flex;align-items:center;gap:0.5rem;width:100%;padding:0.5rem 0.85rem;font-size:0.82rem;text-align:left;border:none;background:transparent;cursor:pointer;">
                    <i class="fa-solid fa-circle-xmark text-slate-500" aria-hidden="true"></i>
                    <span>Take no action</span>
                  </button>
                @endif
                @if(!$report->is_emergency && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true))
                  <button type="submit" form="kebab-emergency-form" class="kebab-item"
                          style="display:flex;align-items:center;gap:0.5rem;width:100%;padding:0.5rem 0.85rem;font-size:0.82rem;text-align:left;border:none;background:transparent;cursor:pointer;color:#dc2626;">
                    <i class="fa-solid fa-triangle-exclamation text-red-600" aria-hidden="true"></i>
                    <span>Flag as emergency</span>
                  </button>
                @endif
                @includeIf('admin.cases.partials.convert-button', ['report' => $report])
              </div>
            </div>
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
          <div class="review-retry-form">
            <button type="submit" form="review-retry-ai-form" class="review-text-link">Retry AI analysis</button>
          </div>
        @endif

        <div class="review-triage-grid">
          {{-- Row 1: Category, Urgency, Purok, Landmark (4-column on desktop) --}}
          <div class="review-triage-sidebar">

            {{-- Field 1: Category --}}
            <div class="review-field-card" data-review-row data-field="category"
                 data-resident-value="{{ $residentCategoryId ?? '' }}"
                 data-resident-label="{{ $residentCategory?->name ?? '' }}"
                 data-ai-value="{{ $aiCategoryId ?? '' }}"
                 data-ai-label="{{ $aiCategoryLabel ?? '' }}"
                 data-ai-present="{{ $aiCategoryPresent ? 'true' : 'false' }}"
                 data-review-required="{{ $categoryReviewRequired ? 'true' : 'false' }}"
                 data-reviewed="{{ $alreadyReviewed || ! $categoryReviewRequired ? 'true' : 'false' }}">
              <div class="review-field-card-header">
                <label for="final-category" class="review-field-card-title">Category</label>
                <div style="display:flex;align-items:center;gap:0.35rem;flex-wrap:wrap;">
                  @if(!empty($voiceProvenance['category']['filled']))
                    @if(!empty($voiceProvenance['category']['edited']))
                      <span class="review-badge review-badge-amber" style="font-size:0.72rem;"><i class="fa-solid fa-pen" style="font-size:0.65rem;"></i> Edited after recording</span>
                    @else
                      <span class="review-badge review-badge-blue" style="font-size:0.72rem;"><i class="fa-solid fa-microphone" style="font-size:0.65rem;"></i> From recording</span>
                    @endif
                  @endif
                  <span class="review-badge review-badge-outline-green" data-comparison-badge hidden></span>
                  <span class="review-badge {{ $categoryFinalState === 'Accepted' ? 'review-badge-green' : ($categoryFinalState === 'Edited' ? 'review-badge-amber' : ($categoryFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                        data-final-state>{{ $categoryFinalState }}</span>
                </div>
              </div>

              <div class="review-field-card-values">
                <div>
                  <span>Resident:</span>
                  @if($residentCategory)
                    <strong data-resident-display>{{ $residentCategory->name }}</strong>
                  @else
                    <span class="review-not-provided">Not chosen by resident</span>
                  @endif
                </div>
                <div>
                  <span>AI:</span>
                  <strong data-ai-value-display @if($aiReady && ! $aiCategoryLabel) class="review-not-provided" @endif>
                    @if($aiReady){{ $aiCategoryLabel ?? 'Not provided' }}
                    @elseif($aiPending)Waiting for suggestion
                    @else Not available
                    @endif
                  </strong>
                </div>
              </div>

              <div>
                <select id="final-category" name="category_id" data-final-control data-required-field required @disabled($emergencyActionOnly)>
                  <option value="">Select category</option>
                  @foreach($categories as $category)
                    <option value="{{ $category->id }}" @selected((string) $finalCategoryId === (string) $category->id)>{{ $category->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="review-field-card-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.35rem;">
                <span class="review-gate-field-hint {{ $finalCategoryId ? 'is-ready' : 'is-missing' }}" data-gate-hint="category">
                  <i class="fa-solid {{ $finalCategoryId ? 'fa-check' : 'fa-lock' }}" aria-hidden="true"></i>
                  <span>{{ $finalCategoryId ? 'Ready to route' : 'Required to route' }}</span>
                </span>
                <div class="review-row-actions">
                  <button type="button" class="review-text-link" data-use-ai @disabled($emergencyActionOnly) @if(! $aiCategoryId || ! $aiCategoryPresent || $aiCategoryId === $residentCategoryId) hidden @endif>Use AI</button>
                  <button type="button" class="review-text-link" data-use-resident @disabled($emergencyActionOnly) @if($residentCategoryId === null || $residentCategoryId === $aiCategoryId) hidden @endif>Use resident's</button>
                  <button type="button" class="review-text-link" data-accept-row @disabled($emergencyActionOnly) @if(! $aiCategoryId || ! $aiCategoryPresent) hidden @endif>Accept</button>
                  <button type="button" class="review-text-link" data-undo-row hidden>Undo</button>
                </div>
              </div>
              @if($residentCategory && $aiCategoryPresent && (string) $residentCategoryId !== (string) $aiCategoryId)
                <small class="review-resident-choice" style="font-size:0.75rem;color:#b45309;">Resident chose: {{ $residentCategory->name }}</small>
              @endif
            </div>

            {{-- Field 2: Urgency --}}
            <div class="review-field-card" data-review-row data-field="urgency"
                 data-resident-value=""
                 data-resident-label=""
                 data-ai-value="{{ $aiUrgency ?? '' }}"
                 data-ai-label="{{ $aiUrgency ?? '' }}"
                 data-ai-present="{{ $aiUrgencyPresent ? 'true' : 'false' }}"
                 data-review-required="{{ $urgencyReviewRequired ? 'true' : 'false' }}"
                 data-reviewed="{{ $alreadyReviewed || ! $urgencyReviewRequired ? 'true' : 'false' }}">
              <div class="review-field-card-header">
                <label for="final-urgency" class="review-field-card-title">Urgency</label>
                <div style="display:flex;align-items:center;gap:0.35rem;">
                  <span class="review-badge review-badge-outline-blue" data-comparison-badge hidden></span>
                  <span class="review-badge {{ $urgencyFinalState === 'Accepted' ? 'review-badge-green' : ($urgencyFinalState === 'Edited' ? 'review-badge-amber' : ($urgencyFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                        data-final-state>{{ $urgencyFinalState }}</span>
                </div>
              </div>

              <div class="review-field-card-values">
                <div>
                  <span>Resident:</span>
                  <span class="review-not-provided" data-resident-display>Not provided</span>
                </div>
                <div>
                  <span>AI:</span>
                  <strong data-ai-value-display @if($aiReady && ! $aiUrgencyPresent) class="review-not-provided" @endif>
                    @if($aiReady){{ $aiUrgency ?? 'Not provided' }}
                    @elseif($aiPending)Waiting for suggestion
                    @else Not available
                    @endif
                  </strong>
                </div>
              </div>

              <div>
                <select id="final-urgency" name="urgency" data-final-control data-required-field required @disabled($emergencyActionOnly)>
                  <option value="">Select urgency</option>
                  @foreach(['Low', 'Medium', 'High', 'Critical'] as $urgency)
                    <option value="{{ $urgency }}" @selected($finalUrgency === $urgency)>{{ $urgency }}</option>
                  @endforeach
                </select>
              </div>

              <div class="review-field-card-footer" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.35rem;">
                <span class="review-gate-field-hint {{ $finalUrgency ? 'is-ready' : 'is-missing' }}" data-gate-hint="urgency">
                  <i class="fa-solid {{ $finalUrgency ? 'fa-check' : 'fa-lock' }}" aria-hidden="true"></i>
                  <span>{{ $finalUrgency ? 'Ready to route' : 'Required to route' }}</span>
                </span>
                <div class="review-row-actions">
                  <button type="button" class="review-text-link" data-use-ai @disabled($emergencyActionOnly) @if(! $aiUrgencyPresent) hidden @endif>Use AI</button>
                  <button type="button" class="review-text-link" data-use-resident @disabled($emergencyActionOnly) hidden>Use resident's</button>
                  <button type="button" class="review-text-link" data-accept-row @disabled($emergencyActionOnly) @if(! $aiUrgencyPresent) hidden @endif>Accept</button>
                  <button type="button" class="review-text-link" data-undo-row hidden>Undo</button>
                </div>
              </div>
            </div>

            {{-- Field 3: Purok --}}
            <div class="review-field-card" data-review-row data-field="purok"
                 data-resident-value="{{ $residentPurok?->id ?? '' }}"
                 data-resident-label="{{ $residentPurok?->name ?? '' }}"
                 data-ai-value="" data-ai-label="" data-ai-present="false"
                 data-review-required="false" data-reviewed="true">
              <div class="review-field-card-header">
                <label for="final-purok" class="review-field-card-title">Purok</label>
                <div style="display:flex;align-items:center;gap:0.35rem;flex-wrap:wrap;">
                  @if(!empty($voiceProvenance['purok']['filled']))
                    @if(!empty($voiceProvenance['purok']['edited']))
                      <span class="review-badge review-badge-amber" style="font-size:0.72rem;"><i class="fa-solid fa-pen" style="font-size:0.65rem;"></i> Edited after recording</span>
                    @else
                      <span class="review-badge review-badge-blue" style="font-size:0.72rem;"><i class="fa-solid fa-microphone" style="font-size:0.65rem;"></i> From recording</span>
                    @endif
                  @endif
                  <span class="review-badge review-badge-gray" data-final-state>Resident</span>
                </div>
              </div>
              <div class="review-field-card-values">
                <div>
                  <span>Resident:</span>
                  @if($residentPurok)
                    <strong data-resident-display>{{ $residentPurok->name }}</strong>
                  @else
                    <span class="review-not-provided">Not provided</span>
                  @endif
                </div>
                <span data-ai-value-display class="sr-only"></span>
                <span data-comparison-badge hidden class="sr-only"></span>
              </div>
              <div>
                <select id="final-purok" name="purok_id" data-final-control @disabled($emergencyActionOnly)>
                  <option value="">Select purok</option>
                  @foreach($puroks as $purok)
                    <option value="{{ $purok->id }}" @selected((string) $finalPurokId === (string) $purok->id)>{{ $purok->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            {{-- Field 4: Landmark --}}
            <div class="review-field-card" data-review-row data-field="landmark"
                 data-resident-value="{{ $residentLandmark ?? '' }}"
                 data-resident-label="{{ $residentLandmark ?? '' }}"
                 data-ai-value="" data-ai-label="" data-ai-present="false"
                 data-review-required="false" data-reviewed="true">
              <div class="review-field-card-header">
                <label for="final-landmark" class="review-field-card-title">Landmark</label>
                <div style="display:flex;align-items:center;gap:0.35rem;flex-wrap:wrap;">
                  @if(!empty($voiceProvenance['landmark']['filled']))
                    @if(!empty($voiceProvenance['landmark']['edited']))
                      <span class="review-badge review-badge-amber" style="font-size:0.72rem;"><i class="fa-solid fa-pen" style="font-size:0.65rem;"></i> Edited after recording</span>
                    @else
                      <span class="review-badge review-badge-blue" style="font-size:0.72rem;"><i class="fa-solid fa-microphone" style="font-size:0.65rem;"></i> From recording</span>
                    @endif
                  @endif
                  <span class="review-badge review-badge-gray" data-final-state>Resident</span>
                </div>
              </div>
              <div class="review-field-card-values">
                <div>
                  <span>Resident:</span>
                  @if(trim((string) $residentLandmark) !== '')
                    <strong data-resident-display>{{ $residentLandmark }}</strong>
                  @else
                    <span class="review-not-provided">Not provided</span>
                  @endif
                </div>
                <span data-ai-value-display class="sr-only"></span>
                <span data-comparison-badge hidden class="sr-only"></span>
              </div>
              <div>
                <input id="final-landmark" type="text" name="landmark" maxlength="255"
                       value="{{ $finalLandmark }}" placeholder="Enter landmark or nearby reference" data-final-control @disabled($emergencyActionOnly)>
              </div>
            </div>

          </div> {{-- End review-triage-sidebar (display:contents) --}}

          {{-- Row 2: Incident Description (full width spanning all columns) --}}
          <div class="review-triage-main review-triage-description-row" data-review-row data-field="description"
               data-resident-value="{{ $residentDescription }}"
               data-resident-label="{{ $residentDescription }}"
               data-ai-value="{{ $aiDescription ?? '' }}"
               data-ai-label="{{ $aiDescription ?? '' }}"
               data-ai-present="{{ $aiDescriptionPresent ? 'true' : 'false' }}"
               data-review-required="true"
               data-reviewed="{{ $descriptionReviewed ? 'true' : 'false' }}">
            <div class="review-field-card" style="display: flex; flex-direction: column;">
              <div class="review-field-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 0.5rem;">
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                  <span class="review-field-card-title" style="font-size:0.92rem;">Incident Description</span>
                  @if(!empty($voiceProvenance['description']['filled']))
                    @if(!empty($voiceProvenance['description']['edited']))
                      <span class="review-badge review-badge-amber" style="font-size:0.72rem;"><i class="fa-solid fa-pen" style="font-size:0.65rem;"></i> Edited after recording</span>
                    @else
                      <span class="review-badge review-badge-blue" style="font-size:0.72rem;"><i class="fa-solid fa-microphone" style="font-size:0.65rem;"></i> From recording</span>
                    @endif
                  @endif
                  @if($voiceAudio && $voiceAudio->ai_description && trim($voiceAudio->ai_description) !== trim($residentDescription))
                    <span class="review-badge review-badge-amber" style="font-size:0.72rem;" title="Resident modified description after voice recording"><i class="fa-solid fa-user-pen"></i> Edited by resident</span>
                  @endif
                  <span class="review-badge review-badge-outline-green" data-comparison-badge hidden></span>
                  <span class="review-badge {{ $descriptionFinalState === 'Accepted' ? 'review-badge-green' : ($descriptionFinalState === 'Edited' ? 'review-badge-amber' : ($descriptionFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                        data-final-state>{{ $descriptionFinalState }}</span>
                </div>
              </div>

              {{-- Comparison Boxes (Side by side for resident text vs AI diff) --}}
              <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(240px, 1fr));gap:0.75rem;margin-top:0.4rem;">
                <div>
                  <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:0.04em;display:block;margin-bottom:0.25rem;">
                    Original Resident Text
                  </span>
                  <div style="padding:0.5rem 0.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;font-size:0.84rem;line-height:1.45;color:#334155;min-height:80px;max-height:120px;overflow-y:auto;">
                    @if(trim((string) $residentDescription) !== '')
                      <span data-resident-display>{{ $residentDescription }}</span>
                    @else
                      <span class="review-not-provided">Not provided</span>
                    @endif
                  </div>
                </div>

                <div>
                  <span style="font-size:0.72rem;font-weight:700;text-transform:uppercase;color:#2563eb;letter-spacing:0.04em;display:block;margin-bottom:0.25rem;">
                    AI Suggestion &amp; Diff
                  </span>
                  <div style="padding:0.5rem 0.75rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;font-size:0.84rem;line-height:1.45;color:#1e3a8a;min-height:80px;max-height:120px;overflow-y:auto;">
                    @if($aiReady && $aiDescriptionPresent)
                      <div class="review-description-diff" data-description-diff data-original="{{ $residentDescription }}" data-ai="{{ $aiDescription }}"></div>
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
                  </div>
                </div>
              </div>

              {{-- Action Buttons directly below comparison boxes --}}
              <div class="review-row-actions" style="margin-top:0.5rem;margin-bottom:0.35rem;padding:0.4rem 0.65rem;background:#f8fafc;border-radius:8px;border:1px solid #f1f5f9;display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                <span style="font-size:0.72rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;">Actions:</span>
                <button type="button" class="review-text-link" data-accept-row @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif style="font-weight:700;color:#16a34a;">
                  <i class="fa-solid fa-check" aria-hidden="true"></i> Accept AI
                </button>
                <button type="button" class="review-text-link" data-use-ai @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>
                  Use AI
                </button>
                <button type="button" class="review-text-link" data-append-ai @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>
                  <i class="fa-solid fa-plus" aria-hidden="true"></i> Append AI summary
                </button>
                <button type="button" class="review-text-link" data-use-original @disabled($emergencyActionOnly)>
                  Use original
                </button>
                <button type="button" class="review-text-link" data-use-resident @disabled($emergencyActionOnly) @if(! $residentDescription || $residentDescription === $aiDescription) hidden @endif>
                  Use resident's
                </button>
                <button type="button" class="review-text-link" data-undo-row hidden style="color:#64748b;">
                  <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Undo
                </button>
              </div>

              {{-- Final Editable Description --}}
              <div style="margin-top:0.25rem;display:flex;flex-direction:column;flex:1;">
                <label for="final-description" style="font-size:0.74rem;font-weight:700;text-transform:uppercase;color:#1e293b;letter-spacing:0.04em;display:block;margin-bottom:0.25rem;">
                  Final Description (Official Record)
                </label>
                <textarea id="final-description" name="staff_description" rows="3" maxlength="2000"
                          style="width:100%;font-size:0.86rem;line-height:1.45;padding:0.45rem 0.65rem;border:1px solid var(--line);border-radius:8px;resize:vertical;min-height:75px;max-height:160px;"
                          data-final-control @disabled($emergencyActionOnly)>{{ $finalDescription }}</textarea>
              </div>
            </div>
          </div> {{-- End review-triage-description-row --}}
        </div> {{-- End review-triage-grid --}}

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

        @if(! $isAfterRoute && ! $emergencyActionOnly)
          <div class="review-actions-row">
            <span class="review-gate-hint review-card2-gate-summary" data-gate-reason-card2 aria-live="polite"></span>
            @if($aiReady)
              <button type="button" class="review-button review-button-blue" data-accept-all>
                <i class="fa-solid fa-check-double" aria-hidden="true"></i> Accept all AI suggestions
              </button>
            @else
              <button type="button" class="review-button review-button-blue" data-accept-all hidden disabled>
                Accept all AI suggestions
              </button>
            @endif
          </div>
        @endif
      </div>
    </details>
  </section>

  @if(! $isAfterRoute && ! $emergencyActionOnly)
    <section class="review-workflow-card overflow-hidden">
      <div class="review-card-heading review-card-heading-static">
        <span class="review-card-number" aria-hidden="true">3</span>
        <span class="review-card-heading-copy">
          <span class="review-card-title">Route and assign</span>
          <span class="review-card-instruction">Confirm who should handle it.</span>
        </span>
      </div>
      <div class="review-card-content space-y-4">
        @php
          $ruleWithin = (bool) ($jurisdiction['within_authority'] ?? true);
          $initialMode = $ruleWithin ? 'within' : 'beyond';
          $defaultAgency = $externalOffices->firstWhere('id', $jurisdiction['office']?->id) ?? $externalOffices->first();
          $defaultLetter = old('referral_letter', $letterTemplates[$defaultAgency?->id]['letter'] ?? '');
          $defaultResidentMessage = old('resident_message', $letterTemplates[$defaultAgency?->id]['resident_message'] ?? '');
          $defaultAgencyId = $defaultAgency?->id;
          $matchedRuleName = $jurisdiction['jurisdiction_note'] ?? ($routingDecision['reason'] ?? 'Category default');
          $isSuggestedMatch = $routingDecision && (string) $officeDefault === (string) ($routingDecision['office_id'] ?? '');

          // Quick fill chips config array
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

          $urgencyBadgeConfig = match($priorityLabel) {
            'Critical' => ['dot' => 'bg-red-600', 'pill' => 'bg-red-50 text-red-800 border-red-200'],
            'High' => ['dot' => 'bg-amber-500', 'pill' => 'bg-amber-50 text-amber-800 border-amber-200'],
            'Medium' => ['dot' => 'bg-blue-500', 'pill' => 'bg-blue-50 text-blue-800 border-blue-200'],
            default => ['dot' => 'bg-slate-400', 'pill' => 'bg-slate-50 text-slate-700 border-slate-200'],
          };
        @endphp

        {{-- Hidden JSON & inputs needed by JS --}}
        <input type="hidden" name="authority_mode" value="{{ $initialMode }}" data-authority-mode-input>
        <script type="application/json" data-routing-map>@json($routingDecisions)</script>
        <script type="application/json" data-letter-templates>@json($letterTemplates)</script>

        {{-- ── 1. Status Strip (Replaces old banner and priority line) ── --}}
        <div class="review-mode-banner rounded-xl border border-slate-200 border-l-[3px] p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 transition-all {{ $ruleWithin ? 'border-l-emerald-600 bg-emerald-50/50 border-emerald-200 review-mode-banner-within' : 'border-l-blue-600 bg-blue-50/50 border-blue-200 review-mode-banner-beyond' }}"
             data-mode-banner data-rule-mode="{{ $initialMode }}">
          <div class="flex items-start gap-3 min-w-0">
            {{-- Soft Circle Icon --}}
            <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 transition-colors {{ $ruleWithin ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}" data-mode-icon>
              @if($ruleWithin)
                <i class="fa-solid fa-shield-halved text-base" aria-hidden="true"></i>
              @else
                <i class="fa-solid fa-arrow-up-right-from-square text-base" aria-hidden="true"></i>
              @endif
            </div>

            <div>
              <div class="flex items-center gap-2.5 flex-wrap">
                <strong class="text-sm font-bold text-slate-900" data-mode-badge>
                  {{ $ruleWithin ? 'Within barangay authority' : 'Beyond barangay authority' }}
                </strong>

                {{-- Prominent Urgency Pill with Colored Dot --}}
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border {{ $urgencyBadgeConfig['pill'] }}" data-urgency-pill>
                  <span class="w-2 h-2 rounded-full {{ $urgencyBadgeConfig['dot'] }}" data-urgency-dot></span>
                  <span>Urgency: <span data-urgency-label>{{ $priorityLabel }}</span></span>
                </span>
                @if($report->due_at)
                  <span class="text-xs text-slate-400 font-medium">Due {{ $report->due_at->format('M d, Y') }}</span>
                @endif
              </div>

              {{-- Dynamic Description --}}
              <p class="text-xs text-slate-600 mt-1 leading-relaxed" data-mode-note>
                @if($ruleWithin)
                  Matched rule: <span data-matched-rule-text>{{ $matchedRuleName }}</span>. Assign it to an office and staff member.
                @else
                  This will be referred to an external agency. A referral letter is sent and follow-up is tracked.
                @endif
              </p>
            </div>
          </div>

          {{-- Segmented Mode Toggle --}}
          <div class="inline-flex p-1 bg-slate-200/70 rounded-lg text-xs self-start md:self-center flex-shrink-0" role="tablist" aria-label="Authority mode toggle">
            <button type="button" class="mode-seg-btn px-3 py-1.5 rounded-md font-semibold transition-all {{ $ruleWithin ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                    data-mode-btn="within">
              Within barangay
            </button>
            <button type="button" class="mode-seg-btn px-3 py-1.5 rounded-md font-semibold transition-all {{ !$ruleWithin ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900' }}"
                    data-mode-btn="beyond">
              Refer to agency
            </button>
          </div>
          {{-- Hidden original toggle button to keep any external hooks compatible --}}
          <button type="button" class="sr-only" data-mode-toggle data-other-mode="{{ $ruleWithin ? 'beyond' : 'within' }}">
            {{ $ruleWithin ? "This isn't barangay authority" : 'Handle within the barangay instead' }}
          </button>
        </div>

        {{-- ── 2. MODE A: Within Barangay Authority ── --}}
        <div class="review-mode-block space-y-4" data-mode-block="within" @if(!$ruleWithin) hidden @endif>
          {{-- Assignment Card --}}
          <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 sm:p-5 space-y-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Assignment</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {{-- Office Select --}}
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label for="office_id" class="text-xs font-bold text-slate-700 flex items-center gap-2">
                    <span>Office</span>
                    <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200" data-suggested-badge @if(!$isSuggestedMatch) style="display:none;" @endif>
                      <i class="fa-solid fa-check text-[9px]" aria-hidden="true"></i> Suggested
                    </span>
                  </label>
                </div>
                <div class="relative">
                  <select id="office_id" name="office_id" class="w-full h-11 text-xs rounded-lg border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-8" data-office-select>
                    <option value="">Select an office</option>
                    @foreach($offices as $office)
                      <option value="{{ $office->id }}"
                              data-default-assignee="{{ $office->default_assignee_id ?? '' }}"
                              @selected((string) $officeDefault === (string) $office->id)>{{ $office->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-blue-700 font-medium mt-1.5" data-routing-reason>
                  <i class="fa-solid fa-route text-blue-600" aria-hidden="true"></i>
                  <span>
                    @if($routingDecision)
                      Rule: {{ $categories->firstWhere('id', $selectedCategoryId)?->name }} → {{ $routingDecision['office_name'] ?? 'No office' }}
                    @else
                      Select a category to see the routing recommendation.
                    @endif
                  </span>
                </div>
                <p class="review-field-hint text-xs text-slate-400 mt-0.5">The department that handles this report</p>
              </div>

              {{-- Assigned to Select --}}
              <div>
                <label for="assigned_to" class="block text-xs font-bold text-slate-700 mb-1">Assigned to</label>
                <div class="relative">
                  <select id="assigned_to" name="assigned_to" class="w-full h-11 text-xs rounded-lg border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-8" data-assigned-select>
                    <option value="">Unassigned</option>
                    @foreach($staffUsers as $staffUser)
                      <option value="{{ $staffUser->id }}" @selected((string) $assignedDefault === (string) $staffUser->id)>{{ $staffUser->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="flex items-center gap-1.5 text-xs text-amber-700 font-medium mt-1.5" data-unassigned-note @if($assignedDefault) style="display:none;" @endif>
                  <i class="fa-solid fa-circle-exclamation text-amber-600 text-[11px]" aria-hidden="true"></i>
                  <span>No one is responsible yet.</span>
                </div>
                <p class="review-field-hint text-xs text-slate-400 mt-0.5">The staff member responsible for follow-through</p>
              </div>
            </div>
          </div>

          {{-- Message to resident Card (Mode A) --}}
          <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between">
              <label for="review_note" class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <span>Message to resident</span>
                <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">Optional</span>
              </label>
            </div>
            <p class="text-xs text-slate-500">Sent to the reporter when you confirm.</p>

            {{-- Quick-fill chips --}}
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Quick fill:</span>
              @foreach($residentMessageChips['within'] as $chip)
                <button type="button" class="btn-quick-fill text-xs px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors" data-chip-target="review_note" data-chip-text="{{ $chip }}">
                  {{ $chip }}
                </button>
              @endforeach
            </div>

            <textarea id="review_note" name="review_note" rows="3" maxlength="1000" class="w-full text-xs rounded-lg border-slate-300 p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="e.g. The assigned office will inspect within 3 working days.">{{ $defaultMessage }}</textarea>
          </div>
        </div>

        {{-- ── 3. MODE B: Beyond Barangay Authority (Agency Referral) ── --}}
        <div class="review-mode-block space-y-4" data-mode-block="beyond" @if($ruleWithin) hidden @endif>
          {{-- Agency & Follow-up Card --}}
          <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 sm:p-5 space-y-4">
            <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">External Agency &amp; Follow-up</div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              {{-- Agency Select --}}
              <div>
                <label for="agency_id" class="block text-xs font-bold text-slate-700 mb-1">External agency</label>
                <div class="relative">
                  <select id="agency_id" name="office_id" class="w-full h-11 text-xs rounded-lg border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-8" data-agency-select @disabled($ruleWithin)>
                    <option value="">Select an agency</option>
                    @foreach($externalOffices as $externalOffice)
                      <option value="{{ $externalOffice->id }}" @selected((string) $defaultAgencyId === (string) $externalOffice->id)>{{ $externalOffice->name }}</option>
                    @endforeach
                  </select>
                </div>
              </div>

              {{-- Follow-up Owner Select --}}
              <div>
                <label for="follow_up_owner_id" class="block text-xs font-bold text-slate-700 mb-1">Follow-up owner</label>
                <div class="relative">
                  <select id="follow_up_owner_id" name="follow_up_owner_id" class="w-full h-11 text-xs rounded-lg border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 pr-8" @disabled($ruleWithin)>
                    <option value="">Unassigned</option>
                    @foreach($staffUsers as $staffUser)
                      <option value="{{ $staffUser->id }}" @selected((string) old('follow_up_owner_id', $currentUserId) === (string) $staffUser->id)>{{ $staffUser->name }}</option>
                    @endforeach
                  </select>
                </div>
                <p class="text-xs text-slate-400 mt-1">Staff member responsible for following up with the agency.</p>
              </div>
            </div>

            {{-- Agency Contact Details Box --}}
            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs space-y-1.5" id="agency-contact-box">
              <div class="flex items-center gap-2 text-slate-700">
                <i class="fa-solid fa-user w-4 text-slate-400 text-center" aria-hidden="true"></i>
                <span data-agency-person>{{ $defaultAgency->contact_person ?? 'Not provided' }}</span>
              </div>
              <div class="flex items-center gap-2 text-slate-700">
                <i class="fa-solid fa-envelope w-4 text-slate-400 text-center" aria-hidden="true"></i>
                <span data-agency-email>{{ $defaultAgency->contact_email ?? 'Not provided' }}</span>
              </div>
              <div class="flex items-center gap-2 text-slate-700">
                <i class="fa-solid fa-phone w-4 text-slate-400 text-center" aria-hidden="true"></i>
                <span data-agency-phone>{{ $defaultAgency->contact_phone ?? 'Not provided' }}</span>
              </div>
              <div class="flex items-center gap-2 text-slate-700">
                <i class="fa-solid fa-location-dot w-4 text-slate-400 text-center" aria-hidden="true"></i>
                <span data-agency-address>{{ $defaultAgency->address ?? 'Not provided' }}</span>
              </div>
              {{-- Hidden original element so legacy JS callers don't fail --}}
              <span class="sr-only" data-agency-info></span>
            </div>
          </div>

          {{-- Referral Letter Card --}}
          <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between">
              <div class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Referral Letter</div>
              <div class="flex items-center gap-2">
                <button type="button" class="btn btn-ghost btn-small text-xs py-1 px-2.5 text-blue-700 font-semibold" id="btn-edit-letter">
                  <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> <span id="btn-edit-letter-text">Edit letter</span>
                </button>
                <button type="button" class="btn btn-ghost btn-small text-xs py-1 px-2.5 text-slate-500 hover:text-slate-800" id="btn-reset-letter" style="display:none;">
                  <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reset to template
                </button>
                <button type="button" class="btn btn-navy btn-small text-xs py-1 px-3" id="btn-done-letter" style="display:none;">
                  <i class="fa-solid fa-check" aria-hidden="true"></i> Done
                </button>
              </div>
            </div>

            {{-- Paper-style Letter Preview (Default) --}}
            <div id="letter-preview-card" class="bg-slate-50/70 border border-slate-200 rounded-xl p-5 font-sans text-xs sm:text-sm leading-relaxed text-slate-800 max-w-prose shadow-inner max-h-[340px] overflow-y-auto whitespace-pre-wrap select-text">
              <div data-letter-preview-text>{{ $defaultLetter }}</div>
            </div>

            {{-- Letter Textarea (Edit Mode) --}}
            <div id="letter-edit-box" style="display:none;">
              <textarea id="referral_letter" name="referral_letter" rows="12" maxlength="20000"
                        class="w-full text-xs font-mono p-3 rounded-lg border-slate-300 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 leading-relaxed"
                        data-referral-letter @disabled($ruleWithin)>{{ $defaultLetter }}</textarea>
            </div>
          </div>

          {{-- Message to resident Card (Mode B) --}}
          <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 sm:p-5 space-y-3">
            <div class="flex items-center justify-between">
              <label for="resident_message" class="text-xs font-bold text-slate-700 flex items-center gap-1.5">
                <span>Message to resident</span>
                <span class="text-[10px] font-semibold text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded">Optional</span>
              </label>
            </div>
            <p class="text-xs text-slate-500">Sent to the reporter when you confirm.</p>

            {{-- Quick-fill chips --}}
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider mr-1">Quick fill:</span>
              @foreach($residentMessageChips['beyond'] as $chip)
                <button type="button" class="btn-quick-fill text-xs px-2.5 py-1 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-200 transition-colors" data-chip-target="resident_message" data-chip-text="{{ $chip }}">
                  {{ $chip }}
                </button>
              @endforeach
            </div>

            <textarea id="resident_message" name="resident_message" rows="3" maxlength="1000"
                      class="w-full text-xs rounded-lg border-slate-300 p-2.5 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      data-resident-message @disabled($ruleWithin) placeholder="e.g. We referred this report to the appropriate agency.">{{ $defaultResidentMessage }}</textarea>
          </div>
        </div>

        {{-- ── 4. Sticky Bottom Action Bar ── --}}
        <div class="sticky bottom-0 z-20 bg-white/95 backdrop-blur border-t border-slate-200 -mx-4 -mb-4 sm:-mx-6 sm:-mb-6 p-4 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 shadow-[0_-4px_16px_rgba(0,0,0,0.06)] rounded-b-2xl">
          <div class="flex items-center gap-2 min-w-0">
            <div class="flex items-center gap-1.5 text-xs font-bold text-slate-800 truncate" data-action-summary>
              <i class="fa-solid fa-arrow-right-to-bracket text-blue-600" aria-hidden="true"></i>
              <span data-action-summary-text>
                @if($ruleWithin)
                  Route to {{ $officeDefault ? ($offices->firstWhere('id', $officeDefault)?->name ?? 'selected office') : 'selected office' }} · {{ $priorityLabel }}
                @else
                  Refer to {{ $defaultAgency?->name ?? 'external agency' }} · {{ $priorityLabel }}
                @endif
              </span>
            </div>
            <span class="review-gate-hint text-xs ml-2" data-gate-reason aria-live="polite"></span>
          </div>

          <div class="flex items-center gap-2 flex-shrink-0 self-end sm:self-auto">
            <a href="{{ route('admin.reports.index') }}" class="btn btn-ghost btn-small text-xs py-2 px-3">Cancel</a>
            
            @if($isRouteable)
              <button type="submit" form="review-form" name="intent" value="route" class="btn btn-gold btn-small text-xs font-bold py-2 px-4 flex items-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" data-route-submit @if(!$ruleWithin) style="display:none;" @endif>
                <i class="fa-solid fa-check" aria-hidden="true"></i>
                <span>Confirm and route</span>
              </button>
            @endif

            <button type="submit" form="review-form" name="intent" value="referral" class="btn btn-gold btn-small text-xs font-bold py-2 px-4 flex items-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" data-referral-submit @if($ruleWithin) style="display:none;" @endif>
              <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
              <span>Prepare referral</span>
            </button>
          </div>
        </div>

        @if($isOverdue && ! $report->escalated_at)
          <div class="review-escalate-form">
            <button type="submit" form="review-escalate-form" class="review-button review-button-outline">Escalate overdue report</button>
          </div>
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
  @endif

  {{-- Auxiliary action forms placed outside #review-form to avoid nested HTML form corruption --}}
  @if(!$report->is_emergency && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true))
    <form id="kebab-emergency-form" method="POST" action="{{ route('admin.reports.emergency', $report) }}" data-confirm="Flag this report as an emergency concern?" class="hidden" style="display:none;">
      @csrf
      @method('PATCH')
      <input type="hidden" name="confirm" value="1">
    </form>
  @endif

  @if($aiUnavailable && $report->ai_status === 'failed' && ! $emergencyActionOnly)
    <form id="review-retry-ai-form" method="POST" action="{{ route('admin.reports.retry-ai', $report) }}" class="hidden" style="display:none;">
      @csrf
    </form>
  @endif

  @if($isOverdue && ! $report->escalated_at)
    <form id="review-escalate-form" method="POST" action="{{ route('admin.reports.escalate', $report) }}" class="hidden" style="display:none;">
      @csrf
    </form>
  @endif

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
    @endif

    @if(app(\App\Services\ReportStatusService::class)->canTransition($report->status, \App\Support\ReportStatus::NO_ACTION))
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
          <p class="review-field-hint">
            This will detach the report from its duplicate parent and return it to <strong>Under Review</strong> status so it can be handled independently.
          </p>
          <label for="unmerge-note" style="font-weight: 600; font-size: 0.85rem; display: block; margin-bottom: 0.35rem;">Reason for unmerging</label>
          <textarea id="unmerge-note" name="note" rows="3" required minlength="5" maxlength="500" placeholder="Explain why this report is not a duplicate and should be returned to review..."></textarea>
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
          <p class="review-field-hint">
            Use when barangay tanods, emergency responders, or personnel directly attended to and addressed the emergency situation on-site. The report will transition to <strong>In Progress</strong> for ongoing tracking.
          </p>
          <label for="emergency-handled-note" style="font-weight: 600; font-size: 0.85rem; display: block; margin-bottom: 0.35rem;">Action taken note</label>
          <textarea id="emergency-handled-note" name="note" rows="3" required minlength="5" maxlength="500" placeholder="Describe the immediate on-site response and current situation (e.g. Tanods responded and secured the area)..."></textarea>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary">Move to In Progress</button>
        </footer>
      </form>
    </dialog>
  @endif

  {{-- Shared chat modal for "Ask for info" from kebab menu --}}
  @include('admin.reports.partials.info-thread-modal')

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
          <div class="review-actions-row" style="margin-top: 0.75rem;">
            <button type="button" class="review-button review-button-outline" data-open-dialog="unmerge-modal">
              <i class="fa-solid fa-code-merge" aria-hidden="true"></i> Unmerge report
            </button>
          </div>
        @else
          <p class="review-next-summary">No further action is being taken on this report.</p>
        @endif
      </div>
    </section>
  @endif

  </div> {{-- End review-bottom-stack --}}
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

  // Kebab menu toggle for "More actions"
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
    if (!event.target.closest('.kebab')) {
      closeAllKebabs();
    }
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
    if (hasUnverifiedAudio) {
      reasons.push('Audio verification required before routing.');
    }
    if (remaining > 0) reasons.push(`Review ${remaining} more field${remaining === 1 ? '' : 's'}.`);
    if (missingCategory || missingUrgency) {
      reasons.push(`Select ${missingCategory && missingUrgency ? 'Category and Urgency' : (missingCategory ? 'Category' : 'Urgency')}.`);
    }

    if (currentMode === 'beyond') {
      if (routeButton) routeButton.disabled = true;
      const missingAgency = !agencySelect?.value;
      if (referralSubmit) {
        referralSubmit.disabled = hasUnverifiedAudio || missingCategory || missingUrgency || missingAgency;
      }
      if (missingAgency) reasons.push('Choose an agency.');
    } else {
      if (referralSubmit) referralSubmit.disabled = true;
      const missingOffice = Boolean(routeButton && !officeSelect?.value);
      if (missingOffice) reasons.push('Choose an office.');
      if (routeButton) {
        routeButton.disabled = hasUnverifiedAudio || missingCategory || missingUrgency || missingOffice;
      }
    }

    if (gateReason) {
      if (reasons.length > 0) {
        gateReason.innerHTML = `<i class="fa-solid fa-circle-exclamation text-amber-500 mr-1" aria-hidden="true"></i> <span>${reasons.join(' ')}</span>`;
        gateReason.className = 'review-gate-hint text-xs ml-2 font-medium text-amber-700 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 inline-flex items-center';
      } else {
        gateReason.innerHTML = '<i class="fa-solid fa-check text-green-600 mr-1" aria-hidden="true"></i> <span>Ready</span>';
        gateReason.className = 'review-gate-hint text-xs ml-2 font-medium text-green-700 bg-green-50 px-2 py-0.5 rounded border border-green-200 inline-flex items-center';
      }
    }

    const reasonText = reasons.join(' ');
    if (routeButton) routeButton.title = routeButton.disabled ? reasonText : 'Confirm review and route report';
    if (referralSubmit) referralSubmit.title = referralSubmit.disabled ? reasonText : 'Prepare and send referral to agency';

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

  let currentRuleText = modeNote?.querySelector('[data-matched-rule-text]')?.textContent.trim() || 'Category default';

  const updateStickySummary = () => {
    const summaryTextEl = root.querySelector('[data-action-summary-text]');
    if (!summaryTextEl) return;
    const urgency = root.querySelector('[name="urgency"]')?.value || 'Normal';
    if (currentMode === 'within') {
      const officeName = officeSelect?.selectedOptions[0]?.text || 'selected office';
      summaryTextEl.textContent = `Route to ${officeName} · ${urgency}`;
    } else {
      const agencyName = agencySelect?.selectedOptions[0]?.text || 'external agency';
      summaryTextEl.textContent = `Refer to ${agencyName} · ${urgency}`;
    }
  };

  const syncSuggestedBadge = () => {
    const catId = root.querySelector('[name="category_id"]')?.value;
    const decision = routingMap[catId];
    const badge = root.querySelector('[data-suggested-badge]');
    if (badge && officeSelect && decision) {
      const isMatch = String(officeSelect.value) === String(decision.office_id) && Boolean(decision.office_id);
      badge.style.display = isMatch ? 'inline-flex' : 'none';
    }
  };

  const syncUnassignedNote = () => {
    const note = root.querySelector('[data-unassigned-note]');
    if (note && assignedSelect) {
      note.style.display = (assignedSelect.value === '') ? 'flex' : 'none';
    }
  };

  const applyLetterTemplate = (officeId, { force = false } = {}) => {
    const template = letterTemplates[officeId];
    if (!template) return;
    if (letterTextarea && (force || letterTextarea.dataset.touched !== 'true')) {
      letterTextarea.value = template.letter || '';
    }
    const previewText = root.querySelector('[data-letter-preview-text]');
    if (previewText) {
      previewText.textContent = letterTextarea ? letterTextarea.value : (template.letter || '');
    }
    if (residentMessageTextarea && (force || residentMessageTextarea.dataset.touched !== 'true')) {
      residentMessageTextarea.value = template.resident_message || '';
    }
    const personEl = root.querySelector('[data-agency-person]');
    const emailEl = root.querySelector('[data-agency-email]');
    const phoneEl = root.querySelector('[data-agency-phone]');
    const addrEl = root.querySelector('[data-agency-address]');
    if (personEl) personEl.textContent = template.contact_person || 'Not provided';
    if (emailEl) emailEl.textContent = template.contact_email || 'Not provided';
    if (phoneEl) phoneEl.textContent = template.contact_phone || 'Not provided';
    if (addrEl) addrEl.textContent = template.address || 'Not provided';
    if (agencyInfo) {
      const parts = [];
      if (template.contact_person) parts.push(`Contact: ${template.contact_person}`);
      if (template.contact_email) parts.push(template.contact_email);
      if (template.contact_phone) parts.push(template.contact_phone);
      if (template.address) parts.push(template.address);
      agencyInfo.textContent = parts.length ? parts.join(' · ') : 'Contact details not provided.';
    }
    updateStickySummary();
  };

  const setMode = (mode, { fromUser = false } = {}) => {
    currentMode = mode === 'beyond' ? 'beyond' : 'within';
    if (fromUser) modeOverridden = true;
    if (authorityModeInput) authorityModeInput.value = currentMode;
    const within = currentMode === 'within';

    // Segmented toggle styling
    root.querySelectorAll('.mode-seg-btn').forEach(btn => {
      const isTarget = btn.dataset.modeBtn === currentMode;
      btn.className = `mode-seg-btn px-3 py-1.5 rounded-md font-semibold transition-all ${isTarget ? 'bg-white text-slate-900 shadow-sm font-bold' : 'text-slate-600 hover:text-slate-900'}`;
    });

    if (modeBanner) {
      modeBanner.classList.toggle('review-mode-banner-within', within);
      modeBanner.classList.toggle('review-mode-banner-beyond', !within);
      modeBanner.classList.toggle('border-l-emerald-600', within);
      modeBanner.classList.toggle('bg-emerald-50/50', within);
      modeBanner.classList.toggle('border-emerald-200', within);
      modeBanner.classList.toggle('border-l-blue-600', !within);
      modeBanner.classList.toggle('bg-blue-50/50', !within);
      modeBanner.classList.toggle('border-blue-200', !within);
    }
    const modeIcon = root.querySelector('[data-mode-icon]');
    if (modeIcon) {
      modeIcon.className = `w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 transition-colors ${within ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700'}`;
      modeIcon.innerHTML = within
        ? '<i class="fa-solid fa-shield-halved text-base" aria-hidden="true"></i>'
        : '<i class="fa-solid fa-arrow-up-right-from-square text-base" aria-hidden="true"></i>';
    }
    if (modeBadge) modeBadge.textContent = within ? 'Within barangay authority' : 'Beyond barangay authority';
    if (modeNote) {
      if (within) {
        modeNote.innerHTML = `Matched rule: <span data-matched-rule-text>${currentRuleText}</span>. Assign it to an office and staff member.`;
      } else {
        modeNote.textContent = 'This will be referred to an external agency. A referral letter is sent and follow-up is tracked.';
      }
    }
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

    // Toggle submit buttons in sticky bar
    if (routeButton) routeButton.style.display = within ? '' : 'none';
    if (referralSubmit) referralSubmit.style.display = within ? 'none' : '';

    if (!within && agencySelect) applyLetterTemplate(agencySelect.value);
    syncSuggestedBadge();
    syncUnassignedNote();
    updateStickySummary();
    updateGate();
  };

  const assignedSelect = root.querySelector('[data-assigned-select]');
  let assignedTouched = false;
  assignedSelect?.addEventListener('change', () => {
    assignedTouched = true;
    syncUnassignedNote();
    updateStickySummary();
  });

  const syncDefaultAssignee = () => {
    if (!officeSelect || !assignedSelect || assignedTouched) return;
    const opt = officeSelect.selectedOptions[0];
    if (opt && opt.dataset.defaultAssignee !== undefined) {
      assignedSelect.value = opt.dataset.defaultAssignee || '';
      syncUnassignedNote();
    }
  };

  root.querySelectorAll('.mode-seg-btn').forEach(btn => {
    btn.addEventListener('click', () => {
      setMode(btn.dataset.modeBtn, { fromUser: true });
    });
  });

  modeToggle?.addEventListener('click', () => {
    setMode(modeToggle.dataset.otherMode === 'beyond' ? 'beyond' : 'within', { fromUser: true });
  });

  agencySelect?.addEventListener('change', () => {
    agencySelect.dataset.touched = 'true';
    applyLetterTemplate(agencySelect.value);
    updateStickySummary();
    updateGate();
  });

  letterTextarea?.addEventListener('input', () => {
    letterTextarea.dataset.touched = 'true';
    const previewText = root.querySelector('[data-letter-preview-text]');
    if (previewText) previewText.textContent = letterTextarea.value;
  });

  residentMessageTextarea?.addEventListener('input', () => {
    residentMessageTextarea.dataset.touched = 'true';
  });

  officeSelect?.addEventListener('change', () => {
    officeTouched = true;
    syncDefaultAssignee();
    syncSuggestedBadge();
    updateStickySummary();
    updateGate();
  });

  // Edit letter toggle buttons
  const btnEditLetter = root.querySelector('#btn-edit-letter');
  const btnResetLetter = root.querySelector('#btn-reset-letter');
  const btnDoneLetter = root.querySelector('#btn-done-letter');
  const previewCard = root.querySelector('#letter-preview-card');
  const editBox = root.querySelector('#letter-edit-box');

  btnEditLetter?.addEventListener('click', () => {
    if (previewCard) previewCard.style.display = 'none';
    if (editBox) editBox.style.display = 'block';
    if (btnEditLetter) btnEditLetter.style.display = 'none';
    if (btnResetLetter) btnResetLetter.style.display = 'inline-flex';
    if (btnDoneLetter) btnDoneLetter.style.display = 'inline-flex';
    if (letterTextarea) letterTextarea.focus();
  });

  btnDoneLetter?.addEventListener('click', () => {
    const previewText = root.querySelector('[data-letter-preview-text]');
    if (previewText && letterTextarea) previewText.textContent = letterTextarea.value;
    if (editBox) editBox.style.display = 'none';
    if (previewCard) previewCard.style.display = 'block';
    if (btnResetLetter) btnResetLetter.style.display = 'none';
    if (btnDoneLetter) btnDoneLetter.style.display = 'none';
    if (btnEditLetter) btnEditLetter.style.display = 'inline-flex';
  });

  btnResetLetter?.addEventListener('click', () => {
    if (agencySelect) applyLetterTemplate(agencySelect.value, { force: true });
  });

  // Quick fill chips
  root.querySelectorAll('.btn-quick-fill').forEach(btn => {
    btn.addEventListener('click', () => {
      const targetId = btn.dataset.chipTarget;
      const targetText = btn.dataset.chipText;
      const targetArea = root.querySelector('#' + targetId);
      if (targetArea && targetText) {
        targetArea.value = targetText;
        targetArea.dispatchEvent(new Event('input', { bubbles: true }));
        targetArea.focus();
      }
    });
  });

  // Urgency pill sync with final control
  root.querySelector('[name="urgency"]')?.addEventListener('change', (e) => {
    const urgency = e.target.value;
    const pill = root.querySelector('[data-urgency-pill]');
    const dot = root.querySelector('[data-urgency-dot]');
    const label = root.querySelector('[data-urgency-label]');
    if (label) label.textContent = urgency;
    if (pill && dot) {
      pill.className = `inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border ${urgency === 'Critical' ? 'bg-red-50 text-red-800 border-red-200' : (urgency === 'High' ? 'bg-amber-50 text-amber-800 border-amber-200' : (urgency === 'Medium' ? 'bg-blue-50 text-blue-800 border-blue-200' : 'bg-slate-50 text-slate-700 border-slate-200'))}`;
      dot.className = `w-2 h-2 rounded-full ${urgency === 'Critical' ? 'bg-red-600' : (urgency === 'High' ? 'bg-amber-500' : (urgency === 'Medium' ? 'bg-blue-500' : 'bg-slate-400'))}`;
    }
    updateStickySummary();
  });

  root.querySelector('[name="category_id"]')?.addEventListener('change', (event) => {
    const decision = routingMap[event.target.value];
    if (!decision) return;
    const ruleMode = decision.within_authority ? 'within' : 'beyond';
    currentRuleText = decision.reason || `${decision.office_name || 'No office'}`;
    if (routingReason) {
      routingReason.innerHTML = `<i class="fa-solid fa-route text-blue-600" aria-hidden="true"></i> <span>Rule: ${decision.office_name || 'No office'} — ${decision.reason}</span>`;
    }
    if (modeNote && !modeOverridden) {
      if (currentMode === 'within') {
        modeNote.innerHTML = `Matched rule: <span data-matched-rule-text>${currentRuleText}</span>. Assign it to an office and staff member.`;
      }
    }
    if (officeSelect && !officeTouched && decision.office_id) {
      officeSelect.value = String(decision.office_id);
      syncDefaultAssignee();
    }
    syncSuggestedBadge();
    if (!modeOverridden) setMode(ruleMode);
    updateStickySummary();
    updateGate();
  });

  setMode(currentMode);
  syncSuggestedBadge();
  syncUnassignedNote();
  updateStickySummary();

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
    // When staff confirms submission, mark any remaining review rows as confirmed
    rows.forEach((row) => {
      row.dataset.reviewed = 'true';
    });
    updateGate();
    const submitterDisabled = event.submitter.value === 'route'
      ? routeButton?.disabled
      : referralSubmit?.disabled;
    if (submitterDisabled) {
      event.preventDefault();
      return;
    }
  });

  root.querySelectorAll('[data-history-tabs]').forEach((tabsCard) => {
    const tabBtns = tabsCard.querySelectorAll('[data-tab-btn]');
    const panels = tabsCard.querySelectorAll('[data-tab-pane]');
    tabBtns.forEach((btn) => {
      btn.addEventListener('click', () => {
        const target = btn.dataset.tabBtn;
        tabBtns.forEach((b) => {
          const active = b === btn;
          b.classList.toggle('is-active', active);
          b.classList.toggle('btn-outline', !active);
        });
        panels.forEach((p) => {
          p.hidden = p.dataset.tabPane !== target;
        });
      });
    });
  });

  updateGate();
  if (aiState === 'pending') window.setTimeout(pollAi, 1500);
})();
</script>
@endpush
