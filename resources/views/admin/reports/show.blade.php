@extends('layouts.admin')
@section('title', 'Review ' . $report->tracking_code)
@section('page-heading', 'Review report')

@section('content')
@php
  $aiResult = $report->aiResult;
  $hasAiSuggestion = $report->ai_status === 'done' && $aiResult !== null && $aiResult->error === null;
  $hasSavedReview = $report->reviewed_at !== null;
  $suggestedCategory = $hasAiSuggestion
    ? $categories->firstWhere('slug', $aiResult->suggested_category)
    : null;
  $confidence = $hasAiSuggestion ? (float) $aiResult->confidence : null;
  $confidenceLabel = $confidence === null
    ? null
    : ($confidence >= 0.8 ? 'High' : ($confidence >= 0.5 ? 'Medium' : 'Low'));
  $categoryDefault = $hasSavedReview
    ? $report->category_id
    : ($hasAiSuggestion ? $suggestedCategory?->id : null);
  $urgencyDefault = $hasSavedReview
    ? $report->urgency
    : ($hasAiSuggestion ? $aiResult->suggested_urgency : null);
  $descriptionDefault = $hasSavedReview
    ? $report->staff_description
    : ($hasAiSuggestion ? ($aiResult->summary ?? $report->description) : $report->description);
  $photoMismatchDefault = $hasSavedReview
    ? $report->photo_mismatch
    : ($hasAiSuggestion && $aiResult->photo_mismatch);
  $emergencyDefault = $hasSavedReview
    ? $report->is_emergency
    : ($hasAiSuggestion && $aiResult->emergency_flag);
  $categorySelected = old('category_id', $categoryDefault);
  $urgencySelected = old('urgency', $urgencyDefault);
  $isOverdue = $report->due_at && $report->due_at->isPast() && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true);
@endphp

<a href="{{ route('admin.reports.index') }}" class="back-link">
  <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to report queue
</a>

@if(session('success'))
  <div role="status" class="flash-success">{{ session('success') }}</div>
@endif

@if($errors->any())
  <div role="alert" class="form-errors">{{ $errors->first() }}</div>
@endif

<div class="page-heading flex items-center justify-between flex-wrap gap-4">
  <div>
    <div class="flex items-center gap-3 flex-wrap">
      <h2 class="tracking-code">{{ $report->tracking_code }}</h2>
      <x-status-badge :status="$report->status" />
      @if($report->is_emergency)
        <span class="status-badge b-red">
          <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Emergency
        </span>
      @endif
      @if($isOverdue)
        <span class="status-badge b-red" title="Due on {{ $report->due_at->format('M d, Y h:i A') }}">
          <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Overdue
        </span>
      @endif
      @if($report->reopened_count > 0)
        <span class="status-badge b-gold">
          <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Reopened ({{ $report->reopened_count }})
        </span>
      @endif
      @if($report->escalated_at)
        <span class="status-badge b-red">
          <i class="fa-solid fa-fire" aria-hidden="true"></i> Escalated ({{ $report->escalated_at->format('M d, Y') }})
        </span>
      @endif
    </div>
    <p>Submitted {{ $report->created_at->format('M d, Y \a\t h:i A') }}
      @if($report->due_at)
        · <span class="{{ $isOverdue ? 'text-red-600 font-semibold' : '' }}">SLA Due: {{ $report->due_at->format('M d, Y h:i A') }}</span>
      @endif
    </p>
  </div>

  @if($isOverdue && !$report->escalated_at)
    <form method="POST" action="{{ route('admin.reports.escalate', $report) }}" class="inline-block" onsubmit="return confirm('Escalate this overdue case?');">
      @csrf
      <button type="submit" class="btn btn-red btn-small">
        <i class="fa-solid fa-fire" aria-hidden="true"></i> Escalate Case
      </button>
    </form>
  @endif
</div>

{{-- Phase 11 Engine Panels --}}
<div class="engine-panels-wrap mb-4">
  <x-engine.ai-panel :report="$report" />
  <x-engine.routing-panel :report="$report" />
  <x-engine.duplicate-panel :report="$report" />
</div>

