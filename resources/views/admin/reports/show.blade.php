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
@endphp

<a href="{{ route('admin.reports.index') }}" class="back-link">
  <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to report queue
</a>

@if($errors->any())
  <div role="alert" class="form-errors">{{ $errors->first() }}</div>
@endif

<div class="page-heading">
  <h2 class="tracking-code">{{ $report->tracking_code }}</h2>
  <p>Submitted {{ $report->created_at->format('M d, Y \a\t h:i A') }}</p>
</div>

<div class="review-layout">
  <div class="review-primary">
    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-comment-dots" aria-hidden="true"></i> Resident report</h2>
      <x-status-badge :status="$report->status" />
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
  </div>

  <aside class="review-sidebar">
    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-user" aria-hidden="true"></i> Reporter</h2>
      <dl class="detail-meta location-meta">
        <div>
          <dt>Name</dt>
          <dd>{{ $report->reporter_name ?: 'Not provided' }}</dd>
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
      </dl>
    </section>

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

    <section class="card review-card">
      <h2 class="card-title"><i class="fa-solid fa-pen" aria-hidden="true"></i> Update status</h2>
      <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="status-form">
        @csrf
        @method('PATCH')
        <label for="status">New status
          <select id="status" name="status" required>
            @foreach($manualStatuses as $status)
              <option value="{{ $status }}" @selected(old('status', in_array($report->status, $manualStatuses, true) ? $report->status : 'under_review') === $status)>
                {{ \App\Models\Report::STATUSES[$status] }}
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
        <label for="note">Note <span class="field-hint">(required)</span>
          <textarea id="note" name="note" rows="3" maxlength="500" required>{{ old('note') }}</textarea>
          @error('note') <span class="field-error">{{ $message }}</span> @enderror
        </label>
        <button type="submit" class="btn btn-gold">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save status
        </button>
      </form>
    </section>

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
