@extends('layouts.app')
@section('title', 'Report ' . $report->tracking_code)

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <h1>Report progress</h1>
      <p>Updates for your report submitted to Barangay Calarian.</p>
      <div class="hero-actions">
        <a class="btn btn-ghost" href="{{ route('track.index') }}">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Track another report
        </a>
      </div>
    </div>
  </section>
@endsection

@section('content')
<section class="card">
  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <p class="field-hint">Tracking code</p>
      <h2 class="tracking-code">{{ $report->tracking_code }}</h2>
    </div>
    <x-status-badge :status="$report->status" />
  </div>

  <p class="tracking-meta">
    Submitted {{ $report->created_at->format('M d, Y \a\t h:i A') }}
    @if($report->purok)
      · {{ $report->purok->name }}
    @endif
  </p>

  <h3 class="card-title resident-report-text">
    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Status history
  </h3>
  <ol class="timeline">
    @forelse($report->statusLogs as $log)
      <li>
        <strong>{{ \App\Models\Report::STATUSES[$log->to_status] ?? ucfirst(str_replace('_', ' ', $log->to_status)) }}</strong>
        <small>{{ $log->created_at->format('M d, Y h:i A') }}</small>
        @if($log->note)
          <small>{{ $log->note }}</small>
        @endif
      </li>
    @empty
      <li><strong>No status updates yet</strong></li>
    @endforelse
  </ol>
</section>
@endsection
