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

      @if($report->photo_path)
        <a href="{{ asset('storage/' . $report->photo_path) }}" target="_blank" rel="noopener noreferrer">
          <img src="{{ asset('storage/' . $report->photo_path) }}" alt="Photo attached to this report" class="report-photo">
        </a>
      @else
        <div class="photo-empty" role="note">
          <span><i class="fa-regular fa-image" aria-hidden="true"></i><br>No photo attached</span>
        </div>
      @endif
    </section>

    <section class="card">
      <h2 class="card-title"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Location details</h2>
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
        <div>
          <dt>Reporter email</dt>
          <dd>
            @if($report->reporter_email)
              <a href="mailto:{{ $report->reporter_email }}">{{ $report->reporter_email }}</a>
            @else
              Not provided
            @endif
          </dd>
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
      <p class="ai-placeholder">
        Automated suggestions are not available yet. Review the resident’s description and location before updating the report.
      </p>
      <p class="field-hint"><i class="fa-solid fa-user-check" aria-hidden="true"></i> Staff make the final decision on every report.</p>
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
        <label for="note">Note <span class="field-hint">(required for out of scope)</span>
          <textarea id="note" name="note" rows="3" maxlength="500">{{ old('note') }}</textarea>
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
