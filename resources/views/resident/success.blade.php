@extends('layouts.app')
@section('title', 'Report Submitted')

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <h1>Thank you for letting us know</h1>
      <p>Your report has been received by Barangay Calarian. Save the tracking code below to check for updates.</p>
    </div>
  </section>
@endsection

@section('content')
<section class="card success-card">
  <span class="alert-icon success-icon">
    <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
  </span>
  <p class="field-hint">Your tracking code</p>
  <p class="tracking-code">{{ $report->tracking_code }}</p>
  <p class="tracking-meta">Keep this code to check your report status.</p>
  <div class="hero-actions success-actions">
    <a class="btn btn-gold" href="{{ route('track.show', $report->tracking_code) }}">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Track this report
    </a>
    <a class="btn btn-outline" href="{{ route('reports.create') }}">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> Submit another report
    </a>
  </div>
  <p class="field-hint success-disclaimer">If this is an emergency, call 911 or the local police. This system is not monitored in real time.</p>
</section>
@endsection
