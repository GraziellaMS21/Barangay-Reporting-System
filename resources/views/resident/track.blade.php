@extends('layouts.app')
@section('title', 'Track a Report - Barangay Calarian')

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
  <style>
    .track-search-card {
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      padding: 2.25rem 2rem;
      background: #ffffff;
      box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 20px 25px -5px rgba(11, 31, 75, 0.08);
      max-width: 680px;
      margin: 0 auto 1.75rem;
    }

    .track-input-group {
      display: flex;
      gap: 0.65rem;
      align-items: stretch;
      margin-top: 1rem;
    }

    @media (max-width: 600px) {
      .track-input-group {
        flex-direction: column;
      }
    }

    .track-input-field {
      flex: 1;
      min-height: 52px;
      border: 1.5px solid #cbd5e1;
      border-radius: 12px;
      padding: 0.75rem 1.15rem;
      font-size: 1.1rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      color: var(--ink);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      transition: all 0.2s;
    }

    .track-input-field:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 4px rgba(36, 86, 214, 0.12);
      outline: none;
    }

    .track-help-card {
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      padding: 1.75rem;
      background: #ffffff;
      box-shadow: 0 2px 4px rgba(11, 31, 75, 0.03);
      max-width: 680px;
      margin: 0 auto;
    }

    .help-step-item {
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f1f5f9;
    }

    .help-step-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .help-step-num {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: #eff6ff;
      color: var(--blue);
      font-weight: 800;
      font-size: 0.82rem;
      display: grid;
      place-items: center;
      flex-shrink: 0;
      margin-top: 0.1rem;
    }
  </style>

  @if($errors->any())
    <div role="alert" class="form-errors" style="max-width: 680px; margin: 0 auto 1.25rem; border-radius: 12px; padding: 1rem 1.25rem;">
      <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;">
        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
      </div>
    </div>
  @endif

  <!-- Search Card -->
  <section class="card track-search-card">
    <div style="display:flex;align-items:center;gap:0.75rem;margin-bottom:0.35rem;">
      <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;display:grid;place-items:center;color:var(--blue);font-size:1.1rem;">
        <i class="fa-solid fa-magnifying-glass"></i>
      </div>
      <div>
        <h2 style="font-size:1.25rem;font-weight:800;color:var(--ink);margin:0;">Look Up Your Report</h2>
        <p style="margin:0.15rem 0 0;font-size:0.88rem;color:#64748b;">Check live review, office assignments, and resolution proof</p>
      </div>
    </div>

    <form method="POST" action="{{ route('track.search') }}" style="margin-top:1.25rem;">
      @csrf
      <label class="sr-only" for="tracking_code">Tracking code</label>
      <div class="track-input-group">
        <input id="tracking_code" class="track-input-field" type="text" name="tracking_code" value="{{ old('tracking_code') }}"
          placeholder="e.g. BRG-2025-0012" autocomplete="off" required>
        <button type="submit" class="btn btn-navy" style="min-height:52px;padding:0 1.5rem;font-weight:800;font-size:0.95rem;">
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> Check Status
        </button>
      </div>
      <p class="field-hint" style="margin-top:0.6rem;font-size:0.82rem;color:#64748b;">
        Example format: <code style="background:#f1f5f9;padding:0.15rem 0.4rem;border-radius:4px;color:var(--blue);font-weight:600;">BRG-XXXXXX</code>
      </p>
    </form>
  </section>

  <!-- Help & Instructions Card -->
  <section class="card track-help-card">
    <h3 class="card-title" style="font-size:1.1rem;font-weight:800;color:var(--ink);margin-bottom:1rem;">
      <i class="fa-solid fa-circle-question" style="color:var(--blue);"></i> Where can I find my tracking code?
    </h3>

    <div>
      <div class="help-step-item">
        <div class="help-step-num">1</div>
        <div>
          <strong style="color:var(--ink);font-size:0.92rem;display:block;">Confirmation Screen</strong>
          <span style="font-size:0.86rem;color:#64748b;">Your tracking code was shown on the success screen immediately after you submitted your report.</span>
        </div>
      </div>

      <div class="help-step-item">
        <div class="help-step-num">2</div>
        <div>
          <strong style="color:var(--ink);font-size:0.92rem;display:block;">Personal Notes or Screenshot</strong>
          <span style="font-size:0.86rem;color:#64748b;">If you took a screenshot or copied the code when reporting, check your saved photos or clipboard.</span>
        </div>
      </div>

      <div class="help-step-item">
        <div class="help-step-num">3</div>
        <div>
          <strong style="color:var(--ink);font-size:0.92rem;display:block;">Contact the Barangay Hall</strong>
          <span style="font-size:0.86rem;color:#64748b;">If you misplaced your code, you can contact the Barangay Calarian office with your name and reported location.</span>
        </div>
      </div>
    </div>
  </section>
@endsection