<div class="review-layout">
  <div class="review-primary">
    {{-- Resident Report Card --}}
    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-comment-dots" aria-hidden="true"></i> Resident report</h2>
      <p class="whitespace-pre-line resident-report-text">{{ $report->description }}</p>

      @if($report->photos->isNotEmpty())
        <div class="photo-preview-grid">
          @foreach($report->photos as $index => $photo)
            <a href="{{ route('admin.reports.photos.show', [$report, $photo]) }}" target="_blank" rel="noopener noreferrer">
              <img src="{{ route('admin.reports.photos.show', [$report, $photo]) }}"
                alt="Photo {{ $index + 1 }} attached to this report" class="report-photo">
            </a>
          @endforeach
        </div>
      @elseif($report->photo_path)
        <a href="{{ route('admin.reports.photo', $report) }}" target="_blank" rel="noopener noreferrer">
          <img src="{{ route('admin.reports.photo', $report) }}" alt="Photo attached to this report" class="report-photo">
        </a>
      @else
        <div class="photo-empty" role="note">
          <span><i class="fa-regular fa-image" aria-hidden="true"></i><br>No photo attached</span>
        </div>
      @endif

      <div class="review-details-divider"></div>
      <div class="review-details-heading">
        <div>
          <h2 class="card-title"><i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Report details</h2>
          <p>Review the information and save the final staff decision.</p>
        </div>
        @if($hasAiSuggestion)
          <span class="review-confidence review-confidence-{{ strtolower($confidenceLabel) }}"
            title="{{ number_format($confidence * 100, 1) }}% confidence">
            {{ $confidenceLabel }} confidence
          </span>
        @endif
      </div>

      @if($hasAiSuggestion && $aiResult->emergency_flag)
        <div class="review-banner review-banner-emergency" role="status">
          <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
          <span>Possible emergency. Staff must confirm.</span>
        </div>
      @endif
      @if($hasAiSuggestion && $aiResult->photo_mismatch)
        <div class="review-banner review-banner-mismatch" role="status">
          <i class="fa-solid fa-image" aria-hidden="true"></i>
          <span>AI flagged a possible mismatch between the photo and report text.</span>
        </div>
      @endif

      @if($report->ai_status === 'failed')
        <div class="review-ai-notice" role="status">
          <span>AI unavailable. Please review and fill in the details manually.</span>
          <form method="POST" action="{{ route('admin.reports.retry-ai', $report) }}">
            @csrf
            <button type="submit" class="btn btn-outline review-secondary-button">
              <i class="fa-solid fa-rotate" aria-hidden="true"></i> Retry AI analysis
            </button>
          </form>
        </div>
      @elseif($report->ai_status === 'pending')
        <p class="review-ai-notice" role="status">AI analysis in progress. You can fill in the review manually.</p>
      @endif

      @if($hasAiSuggestion)
        <p class="review-ai-label">
          <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
          AI suggestion — staff review required
        </p>
      @endif

      <form method="POST" action="{{ route('admin.reports.review', $report) }}" class="review-form" data-review-form>
        @csrf
        @method('PATCH')

        <div class="review-field-grid">
          <div class="review-field">
            <label for="category_id">
              Category
              @if($hasAiSuggestion && $suggestedCategory && !$hasSavedReview && !session()->hasOldInput('category_id'))
                <span class="review-prefilled" data-prefilled-for="category_id">AI-prefilled</span>
              @endif
            </label>
            <select id="category_id" name="category_id" required aria-describedby="category-error">
              <option value="" @selected($categorySelected === null || $categorySelected === '')>Select a category</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $categorySelected === (string) $category->id)>
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
            @error('category_id') <span id="category-error" class="field-error">{{ $message }}</span> @enderror
          </div>

          <div class="review-field">
            <label for="urgency">
              Urgency
              @if($hasAiSuggestion && !$hasSavedReview && !session()->hasOldInput('urgency'))
                <span class="review-prefilled" data-prefilled-for="urgency">AI-prefilled</span>
              @endif
            </label>
            <select id="urgency" name="urgency" required aria-describedby="urgency-error">
              <option value="" @selected($urgencySelected === null || $urgencySelected === '')>Select urgency</option>
              @foreach(['Low', 'Medium', 'High', 'Critical'] as $urgency)
                <option value="{{ $urgency }}" @selected($urgencySelected === $urgency)>
                  {{ $urgency }}
                </option>
              @endforeach
            </select>
            @error('urgency') <span id="urgency-error" class="field-error">{{ $message }}</span> @enderror
          </div>
        </div>

        <div class="review-field">
          <label for="staff_description">
            Reviewed description
            @if($hasAiSuggestion && !$hasSavedReview && !session()->hasOldInput('staff_description'))
              <span class="review-prefilled" data-prefilled-for="staff_description">AI-prefilled</span>
            @endif
          </label>
          @if($hasAiSuggestion)
            <div class="review-description-tabs" role="tablist" aria-label="Description versions">
              <button type="button" role="tab" id="review-tab-ai" aria-controls="review-panel-ai"
                aria-selected="true" data-review-tab="ai">AI suggested</button>
              <button type="button" role="tab" id="review-tab-original" aria-controls="review-panel-original"
                aria-selected="false" data-review-tab="original" tabindex="-1">Original</button>
            </div>
            <div id="review-panel-ai" role="tabpanel" aria-labelledby="review-tab-ai" data-review-panel="ai">
              <p class="review-source-label">AI suggested description</p>
              <p class="review-source-text" data-review-source>{{ $aiResult->summary ?: 'No AI suggested description was saved.' }}</p>
              <button type="button" class="btn btn-outline review-secondary-button" data-copy-description="ai">
                Use AI version
              </button>
            </div>
            <div id="review-panel-original" role="tabpanel" aria-labelledby="review-tab-original"
              data-review-panel="original" hidden>
              <p class="review-source-text" data-review-source>{{ $report->description }}</p>
              <button type="button" class="btn btn-outline review-secondary-button" data-copy-description="original">
                Use original
              </button>
            </div>
          @else
            <div class="review-description-tabs" role="tablist" aria-label="Description versions">
              <button type="button" role="tab" id="review-tab-original" aria-controls="review-panel-original"
                aria-selected="true" data-review-tab="original">Original</button>
            </div>
            <div id="review-panel-original" role="tabpanel" aria-labelledby="review-tab-original"
              data-review-panel="original">
              <p class="review-source-text" data-review-source>{{ $report->description }}</p>
              <button type="button" class="btn btn-outline review-secondary-button" data-copy-description="original">
                Use original
              </button>
            </div>
          @endif
          <textarea id="staff_description" name="staff_description" rows="4" maxlength="2000" required
            aria-describedby="staff-description-error">{{ old('staff_description', $descriptionDefault) }}</textarea>
          @error('staff_description') <span id="staff-description-error" class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="review-checkboxes">
          <label class="review-checkbox">
            <input type="hidden" name="photo_mismatch" value="0">
            <input type="checkbox" name="photo_mismatch" value="1"
              @checked((bool) old('photo_mismatch', $photoMismatchDefault))>
            <span>
              <strong>
                Photo doesn't match the report text
                @if($hasAiSuggestion && !$hasSavedReview && !session()->hasOldInput('photo_mismatch'))
                  <span class="review-prefilled" data-prefilled-for="photo_mismatch">AI-prefilled</span>
                @endif
              </strong>
              <small>Flag this when the attached photo appears unrelated to the reported issue.</small>
            </span>
          </label>
          @error('photo_mismatch') <span class="field-error">{{ $message }}</span> @enderror

          <label class="review-checkbox">
            <input type="hidden" name="is_emergency" value="0">
            <input type="checkbox" name="is_emergency" value="1"
              @checked((bool) old('is_emergency', $emergencyDefault))>
            <span>
              <strong>
                Possible emergency or crime
                @if($hasAiSuggestion && !$hasSavedReview && !session()->hasOldInput('is_emergency'))
                  <span class="review-prefilled" data-prefilled-for="is_emergency">AI-prefilled</span>
                @endif
              </strong>
              <small>Confirming this flag alerts the configured barangay contacts and hides the report from the public map.</small>
            </span>
          </label>
          @error('is_emergency') <span class="field-error">{{ $message }}</span> @enderror
        </div>

        <div class="review-field">
          <label for="review_note">Confirmation note <span class="field-hint">(optional)</span></label>
          <textarea id="review_note" name="review_note" rows="3" maxlength="1000"
            aria-describedby="review-note-error">{{ old('review_note', $hasSavedReview ? $report->review_note : '') }}</textarea>
          @error('review_note') <span id="review-note-error" class="field-error">{{ $message }}</span> @enderror
        </div>

        <button type="submit" class="btn btn-gold review-save-button">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save review details
        </button>
      </form>
    </section>

    {{-- "Needs More Info" Request Section --}}
    <section class="card review-card mt-6">
      <h2 class="card-title"><i class="fa-solid fa-clipboard-question" aria-hidden="true"></i> Needs More Info from Resident</h2>
      <p>Ask clarifying questions if the report lacks location details or essential info. This automatically updates status to <strong>Needs Info</strong>.</p>

      <form method="POST" action="{{ route('admin.reports.info-requests.store', $report) }}" class="mt-4">
        @csrf
        <label for="question">Question for resident
          <textarea id="question" name="question" rows="3" maxlength="2000" required
            placeholder="e.g. Could you specify which house number or corner this issue is located near?">{{ old('question') }}</textarea>
          @error('question') <span class="field-error">{{ $message }}</span> @enderror
        </label>
        <button type="submit" class="btn btn-navy mt-2">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Send request to resident
        </button>
      </form>

      @if($report->infoRequests->isNotEmpty())
        <div class="mt-6">
          <h3 class="font-semibold text-sm uppercase tracking-wide text-gray-600 mb-2">Previous Info Requests</h3>
          <div class="space-y-4">
            @foreach($report->infoRequests as $infoReq)
              <div class="border rounded p-3 bg-gray-50">
                <div class="flex justify-between text-xs text-gray-500 mb-1">
                  <span><strong>Asked by:</strong> {{ $infoReq->requester?->name ?? 'Staff' }}</span>
                  <span>{{ $infoReq->created_at->format('M d, Y h:i A') }}</span>
                </div>
                <p class="text-sm font-medium text-gray-800">{{ $infoReq->question }}</p>
                <div class="mt-2 pt-2 border-t border-gray-200">
                  @if($infoReq->responded_at)
                    <div class="text-xs text-green-700 font-semibold mb-1">
                      <i class="fa-solid fa-check" aria-hidden="true"></i> Resident responded on {{ $infoReq->responded_at->format('M d, Y h:i A') }}:
                    </div>
                    <p class="text-sm text-gray-700 bg-white p-2 rounded border">{{ $infoReq->response }}</p>
                  @else
                    <span class="text-xs text-amber-700"><i class="fa-solid fa-hourglass-half" aria-hidden="true"></i> Awaiting resident response on tracking page.</span>
                  @endif
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </section>

    {{-- Resolution Flow & Proof Section --}}
    <section class="card review-card mt-6">
      <h2 class="card-title"><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Case Resolution & Proof</h2>
      <p>Submit completion notes and optional inspection photo. This moves the case to <strong>Awaiting Confirmation</strong> so the resident can confirm or reopen.</p>

      @if($report->status === \App\Support\ReportStatus::AWAITING_CONFIRMATION)
        <div class="p-3 my-3 bg-blue-50 border border-blue-200 rounded text-blue-900 text-sm">
          <strong>Awaiting resident confirmation.</strong> If the resident does not respond within {{ $autoCloseDays }} days, staff may close this case manually.
          <form method="POST" action="{{ route('admin.reports.close', $report) }}" class="mt-2 inline-block" onsubmit="return confirm('Close and mark as resolved?');">
            @csrf
            <button type="submit" class="btn btn-small btn-navy">
              <i class="fa-solid fa-check-double" aria-hidden="true"></i> Close case manually now
            </button>
          </form>
        </div>
      @endif

      <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" enctype="multipart/form-data" class="mt-4">
        @csrf
        <label for="resolution_note">Resolution Note <span class="field-hint">(required)</span>
          <textarea id="resolution_note" name="note" rows="3" maxlength="2000" required
            placeholder="Describe what action was taken to fix or resolve this problem...">{{ old('note') }}</textarea>
          @error('note') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <label for="resolution_photo">Proof Photo <span class="field-hint">(optional)</span>
          <input type="file" id="resolution_photo" name="photo" accept="image/*">
          @error('photo') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        <button type="submit" class="btn btn-gold mt-2">
          <i class="fa-solid fa-award" aria-hidden="true"></i> Propose Resolution
        </button>
      </form>

      @if($report->resolutionProofs->isNotEmpty())
        <div class="mt-6">
          <h3 class="font-semibold text-sm uppercase tracking-wide text-gray-600 mb-2">Submitted Resolution Proofs</h3>
          <div class="space-y-4">
            @foreach($report->resolutionProofs as $proof)
              <div class="border rounded p-3 bg-gray-50 flex flex-col md:flex-row gap-4">
                @if($proof->photo_path)
                  <a href="{{ route('admin.reports.proofs.show', [$report, $proof]) }}" target="_blank" rel="noopener noreferrer">
                    <img src="{{ route('admin.reports.proofs.show', [$report, $proof]) }}"
                      alt="Resolution proof photo" class="w-32 h-24 object-cover rounded border">
                  </a>
                @endif
                <div class="flex-1">
                  <div class="flex justify-between text-xs text-gray-500 mb-1">
                    <span><strong>Submitted by:</strong> {{ $proof->user?->name ?? 'Staff' }}</span>
                    <span>{{ $proof->created_at->format('M d, Y h:i A') }}</span>
                  </div>
                  <p class="text-sm text-gray-800">{{ $proof->note }}</p>
                </div>
              </div>
            @endforeach
          </div>
        </div>
      @endif
    </section>

    {{-- Internal Notes Section (Staff Only) --}}
    <section class="card review-card mt-6">
      <h2 class="card-title"><i class="fa-solid fa-lock" aria-hidden="true"></i> Staff Internal Notes</h2>
      <p class="text-xs text-gray-500 mb-3">Internal notes are visible to barangay staff and admins only. They will never be seen by the resident.</p>

      <form method="POST" action="{{ route('admin.reports.internal-notes.store', $report) }}">
        @csrf
        <label for="internal_note_body">Add internal note
          <textarea id="internal_note_body" name="body" rows="3" maxlength="3000" required
            placeholder="Add internal observations, coordination notes with local teams, etc.">{{ old('body') }}</textarea>
          @error('body') <span class="field-error">{{ $message }}</span> @enderror
        </label>
        <button type="submit" class="btn btn-navy btn-small mt-2">
          <i class="fa-solid fa-plus" aria-hidden="true"></i> Add internal note
        </button>
      </form>

      @if($report->internalNotes->isNotEmpty())
        <div class="mt-4 space-y-3">
          @foreach($report->internalNotes as $note)
            <div class="border-l-4 border-amber-500 bg-amber-50/50 p-3 rounded-r text-sm">
              <div class="flex justify-between text-xs text-gray-500 mb-1">
                <strong>{{ $note->user?->name ?? 'Staff' }}</strong>
                <span>{{ $note->created_at->format('M d, Y h:i A') }}</span>
              </div>
              <p class="text-gray-800 whitespace-pre-line">{{ $note->body }}</p>
            </div>
          @endforeach
        </div>
      @endif
    </section>
  </div>

  <aside class="review-sidebar">
    {{-- Case Assignment Card --}}
    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Case Assignment</h2>
      <form method="POST" action="{{ route('admin.reports.assign', $report) }}">
        @csrf
        @method('PATCH')
        <label for="assigned_to_select">Assigned Staff
          <select id="assigned_to_select" name="assigned_to">
            <option value="">-- Unassigned --</option>
            @foreach($staffUsers as $user)
              <option value="{{ $user->id }}" @selected((string) old('assigned_to', $report->assigned_to) === (string) $user->id)>
                {{ $user->name }} ({{ ucfirst($user->role) }})
              </option>
            @endforeach
          </select>
        </label>
        <button type="submit" class="btn btn-navy btn-small mt-2">
          <i class="fa-solid fa-user-pen" aria-hidden="true"></i> Update assignment
        </button>
      </form>
    </section>

    {{-- Reporter Card --}}
    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-user" aria-hidden="true"></i> Reporter</h2>
      <dl class="detail-meta location-meta">
        <div>
          <dt>Name</dt>
          <dd>{{ $report->is_anonymous ? 'Anonymous resident' : ($report->reporter_name ?: 'Not provided') }}</dd>
        </div>
        <div>
          <dt>Contact number</dt>
          <dd>
            @if($report->reporter_contact)
              <a href="tel:{{ $report->reporter_contact }}">{{ $report->reporter_contact }}</a>
            @else
              Not provided
            @endif
          </dd>
        </div>
        <div>
          <dt>Email</dt>
          <dd>
            @if($report->reporter_email)
              <a href="mailto:{{ $report->reporter_email }}">{{ $report->reporter_email }}</a>
            @else
              Not provided
            @endif
          </dd>
        </div>
        @if($report->preferred_contact)
          <div>
            <dt>Preferred contact</dt>
            <dd>{{ ucfirst($report->preferred_contact) }}</dd>
          </div>
        @endif
      </dl>
    </section>

    {{-- Location Card --}}
    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Location details</h2>
      @if($recommendedOffice && !$report->is_emergency)
        <p class="field-hint"><strong>Recommended office:</strong> {{ $recommendedOffice->name }}</p>
      @endif
      <dl class="detail-meta location-meta">
        <div>
          <dt>Purok</dt>
          <dd>{{ $report->purok?->name ?? 'Not specified' }}</dd>
        </div>
        <div>
          <dt>Landmark</dt>
          <dd>{{ $report->landmark ?: 'Not provided' }}</dd>
        </div>
        <div>
          <dt>Submitted</dt>
          <dd>{{ $report->created_at->format('M d, Y h:i A') }}</dd>
        </div>
      </dl>

      @if($report->lat !== null && $report->lng !== null)
        <div id="report-location-map" class="map-frame location-map" data-lat="{{ $report->lat }}" data-lng="{{ $report->lng }}"
          role="img" aria-label="Map showing the report location"></div>
        <p class="field-hint">
          <a href="https://www.google.com/maps?q={{ $report->lat }},{{ $report->lng }}"
            target="_blank" rel="noopener noreferrer">Open location in Google Maps</a>
        </p>
      @else
        <p class="field-hint">No map coordinates were provided with this report.</p>
      @endif
    </section>

    {{-- Update Status Card with Response Templates --}}
    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-pen" aria-hidden="true"></i> Update status</h2>
      <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="status-form">
        @csrf
        @method('PATCH')
        <label for="status">New status
          <select id="status" name="status" required>
            @foreach($manualStatuses as $status)
              <option value="{{ $status }}" @selected(old('status', in_array($report->status, $manualStatuses, true) ? $report->status : 'under_review') === $status)>
                {{ \App\Models\Report::STATUSES[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
              </option>
            @endforeach
          </select>
          @error('status') <span class="field-error">{{ $message }}</span> @enderror
        </label>

        @if(!$report->is_emergency)
          <label for="office_id">Office
            <select id="office_id" name="office_id">
              <option value="">Auto recommended office</option>
              @foreach($offices as $office)
                <option value="{{ $office->id }}" @selected(old('office_id', $report->office_id) == $office->id)>
                  {{ $office->name }}
                </option>
              @endforeach
            </select>
          </label>
        @endif

        {{-- Response template selector --}}
        @if($responseTemplates->isNotEmpty())
          <label for="template_select">Response template <span class="field-hint">(optional)</span>
            <select id="template_select" class="template-selector" onchange="if(this.value){ document.getElementById('note').value = this.value; }">
              <option value="">-- Choose a response template --</option>
              @foreach($responseTemplates as $tmpl)
                <option value="{{ $tmpl->body }}">{{ $tmpl->name }}</option>
              @endforeach
            </select>
          </label>
        @endif

        <label for="note">Status Note <span class="field-hint">(required)</span>
          <textarea id="note" name="note" rows="3" maxlength="500" required placeholder="Note displayed in status history and tracking page...">{{ old('note') }}</textarea>
          @error('note') <span class="field-error">{{ $message }}</span> @enderror
        </label>
        <button type="submit" class="btn btn-gold">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save status
        </button>
      </form>
    </section>

    {{-- Status History Card --}}
    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Status history</h2>
      <ol class="timeline">
        @forelse($report->statusLogs as $log)
          <li>
            <strong>{{ \App\Models\Report::STATUSES[$log->to_status] ?? ucfirst(str_replace('_', ' ', $log->to_status)) }}</strong>
            <small>{{ $log->created_at->format('M d, Y h:i A') }}</small>
            <small>{{ $log->user?->name ?? 'Resident or system' }}</small>
            @if($log->note)
              <small>{{ $log->note }}</small>
            @endif
          </li>
        @empty
          <li><strong>No status history yet.</strong></li>
        @endforelse
      </ol>
    </section>
  </aside>
</div>
@endsection
