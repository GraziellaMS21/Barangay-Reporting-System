@extends('layouts.app')
@section('title', 'Track a Report')

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <h1>Track a report</h1>
      <p>Enter the tracking code you received after submitting your report to see its latest status and updates.</p>
      <div class="hero-actions">
        <a class="btn btn-ghost" href="{{ route('reports.create') }}">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to report form
        </a>
      </div>
    </div>
  </section>
@endsection

@section('content')
  @if($errors->any())
    <div role="alert" class="form-errors">{{ $errors->first() }}</div>
  @endif

  <form method="POST" action="{{ route('track.search') }}" class="track-search">
    @csrf
    <label class="sr-only" for="tracking_code">Tracking code</label>
    <input id="tracking_code" type="text" name="tracking_code" value="{{ old('tracking_code') }}"
      placeholder="BRG-XXXXXX" autocomplete="off" required>
    <button type="submit" class="btn btn-navy">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Search
    </button>
  </form>

  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-circle-info" aria-hidden="true"></i> Where can I find my code?</h2>
    <p class="field-hint">Your tracking code is shown after you submit a report. Keep it safe so you can return to this page and check for updates.</p>
  </section>
@endsection
