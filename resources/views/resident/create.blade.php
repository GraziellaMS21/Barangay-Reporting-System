@extends('layouts.app')
@section('title', 'Report a Problem - Barangay Calarian')

@section('hero')
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1 data-i18n="hero_title">Report a problem in Barangay Calarian</h1>
        <p data-i18n="hero_desc">Send a report with category details, photo, and location. Barangay staff will review it,
          route it to the proper office, and keep you updated with a tracking code.</p>
        <em class="hero-motto">Pusong May Malasakit, Serbisyong Tapat sa Mamamayan</em>
        <div class="hero-actions" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:center;">
          <a class="btn btn-ghost" href="{{ route('home') }}" data-i18n="hero_btn_track">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Return
          </a>
        </div>
      </div>
      <div class="hero-seal" aria-hidden="true"><img src="{{ asset('storage/calarian-seal.png') }}" alt=""></div>
    </div>
  </section>
@endsection

@section('content')
  @php
    $masterVoiceEnabled = filter_var(\App\Models\Setting::value('voice.enabled') ?? config('voice.enabled', true), FILTER_VALIDATE_BOOLEAN);
    $residentVoiceEnabled = $masterVoiceEnabled && filter_var(\App\Models\Setting::value('voice.resident_recording_enabled') ?? config('voice.resident_recording_enabled', true), FILTER_VALIDATE_BOOLEAN);

    // Ensure "Other" / "Others" category is always at the last position
    $sortedCategories = $categories->sortBy(function($c) {
        $slug = strtolower($c->slug ?? '');
        $name = strtolower($c->name ?? '');
        return ($slug === 'other' || $slug === 'others' || $name === 'other' || $name === 'others') ? 1 : 0;
    })->values();
  @endphp

  <style>
    /* Card Container & Design Tokens */
    .report-card {
      --wiz-ink: var(--ink, #0b1f4b);
      --wiz-blue: #2563eb;
      --wiz-red: var(--red, #e0352b);
      --wiz-gold: var(--gold, #ffc21a);
      --wiz-surface: #f8fafc;
      --wiz-border: #e2e8f0;
      --wiz-muted: #64748b;
      max-width: 1120px;
      width: 100%;
      margin: 0 auto;
      background: #ffffff;
      border: 1px solid var(--wiz-border);
      border-radius: 20px;
      overflow: hidden;
      padding: 0;
      box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 16px 32px -8px rgba(11, 31, 75, 0.08);
      position: relative;
    }

    /* Zone 1: Top Utility Bar & Slim Dispute Strip */
    .card-topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
      padding: 0.8rem 1.5rem;
      background: var(--wiz-surface);
      border-bottom: 1px solid var(--wiz-border);
    }

    .emergency-pill-strip {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 999px;
      padding: 0.28rem 0.4rem 0.28rem 0.8rem;
      font-size: 0.82rem;
    }

    .emergency-pill-strip .emg-label {
      color: var(--wiz-red);
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .emergency-pill-strip .emg-action-btn {
      background: var(--wiz-red);
      color: #ffffff;
      border: none;
      padding: 0.26rem 0.75rem;
      border-radius: 999px;
      font-size: 0.76rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .emergency-pill-strip .emg-action-btn:hover {
      background: #b91c1c;
      transform: translateY(-1px);
    }

    .emergency-pill-strip .emg-action-btn:active {
      transform: scale(0.98);
    }

    .lang-segmented-control {
      display: inline-flex;
      background: #e2e8f0;
      border: 1px solid #cbd5e1;
      border-radius: 999px;
      padding: 2px;
      gap: 2px;
    }

    .lang-segmented-btn {
      border: none;
      background: transparent;
      padding: 0.28rem 0.8rem;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 700;
      color: var(--wiz-muted);
      cursor: pointer;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .lang-segmented-btn:hover:not(.active) {
      color: var(--wiz-ink);
    }

    .lang-segmented-btn.active {
      background: var(--wiz-ink);
      color: #ffffff;
      box-shadow: 0 2px 4px rgba(11, 31, 75, 0.2);
    }

    .dispute-slim-bar {
      padding: 0.55rem 1.5rem;
      background: #fffbeb;
      border-bottom: 1px solid #fde68a;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
      font-size: 0.82rem;
      color: #92400e;
    }

    .dispute-slim-left {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      font-weight: 600;
    }

    .dispute-slim-link {
      font-weight: 700;
      color: #92400e;
      text-decoration: underline;
      white-space: nowrap;
      transition: color 160ms ease;
    }

    .dispute-slim-link:hover {
      color: #78350f;
    }

    /* Zone 2: 3-Step Interactive Progress Stepper */
    .wizard-stepper-wrap {
      padding: 1.1rem 1.75rem 1rem;
      background: #ffffff;
      border-bottom: 1px solid #f1f5f9;
    }

    .wizard-stepper-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      position: relative;
      align-items: start;
    }

    .wizard-track-rail {
      position: absolute;
      top: 14px;
      left: calc(100% / 6);
      right: calc(100% / 6);
      height: 3px;
      background: #e2e8f0;
      border-radius: 999px;
      z-index: 1;
      overflow: hidden;
    }

    #wizard-progress-fill {
      height: 100%;
      width: 0%;
      background: var(--wiz-blue);
      border-radius: 999px;
      transition: width 350ms cubic-bezier(0.22, 1, 0.36, 1);
    }

    .wizard-step-tab {
      position: relative;
      z-index: 2;
      display: flex;
      flex-direction: column;
      align-items: center;
      gap: 0.4rem;
      background: transparent;
      border: none;
      padding: 0 0.25rem;
      cursor: default;
      text-align: center;
      font-family: inherit;
    }

    .wizard-step-tab.is-completed {
      cursor: pointer;
    }

    .wizard-step-circle {
      width: 30px;
      height: 30px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.82rem;
      font-weight: 800;
      background: #ffffff;
      border: 2px solid #cbd5e1;
      color: var(--wiz-muted);
      transition: all 200ms ease;
    }

    .wizard-step-label {
      font-size: 0.78rem;
      font-weight: 600;
      color: var(--wiz-muted);
      line-height: 1.2;
      transition: color 200ms ease;
    }

    .wizard-step-tab.is-active .wizard-step-circle {
      background: var(--wiz-blue);
      border-color: var(--wiz-blue);
      color: #ffffff;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
      transform: scale(1.06);
    }

    .wizard-step-tab.is-active .wizard-step-label {
      color: var(--wiz-ink);
      font-weight: 700;
    }

    .wizard-step-tab.is-completed .wizard-step-circle {
      background: #10b981;
      border-color: #10b981;
      color: #ffffff;
    }

    .wizard-step-tab.is-completed .wizard-step-label {
      color: #065f46;
      font-weight: 700;
    }

    .wizard-step-tab.is-completed:hover .wizard-step-circle {
      transform: scale(1.05);
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.14);
    }

    /* Zone 3: Active Step Content Body */
    .wizard-body {
      padding: 1.5rem 1.75rem;
    }

    .wizard-panel {
      display: none;
    }

    .wizard-panel.is-active {
      display: block;
    }

    .wizard-panel.anim-slide-right {
      animation: stepSlideInRight 260ms cubic-bezier(0.16, 1, 0.3, 1);
    }

    .wizard-panel.anim-slide-left {
      animation: stepSlideInLeft 260ms cubic-bezier(0.16, 1, 0.3, 1);
    }

    @keyframes stepSlideInRight {
      from { opacity: 0; transform: translateX(16px); }
      to { opacity: 1; transform: translateX(0); }
    }

    @keyframes stepSlideInLeft {
      from { opacity: 0; transform: translateX(-16px); }
      to { opacity: 1; transform: translateX(0); }
    }

    @keyframes panelFadeDown {
      from { opacity: 0; transform: translateY(-6px); }
      to { opacity: 1; transform: translateY(0); }
    }

    @keyframes badgePop {
      from { opacity: 0; transform: scale(0.9); }
      to { opacity: 1; transform: scale(1); }
    }

    @keyframes gentleShake {
      0%, 100% { transform: translateX(0); }
      25% { transform: translateX(-5px); }
      75% { transform: translateX(5px); }
    }

    .gentle-shake {
      animation: gentleShake 260ms ease-in-out;
    }

    /* Voice Strip (Compact Inline Trigger) */
    .voice-strip-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 0.75rem 1rem;
      margin-bottom: 1.25rem;
    }

    .voice-strip-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .voice-strip-icon {
      width: 36px;
      height: 36px;
      border-radius: 10px;
      background: #dbeafe;
      color: var(--wiz-blue);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.95rem;
      flex-shrink: 0;
    }

    .voice-trigger-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      background: #ffffff;
      color: #1d4ed8;
      border: 1.5px solid #bfdbfe;
      border-radius: 10px;
      padding: 0.48rem 0.9rem;
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 1px 2px rgba(11, 31, 75, 0.04);
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
      white-space: nowrap;
    }

    .voice-trigger-btn:hover {
      background: #eff6ff;
      border-color: #93c5fd;
      transform: translateY(-1px);
    }

    .voice-trigger-btn:active {
      transform: scale(0.98);
    }

    .voice-saved-pill {
      display: none;
      align-items: center;
      gap: 0.35rem;
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #a7f3d0;
      border-radius: 999px;
      padding: 0.2rem 0.6rem;
      font-size: 0.74rem;
      font-weight: 700;
    }

    .from-recording-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      background: #ecfdf5;
      color: #065f46;
      border: 1px solid #a7f3d0;
      border-radius: 999px;
      padding: 0.14rem 0.55rem;
      font-size: 0.72rem;
      font-weight: 700;
      margin-left: 0.4rem;
      vertical-align: middle;
      animation: badgePop 180ms ease-out;
    }

    /* Section Headings */
    .card-section-heading {
      display: flex;
      align-items: center;
      gap: 0.55rem;
      margin-bottom: 0.75rem;
    }

    .card-section-icon {
      display: inline-grid;
      place-items: center;
      width: 30px;
      height: 30px;
      border-radius: 9px;
      background: #eff6ff;
      color: var(--wiz-blue);
      font-size: 0.85rem;
      flex-shrink: 0;
    }

    .card-section-title {
      font-size: 1rem;
      font-weight: 700;
      color: var(--wiz-ink);
      margin: 0;
      letter-spacing: -0.01em;
    }

    .optional-pill-badge {
      background: #f1f5f9;
      color: var(--wiz-muted);
      font-size: 0.72rem;
      font-weight: 600;
      padding: 0.15rem 0.5rem;
      border-radius: 999px;
    }

    /* Category Chips */
    .cat-chips-wrap {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      margin-bottom: 1rem;
    }

    .cat-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      padding: 0.48rem 0.9rem;
      border: 1.5px solid #e2e8f0;
      border-radius: 999px;
      background: #f8fafc;
      color: var(--tx);
      font-size: 0.84rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
      user-select: none;
    }

    .cat-chip i {
      color: var(--wiz-muted);
      font-size: 0.88rem;
      transition: color 160ms, transform 160ms;
    }

    .cat-chip:hover {
      background: #eff6ff;
      border-color: #93c5fd;
      color: #1d4ed8;
      transform: translateY(-1px);
    }

    .cat-chip:hover i {
      color: var(--wiz-blue);
    }

    .cat-chip:active {
      transform: scale(0.98);
    }

    .cat-chip.selected {
      background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
      color: #ffffff;
      border-color: #1e40af;
      box-shadow: 0 4px 10px rgba(37, 99, 235, 0.25);
      transform: translateY(-1px);
    }

    .cat-chip.selected i {
      color: #fbbf24;
    }

    /* Dynamic Questions Container */
    #dynamic-questions-container {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-left: 3.5px solid var(--wiz-blue);
      border-radius: 12px;
      padding: 0.95rem 1.1rem;
      margin-bottom: 1.1rem;
      animation: panelFadeDown 220ms ease-out;
    }

    .dynamic-opt-chip {
      min-height: 38px;
      padding: 0.35rem 0.85rem;
      border-radius: 999px;
      border: 1.5px solid #cbd5e1;
      background: #ffffff;
      color: var(--wiz-ink);
      font-size: 0.83rem;
      font-weight: 600;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.35rem;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dynamic-opt-chip:hover {
      border-color: #93c5fd;
      background: #eff6ff;
      transform: translateY(-1px);
    }

    .dynamic-opt-chip:active {
      transform: scale(0.98);
    }

    .dynamic-opt-chip.active {
      background: #eff6ff;
      border-color: var(--wiz-blue);
      color: var(--wiz-blue);
      font-weight: 700;
      box-shadow: 0 0 0 1px var(--wiz-blue);
    }

    /* Problem Description */
    .desc-label-row {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.4rem;
    }

    .wizard-textarea {
      width: 100%;
      border-radius: 12px;
      border: 1.5px solid #cbd5e1;
      padding: 0.7rem 0.9rem;
      font-size: 0.93rem;
      color: var(--wiz-ink);
      background: #ffffff;
      transition: border-color 160ms ease, box-shadow 160ms ease;
      resize: vertical;
    }

    .wizard-textarea:focus {
      outline: none;
      border-color: var(--wiz-blue);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    .wizard-input,
    .wizard-select {
      width: 100%;
      height: 44px;
      border-radius: 10px;
      border: 1.5px solid #cbd5e1;
      padding: 0 0.85rem;
      font-size: 0.92rem;
      color: var(--wiz-ink);
      background: #ffffff;
      transition: border-color 160ms ease, box-shadow 160ms ease;
    }

    .wizard-input:focus,
    .wizard-select:focus {
      outline: none;
      border-color: var(--wiz-blue);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12);
    }

    /* Step Inline Error Alert */
    .step-inline-error {
      display: none;
      margin-top: 0.55rem;
      padding: 0.55rem 0.85rem;
      border-radius: 10px;
      background: #fef2f2;
      border: 1px solid #fecaca;
      color: #b91c1c;
      font-size: 0.82rem;
      font-weight: 600;
      align-items: center;
      gap: 0.45rem;
      animation: panelFadeDown 220ms ease-out;
    }

    .step-inline-error.is-visible {
      display: flex;
    }

    /* Danger Callout & Inline Emergency */
    .danger-callout-box {
      background: #fff8f8;
      border: 1px solid #fecaca;
      border-radius: 12px;
      padding: 0.7rem 1rem;
      margin-bottom: 0.75rem;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      cursor: pointer;
      transition: background 160ms, border-color 160ms;
    }

    .danger-callout-box:hover {
      background: #fef2f2;
      border-color: #fca5a5;
    }

    #inline-emergency {
      animation: panelFadeDown 220ms ease-out;
    }

    /* Collapsible Card Sections */
    .card-collapsible {
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      margin-bottom: 0;
      background: #ffffff;
      overflow: hidden;
      transition: border-color 160ms, box-shadow 160ms;
    }

    .card-collapsible[open] {
      border-color: #cbd5e1;
      box-shadow: 0 2px 5px rgba(11, 31, 75, 0.04);
    }

    .card-collapsible summary {
      padding: 0.75rem 1rem;
      font-weight: 700;
      font-size: 0.88rem;
      color: var(--wiz-ink);
      cursor: pointer;
      display: flex;
      align-items: center;
      justify-content: space-between;
      background: #f8fafc;
      user-select: none;
      list-style: none;
    }

    .card-collapsible summary::-webkit-details-marker {
      display: none;
    }

    .card-collapsible summary::after {
      content: '\f078';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      font-size: 0.78rem;
      color: #94a3b8;
      transition: transform 200ms ease;
    }

    .card-collapsible[open] summary::after {
      transform: rotate(180deg);
    }

    .card-collapsible-content {
      padding: 1rem 1.1rem;
      border-top: 1px solid #f1f5f9;
      background: #ffffff;
    }

    /* Toggle Buttons */
    .toggle-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      flex-wrap: wrap;
    }

    .toggle-btns {
      display: inline-flex;
      border-radius: 10px;
      overflow: hidden;
      border: 1.5px solid #cbd5e1;
      background: #f1f5f9;
      padding: 2px;
      gap: 2px;
      flex-wrap: wrap;
    }

    .toggle-btn {
      min-height: 36px;
      padding: 0.3rem 0.85rem;
      border: none;
      background: transparent;
      cursor: pointer;
      font-size: 0.82rem;
      font-weight: 700;
      color: var(--wiz-muted);
      border-radius: 8px;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
      display: inline-flex;
      align-items: center;
      justify-content: center;
    }

    .toggle-btn:hover:not(.active) {
      color: var(--wiz-ink);
    }

    .toggle-btn.active {
      background: var(--wiz-blue);
      color: #ffffff;
      box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
    }

    /* Step 2: Location Grid & Status Pill */
    .location-status-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.22rem 0.7rem;
      border-radius: 999px;
      font-size: 0.75rem;
      font-weight: 700;
      background: #f1f5f9;
      color: var(--wiz-muted);
      border: 1px solid #e2e8f0;
      transition: all 200ms ease;
    }

    .location-status-pill.is-ready {
      background: #ecfdf5;
      color: #065f46;
      border-color: #a7f3d0;
    }

    .step2-split-grid {
      display: grid;
      grid-template-columns: 1.25fr 1fr;
      gap: 1.15rem;
      align-items: start;
    }

    .map-container-box {
      border: 1.5px solid #cbd5e1;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    .btn-gps-action {
      width: 100%;
      height: 44px;
      background: #eff6ff;
      border: 1.5px solid #bfdbfe;
      color: #1d4ed8;
      border-radius: 10px;
      font-weight: 700;
      font-size: 0.88rem;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .btn-gps-action:hover {
      background: #dbeafe;
      border-color: #93c5fd;
      transform: translateY(-1px);
    }

    .btn-gps-action:active {
      transform: scale(0.98);
    }

    /* Step 3: Photo Dropzone & Previews */
    .photo-dropzone {
      border: 2px dashed #cbd5e1;
      border-radius: 14px;
      background: #f8fafc;
      padding: 1.25rem 1rem;
      text-align: center;
      cursor: pointer;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
      margin-bottom: 1rem;
    }

    .photo-dropzone:hover,
    .photo-dropzone.is-dragover {
      border-color: var(--wiz-blue);
      background: #eff6ff;
    }

    .photo-dropzone-icon {
      width: 42px;
      height: 42px;
      border-radius: 999px;
      background: #dbeafe;
      color: var(--wiz-blue);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      margin-bottom: 0.45rem;
    }

    #photo-previews.photo-preview-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(92px, 1fr));
      gap: 0.65rem;
      margin-top: 0.75rem;
    }

    #photo-previews:empty {
      display: none;
    }

    #anon-notice-banner {
      animation: panelFadeDown 220ms ease-out;
    }

    .consent-slate-box {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 0.9rem 1rem;
      margin-top: 1rem;
      font-size: 0.83rem;
      line-height: 1.45;
    }

    /* Zone 4: Unified Wizard Footer Bar */
    .wizard-footer-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      padding: 1rem 1.75rem;
      background: var(--wiz-surface);
      border-top: 1px solid var(--wiz-border);
    }

    .wizard-btn-primary {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.5rem;
      background: var(--wiz-blue);
      color: #ffffff;
      border: none;
      border-radius: 11px;
      padding: 0.65rem 1.35rem;
      min-height: 44px;
      font-size: 0.92rem;
      font-weight: 700;
      cursor: pointer;
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.25);
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .wizard-btn-primary:hover {
      background: #1d4ed8;
      transform: translateY(-1px);
    }

    .wizard-btn-primary:active {
      transform: scale(0.98);
    }

    .wizard-btn-outline {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.45rem;
      background: #ffffff;
      color: var(--wiz-ink);
      border: 1.5px solid #cbd5e1;
      border-radius: 11px;
      padding: 0.6rem 1.15rem;
      min-height: 44px;
      font-size: 0.9rem;
      font-weight: 700;
      text-decoration: none;
      cursor: pointer;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .wizard-btn-outline:hover {
      background: #f1f5f9;
      border-color: #94a3b8;
      transform: translateY(-1px);
    }

    .wizard-btn-outline:active {
      transform: scale(0.98);
    }

    #btn-final-submit {
      min-width: 190px;
      min-height: 46px;
      font-weight: 800;
      font-size: 0.96rem;
      padding: 0.65rem 1.5rem;
      border-radius: 11px;
      box-shadow: 0 4px 12px rgba(255, 194, 26, 0.35);
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    #btn-final-submit:hover {
      transform: translateY(-1px);
    }

    #btn-final-submit:active {
      transform: scale(0.98);
    }

    /* Responsive Breakpoints */
    @media (max-width: 768px) {
      .step2-split-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (max-width: 640px) {
      .report-card {
        border-radius: 16px;
      }
      .card-topbar,
      .dispute-slim-bar {
        padding-left: 1.15rem;
        padding-right: 1.15rem;
      }
      .wizard-stepper-wrap {
        padding: 0.95rem 1rem 0.85rem;
      }
      .wizard-body {
        padding: 1.15rem;
      }
      .wizard-footer-bar {
        padding: 0.9rem 1.15rem;
      }
      .wizard-footer-bar.is-step-1,
      .wizard-footer-bar.is-step-2 {
        flex-direction: row;
      }
      .wizard-footer-bar.is-step-1 > *,
      .wizard-footer-bar.is-step-2 > * {
        flex: 1;
      }
      .wizard-footer-bar.is-step-3 {
        flex-direction: column-reverse;
        align-items: stretch;
      }
      .wizard-footer-bar.is-step-3 #btn-final-submit,
      .wizard-footer-bar.is-step-3 #btn-prev-3 {
        width: 100%;
        min-height: 46px;
        justify-content: center;
      }
    }

    @media (prefers-reduced-motion: reduce) {
      .wizard-panel.anim-slide-right,
      .wizard-panel.anim-slide-left,
      #dynamic-questions-container,
      #inline-emergency,
      #anon-notice-banner,
      .from-recording-badge,
      .gentle-shake {
        animation: none !important;
      }
      #wizard-progress-fill,
      .wizard-step-circle,
      .cat-chip,
      .dynamic-opt-chip,
      .toggle-btn,
      .wizard-btn-primary,
      .wizard-btn-outline,
      #btn-final-submit,
      .photo-dropzone {
        transition: none !important;
      }
    }

    /* Emergency Hotlines Screen Card */
    .emergency-hero-card {
      max-width: 1120px;
      width: 100%;
      margin: 0 auto;
      border: 2px solid #ef4444;
      border-radius: 20px;
      background: #ffffff;
      padding: 2rem;
      box-shadow: 0 10px 25px -5px rgba(224, 53, 43, 0.15);
      animation: panelFadeDown 220ms ease-out;
    }

    .hotline-card-enhanced {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.95rem 1.15rem;
      border-radius: 12px;
      border: 1.5px solid #e2e8f0;
      background: #f8fafc;
      color: var(--ink);
      font-weight: 700;
      text-decoration: none;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .hotline-card-enhanced:hover {
      background: #eff6ff;
      border-color: #93c5fd;
      color: #1d4ed8;
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(36, 86, 214, 0.1);
    }

    .hotline-card-primary {
      background: #fee2e2;
      border-color: #fca5a5;
      color: #b91c1c;
      font-size: 1.05rem;
    }

    .hotline-card-primary:hover {
      background: #fecaca;
      border-color: #ef4444;
      color: #991b1b;
    }
  </style>

  <!-- Emergency Screen Card -->
  <section id="emergency-screen" class="emergency-hero-card" style="display:none;">
    <div style="display:flex;align-items:center;gap:1rem;color:var(--red);margin-bottom:1.25rem;padding-bottom:1rem;border-bottom:1px solid #fee2e2;">
      <div style="width:48px;height:48px;border-radius:12px;background:#fee2e2;display:grid;place-items:center;flex-shrink:0;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size:1.6rem;color:var(--red);"></i>
      </div>
      <div>
        <h2 style="color:var(--red);margin:0;font-size:1.35rem;font-weight:800;" data-i18n="emg_title">Emergency Detected</h2>
        <p style="margin:0.2rem 0 0;color:#64748b;font-size:0.92rem;" data-i18n="emg_subtitle">For immediate life-threatening danger, call official hotlines directly.</p>
      </div>
    </div>

    <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.5rem;">
      <p style="font-weight:800;color:#b91c1c;margin:0;display:flex;align-items:center;gap:0.5rem;" data-i18n="emg_not_monitored">
        <i class="fa-solid fa-bell-slash"></i> THIS ONLINE SYSTEM IS NOT MONITORED IN REAL-TIME.
      </p>
      <p style="margin:0.35rem 0 0;color:#7f1d1d;font-size:0.88rem;" data-i18n="emg_call_prompt">
        Please contact emergency services immediately using the numbers below:
      </p>
    </div>

    <div style="display:grid;gap:0.75rem;margin-bottom:1.75rem;">
      <a href="tel:911" class="hotline-card-enhanced hotline-card-primary">
        <span style="display:flex;align-items:center;gap:0.75rem;">
          <i class="fa-solid fa-phone-volume"></i>
          <span>911 — National Emergency Hotline</span>
        </span>
        <span style="background:var(--red);color:#ffffff;padding:0.25rem 0.65rem;border-radius:999px;font-size:0.75rem;font-weight:700;">CALL NOW</span>
      </a>

      @foreach($contacts as $contact)
        @php $num = trim((string) $contact->number); @endphp
        @if($num !== '' && stripos($num, 'N/A') === false && $num !== '911')
          <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}" class="hotline-card-enhanced">
            <span style="display:flex;align-items:center;gap:0.65rem;">
              <i class="fa-solid fa-phone" style="color:var(--blue);"></i>
              <span>{{ $contact->name }}</span>
            </span>
            <span style="color:var(--blue);font-family:monospace;font-size:0.95rem;">{{ $num }}</span>
          </a>
        @endif
      @endforeach
    </div>

    <div style="text-align:center;padding-top:1rem;border-top:1px solid #f1f5f9;">
      <button type="button" class="btn btn-outline" onclick="hideEmergency()" data-i18n="emg_back_btn" style="min-width:180px;">
        <i class="fa-solid fa-arrow-left"></i> Return to report
      </button>
    </div>
  </section>

  <!-- Main Report Form Card -->
  <section id="report-form" class="card report-card">
    <!-- Zone 1: Consolidated Utility Header -->
    <div class="card-topbar">
      <div class="emergency-pill-strip">
        <span class="emg-label">
          <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
          <span data-i18n="emg_strip">Emergency? Call 911</span>
        </span>
        <button type="button" class="emg-action-btn" onclick="showEmergency()">
          <i class="fa-solid fa-phone-flip" aria-hidden="true"></i>
          <span data-i18n="emg_btn">Emergency / Crime</span>
        </button>
      </div>

      <div class="lang-segmented-control" role="group" aria-label="Select Language">
        <button type="button" class="lang-segmented-btn js-lang-btn-en active" onclick="setLanguage('en')">EN</button>
        <button type="button" class="lang-segmented-btn js-lang-btn-fil" onclick="setLanguage('fil')">FIL</button>
      </div>
    </div>

    <!-- Slim Dispute Redirect Bar -->
    <div class="dispute-slim-bar">
      <span class="dispute-slim-left">
        <i class="fa-solid fa-circle-info" style="color:#d97706;" aria-hidden="true"></i>
        <span data-i18n="dispute_prompt">Is this a dispute with a specific person?</span>
      </span>
      <a href="{{ route('complaint.create') }}" class="dispute-slim-link" data-i18n="dispute_link">
        File a Dispute Complaint &rarr;
      </a>
    </div>

    <!-- Zone 2: 3-Step Interactive Progress Stepper -->
    <div class="wizard-stepper-wrap" aria-label="Report progress">
      <div class="wizard-stepper-grid">
        <div class="wizard-track-rail" aria-hidden="true">
          <div id="wizard-progress-fill"></div>
        </div>

        <button type="button" class="wizard-step-tab is-active" data-step="1" onclick="handleStepTabClick(1)" aria-current="step">
          <span class="wizard-step-circle" data-step-num="1">1</span>
          <span class="wizard-step-label" data-i18n="wiz_step_1">Issue Details</span>
        </button>

        <button type="button" class="wizard-step-tab" data-step="2" onclick="handleStepTabClick(2)">
          <span class="wizard-step-circle" data-step-num="2">2</span>
          <span class="wizard-step-label" data-i18n="wiz_step_2">Location</span>
        </button>

        <button type="button" class="wizard-step-tab" data-step="3" onclick="handleStepTabClick(3)">
          <span class="wizard-step-circle" data-step-num="3">3</span>
          <span class="wizard-step-label" data-i18n="wiz_step_3">Photo &amp; Submit</span>
        </button>
      </div>
    </div>

    <form id="report-form-el" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="has_consent_field" value="1">
      <input type="hidden" name="report_type" id="report_type_input" value="non_emergency">
      <input type="hidden" name="category_id" id="category_id_input" value="{{ old('category_id') }}">
      <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
      <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
      <input type="hidden" name="is_ongoing" id="is_ongoing" value="{{ old('is_ongoing') !== null ? old('is_ongoing') : '' }}">
      <input type="hidden" name="is_recurring" id="is_recurring" value="{{ old('is_recurring') !== null ? old('is_recurring') : '' }}">
      <div id="voice-provenance-container"></div>

      <!-- Zone 3: Active Step Content Body -->
      <div class="wizard-body">
        @if($errors->any())
          <div role="alert" class="form-errors" style="margin-bottom:1.1rem;border-radius:12px;padding:0.85rem 1rem;">
            <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;margin-bottom:0.35rem;color:#b91c1c;">
              <i class="fa-solid fa-circle-exclamation"></i> Please correct the errors below:
            </div>
            <ul style="margin:0;padding-left:1.3rem;">
              @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
              @endforeach
            </ul>
          </div>
        @endif

        <!-- STEP 1: Issue Details -->
        <div class="wizard-panel is-active" data-wizard-panel="1">
          @if($residentVoiceEnabled)
            <!-- Quick Report by Voice Strip (Compact Inline Trigger) -->
            <div id="voice-intake-strip" class="voice-strip-card">
              <div class="voice-strip-row">
                <div style="display:flex;align-items:center;gap:0.75rem;min-width:0;">
                  <div class="voice-strip-icon" aria-hidden="true">
                    <i class="fa-solid fa-microphone-lines"></i>
                  </div>
                  <div style="min-width:0;">
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                      <strong style="font-size:0.9rem;color:var(--wiz-ink);">Prefer to speak? Quick report by voice</strong>
                      <span id="voice-saved-pill" class="voice-saved-pill">
                        <i class="fa-solid fa-circle-check"></i>
                        <span data-i18n="voice_saved_badge">Recording saved</span>
                      </span>
                    </div>
                    <div style="font-size:0.77rem;color:var(--wiz-muted);margin-top:0.1rem;">
                      AI transcribes English, Filipino, Bisaya, or Chavacano to prefill fields &middot;
                      <a href="#category-heading" style="color:var(--wiz-muted);text-decoration:none;">or fill in the form yourself below &darr;</a>
                    </div>
                  </div>
                </div>

                <button type="button" id="btn-open-voice-modal" class="voice-trigger-btn" onclick="openVoiceModal()">
                  <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                  <span id="btn-open-voice-label" data-i18n="voice_btn_trigger">Record / Try Demo</span>
                </button>
              </div>

              {{-- Clarity / Feedback Banner inside strip --}}
              <div id="resident-voice-clarity-banner" style="display:none;margin-top:0.75rem;padding:0.7rem 0.9rem;border-radius:8px;font-size:0.84rem;" aria-live="polite"></div>
            </div>
          @endif

          <!-- Category Selection -->
          <div class="card-section-heading" id="category-heading" aria-label="Choose a category (optional)">
            <div class="card-section-icon"><i class="fa-solid fa-tags"></i></div>
            <h2 class="card-section-title" data-i18n="choose_category">Choose a category</h2>
            <span class="optional-pill-badge" data-i18n="lbl_optional_pill">Optional</span>
          </div>

          <div class="cat-chips-wrap">
            @foreach($sortedCategories as $category)
              <button type="button" class="cat-chip {{ old('category_id') == $category->id ? 'selected' : '' }}"
                data-cat-id="{{ $category->id }}" data-cat-slug="{{ $category->slug }}"
                onclick="selectCategory({{ $category->id }})"
                aria-pressed="{{ old('category_id') == $category->id ? 'true' : 'false' }}">
                @if($category->slug === 'drainage') <i class="fa-solid fa-water"></i>
                @elseif($category->slug === 'garbage') <i class="fa-solid fa-trash-can"></i>
                @elseif($category->slug === 'road-damage') <i class="fa-solid fa-road"></i>
                @elseif($category->slug === 'streetlight') <i class="fa-solid fa-lightbulb"></i>
                @elseif($category->slug === 'flooding') <i class="fa-solid fa-house-flood-water"></i>
                @elseif(in_array(strtolower($category->slug), ['other', 'others']) || in_array(strtolower($category->name), ['other', 'others'])) <i class="fa-solid fa-ellipsis"></i>
                @else <i class="fa-solid fa-circle-question"></i>
                @endif
                <span class="cat-name-label" data-slug="{{ $category->slug }}" data-name-en="{{ $category->name }}">{{ $category->name }}</span>
              </button>
            @endforeach
          </div>
          @error('category_id')
            <p class="field-error" style="margin-top:-0.4rem;margin-bottom:0.85rem;">{{ $message }}</p>
          @enderror

          <!-- Dynamic Questions (Rendered on Category Selection) -->
          <div id="dynamic-questions-container" style="display:none;">
            <div id="dynamic-questions-fields"></div>
          </div>

          <!-- Problem Description -->
          <div class="field" style="margin-bottom:1rem;">
            <div class="desc-label-row">
              <label for="description" style="font-size:0.93rem;font-weight:700;margin:0;display:inline-flex;align-items:center;gap:0.4rem;">
                <i class="fa-solid fa-align-left" style="color:var(--wiz-blue);"></i>
                <span id="description-label" data-i18n="lbl_description">What is the problem?</span>
                <span style="color:var(--wiz-red);">*</span>
              </label>
              <div id="description-char-counter" style="font-size:0.78rem;font-weight:700;color:#64748b;" aria-live="polite">
                <span id="desc-char-count">0</span> / 10 <span id="desc-char-status">min</span>
              </div>
            </div>
            <textarea id="description" name="description" class="wizard-textarea" rows="3" required minlength="10" maxlength="2000"
              placeholder="Please describe the issue in detail...">{{ old('description') }}</textarea>
            <p class="field-hint" data-i18n="hint_description" style="margin:0.35rem 0 0;font-size:0.78rem;color:var(--wiz-muted);">
              English, Filipino, Bisaya, or Chavacano
            </p>
            <div id="step1-error" class="step-inline-error" role="alert">
              <i class="fa-solid fa-circle-exclamation"></i>
              <span data-i18n="err_step1_desc">Please enter at least 10 characters describing the problem.</span>
            </div>
            @error('description')
              <p class="field-error" style="margin-top:0.35rem;">{{ $message }}</p>
            @enderror
          </div>

          <!-- Danger Right Now Checkbox -->
          <label class="danger-callout-box">
            <input type="checkbox" id="people_at_risk_cb" name="people_at_risk" value="1"
              {{ old('people_at_risk') ? 'checked' : '' }} onchange="toggleDanger(this.checked)"
              style="width:1.1rem;height:1.1rem;accent-color:var(--wiz-red);cursor:pointer;flex-shrink:0;">
            <div style="flex:1;">
              <span style="font-weight:700;color:#991b1b;font-size:0.9rem;" data-i18n="danger_checkbox">
                Someone is in danger right now
              </span>
              <p style="margin:0.1rem 0 0;font-size:0.78rem;color:#b91c1c;" data-i18n="danger_sub">
                Check this if there is immediate risk to health, safety, or life.
              </p>
            </div>
          </label>

          <!-- Inline Emergency Warning -->
          <div id="inline-emergency"
            style="display:none;background:#fef2f2;border:1.5px solid #fecaca;border-left:4px solid var(--wiz-red);border-radius:12px;padding:0.9rem 1.1rem;margin-bottom:0.85rem;">
            <p style="color:#b91c1c;font-weight:800;margin:0 0 0.5rem;display:flex;align-items:center;gap:0.5rem;font-size:0.9rem;" data-i18n="emg_inline">
              <i class="fa-solid fa-phone-volume"></i> Emergency contacts
            </p>
            <a href="tel:911" class="hotline-card-enhanced hotline-card-primary" style="margin-bottom:0.45rem;padding:0.75rem 1rem;">
              <span>911 — National Emergency Hotline</span>
              <span style="font-size:0.75rem;background:var(--wiz-red);color:#fff;padding:0.2rem 0.6rem;border-radius:999px;">CALL NOW</span>
            </a>
            @foreach($contacts as $contact)
              @php $num = trim((string) $contact->number); @endphp
              @if($num !== '' && stripos($num, 'N/A') === false && $num !== '911')
                <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}" class="hotline-card-enhanced" style="margin-bottom:0.35rem;padding:0.6rem 0.9rem;">
                  <span>{{ $contact->name }}</span>
                  <span style="color:var(--wiz-blue);font-family:monospace;">{{ $num }}</span>
                </a>
              @endif
            @endforeach
          </div>

          <!-- More Details (Collapsible Card Section) -->
          <details class="card-collapsible">
            <summary>
              <span style="display:flex;align-items:center;gap:0.5rem;">
                <i class="fa-solid fa-sliders" style="color:var(--wiz-blue);"></i>
                <span data-i18n="more_details">More details (optional)</span>
              </span>
            </summary>
            <div class="card-collapsible-content">
              <div class="toggle-row" style="margin-bottom:0.85rem;">
                <div>
                  <strong style="color:var(--wiz-ink);display:block;font-size:0.88rem;" data-i18n="q_ongoing">Is this happening right now?</strong>
                  <small style="color:var(--wiz-muted);font-size:0.78rem;" data-i18n="q_ongoing_sub">Indicate whether the incident is currently active</small>
                </div>
                <div class="toggle-btns" id="ongoing-toggle-btns">
                  <button type="button" class="toggle-btn {{ old('is_ongoing') === '1' || old('is_ongoing') === 1 ? 'active' : '' }}"
                    onclick="setBinaryField('is_ongoing', 1, this)" data-i18n="btn_yes">Yes</button>
                  <button type="button" class="toggle-btn {{ old('is_ongoing') === '0' || (old('is_ongoing') === 0 && old('is_ongoing') !== null && old('is_ongoing') !== '') ? 'active' : '' }}"
                    onclick="setBinaryField('is_ongoing', 0, this)" data-i18n="btn_no">No</button>
                  <button type="button" class="toggle-btn"
                    onclick="setBinaryField('is_ongoing', '', this)" data-i18n="btn_not_sure">Not sure</button>
                </div>
              </div>

              <div class="toggle-row" style="padding-top:0.75rem;border-top:1px dashed #e2e8f0;">
                <div>
                  <strong style="color:var(--wiz-ink);display:block;font-size:0.88rem;" data-i18n="q_recurring">Recurring or one-time?</strong>
                  <small style="color:var(--wiz-muted);font-size:0.78rem;" data-i18n="q_recurring_sub">Has this problem happened repeatedly in your area?</small>
                </div>
                <div class="toggle-btns" id="recurring-toggle-btns">
                  <button type="button" class="toggle-btn {{ old('is_recurring') === '1' || old('is_recurring') === 1 ? 'active' : '' }}"
                    onclick="setBinaryField('is_recurring', 1, this)" data-i18n="btn_recurring">Recurring</button>
                  <button type="button" class="toggle-btn {{ old('is_recurring') === '0' || (old('is_recurring') === 0 && old('is_recurring') !== null && old('is_recurring') !== '') ? 'active' : '' }}"
                    onclick="setBinaryField('is_recurring', 0, this)" data-i18n="btn_onetime">One-time</button>
                  <button type="button" class="toggle-btn"
                    onclick="setBinaryField('is_recurring', '', this)" data-i18n="btn_not_sure">Not sure</button>
                </div>
              </div>
            </div>
          </details>
        </div>

        <!-- STEP 2: Location -->
        <div class="wizard-panel" data-wizard-panel="2">
          <div style="margin-bottom:0.9rem;">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;margin-bottom:0.25rem;">
              <div style="display:flex;align-items:center;gap:0.55rem;">
                <div class="card-section-icon"><i class="fa-solid fa-map-location-dot"></i></div>
                <h2 class="card-section-title" data-i18n="where_is_it">Where is it?</h2>
              </div>
              <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                <button type="button" id="btn-quick-locate" class="wizard-btn-outline" style="min-height:32px;padding:0.25rem 0.7rem;font-size:0.78rem;border-radius:999px;">
                  <i class="fa-solid fa-crosshairs" style="color:var(--wiz-blue);"></i>
                  <span data-i18n="btn_quick_locate">Use GPS</span>
                </button>
                <span id="location-status-pill" class="location-status-pill">
                  <i class="fa-solid fa-location-dot"></i>
                  <span id="location-status-text" data-i18n="loc_status_pending">No location yet</span>
                </span>
              </div>
            </div>
            <p style="margin:0;font-size:0.82rem;color:var(--wiz-muted);" data-i18n="loc_help">
              Provide at least one: drop a map pin, choose a purok, or enter a landmark.
            </p>
          </div>

          <div class="step2-split-grid">
            <!-- Left Column: Map -->
            <div>
              <div class="map-container-box">
                <div id="map" class="map-frame" style="height:300px;min-height:280px;" aria-label="Choose the report location on the map"></div>
              </div>
              <div id="nearby-line" style="display:none;margin-top:0.55rem;padding:0.45rem 0.75rem;background:#fffbeb;border:1px solid #fde68a;border-radius:999px;font-size:0.8rem;"></div>
              <div id="nearby-list" style="display:none;margin-top:0.35rem;font-size:0.82rem;"></div>
            </div>

            <!-- Right Column: Inputs + GPS CTA -->
            <div id="step2-location-inputs" style="display:flex;flex-direction:column;gap:0.9rem;">
              <div class="field" style="margin:0;">
                <label for="purok_id" style="display:block;font-size:0.85rem;font-weight:700;color:var(--wiz-ink);margin-bottom:0.35rem;">
                  <i class="fa-solid fa-building-user" style="color:var(--wiz-blue);margin-right:0.3rem;"></i>
                  <span data-i18n="lbl_purok">Purok</span>
                </label>
                <select id="purok_id" name="purok_id" class="wizard-select">
                  <option value="" data-i18n="opt_select_purok">Select purok</option>
                  @foreach($puroks as $purok)
                    <option value="{{ $purok->id }}" @selected(old('purok_id') == $purok->id)>{{ $purok->name }}</option>
                  @endforeach
                </select>
              </div>

              <div class="field" style="margin:0;">
                <label for="landmark" style="display:block;font-size:0.85rem;font-weight:700;color:var(--wiz-ink);margin-bottom:0.35rem;">
                  <i class="fa-solid fa-signs-post" style="color:var(--wiz-blue);margin-right:0.3rem;"></i>
                  <span data-i18n="lbl_landmark">Landmark or Street</span>
                </label>
                <input type="text" id="landmark" name="landmark" class="wizard-input" value="{{ old('landmark') }}"
                  placeholder="e.g. Near the chapel, beside school">
              </div>

              <div>
                <button type="button" id="btn-locate" class="btn-gps-action">
                  <i class="fa-solid fa-crosshairs"></i>
                  <span data-i18n="btn_use_location">Use my current location</span>
                </button>
                @error('location')
                  <p class="field-error" style="margin-top:0.4rem;">{{ $message }}</p>
                @enderror
                @error('purok_id')
                  <p class="field-error" style="margin-top:0.4rem;">{{ $message }}</p>
                @enderror
                @error('landmark')
                  <p class="field-error" style="margin-top:0.4rem;">{{ $message }}</p>
                @enderror
              </div>
            </div>
          </div>

          <div id="step2-error" class="step-inline-error" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span data-i18n="err_step2_loc">Please drop a map pin, choose a purok, or enter a landmark.</span>
          </div>
        </div>

        <!-- STEP 3: Photo, Updates & Submit -->
        <div class="wizard-panel" data-wizard-panel="3">
          <div class="card-section-heading">
            <div class="card-section-icon"><i class="fa-solid fa-camera"></i></div>
            <h2 class="card-section-title" data-i18n="photo_updates">Photo and updates</h2>
          </div>

          <!-- Interactive Photo Upload Dropzone -->
          <div class="field" style="margin-bottom:1rem;">
            <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png" multiple hidden>
            <div id="photo-dropzone" class="photo-dropzone" role="button" tabindex="0" aria-label="Add photos">
              <div class="photo-dropzone-icon">
                <i class="fa-solid fa-cloud-arrow-up"></i>
              </div>
              <div id="add-photos" style="font-weight:700;font-size:0.92rem;color:var(--wiz-ink);margin-bottom:0.2rem;" data-i18n="photo_dropzone_title">
                Click or drag to add photos (optional)
              </div>
              <div style="font-size:0.78rem;color:var(--wiz-muted);" data-i18n="photo_dropzone_tip">
                Tip: show the problem near a landmark; avoid faces &amp; license plates · Max 5 MB
              </div>
            </div>

            <div id="photo-previews" class="photo-preview-grid" aria-live="polite"></div>
            <p id="photo-empty-state" class="field-hint" style="display:none;">No photos selected yet.</p>
            <p id="photo-upload-error" class="field-hint" role="alert" style="color:var(--wiz-red);font-weight:700;margin-top:0.4rem;" hidden></p>
            <p id="photo-total-size" class="field-hint" aria-live="polite" style="font-weight:600;margin-top:0.35rem;font-size:0.8rem;color:var(--wiz-muted);"></p>
            @error('photos')
              <p class="field-error">{{ $message }}</p>
            @enderror
            @error('photos.*')
              <p class="field-error">{{ $message }}</p>
            @enderror
          </div>

          <!-- Updates & Anonymity Collapsible -->
          <details class="card-collapsible" id="contact-collapsible" @if(old('is_anonymous') || old('reporter_name') || old('reporter_contact') || old('reporter_email') || $errors->has('reporter_name') || $errors->has('reporter_contact') || $errors->has('reporter_email')) open @endif>
            <summary>
              <span style="display:flex;align-items:center;gap:0.5rem;">
                <i class="fa-solid fa-envelope-open-text" style="color:var(--wiz-blue);"></i>
                <span data-i18n="updates_optional">Get updates on this report (optional)</span>
              </span>
            </summary>
            <div class="card-collapsible-content">
              <label style="display:flex;align-items:center;gap:0.65rem;font-weight:700;color:var(--wiz-ink);margin-bottom:0.75rem;cursor:pointer;font-size:0.9rem;">
                <input type="checkbox" name="is_anonymous" id="is_anonymous" value="1"
                  {{ old('is_anonymous') ? 'checked' : '' }} onchange="toggleAnonymousFields(this.checked)"
                  style="width:1.1rem;height:1.1rem;accent-color:var(--wiz-blue);cursor:pointer;">
                <span data-i18n="chk_anonymous">Submit this report anonymously</span>
              </label>

              <div id="anon-notice-banner" style="display:none;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:0.65rem 0.85rem;color:#92400e;font-size:0.82rem;font-weight:600;margin-bottom:0.85rem;" role="status">
                <i class="fa-solid fa-circle-info" style="margin-right:0.35rem;"></i>
                <span data-i18n="anon_notice_text">Anonymous reports will not receive status updates via SMS or email. You will need to check the website using your tracking code.</span>
              </div>

              <div id="contact-fields-section" style="transition:opacity 0.2s ease;">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                  <div class="field" style="margin:0;">
                    <label for="reporter_name" style="display:block;font-size:0.82rem;font-weight:700;color:var(--wiz-ink);margin-bottom:0.3rem;" data-i18n="lbl_name">
                      <i class="fa-solid fa-user"></i> Name (optional)
                    </label>
                    <input type="text" id="reporter_name" name="reporter_name" class="wizard-input" value="{{ old('reporter_name') }}"
                      maxlength="100" placeholder="Juan Dela Cruz">
                    @error('reporter_name')
                      <p class="field-error">{{ $message }}</p>
                    @enderror
                  </div>

                  <div class="field" style="margin:0;">
                    <label for="reporter_contact" style="display:block;font-size:0.82rem;font-weight:700;color:var(--wiz-ink);margin-bottom:0.3rem;" data-i18n="lbl_phone">
                      <i class="fa-solid fa-phone"></i> Mobile number (optional)
                    </label>
                    <input type="tel" id="reporter_contact" name="reporter_contact" class="wizard-input" value="{{ old('reporter_contact') }}"
                      maxlength="20" placeholder="09171234567">
                    <p class="field-hint" data-i18n="hint_phone" style="margin:0.25rem 0 0;font-size:0.75rem;color:var(--wiz-muted);">Barangay staff may call you. Philippine format.</p>
                    @error('reporter_contact')
                      <p class="field-error">{{ $message }}</p>
                    @enderror
                  </div>
                </div>

                <div class="field" style="margin-top:0.75rem;margin-bottom:0;">
                  <label for="reporter_email" style="display:block;font-size:0.82rem;font-weight:700;color:var(--wiz-ink);margin-bottom:0.3rem;" data-i18n="lbl_email">
                    <i class="fa-solid fa-at"></i> Email (optional)
                  </label>
                  <input type="email" id="reporter_email" name="reporter_email" class="wizard-input" value="{{ old('reporter_email') }}"
                    placeholder="juan@example.com">
                  @error('reporter_email')
                    <p class="field-error">{{ $message }}</p>
                  @enderror
                </div>
              </div>
            </div>
          </details>

          <!-- Data Privacy Consent -->
          <div class="consent-slate-box">
            <label style="display:flex;align-items:flex-start;gap:0.7rem;cursor:pointer;font-weight:600;color:var(--wiz-ink);">
              <input type="checkbox" name="consent" id="consent_checkbox" value="1" required
                {{ old('consent') ? 'checked' : '' }}
                style="margin-top:0.15rem;width:1.1rem;height:1.1rem;accent-color:var(--wiz-blue);flex-shrink:0;">
              <span id="consent_label_text">
                <span data-i18n="consent_certify">I hereby certify that the information provided is true and correct to the best of my knowledge. In accordance with the</span>
                <strong>Data Privacy Act of 2012 (RA 10173)</strong>,
                <span data-i18n="consent_body">I consent to the collection and processing of these details by authorized Barangay Calarian officials solely for official community problem reporting, verification, and resolution.</span>
                (<a href="#" onclick="openPrivacy(event)" data-i18n="privacy_link" style="text-decoration:underline;font-weight:700;color:var(--wiz-blue);">Data Privacy Notice</a>)
              </span>
            </label>
            @error('consent')
              <p class="field-error" style="margin-top:0.45rem;margin-bottom:0;">{{ $message }}</p>
            @enderror
          </div>
        </div>
      </div>

      <!-- Zone 4: Unified Wizard Footer Bar -->
      <div class="wizard-footer-bar is-step-1" id="wizard-footer-bar">
        <!-- Step 1 Footer -->
        <a href="{{ route('home') }}" class="wizard-btn-outline" id="btn-cancel-1" data-step-footer="1" data-i18n="btn_cancel">Cancel</a>
        <button type="button" class="wizard-btn-primary" id="btn-next-1" data-step-footer="1" onclick="nextFromStep1()">
          <span data-i18n="btn_next_loc">Next: Location</span>
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>

        <!-- Step 2 Footer -->
        <button type="button" class="wizard-btn-outline" id="btn-prev-2" data-step-footer="2" onclick="goToWizardStep(1)" style="display:none;">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          <span data-i18n="btn_back">Back</span>
        </button>
        <button type="button" class="wizard-btn-primary" id="btn-next-2" data-step-footer="2" onclick="nextFromStep2()" style="display:none;">
          <span data-i18n="btn_next_submit">Next: Photo &amp; Submit</span>
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>

        <!-- Step 3 Footer -->
        <button type="button" class="wizard-btn-outline" id="btn-prev-3" data-step-footer="3" onclick="goToWizardStep(2)" style="display:none;">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          <span data-i18n="btn_back">Back</span>
        </button>
        <button type="submit" id="btn-final-submit" class="btn btn-gold" data-step-footer="3" style="display:none;">
          <i class="fa-solid fa-paper-plane mr-1.5" aria-hidden="true"></i>
          <span data-i18n="btn_submit_final">Submit report</span>
        </button>
      </div>

      @if($residentVoiceEnabled)
        <!-- Voice Recording Modal (kept inside form so audio_token submits with report) -->
        <dialog id="voice-recorder-modal" class="legend-modal" role="dialog" aria-modal="true" aria-labelledby="voice-modal-title">
          <div class="legend-frame" style="border-radius:18px;max-width:600px;width:100%;">
            <div class="legend-header" style="display:flex;align-items:center;justify-content:space-between;padding-bottom:0.75rem;border-bottom:1px solid #f1f5f9;">
              <div style="display:flex;align-items:center;gap:0.6rem;">
                <div class="voice-strip-icon" style="width:32px;height:32px;border-radius:9px;" aria-hidden="true">
                  <i class="fa-solid fa-microphone-lines"></i>
                </div>
                <div>
                  <h3 id="voice-modal-title" style="margin:0;font-size:1.05rem;font-weight:800;color:var(--wiz-ink);" data-i18n="voice_modal_title">
                    Quick report by voice
                  </h3>
                  <p style="margin:0;font-size:0.76rem;color:var(--wiz-muted);" data-i18n="voice_modal_sub">
                    Record your concern or upload an audio file to prefill the form.
                  </p>
                </div>
              </div>
              <button type="button" class="review-modal-close" onclick="closeVoiceModal()" aria-label="Close">&times;</button>
            </div>

            <div style="margin-top:1rem;">
              <x-voice.recorder target-input="description" origin="resident" analyze-url="{{ route('reports.voice.analyze') }}" />
            </div>

            <div style="margin-top:1rem;padding-top:0.85rem;border-top:1px solid #f1f5f9;display:flex;justify-content:space-between;align-items:center;gap:0.75rem;flex-wrap:wrap;">
              <button type="button" class="wizard-btn-outline" style="min-height:38px;padding:0.4rem 0.9rem;font-size:0.84rem;" onclick="closeVoiceModal()" data-i18n="btn_close">
                Close
              </button>
              <button type="button" id="btn-save-voice-modal" class="wizard-btn-primary" style="min-height:38px;padding:0.45rem 1.1rem;font-size:0.85rem;display:none;" onclick="saveVoiceModal()">
                <i class="fa-solid fa-check" aria-hidden="true"></i>
                <span data-i18n="voice_save_btn">Save &amp; Use Recording</span>
              </button>
            </div>
          </div>
        </dialog>
      @endif
    </form>
  </section>

  <!-- Privacy Notice Modal -->
  <dialog id="privacy-modal" class="legend-modal" role="dialog" aria-modal="true" aria-labelledby="privacy-modal-title">
    <div class="legend-frame" style="border-radius:18px;max-width:540px;">
      <div class="legend-header">
        <h3 id="privacy-modal-title" style="margin:0;font-size:1.15rem;font-weight:800;color:var(--ink);" data-i18n="privacy_title">
          <i class="fa-solid fa-shield-halved" style="color:var(--blue);margin-right:0.4rem;"></i> Data Privacy Notice (RA 10173)
        </h3>
        <button type="button" class="review-modal-close" onclick="document.getElementById('privacy-modal').close()" aria-label="Close">&times;</button>
      </div>
      <div style="font-size:0.92rem;color:var(--tx);line-height:1.6;margin-top:1rem;" data-i18n="privacy_text">
        By submitting this report, you acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly to investigate, route, and resolve community problems. Your information will never be shared with unauthorized parties.
      </div>
      <div style="margin-top:1.5rem;text-align:right;">
        <button type="button" class="btn btn-navy btn-small"
          onclick="document.getElementById('privacy-modal').close()">Close</button>
      </div>
    </div>
  </dialog>

  <!-- Nearby Reports Modal -->
  <dialog id="nearby-reports-modal" class="legend-modal" role="dialog" aria-modal="true" aria-labelledby="nearby-modal-title">
    <div class="legend-frame" style="border-radius:18px;max-width:560px;">
      <div class="legend-header">
        <h3 id="nearby-modal-title" style="margin:0;font-size:1.15rem;font-weight:800;color:var(--ink);">
          <i class="fa-solid fa-triangle-exclamation" style="color:#d97706;margin-right:0.4rem;"></i> Similar Reports Nearby
        </h3>
        <button type="button" class="review-modal-close" onclick="document.getElementById('nearby-reports-modal').close()" aria-label="Close">&times;</button>
      </div>
      <p style="font-size:0.86rem;color:#64748b;margin:0.5rem 0 1rem;">
        Other residents have reported similar issues near this location. You can review them below to avoid duplicate submissions:
      </p>
      <div id="nearby-reports-modal-body" style="font-size:0.875rem;line-height:1.5;display:flex;flex-direction:column;gap:0.65rem;max-height:360px;overflow-y:auto;"></div>
      <div style="margin-top:1.5rem;text-align:right;">
        <button type="button" class="btn btn-outline btn-small"
          onclick="document.getElementById('nearby-reports-modal').close()">Close</button>
      </div>
    </div>
  </dialog>

  <script id="categories-data" type="application/json">
    {!! json_encode($sortedCategories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'question_schema' => $c->question_schema ?? []])) !!}
  </script>

  <script>
    const i18n = {
      en: {
        hero_title: "Report a problem in Barangay Calarian",
        hero_desc: "Send a report with category details, photo, and location. Barangay staff will review it, route it to the proper office, and keep you updated with a tracking code.",
        hero_btn_report: "Report a problem",
        hero_btn_track: "Return",
        emg_strip: "Emergency? Call 911",
        emg_btn: "Emergency / Crime",
        emg_title: "Emergency Detected",
        emg_subtitle: "For immediate life-threatening danger, call official hotlines directly.",
        emg_not_monitored: "THIS ONLINE SYSTEM IS NOT MONITORED IN REAL-TIME.",
        emg_call_prompt: "Please contact emergency services immediately using the numbers below:",
        emg_back_btn: "Return to report",
        emg_inline: "Emergency contacts",
        dispute_prompt: "Is this a dispute with a specific person?",
        dispute_link: "File a Dispute Complaint →",
        wiz_step_1: "Issue Details",
        wiz_step_2: "Location",
        wiz_step_3: "Photo & Submit",
        btn_quick_locate: "Use GPS",
        voice_btn_trigger: "Record / Try Demo",
        voice_btn_edit: "Review / Re-record",
        voice_saved_badge: "Recording saved",
        voice_modal_sub: "Record your concern or upload an audio file to prefill the form.",
        voice_save_btn: "Save & Use Recording",
        btn_close: "Close",
        where_is_it: "Where is it?",
        photo_updates: "Photo and updates",
        choose_category: "Choose a category",
        lbl_optional: "(optional)",
        lbl_optional_pill: "Optional",
        lbl_description: "What is the problem?",
        lbl_explain_more: "Explain more",
        placeholder_explain_more: "Add any other details that can help the staff.",
        hint_description: "English, Filipino, Bisaya, or Chavacano",
        danger_checkbox: "Someone is in danger right now",
        danger_sub: "Check this if there is immediate risk to health, safety, or life.",
        more_details: "More details (optional)",
        q_ongoing: "Is this happening right now?",
        q_ongoing_sub: "Indicate whether the incident is currently active",
        q_recurring: "Recurring or one-time?",
        q_recurring_sub: "Has this problem happened repeatedly in your area?",
        btn_yes: "Yes",
        btn_no: "No",
        btn_not_sure: "Not sure",
        btn_recurring: "Recurring",
        btn_onetime: "One-time",
        loc_help: "Provide at least one: drop a map pin, choose a purok, or enter a landmark.",
        loc_status_pending: "No location yet",
        loc_status_pin: "Pin dropped",
        loc_status_added: "Location added",
        btn_use_location: "Use my current location",
        lbl_purok: "Purok",
        opt_select_purok: "Select purok",
        lbl_landmark: "Landmark or Street",
        photo_dropzone_title: "Click or drag to add photos (optional)",
        photo_dropzone_tip: "Tip: show the problem near a landmark; avoid faces & license plates · Max 5 MB",
        chk_anonymous: "Submit this report anonymously",
        lbl_name: "Name (optional)",
        lbl_phone: "Mobile number (optional)",
        hint_phone: "Barangay staff may call you. Philippine format.",
        lbl_email: "Email (optional)",
        updates_optional: "Get updates on this report (optional)",
        privacy_title: "Data Privacy Notice (RA 10173)",
        privacy_text: "By submitting this report, you acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly to investigate, route, and resolve community problems. Your information will never be shared with unauthorized parties.",
        btn_submit_final: "Submit report",
        btn_cancel: "Cancel",
        btn_back: "Back",
        btn_next_loc: "Next: Location",
        btn_next_submit: "Next: Photo & Submit",
        err_step1_desc: "Please enter at least 10 characters describing the problem.",
        err_step2_loc: "Please drop a map pin, choose a purok, or enter a landmark.",
        consent_agree: "I agree to the",
        consent_certify: "I hereby certify that the information provided is true and correct to the best of my knowledge. In accordance with the",
        consent_body: "I consent to the collection and processing of these details by authorized Barangay Calarian officials solely for official community problem reporting, verification, and resolution.",
        privacy_link: "Data Privacy Notice",
        anon_notice_text: "Anonymous reports will not receive status updates via SMS or email. You will need to check the website using your tracking code.",
        desc_char_min: "min",
        desc_char_reached: "reached"
      },
      fil: {
        hero_title: "Mag-ulat ng problema sa Barangay Calarian",
        hero_desc: "Magsumite ng ulat na may kategorya, larawan, at lokasyon. Susuriin ito ng mga kawani ng barangay, ipapadala sa tamang tanggapan, at bibigyan ka ng tracking code.",
        hero_btn_report: "Mag-ulat ng problema",
        hero_btn_track: "Bumalik",
        emg_strip: "Emergency? Tumawag sa 911",
        emg_btn: "Emergency / Krimen",
        emg_title: "Pang-emergency na Alerto",
        emg_subtitle: "Para sa banta sa buhay, tumawag agad sa hotlines.",
        emg_not_monitored: "ANG ONLINE SYSTEM NA ITO AY HINDI MINUMONITOR NANG REAL-TIME.",
        emg_call_prompt: "Makipag-ugnayan kaagad sa mga numero sa ibaba:",
        emg_back_btn: "Bumalik sa ulat",
        emg_inline: "Mga emergency contact",
        dispute_prompt: "Alitan sa isang partikular na tao?",
        dispute_link: "Mag-file ng Dispute Complaint →",
        wiz_step_1: "Detalye",
        wiz_step_2: "Lokasyon",
        wiz_step_3: "Larawan at Isumite",
        btn_quick_locate: "Gamitin ang GPS",
        voice_btn_trigger: "Mag-record / Subukan",
        voice_btn_edit: "Suriin / Mag-record Muli",
        voice_saved_badge: "Nai-save ang recording",
        voice_modal_sub: "I-record ang iyong ulat o mag-upload ng audio upang awtomatikong mapunan ang form.",
        voice_save_btn: "I-save at Gamitin",
        btn_close: "Isara",
        where_is_it: "Saan ito?",
        photo_updates: "Larawan at updates",
        choose_category: "Pumili ng kategorya",
        lbl_optional: "(opsyonal)",
        lbl_optional_pill: "Opsyonal",
        lbl_description: "Ano ang problema?",
        lbl_explain_more: "Ipaliwanag pa",
        placeholder_explain_more: "Magdagdag ng iba pang detalye na makakatulong sa kawani.",
        hint_description: "English, Filipino, Bisaya, o Chavacano",
        danger_checkbox: "May nasa panganib ba ngayon?",
        danger_sub: "Lagyan ng tsek kung may agarang banta sa kalusugan, kaligtasan, o buhay.",
        more_details: "Karagdagang detalye (opsyonal)",
        q_ongoing: "Kasalukuyan ba itong nangyayari?",
        q_ongoing_sub: "Ipahiwatig kung aktibo pa ang insidente ngayon",
        q_recurring: "Paulit-ulit o minsan lang?",
        q_recurring_sub: "Madalas bang nangyayari ang problemang ito sa inyong lugar?",
        btn_yes: "Oo",
        btn_no: "Hindi",
        btn_not_sure: "Hindi sigurado",
        btn_recurring: "Paulit-ulit",
        btn_onetime: "Minsan lang",
        loc_help: "Magbigay ng kahit isa: mag-pin sa mapa, pumili ng purok, o maglagay ng landmark.",
        loc_status_pending: "Wala pang lokasyon",
        loc_status_pin: "May pin na",
        loc_status_added: "May lokasyon na",
        btn_use_location: "Gamitin ang aking kasalukuyang lokasyon",
        lbl_purok: "Purok",
        opt_select_purok: "Pumili ng purok",
        lbl_landmark: "Landmark o Kalye",
        photo_dropzone_title: "I-click o i-drag upang magdagdag ng larawan (opsyonal)",
        photo_dropzone_tip: "Tip: ipakita ang problema malapit sa landmark; iwasan ang mukha at plaka · Max 5 MB",
        chk_anonymous: "Isumite nang hindi nagpapakilala",
        lbl_name: "Pangalan (opsyonal)",
        lbl_phone: "Numero ng telepono (opsyonal)",
        hint_phone: "Maaaring tumawag ang barangay. Format sa Pilipinas.",
        lbl_email: "Email (opsyonal)",
        updates_optional: "Gusto ng updates? (opsyonal)",
        privacy_title: "Paunawa sa Data Privacy (RA 10173)",
        privacy_text: "Sa pagsusumite ng ulat na ito, kinikilala mo na ang Barangay Calarian ay nangongolekta ng detalye ng suliranin, lokasyon, at opsyonal na impormasyon.",
        btn_submit_final: "Isumite ang ulat",
        btn_cancel: "Kanselahin",
        btn_back: "Bumalik",
        btn_next_loc: "Susunod: Lokasyon",
        btn_next_submit: "Susunod: Larawan at Isumite",
        err_step1_desc: "Mangyaring maglagay ng hindi bababa sa 10 titik na naglalarawan sa problema.",
        err_step2_loc: "Mangyaring mag-pin sa mapa, pumili ng purok, o maglagay ng landmark.",
        consent_agree: "Sumasang-ayon ako sa",
        consent_certify: "Pinatutunayan ko na ang impormasyong ibinigay ay totoo at tama sa abot ng aking kaalaman. Alinsunod sa",
        consent_body: "pumapayag ako sa pagkolekta at pagproseso ng mga detalyeng ito ng mga awtorisadong opisyal ng Barangay Calarian para lamang sa opisyal na pag-uulat at pagresolba ng suliraning ito.",
        privacy_link: "Paunawa sa Data Privacy",
        anon_notice_text: "Ang mga di-nagpapakilalang ulat ay hindi makakatanggap ng updates sa SMS o email. Kakailanganin mong suriin ang website gamit ang tracking code.",
        desc_char_min: "pinakamababa",
        desc_char_reached: "naabot na"
      }
    };

    let currentLang = 'en';
    let currentWizardStep = 1;
    let maxCompletedStep = 0;

    function triggerShake(el) {
      if (!el) return;
      el.classList.remove('gentle-shake');
      void el.offsetWidth;
      el.classList.add('gentle-shake');
    }

    function updateLocationStatusPill() {
      const pill = document.getElementById('location-status-pill');
      const txt = document.getElementById('location-status-text');
      if (!pill || !txt) return;
      const lat = (document.getElementById('lat')?.value || '').trim();
      const lng = (document.getElementById('lng')?.value || '').trim();
      const purok = (document.getElementById('purok_id')?.value || '').trim();
      const landmark = (document.getElementById('landmark')?.value || '').trim();
      const dict = i18n[currentLang] || i18n.en;

      if (lat && lng) {
        pill.classList.add('is-ready');
        txt.textContent = dict.loc_status_pin;
      } else if (purok || landmark) {
        pill.classList.add('is-ready');
        txt.textContent = dict.loc_status_added;
      } else {
        pill.classList.remove('is-ready');
        txt.textContent = dict.loc_status_pending;
      }
    }

    function goToWizardStep(targetStep, options = {}) {
      const prevStep = currentWizardStep;
      if (targetStep === prevStep && !options.force) return;
      if (targetStep > maxCompletedStep + 1 && !options.force) return;

      const direction = targetStep >= prevStep ? 'right' : 'left';
      currentWizardStep = targetStep;
      if (targetStep - 1 > maxCompletedStep) {
        maxCompletedStep = targetStep - 1;
      }

      // Toggle panels with directional animation
      document.querySelectorAll('.wizard-panel').forEach(panel => {
        const s = parseInt(panel.dataset.wizardPanel, 10);
        panel.classList.remove('is-active', 'anim-slide-right', 'anim-slide-left');
        if (s === targetStep) {
          panel.classList.add('is-active');
          if (!options.skipAnim) {
            panel.classList.add(direction === 'right' ? 'anim-slide-right' : 'anim-slide-left');
          }
        }
      });

      // Update stepper tabs & progress fill
      const fillPct = targetStep === 1 ? '0%' : (targetStep === 2 ? '50%' : '100%');
      const fillEl = document.getElementById('wizard-progress-fill');
      if (fillEl) fillEl.style.width = fillPct;

      document.querySelectorAll('.wizard-step-tab').forEach(tab => {
        const s = parseInt(tab.dataset.step, 10);
        const circle = tab.querySelector('.wizard-step-circle');
        tab.classList.remove('is-active', 'is-completed');
        tab.removeAttribute('aria-current');

        if (s < targetStep) {
          tab.classList.add('is-completed');
          if (circle) circle.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
        } else if (s === targetStep) {
          tab.classList.add('is-active');
          tab.setAttribute('aria-current', 'step');
          if (circle) circle.textContent = String(s);
        } else {
          if (s <= maxCompletedStep) {
            tab.classList.add('is-completed');
            if (circle) circle.innerHTML = '<i class="fa-solid fa-check" aria-hidden="true"></i>';
          } else if (circle) {
            circle.textContent = String(s);
          }
        }
      });

      // Update unified footer bar
      const footerBar = document.getElementById('wizard-footer-bar');
      if (footerBar) {
        footerBar.classList.remove('is-step-1', 'is-step-2', 'is-step-3');
        footerBar.classList.add(`is-step-${targetStep}`);
        footerBar.querySelectorAll('[data-step-footer]').forEach(el => {
          el.style.display = parseInt(el.dataset.stepFooter, 10) === targetStep ? '' : 'none';
        });
      }

      // Leaflet hidden-tab resize fix
      if (targetStep === 2) {
        setTimeout(() => {
          if (mapInstance) mapInstance.invalidateSize();
        }, 60);
      }

      // Smooth scroll top of .report-card into view if above viewport
      if (!options.skipScroll) {
        const card = document.querySelector('.report-card');
        if (card) {
          const rect = card.getBoundingClientRect();
          if (rect.top < 0) {
            card.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }
      }
    }

    function handleStepTabClick(step) {
      if (step === currentWizardStep) return;
      if (step < currentWizardStep || step <= maxCompletedStep) {
        goToWizardStep(step);
      }
    }

    function validateStep1() {
      const desc = document.getElementById('description');
      const err = document.getElementById('step1-error');
      const val = desc ? desc.value.trim() : '';
      if (val.length < 10) {
        if (err) err.classList.add('is-visible');
        if (desc) {
          desc.focus();
          triggerShake(desc);
        }
        return false;
      }
      if (err) err.classList.remove('is-visible');
      return true;
    }

    function nextFromStep1() {
      if (validateStep1()) {
        maxCompletedStep = Math.max(maxCompletedStep, 1);
        goToWizardStep(2);
      }
    }

    function validateStep2() {
      const lat = (document.getElementById('lat')?.value || '').trim();
      const lng = (document.getElementById('lng')?.value || '').trim();
      const purok = (document.getElementById('purok_id')?.value || '').trim();
      const landmark = (document.getElementById('landmark')?.value || '').trim();
      const err = document.getElementById('step2-error');

      if (!(lat && lng) && !purok && !landmark) {
        if (err) err.classList.add('is-visible');
        triggerShake(document.getElementById('step2-location-inputs'));
        return false;
      }
      if (err) err.classList.remove('is-visible');
      return true;
    }

    function nextFromStep2() {
      if (validateStep2()) {
        maxCompletedStep = Math.max(maxCompletedStep, 2);
        goToWizardStep(3);
      }
    }

    function setLanguage(lang) {
      currentLang = lang;
      document.querySelectorAll('.js-lang-btn-en').forEach(b => b.classList.toggle('active', lang === 'en'));
      document.querySelectorAll('.js-lang-btn-fil').forEach(b => b.classList.toggle('active', lang === 'fil'));
      document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (i18n[lang] && i18n[lang][key]) el.textContent = i18n[lang][key];
      });

      const categoryNamesFil = {
        'drainage': 'Kanal at Pagbaha',
        'garbage': 'Basura at Kalinisan',
        'road-damage': 'Sira sa Kalsada at Bangketa',
        'streetlight': 'Ilaw sa Poste / Kuryente',
        'flooding': 'Baha sa Komunidad',
        'other': 'Iba pa',
        'others': 'Iba pa'
      };
      document.querySelectorAll('.cat-name-label').forEach(el => {
        const slug = (el.getAttribute('data-slug') || '').toLowerCase();
        if (lang === 'fil' && categoryNamesFil[slug]) {
          el.textContent = categoryNamesFil[slug];
        } else {
          el.textContent = el.getAttribute('data-name-en');
        }
      });

      try { localStorage.setItem('calarian_lang', lang); } catch (e) { }
      const selectedCat = document.getElementById('category_id_input').value;
      if (selectedCat) renderQuestions(parseInt(selectedCat, 10));
      updateDescriptionLabel();
      updateCharCounter();
      updateLocationStatusPill();
    }

    function toggleDanger(checked) {
      document.getElementById('inline-emergency').style.display = checked ? 'block' : 'none';
    }

    function setBinaryField(fieldId, val, btnEl) {
      const input = document.getElementById(fieldId);
      const parent = btnEl ? btnEl.closest('.toggle-btns') : null;
      const wasActive = btnEl && btnEl.classList.contains('active');

      if (wasActive || val === '' || val === null) {
        if (input) input.value = '';
        if (parent) parent.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        if (!wasActive && btnEl && val === '') {
          btnEl.classList.add('active');
        }
        return;
      }

      if (input) input.value = val;
      if (parent) {
        parent.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
        btnEl.classList.add('active');
      }
    }

    function toggleAnonymousFields(isAnon) {
      const section = document.getElementById('contact-fields-section');
      const notice = document.getElementById('anon-notice-banner');
      const nameInp = document.getElementById('reporter_name');
      const contactInp = document.getElementById('reporter_contact');
      const emailInp = document.getElementById('reporter_email');
      if (!section) return;

      if (isAnon) {
        section.style.opacity = '0.4';
        section.style.pointerEvents = 'none';
        section.setAttribute('aria-hidden', 'true');
        if (notice) notice.style.display = 'block';
        if (nameInp) { nameInp.value = ''; nameInp.disabled = true; }
        if (contactInp) { contactInp.value = ''; contactInp.disabled = true; }
        if (emailInp) { emailInp.value = ''; emailInp.disabled = true; }
      } else {
        section.style.opacity = '1';
        section.style.pointerEvents = 'auto';
        section.removeAttribute('aria-hidden');
        if (notice) notice.style.display = 'none';
        if (nameInp) nameInp.disabled = false;
        if (contactInp) contactInp.disabled = false;
        if (emailInp) emailInp.disabled = false;
      }
    }

    function updateCharCounter() {
      const textarea = document.getElementById('description');
      const countSpan = document.getElementById('desc-char-count');
      const statusSpan = document.getElementById('desc-char-status');
      const counterContainer = document.getElementById('description-char-counter');
      if (!textarea || !countSpan) return;
      const len = textarea.value.length;
      countSpan.textContent = len;
      const dict = i18n[currentLang] || i18n.en;
      if (len >= 10) {
        counterContainer.style.color = '#16a34a';
        if (statusSpan) statusSpan.innerHTML = `<i class="fa-solid fa-check" aria-hidden="true"></i> ${dict.desc_char_reached || 'reached'}`;
        const step1Err = document.getElementById('step1-error');
        if (step1Err) step1Err.classList.remove('is-visible');
      } else {
        counterContainer.style.color = len > 0 ? '#b45309' : '#64748b';
        if (statusSpan) statusSpan.textContent = dict.desc_char_min || 'min';
      }
    }

    function showEmergency() {
      document.getElementById('emergency-screen').style.display = 'block';
      document.getElementById('report-form').style.display = 'none';
      document.getElementById('emergency-screen').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    function hideEmergency() {
      document.getElementById('emergency-screen').style.display = 'none';
      document.getElementById('report-form').style.display = 'block';
    }

    let categoriesData = [];
    try { categoriesData = JSON.parse(document.getElementById('categories-data').textContent); } catch (e) { }

    const oldAnswers = @json(old('answers', []));

    const questionI18n = {
      water_blocked: { label: { en: 'Is water blocked or standing now?', fil: 'Nakatambak o hindi ba umaagos ang tubig ngayon?' }, placeholder: { en: '', fil: '' } },
      water_present: { label: { en: 'Is the water still there now?', fil: 'Nandoon pa rin ba ang baha ngayon?' }, placeholder: { en: '', fil: '' } },
      people_affected: { label: { en: 'Are homes or people affected?', fil: 'May mga bahay o tao bang apektado?' }, placeholder: { en: '', fil: '' } },
      garbage_type: { label: { en: 'What is the garbage situation?', fil: 'Ano ang sitwasyon ng basura?' }, placeholder: { en: '', fil: '' } },
      damage_type: { label: { en: 'Type of road damage', fil: 'Uri ng sira sa daan' }, placeholder: { en: '', fil: '' } },
      blocks_traffic: { label: { en: 'Does it block vehicles?', fil: 'Humaharang ba ito sa mga sasakyan?' }, placeholder: { en: '', fil: '' } },
      light_condition: { label: { en: 'What is the issue with the light?', fil: 'Ano ang problema sa ilaw?' }, placeholder: { en: '', fil: '' } },
      water_level: { label: { en: 'How high is the water?', fil: 'Gaano kalalim ang tubig?' }, placeholder: { en: '', fil: '' } },
      blocked_drain: { label: { en: 'Is the drain blocked?', fil: 'Nakabara ba ang kanal?' }, placeholder: { en: '', fil: '' } },
      volume: { label: { en: 'Approximate volume', fil: 'Tantyang dami' }, placeholder: { en: '', fil: '' } },
      size: { label: { en: 'Size', fil: 'Laki' }, placeholder: { en: '', fil: '' } },
      not_working: { label: { en: 'Is it not working?', fil: 'Hindi ba gumagana?' }, placeholder: { en: '', fil: '' } },
      pole_number: { label: { en: 'Pole number', fil: 'Numero ng poste' }, placeholder: { en: '', fil: '' } },
      water_depth: { label: { en: 'Water depth', fil: 'Lalim ng tubig' }, placeholder: { en: '', fil: '' } },
      area_affected: { label: { en: 'Area affected', fil: 'Naapektuhang lugar' }, placeholder: { en: '', fil: '' } },
      details: { label: { en: 'Additional details', fil: 'Karagdagang detalye' }, placeholder: { en: '', fil: '' } }
    };

    const optionI18n = {
      'Yes': { en: 'Yes', fil: 'Oo' },
      'No': { en: 'No', fil: 'Hindi' },
      'Not sure': { en: 'Not sure', fil: 'Hindi sigurado' },
      'Partially': { en: 'Partially', fil: 'Bahagya' },
      'Other': { en: 'Other', fil: 'Iba pa' },
      'Uncollected': { en: 'Uncollected', fil: 'Hindi nakolekta' },
      'Dumped pile': { en: 'Dumped pile', fil: 'Tambak na basura' },
      'Overflowing bin': { en: 'Overflowing bin', fil: 'Umaapaw na basurahan' },
      'Pothole': { en: 'Pothole', fil: 'Lubak' },
      'Crack': { en: 'Crack', fil: 'Bitak' },
      'Broken edge': { en: 'Broken edge', fil: 'Sirang gilid' },
      'Not working (dark)': { en: 'Not working (dark)', fil: 'Hindi gumagana (madilim)' },
      'Flickering': { en: 'Flickering', fil: 'Kumikislap-kislap' },
      'Pole damaged': { en: 'Pole damaged', fil: 'Sirang poste' }
    };

    function getOptionLabel(opt) {
      if (currentLang === 'fil' && optionI18n[opt] && optionI18n[opt].fil) {
        return optionI18n[opt].fil;
      }
      return (optionI18n[opt] && optionI18n[opt].en) ? optionI18n[opt].en : opt;
    }

    function selectQuestionOption(key, val, btnEl) {
      const hiddenInp = document.getElementById(`answer_${key}`);
      const parent = btnEl ? btnEl.closest('.cat-question-chips') : null;
      const wasActive = btnEl && btnEl.classList.contains('active');

      if (wasActive) {
        if (hiddenInp) hiddenInp.value = '';
        btnEl.classList.remove('active');
        btnEl.setAttribute('aria-pressed', 'false');
      } else {
        if (hiddenInp) hiddenInp.value = val;
        if (parent) {
          parent.querySelectorAll('.dynamic-opt-chip').forEach(b => {
            b.classList.remove('active');
            b.setAttribute('aria-pressed', 'false');
          });
        }
        btnEl.classList.add('active');
        btnEl.setAttribute('aria-pressed', 'true');
      }
    }

    const categoryPlaceholders = {
      'drainage': {
        en: "Where is the clog? Is dirty water overflowing onto the road or sidewalk?",
        fil: "Saan ang bara? Umaapaw ba ang maruming tubig sa kalsada o bangketa?"
      },
      'flooding': {
        en: "How deep is the flood? Which streets or houses are reached?",
        fil: "Gaano kalalim ang baha? Aling mga kalsada o bahay ang naaabot?"
      },
      'garbage': {
        en: "Where is the trash piled? Is it causing bad odor or blocking passage?",
        fil: "Saan nakatambak ang basura? May masamang amoy ba o nakaharang sa daanan?"
      },
      'road-damage': {
        en: "Where is the damage located? Is it causing accidents or hazards to motorists?",
        fil: "Saan eksaktong bahagi ng kalsada ang sira? Peligro ba ito sa motorista o nagdudulot ng aksidente?"
      },
      'streetlight': {
        en: "Which light is out? Near which house, post, or corner?",
        fil: "Aling ilaw ang patay? Sa tapat ng aling bahay, poste, o kanto?"
      },
      'other': {
        en: "Please describe the problem, what caused it, and where it is located.",
        fil: "Pakilarawan ang problema, ano ang sanhi nito, at saan ito matatagpuan."
      }
    };

    function qLabel(q) {
      const m = questionI18n[q.key];
      if (m && m.label && m.label[currentLang]) return m.label[currentLang];
      return q.label;
    }

    function qPlaceholder(q) {
      const m = questionI18n[q.key];
      if (m && m.placeholder && m.placeholder[currentLang]) return m.placeholder[currentLang];
      return '';
    }

    function selectCategory(catId) {
      const input = document.getElementById('category_id_input');
      if (input.value && parseInt(input.value, 10) === catId) {
        deselectCategory();
        return;
      }
      input.value = catId;
      renderQuestions(catId);
      triggerNearbyReportsCheck();
    }

    function deselectCategory() {
      const input = document.getElementById('category_id_input');
      input.value = '';
      document.querySelectorAll('.cat-chip').forEach(card => {
        card.classList.remove('selected');
        card.setAttribute('aria-pressed', 'false');
      });
      const container = document.getElementById('dynamic-questions-container');
      document.getElementById('dynamic-questions-fields').innerHTML = '';
      container.style.display = 'none';
      updateDescriptionLabel();
      triggerNearbyReportsCheck();
    }

    function updateDescriptionLabel() {
      const catInput = document.getElementById('category_id_input');
      const catId = catInput ? catInput.value : '';
      const labelSpan = document.getElementById('description-label');
      const textarea = document.getElementById('description');
      const dict = i18n[currentLang] || i18n.en;

      if (catId) {
        labelSpan.textContent = dict.lbl_explain_more;
        const cat = categoriesData.find(c => c.id === parseInt(catId, 10));
        const slug = cat ? (cat.slug || '').toLowerCase() : '';
        if (slug && categoryPlaceholders[slug] && categoryPlaceholders[slug][currentLang]) {
          textarea.placeholder = categoryPlaceholders[slug][currentLang];
        } else {
          textarea.placeholder = dict.placeholder_explain_more;
        }
      } else {
        labelSpan.textContent = dict.lbl_description;
        textarea.placeholder = currentLang === 'fil'
          ? 'Pakilarawan nang detalyado ang problema...'
          : 'Please describe the issue in detail...';
      }
    }

    function renderQuestions(catId) {
      document.querySelectorAll('.cat-chip').forEach(card => {
        const isMatch = parseInt(card.getAttribute('data-cat-id'), 10) === catId;
        card.classList.toggle('selected', isMatch);
        card.setAttribute('aria-pressed', isMatch ? 'true' : 'false');
      });
      const category = categoriesData.find(c => c.id === catId);
      const container = document.getElementById('dynamic-questions-container');
      const fields = document.getElementById('dynamic-questions-fields');

      const currentDomValues = {};
      fields.querySelectorAll('input[name^="answers["]').forEach(inp => {
        const match = inp.name.match(/^answers\[([^\]]+)\]/);
        if (match) currentDomValues[match[1]] = inp.value;
      });
      fields.querySelectorAll('textarea[name^="answers["]').forEach(ta => {
        const match = ta.name.match(/^answers\[([^\]]+)\]/);
        if (match) currentDomValues[match[1]] = ta.value;
      });

      fields.innerHTML = '';
      if (category && category.question_schema && category.question_schema.length > 0) {
        category.question_schema.slice(0, 2).forEach((q, idx, arr) => {
          const qDiv = document.createElement('div');
          qDiv.className = 'field';
          qDiv.style.marginBottom = idx === arr.length - 1 ? '0' : '0.85rem';

          const label = document.createElement('div');
          label.style.display = 'flex';
          label.style.alignItems = 'center';
          label.style.gap = '0.45rem';
          label.style.marginBottom = '0.35rem';
          const labelText = (typeof qLabel === 'function') ? qLabel(q) : q.label;
          label.innerHTML = `
            <span style="font-weight:700;font-size:0.86rem;color:var(--ink);">${labelText}</span>
            <span style="font-size:0.72rem;font-weight:600;color:#64748b;background:#e2e8f0;padding:0.1rem 0.45rem;border-radius:999px;">${currentLang === 'fil' ? 'opsyonal' : 'optional'}</span>
          `;
          qDiv.appendChild(label);

          const currentVal = (currentDomValues[q.key] !== undefined && currentDomValues[q.key] !== '')
            ? currentDomValues[q.key]
            : ((oldAnswers && oldAnswers[q.key] !== undefined) ? oldAnswers[q.key] : '');

          if (q.type === 'select' && Array.isArray(q.options)) {
            const hiddenInp = document.createElement('input');
            hiddenInp.type = 'hidden';
            hiddenInp.name = `answers[${q.key}]`;
            hiddenInp.id = `answer_${q.key}`;
            hiddenInp.value = currentVal;
            qDiv.appendChild(hiddenInp);

            const chipsWrap = document.createElement('div');
            chipsWrap.className = 'cat-question-chips';
            chipsWrap.setAttribute('role', 'group');
            chipsWrap.setAttribute('aria-label', labelText);
            chipsWrap.style.display = 'flex';
            chipsWrap.style.flexWrap = 'wrap';
            chipsWrap.style.gap = '0.45rem';

            q.options.forEach(opt => {
              const optBtn = document.createElement('button');
              optBtn.type = 'button';
              const isSelected = String(currentVal) === String(opt);
              optBtn.className = 'dynamic-opt-chip' + (isSelected ? ' active' : '');
              optBtn.setAttribute('aria-pressed', isSelected ? 'true' : 'false');
              optBtn.textContent = getOptionLabel(opt);
              optBtn.addEventListener('click', () => selectQuestionOption(q.key, opt, optBtn));
              chipsWrap.appendChild(optBtn);
            });
            qDiv.appendChild(chipsWrap);
          } else if (q.type === 'boolean') {
            const hiddenInp = document.createElement('input');
            hiddenInp.type = 'hidden';
            hiddenInp.name = `answers[${q.key}]`;
            hiddenInp.id = `answer_${q.key}`;
            hiddenInp.value = currentVal;
            qDiv.appendChild(hiddenInp);

            const chipsWrap = document.createElement('div');
            chipsWrap.className = 'cat-question-chips';
            chipsWrap.setAttribute('role', 'group');
            chipsWrap.setAttribute('aria-label', labelText);
            chipsWrap.style.display = 'flex';
            chipsWrap.style.flexWrap = 'wrap';
            chipsWrap.style.gap = '0.45rem';

            const yesLabel = currentLang === 'fil' ? 'Oo' : 'Yes';
            const noLabel = currentLang === 'fil' ? 'Hindi' : 'No';

            const btnYes = document.createElement('button');
            btnYes.type = 'button';
            const isYes = String(currentVal) === '1';
            btnYes.className = 'dynamic-opt-chip' + (isYes ? ' active' : '');
            btnYes.setAttribute('aria-pressed', isYes ? 'true' : 'false');
            btnYes.textContent = yesLabel;
            btnYes.addEventListener('click', () => selectQuestionOption(q.key, '1', btnYes));

            const btnNo = document.createElement('button');
            btnNo.type = 'button';
            const isNo = String(currentVal) === '0';
            btnNo.className = 'dynamic-opt-chip' + (isNo ? ' active' : '');
            btnNo.setAttribute('aria-pressed', isNo ? 'true' : 'false');
            btnNo.textContent = noLabel;
            btnNo.addEventListener('click', () => selectQuestionOption(q.key, '0', btnNo));

            chipsWrap.appendChild(btnYes);
            chipsWrap.appendChild(btnNo);
            qDiv.appendChild(chipsWrap);
          } else if (q.type === 'textarea') {
            const ta = document.createElement('textarea');
            ta.name = `answers[${q.key}]`;
            ta.className = 'wizard-textarea';
            ta.rows = 2;
            ta.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : '';
            if (currentVal) ta.value = currentVal;
            qDiv.appendChild(ta);
          } else if (q.type === 'number') {
            const inp = document.createElement('input');
            inp.type = 'number';
            inp.name = `answers[${q.key}]`;
            inp.className = 'wizard-input';
            inp.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : '';
            if (currentVal) inp.value = currentVal;
            qDiv.appendChild(inp);
          } else {
            const inp = document.createElement('input');
            inp.type = 'text';
            inp.name = `answers[${q.key}]`;
            inp.className = 'wizard-input';
            inp.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : '';
            if (currentVal) inp.value = currentVal;
            qDiv.appendChild(inp);
          }
          fields.appendChild(qDiv);
        });
        container.style.display = 'block';
      } else {
        container.style.display = 'none';
      }
      updateDescriptionLabel();
    }

    // Leaflet Map Initialization
    let mapInstance = null;
    document.addEventListener('DOMContentLoaded', () => {
      const CALARIAN_CENTER = [6.921, 122.078];
      const CALARIAN_BOUNDS = [[6.905, 122.060], [6.940, 122.098]];
      if (typeof L !== 'undefined') {
        const mapEl = document.getElementById('map');
        if (mapEl && !mapEl._leaflet_id) {
          mapInstance = L.map('map', { scrollWheelZoom: false }).setView(CALARIAN_CENTER, 15);
          window.leafletMapInstance = mapInstance;
          if (window.setupCooperativeMapGestures) {
            window.setupCooperativeMapGestures(mapInstance, mapEl);
          }
          L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OSM' }).addTo(mapInstance);
          mapInstance.setMaxBounds(CALARIAN_BOUNDS);
        } else if (window.leafletMapInstance) {
          mapInstance = window.leafletMapInstance;
          if (window.setupCooperativeMapGestures && mapEl) {
            window.setupCooperativeMapGestures(mapInstance, mapEl);
          }
        }

        let marker = null;
        function placePin(lat, lng) {
          if (marker) marker.setLatLng([lat, lng]);
          else marker = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);
          marker.on('dragend', e => {
            const p = e.target.getLatLng();
            document.getElementById('lat').value = p.lat;
            document.getElementById('lng').value = p.lng;
            updateLocationStatusPill();
            const step2Err = document.getElementById('step2-error');
            if (step2Err) step2Err.classList.remove('is-visible');
            triggerNearbyReportsCheck();
          });
          document.getElementById('lat').value = lat;
          document.getElementById('lng').value = lng;
          updateLocationStatusPill();
          const step2Err = document.getElementById('step2-error');
          if (step2Err) step2Err.classList.remove('is-visible');
          triggerNearbyReportsCheck();
        }

        const oldLat = parseFloat(document.getElementById('lat').value);
        const oldLng = parseFloat(document.getElementById('lng').value);
        if (!Number.isNaN(oldLat) && !Number.isNaN(oldLng)) {
          placePin(oldLat, oldLng);
          mapInstance.setView([oldLat, oldLng], 16);
        }

        mapInstance.on('click', e => placePin(e.latlng.lat, e.latlng.lng));

        function requestGeolocation(btnEl) {
          if (!navigator.geolocation) {
            alert(currentLang === 'fil' ? 'Hindi suportado ang geolocation sa iyong browser.' : 'Geolocation is not supported by your browser.');
            return;
          }
          const origText = btnEl ? btnEl.innerHTML : '';
          if (btnEl) {
            btnEl.innerHTML = `<i class="fa-solid fa-spinner fa-spin"></i> <span>${currentLang === 'fil' ? 'Hinahanap...' : 'Locating...'}</span>`;
            btnEl.disabled = true;
          }
          navigator.geolocation.getCurrentPosition(
            pos => {
              placePin(pos.coords.latitude, pos.coords.longitude);
              mapInstance.setView([pos.coords.latitude, pos.coords.longitude], 16);
              if (btnEl) {
                btnEl.innerHTML = origText;
                btnEl.disabled = false;
              }
            },
            err => {
              if (btnEl) {
                btnEl.innerHTML = origText;
                btnEl.disabled = false;
              }
              alert(currentLang === 'fil' ? 'Hindi makuha ang lokasyon. Mangyaring mag-click sa mapa o pumili ng purok.' : 'Unable to retrieve location. Please click on the map or select a purok.');
            },
            { timeout: 8000 }
          );
        }

        const btnLocate = document.getElementById('btn-locate');
        if (btnLocate) {
          btnLocate.addEventListener('click', () => requestGeolocation(btnLocate));
        }
        const btnQuickLocate = document.getElementById('btn-quick-locate');
        if (btnQuickLocate) {
          btnQuickLocate.addEventListener('click', () => requestGeolocation(btnQuickLocate));
        }
      }

      // Location input listeners for status pill & error clearing
      const purokSel = document.getElementById('purok_id');
      const landmarkInp = document.getElementById('landmark');
      if (purokSel) {
        purokSel.addEventListener('change', () => {
          updateLocationStatusPill();
          const step2Err = document.getElementById('step2-error');
          if (step2Err && purokSel.value) step2Err.classList.remove('is-visible');
        });
      }
      if (landmarkInp) {
        landmarkInp.addEventListener('input', () => {
          updateLocationStatusPill();
          const step2Err = document.getElementById('step2-error');
          if (step2Err && landmarkInp.value.trim()) step2Err.classList.remove('is-visible');
        });
      }
      updateLocationStatusPill();

      // Initialize live character counter for description
      const descEl = document.getElementById('description');
      if (descEl) {
        descEl.addEventListener('input', updateCharCounter);
        updateCharCounter();
      }

      // Initialize anonymous toggle state
      const anonCb = document.getElementById('is_anonymous');
      if (anonCb && anonCb.checked) {
        toggleAnonymousFields(true);
      }

      try {
        const saved = localStorage.getItem('calarian_lang');
        if (saved && (saved === 'en' || saved === 'fil')) setLanguage(saved);
      } catch (e) { }

      const restoredCat = document.getElementById('category_id_input').value;
      if (restoredCat) renderQuestions(parseInt(restoredCat, 10));
      else updateDescriptionLabel();

      if (document.getElementById('people_at_risk_cb').checked) toggleDanger(true);

      // Restore toggle buttons active status based on values
      const ongoingVal = document.getElementById('is_ongoing').value;
      const recurringVal = document.getElementById('is_recurring').value;
      if (ongoingVal !== '') {
        const ongoingBtns = document.querySelectorAll('#ongoing-toggle-btns .toggle-btn');
        if (ongoingBtns.length >= 2) {
          ongoingBtns[0].classList.toggle('active', ongoingVal == '1');
          ongoingBtns[1].classList.toggle('active', ongoingVal == '0');
        }
      }
      if (recurringVal !== '') {
        const recurringBtns = document.querySelectorAll('#recurring-toggle-btns .toggle-btn');
        if (recurringBtns.length >= 2) {
          recurringBtns[0].classList.toggle('active', recurringVal == '1');
          recurringBtns[1].classList.toggle('active', recurringVal == '0');
        }
      }

      // Server-Side Error Auto-Recovery: Jump to the step owning the first validation error
      @if($errors->any())
        const serverErrorKeys = @json($errors->keys());
        let errorStep = 1;
        const step2Keys = ['location', 'lat', 'lng', 'purok_id', 'landmark'];
        const step3Keys = ['photos', 'reporter_name', 'reporter_contact', 'reporter_email', 'consent'];

        for (const key of serverErrorKeys) {
          if (step2Keys.includes(key)) {
            errorStep = 2;
            break;
          }
          if (step3Keys.includes(key) || key.startsWith('photos.')) {
            errorStep = 3;
            break;
          }
          if (key === 'category_id' || key === 'description' || key === 'people_at_risk' || key.startsWith('answers.')) {
            errorStep = 1;
            break;
          }
        }
        maxCompletedStep = Math.max(maxCompletedStep, errorStep - 1);
        goToWizardStep(errorStep, { force: true, skipAnim: true, skipScroll: true });
        const firstError = document.querySelector('.field-error');
        if (firstError) {
          setTimeout(() => firstError.scrollIntoView({ behavior: 'smooth', block: 'center' }), 80);
        }
      @endif

      // Submit button loading state & double-click protection (attached to #report-form-el)
      const reportFormEl = document.getElementById('report-form-el');
      if (reportFormEl) {
        let isSubmitting = false;
        reportFormEl.addEventListener('submit', (e) => {
          if (isSubmitting) {
            e.preventDefault();
            return;
          }
          isSubmitting = true;
          const submitBtn = document.getElementById('btn-final-submit');
          if (submitBtn) {
            submitBtn.style.pointerEvents = 'none';
            submitBtn.style.opacity = '0.85';
            submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> <span>${currentLang === 'fil' ? 'Isinusumite...' : 'Submitting report...'}</span>`;
          }
        });
      }
    });

    async function triggerNearbyReportsCheck() {
      const lat = document.getElementById('lat').value;
      const lng = document.getElementById('lng').value;
      const catId = document.getElementById('category_id_input').value;
      const line = document.getElementById('nearby-line');
      const list = document.getElementById('nearby-list');
      if (!lat || !lng) { line.style.display = 'none'; list.style.display = 'none'; return; }
      try {
        const res = await fetch(`/api/nearby-reports?lat=${lat}&lng=${lng}&category=${catId}`);
        if (!res.ok) { line.style.display = 'none'; list.style.display = 'none'; return; }
        const reports = await res.json();
        if (Array.isArray(reports) && reports.length > 0) {
          line.innerHTML = `<span style="color:#92400e;font-weight:700;"><i class="fa-solid fa-triangle-exclamation" style="color:#d97706;"></i> ${reports.length} similar report(s) found nearby.</span> <button type="button" style="background:none;border:none;padding:0;color:#92400e;text-decoration:underline;font-size:0.8rem;margin-left:0.35rem;font-weight:800;cursor:pointer;" onclick="openNearbyModal()">View details</button>`;
          line.style.display = 'inline-block';
          const modalBody = document.getElementById('nearby-reports-modal-body');
          if (modalBody) {
            modalBody.innerHTML = reports.map(r => `
              <div style="padding:0.75rem 0.9rem;border:1px solid var(--line);border-radius:10px;background:#f8fafc;display:flex;align-items:center;justify-content:space-between;gap:0.75rem;">
                <div>
                  <strong class="font-mono text-blue-700">${r.tracking_code}</strong>
                  <span style="color:#64748b;margin:0 0.35rem;">·</span>
                  <span style="font-weight:600;">${r.category}</span>
                </div>
                <span class="status-badge" style="font-size:0.72rem;">${r.status}</span>
              </div>
            `).join('');
          }
        } else {
          line.style.display = 'none'; list.style.display = 'none';
        }
      } catch (e) { line.style.display = 'none'; list.style.display = 'none'; }
    }

    // Photo Compression, Drag & Drop, and Previews
    let selectedPhotosList = [];
    const photosInput = document.getElementById('photos');
    const photoDropzone = document.getElementById('photo-dropzone');
    const photoPreviews = document.getElementById('photo-previews');
    const photoTotalSize = document.getElementById('photo-total-size');
    const photoUploadError = document.getElementById('photo-upload-error');

    async function processIncomingPhotos(files) {
      const incoming = Array.from(files || []).filter(f => f.type === 'image/jpeg' || f.type === 'image/png' || f.type.startsWith('image/'));
      if (!incoming.length) return;
      for (const file of incoming) {
        const optimized = await compressImageFile(file);
        selectedPhotosList.push(optimized);
      }
      renderPhotoPreviews();
    }

    if (photoDropzone && photosInput) {
      photoDropzone.addEventListener('click', () => photosInput.click());
      photoDropzone.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') {
          e.preventDefault();
          photosInput.click();
        }
      });
      photoDropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        photoDropzone.classList.add('is-dragover');
      });
      photoDropzone.addEventListener('dragleave', () => {
        photoDropzone.classList.remove('is-dragover');
      });
      photoDropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        photoDropzone.classList.remove('is-dragover');
        if (e.dataTransfer && e.dataTransfer.files) {
          processIncomingPhotos(e.dataTransfer.files);
        }
      });
      photosInput.addEventListener('change', () => {
        processIncomingPhotos(photosInput.files);
      });
    }

    async function compressImageFile(file, maxWidth = 1600, maxHeight = 1600, quality = 0.8) {
      if (file.size < 400 * 1024) return file;
      return new Promise(resolve => {
        const reader = new FileReader();
        reader.readAsDataURL(file);
        reader.onload = ev => {
          const img = new Image();
          img.src = ev.target.result;
          img.onload = () => {
            let width = img.width, height = img.height;
            if (width > height) { if (width > maxWidth) { height = Math.round(height * maxWidth / width); width = maxWidth; } }
            else { if (height > maxHeight) { width = Math.round(width * maxHeight / height); height = maxHeight; } }
            const canvas = document.createElement('canvas'); canvas.width = width; canvas.height = height;
            const ctx = canvas.getContext('2d'); ctx.drawImage(img, 0, 0, width, height);
            canvas.toBlob(blob => {
              if (blob && blob.size < file.size) {
                const compressed = new File([blob], file.name.replace(/\.[^/.]+$/, '') + '.jpg', { type: 'image/jpeg', lastModified: Date.now() });
                resolve(compressed);
              } else resolve(file);
            }, 'image/jpeg', quality);
          };
          img.onerror = () => resolve(file);
        };
        reader.onerror = () => resolve(file);
      });
    }

    function renderPhotoPreviews() {
      const dt = new DataTransfer();
      selectedPhotosList.forEach(p => dt.items.add(p));
      photosInput.files = dt.files;
      photoPreviews.innerHTML = '';
      let totalBytes = 0;
      selectedPhotosList.forEach((photo, idx) => {
        totalBytes += photo.size;
        const url = URL.createObjectURL(photo);
        const item = document.createElement('div');
        item.className = 'photo-preview-item';
        item.style.position = 'relative';
        item.style.borderRadius = '12px';
        item.style.overflow = 'hidden';
        item.style.border = '1px solid #e2e8f0';
        item.style.boxShadow = '0 2px 5px rgba(0,0,0,0.06)';
        item.innerHTML = `
          <img src="${url}" alt="Preview" style="width:100%;height:84px;object-fit:cover;display:block;">
          <button type="button" class="photo-preview-remove" onclick="removePhoto(${idx})" aria-label="Remove photo">
            <i class="fa-solid fa-xmark"></i>
          </button>
        `;
        photoPreviews.appendChild(item);
      });
      const totalMb = (totalBytes / (1024 * 1024)).toFixed(2);
      if (photoTotalSize) photoTotalSize.textContent = selectedPhotosList.length > 0 ? `${selectedPhotosList.length} photo(s) (${totalMb} MB total)` : '';
      if (photoUploadError) {
        if (totalBytes > 5 * 1024 * 1024) { photoUploadError.textContent = 'Combined size exceeds 5 MB limit.'; photoUploadError.hidden = false; }
        else photoUploadError.hidden = true;
      }
    }

    function removePhoto(index) { selectedPhotosList.splice(index, 1); renderPhotoPreviews(); }

    function openPrivacy(e) {
      if (e) e.preventDefault();
      const modal = document.getElementById('privacy-modal');
      if (modal) {
        if (typeof modal.showModal === 'function') modal.showModal();
        else modal.setAttribute('open', '');
      }
    }

    const privacyModalEl = document.getElementById('privacy-modal');
    if (privacyModalEl) {
      privacyModalEl.addEventListener('click', (e) => {
        if (e.target === privacyModalEl) privacyModalEl.close();
      });
    }

    function openNearbyModal(e) {
      if (e) e.preventDefault();
      const modal = document.getElementById('nearby-reports-modal');
      if (modal) {
        if (typeof modal.showModal === 'function') modal.showModal();
        else modal.setAttribute('open', '');
      }
    }

    const nearbyModalEl = document.getElementById('nearby-reports-modal');
    if (nearbyModalEl) {
      nearbyModalEl.addEventListener('click', (e) => {
        if (e.target === nearbyModalEl) nearbyModalEl.close();
      });
    }

    function openVoiceModal() {
      const modal = document.getElementById('voice-recorder-modal');
      if (modal) {
        if (typeof modal.showModal === 'function') modal.showModal();
        else modal.setAttribute('open', '');
      }
    }

    function closeVoiceModal() {
      const modal = document.getElementById('voice-recorder-modal');
      if (!modal) return;
      // Release microphone if recording is currently active
      const recState = modal.querySelector('.voice-recording-state');
      const cancelBtn = modal.querySelector('.voice-btn-cancel');
      if (recState && recState.style.display !== 'none' && cancelBtn) {
        cancelBtn.click();
      }
      if (typeof modal.close === 'function') modal.close();
      else modal.removeAttribute('open');
    }

    function saveVoiceModal() {
      const savedPill = document.getElementById('voice-saved-pill');
      const triggerLabel = document.getElementById('btn-open-voice-label');
      const dict = i18n[currentLang] || i18n.en;
      if (savedPill) savedPill.style.display = 'inline-flex';
      if (triggerLabel) triggerLabel.textContent = dict.voice_btn_edit || 'Review / Re-record';
      closeVoiceModal();
    }

    const voiceModalEl = document.getElementById('voice-recorder-modal');
    if (voiceModalEl) {
      voiceModalEl.addEventListener('click', (e) => {
        if (e.target === voiceModalEl) closeVoiceModal();
      });
      voiceModalEl.addEventListener('cancel', (e) => {
        e.preventDefault();
        closeVoiceModal();
      });
    }

    // Phase C: Resident Voice Intake Integration
    const voiceStrip = document.getElementById('voice-intake-strip');
    const clarityBanner = document.getElementById('resident-voice-clarity-banner');
    const voiceProvenanceContainer = document.getElementById('voice-provenance-container');
    const filledByVoice = new Set();
    const contactFieldsEdited = new Set();

    function updateVoiceProvenanceInputs() {
      if (!voiceProvenanceContainer) return;
      voiceProvenanceContainer.innerHTML = '';
      filledByVoice.forEach(field => {
        const inp = document.createElement('input');
        inp.type = 'hidden';
        inp.name = 'voice_fields_filled[]';
        inp.value = field;
        voiceProvenanceContainer.appendChild(inp);
      });
      ['reporter_name', 'reporter_contact', 'reporter_email'].forEach(field => {
        if (contactFieldsEdited.has(field)) {
          const inp = document.createElement('input');
          inp.type = 'hidden';
          inp.name = 'voice_field_edited_' + field;
          inp.value = '1';
          voiceProvenanceContainer.appendChild(inp);
        }
      });
    }

    function removeVoiceBadge(field) {
      document.querySelectorAll(`.from-recording-badge[data-field="${field}"]`).forEach(el => el.remove());
    }

    function addVoiceBadge(field, targetEl, text = 'From recording') {
      removeVoiceBadge(field);
      const badge = document.createElement('span');
      badge.className = 'from-recording-badge';
      badge.dataset.field = field;
      badge.innerHTML = `<i class="fa-solid fa-microphone"></i> ${text}`;
      if (targetEl) {
        if (targetEl.tagName === 'LABEL' || targetEl.classList.contains('card-section-title') || targetEl.classList.contains('cat-chip')) {
          targetEl.appendChild(badge);
        } else if (targetEl.parentElement) {
          const lbl = targetEl.parentElement.querySelector('label') || targetEl.parentElement;
          lbl.appendChild(badge);
        }
      }
    }

    window.addEventListener('voice-analyzed', function(e) {
      const data = e.detail;
      if (!data) return;

      const isUnclear = data.status === 'needs_verification' || data.status === 'failed' || data.is_unclear || data.clarity === 'unclear';
      const isPartial = data.is_partial || data.clarity === 'partial';

      if (clarityBanner) {
        clarityBanner.style.display = 'block';
        if (isUnclear) {
          clarityBanner.style.background = '#fffbeb';
          clarityBanner.style.border = '1px solid #fde68a';
          clarityBanner.style.color = '#92400e';
          clarityBanner.innerHTML = '<i class="fa-solid fa-circle-exclamation"></i> We couldn\'t understand this recording. Please type your report or record again.';
        } else if (isPartial) {
          clarityBanner.style.background = '#fffbeb';
          clarityBanner.style.border = '1px solid #fde68a';
          clarityBanner.style.color = '#92400e';
          clarityBanner.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Parts of this recording were unclear. Please check every field.';
        }
      }

      if (isUnclear) return;

      let filledCount = 0;
      let filledOtherSteps = false;

      // Problem description
      const descEl = document.getElementById('description');
      if (descEl && data.description) {
        if (!descEl.value || descEl.value.trim() === '' || descEl.dataset.aiFilled === 'true') {
          descEl.value = data.description;
          descEl.dataset.aiFilled = 'true';
          descEl.dispatchEvent(new Event('input', { bubbles: true }));
          const descLabel = document.getElementById('description-label');
          addVoiceBadge('description', descLabel ? descLabel.parentElement : descEl);
          filledByVoice.add('description');
          filledCount++;
        }
      }

      // Category
      if (data.category || data.category_id) {
        const catInput = document.getElementById('category_id_input');
        if (!catInput.value || catInput.dataset.aiFilled === 'true') {
          const targetChip = document.querySelector(`.cat-chip[data-cat-slug="${data.category}"]`) ||
                             document.querySelector(`.cat-chip[data-cat-id="${data.category_id}"]`);
          if (targetChip) {
            const catId = parseInt(targetChip.dataset.catId, 10);
            selectCategory(catId);
            catInput.dataset.aiFilled = 'true';
            addVoiceBadge('category', targetChip);
            filledByVoice.add('category');
            filledCount++;
          }
        }
      }

      // Emergency / People at risk
      if (data.emergency) {
        const emgCb = document.getElementById('people_at_risk_cb');
        if (emgCb) {
          emgCb.checked = true;
          toggleDanger(true);
          emgCb.dataset.aiFilled = 'true';
          const dangerBox = emgCb.closest('.danger-callout-box');
          addVoiceBadge('people_at_risk', dangerBox ? dangerBox.querySelector('span') : emgCb, 'From recording, please confirm');
          filledByVoice.add('emergency');
          filledCount++;
        }
      }

      // Purok (Step 2)
      if (data.purok_id) {
        const purokSel = document.getElementById('purok_id');
        if (purokSel && (!purokSel.value || purokSel.dataset.aiFilled === 'true')) {
          purokSel.value = data.purok_id;
          purokSel.dataset.aiFilled = 'true';
          purokSel.dispatchEvent(new Event('change', { bubbles: true }));
          addVoiceBadge('purok', purokSel.parentElement ? purokSel.parentElement.querySelector('label') : purokSel);
          filledByVoice.add('purok');
          filledCount++;
          filledOtherSteps = true;
        }
      }

      // Landmark (Step 2)
      if (data.landmark) {
        const landmarkInp = document.getElementById('landmark');
        if (landmarkInp && (!landmarkInp.value || landmarkInp.value.trim() === '' || landmarkInp.dataset.aiFilled === 'true')) {
          landmarkInp.value = data.landmark;
          landmarkInp.dataset.aiFilled = 'true';
          addVoiceBadge('landmark', landmarkInp.parentElement ? landmarkInp.parentElement.querySelector('label') : landmarkInp);
          filledByVoice.add('landmark');
          filledCount++;
          filledOtherSteps = true;
          updateLocationStatusPill();
        }
      }

      // Contact Fields (Step 3)
      let contactExpanded = false;
      const anonCb = document.getElementById('is_anonymous');
      const isAnon = anonCb && anonCb.checked;

      if (!isAnon) {
        const nameInp = document.getElementById('reporter_name');
        if (nameInp && data.caller_name && (!nameInp.value || nameInp.value.trim() === '' || nameInp.dataset.aiFilled === 'true')) {
          nameInp.value = data.caller_name;
          nameInp.dataset.aiFilled = 'true';
          addVoiceBadge('reporter_name', nameInp.parentElement ? nameInp.parentElement.querySelector('label') : nameInp);
          filledByVoice.add('reporter_name');
          filledCount++;
          contactExpanded = true;
          filledOtherSteps = true;
        }

        const phoneInp = document.getElementById('reporter_contact');
        if (phoneInp && data.caller_phone && (!phoneInp.value || phoneInp.value.trim() === '' || phoneInp.dataset.aiFilled === 'true')) {
          phoneInp.value = data.caller_phone;
          phoneInp.dataset.aiFilled = 'true';
          addVoiceBadge('reporter_contact', phoneInp.parentElement ? phoneInp.parentElement.querySelector('label') : phoneInp);
          filledByVoice.add('reporter_contact');
          filledCount++;
          contactExpanded = true;
          filledOtherSteps = true;
        }

        const emailInp = document.getElementById('reporter_email');
        if (emailInp && data.caller_email && (!emailInp.value || emailInp.value.trim() === '' || emailInp.dataset.aiFilled === 'true')) {
          emailInp.value = data.caller_email;
          emailInp.dataset.aiFilled = 'true';
          addVoiceBadge('reporter_email', emailInp.parentElement ? emailInp.parentElement.querySelector('label') : emailInp);
          filledByVoice.add('reporter_email');
          filledCount++;
          contactExpanded = true;
          filledOtherSteps = true;
        }
      }

      if (contactExpanded) {
        const contactDetails = document.getElementById('contact-collapsible');
        if (contactDetails) contactDetails.open = true;
      }

      if (!isUnclear && !isPartial && clarityBanner) {
        clarityBanner.style.background = '#ecfdf5';
        clarityBanner.style.border = '1px solid #a7f3d0';
        clarityBanner.style.color = '#065f46';
        const extraNote = filledOtherSteps ? ' Review Location & Contact details as you click Next.' : ' Please check them.';
        clarityBanner.innerHTML = `<i class="fa-solid fa-circle-check"></i> Filled ${filledCount} field${filledCount === 1 ? '' : 's'} from your recording.${extraNote}`;
      }

      updateVoiceProvenanceInputs();

      const saveVoiceBtn = document.getElementById('btn-save-voice-modal');
      if (saveVoiceBtn) saveVoiceBtn.style.display = 'inline-flex';
      const savedPill = document.getElementById('voice-saved-pill');
      if (savedPill) savedPill.style.display = 'inline-flex';
      const triggerLabel = document.getElementById('btn-open-voice-label');
      const dict = i18n[currentLang] || i18n.en;
      if (triggerLabel) triggerLabel.textContent = dict.voice_btn_edit || 'Review / Re-record';
    });

    // Reset event
    window.addEventListener('voice-reset', function() {
      if (clarityBanner) {
        clarityBanner.style.display = 'none';
        clarityBanner.innerHTML = '';
      }
      const saveVoiceBtn = document.getElementById('btn-save-voice-modal');
      if (saveVoiceBtn) saveVoiceBtn.style.display = 'none';
      const savedPill = document.getElementById('voice-saved-pill');
      if (savedPill) savedPill.style.display = 'none';
      const triggerLabel = document.getElementById('btn-open-voice-label');
      const dict = i18n[currentLang] || i18n.en;
      if (triggerLabel) triggerLabel.textContent = dict.voice_btn_trigger || 'Record / Try Demo';

      filledByVoice.forEach(field => removeVoiceBadge(field));
      filledByVoice.clear();
      contactFieldsEdited.clear();
      updateVoiceProvenanceInputs();
    });

    // User edits remove "From recording" badge
    ['description', 'landmark', 'purok_id', 'reporter_name', 'reporter_contact', 'reporter_email'].forEach(id => {
      const el = document.getElementById(id);
      if (el) {
        el.addEventListener('input', function() {
          if (this.dataset.aiFilled === 'true') {
            this.dataset.aiFilled = 'false';
            removeVoiceBadge(id === 'purok_id' ? 'purok' : id);
            if (['reporter_name', 'reporter_contact', 'reporter_email'].includes(id)) {
              contactFieldsEdited.add(id);
            }
            updateVoiceProvenanceInputs();
          }
        });
      }
    });

    // Category deselect/change removes badge
    document.querySelectorAll('.cat-chip').forEach(chip => {
      chip.addEventListener('click', function() {
        const catInput = document.getElementById('category_id_input');
        if (catInput && catInput.dataset.aiFilled === 'true') {
          catInput.dataset.aiFilled = 'false';
          removeVoiceBadge('category');
          updateVoiceProvenanceInputs();
        }
      });
    });

    // Anonymous toggle immediately clears AI-filled contact fields
    const anonCheckbox = document.getElementById('is_anonymous');
    if (anonCheckbox) {
      anonCheckbox.addEventListener('change', function() {
        if (this.checked) {
          ['reporter_name', 'reporter_contact', 'reporter_email'].forEach(id => {
            const inp = document.getElementById(id);
            if (inp && inp.dataset.aiFilled === 'true') {
              inp.value = '';
              inp.dataset.aiFilled = 'false';
              removeVoiceBadge(id);
              filledByVoice.delete(id);
            }
          });
          updateVoiceProvenanceInputs();
        }
      });
    }
  </script>
@endsection
