@extends('layouts.admin')
@section('title', 'Review ' . $report->tracking_code)
@section('page-heading', 'Review report')

@section('content')
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

<div class="detail-grid">
  <div class="detail-column">
    <section class="card">
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
    </section>

    <section class="card">
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

    <section class="card">
      <h2 class="card-title"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Location details</h2>
      @if($recommendedOffice)
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
  </div>

  <div class="detail-column">
    <section class="card ai-card">
      <h2 class="card-title"><i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> AI suggestion</h2>
      @if($report->aiResult?->error)
        <p role="status" class="ai-placeholder">{{ $report->aiResult->error }}</p>
      @elseif($report->aiResult)
        <p class="field-hint">
          @if($report->aiResult->confirmed_at)
            Staff-confirmed by {{ $report->aiResult->confirmedBy?->name ?? 'another staff member' }}
            on {{ $report->aiResult->confirmed_at->format('M d, Y h:i A') }}.
            <br>Note: {{ $report->aiResult->confirmation_note }}
          @else
            AI suggestion, staff review required. These suggestions do not become report values until you confirm them.
          @endif
        </p>
        <form method="POST" action="{{ route('admin.reports.ai-result', $report) }}" class="status-form">
          @csrf
          @method('PATCH')
          <label for="suggested_category">Category
            <select id="suggested_category" name="suggested_category" required>
              <option value="unclassified" @selected(old('suggested_category', $report->aiResult->suggested_category) === 'unclassified')>
                Unclassified
              </option>
              @foreach($categories as $category)
                <option value="{{ $category->slug }}"
                  @selected(old('suggested_category', $report->aiResult->suggested_category) === $category->slug)>
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
          </label>
          <label for="suggested_urgency">Urgency
            <select id="suggested_urgency" name="suggested_urgency" required>
              @foreach(['Low', 'Medium', 'High', 'Critical'] as $urgency)
                <option value="{{ $urgency }}"
                  @selected(old('suggested_urgency', $report->aiResult->suggested_urgency) === $urgency)>
                  {{ $urgency }}
                </option>
              @endforeach
            </select>
          </label>
          <label for="summary">Summary
            <textarea id="summary" name="summary" rows="3" maxlength="500" required>{{ old('summary', $report->aiResult->summary) }}</textarea>
          </label>
          <label for="photo_description">Photo description
            <textarea id="photo_description" name="photo_description" rows="2" maxlength="1000">{{ old('photo_description', $report->aiResult->photo_description) }}</textarea>
          </label>
          <label for="location_details">Location mentioned in report
            <input id="location_details" name="location_details" type="text" maxlength="255"
              value="{{ old('location_details', $report->aiResult->location_details) }}">
          </label>
          <label for="key_facts_text">Key facts <span class="field-hint">(one per line, up to 8)</span>
            <textarea id="key_facts_text" name="key_facts_text" rows="4" maxlength="2000">{{ old('key_facts_text', implode("\n", $report->aiResult->key_facts ?? [])) }}</textarea>
          </label>
          <label for="confidence">AI confidence
            <input id="confidence" name="confidence" type="number" min="0" max="1" step="0.01"
              value="{{ old('confidence', number_format($report->aiResult->confidence, 2)) }}" required>
          </label>
          <label for="confirmation_note">Confirmation note
            <textarea id="confirmation_note" name="confirmation_note" rows="2" maxlength="500" required>{{ old('confirmation_note', $report->aiResult->confirmation_note) }}</textarea>
          </label>
          <label class="remember">
            <input type="hidden" name="photo_mismatch" value="0">
            <input type="checkbox" name="photo_mismatch" value="1"
              @checked(old('photo_mismatch', $report->aiResult->photo_mismatch))>
            Photo conflicts with the report text
          </label>
          <label class="remember">
            <input type="hidden" name="emergency_flag" value="0">
            <input type="checkbox" name="emergency_flag" value="1"
              @checked(old('emergency_flag', $report->aiResult->emergency_flag))>
            AI flagged this as an emergency or crime
          </label>
          <p class="field-hint">
            <i class="fa-solid fa-user-check" aria-hidden="true"></i>
            AI suggestions never change a report until a staff member confirms them.
          </p>
          <button type="submit" class="btn btn-gold">
            <i class="fa-solid fa-check" aria-hidden="true"></i> Confirm and save staff decision
          </button>
        </form>
      @else
        <p class="ai-placeholder">AI suggestions are pending. Review the resident’s description and location manually.</p>
      @endif
    </section>

    <section class="card">
      <h2 class="card-title"><i class="fa-solid fa-pen" aria-hidden="true"></i> Update status</h2>
      <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="status-form">
        @csrf
        @method('PATCH')
        <label for="status">New status
          <select id="status" name="status" required>
            @foreach($manualStatuses as $status)
              <option value="{{ $status }}" @selected(old('status', $report->status) === $status)>
                {{ \App\Models\Report::STATUSES[$status] }}
              </option>
            @endforeach
          </select>
        </label>
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
        <label for="note">Note <span class="field-hint">(required)</span>
          <textarea id="note" name="note" rows="3" maxlength="500" required>{{ old('note') }}</textarea>
        </label>
        <button type="submit" class="btn btn-gold">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save status
        </button>
      </form>
    </section>

    <section class="card">
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
  </div>
</div>
@endsection
