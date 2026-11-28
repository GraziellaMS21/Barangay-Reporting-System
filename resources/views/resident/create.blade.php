@extends('layouts.app')
@section('title', 'Report a Concern - Barangay Calarian')

@section('hero')
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1 data-i18n="hero_title">Report a concern in Barangay Calarian</h1>
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

    /* Zone 1: Top Utility Bar (3 elements: Emergency Pill, Dispute Pill, Language Switcher) */
    .card-topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.75rem;
      flex-wrap: wrap;
      padding: 0.85rem 1.5rem;
      background: #ffffff;
      border-bottom: 1px solid #f1f5f9;
    }

    .emg-action-pill {
      background: var(--wiz-red, #e0352b);
      color: #ffffff;
      border: none;
      padding: 0.38rem 0.95rem;
      border-radius: 9999px;
      font-size: 0.84rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 1px 2px rgba(224, 53, 43, 0.2);
    }

    .emg-action-pill:hover {
      background: #b91c1c;
      transform: translateY(-1px);
    }

    .emg-action-pill:active {
      transform: scale(0.98);
    }

    .dispute-pill-link {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      padding: 0.38rem 1.1rem;
      background: #fffbeb;
      border: 1px solid #fde68a;
      border-radius: 9999px;
      font-size: 0.84rem;
      font-weight: 600;
      color: #92400e;
      text-decoration: none;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .dispute-pill-link i {
      color: #d97706;
      font-size: 0.92rem;
    }

    .dispute-pill-link:hover {
      background: #fef3c7;
      border-color: #fcd34d;
      color: #78350f;
      transform: translateY(-1px);
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
      color: var(--wiz-muted, #64748b);
      cursor: pointer;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
    }

    .lang-segmented-btn:hover:not(.active) {
      color: var(--wiz-ink, #0b1f4b);
    }

    .lang-segmented-btn.active {
      background: var(--wiz-ink, #0b1f4b);
      color: #ffffff;
      box-shadow: 0 2px 4px rgba(11, 31, 75, 0.2);
    }

    /* Zone 2: 3-Step Interactive Stepper */
    .wizard-stepper-wrap {
      padding: 1.15rem 1.75rem;
      background: #ffffff;
      border-bottom: 1px solid #f1f5f9;
    }

    .wizard-stepper-row {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.85rem;
      flex-wrap: wrap;
    }

    .wizard-step-tab {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      background: transparent;
      border: none;
      padding: 0.3rem 0.5rem;
      cursor: default;
      border-radius: 999px;
      transition: all 160ms ease;
      font-family: inherit;
    }

    .wizard-step-tab.is-completed {
      cursor: pointer;
    }

    .wizard-step-circle {
      width: 28px;
      height: 28px;
      border-radius: 999px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.82rem;
      font-weight: 800;
      background: #f1f5f9;
      border: 1.5px solid #cbd5e1;
      color: var(--wiz-muted, #64748b);
      transition: all 200ms ease;
      flex-shrink: 0;
    }

    .wizard-step-label {
      font-size: 0.9rem;
      font-weight: 600;
      color: var(--wiz-muted, #64748b);
      line-height: 1.2;
      transition: color 200ms ease;
      white-space: nowrap;
    }

    .wizard-step-tab.is-active .wizard-step-circle {
      background: var(--wiz-blue, #2563eb);
      border-color: var(--wiz-blue, #2563eb);
      color: #ffffff;
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.14);
      transform: scale(1.05);
    }

    .wizard-step-tab.is-active .wizard-step-label {
      color: var(--wiz-ink, #0b1f4b);
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
      transform: scale(1.08);
      box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.14);
    }

    .wizard-step-line {
      width: 32px;
      height: 2px;
      background: #cbd5e1;
      border-radius: 999px;
      flex-shrink: 0;
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
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 16px;
      padding: 0.95rem 1.25rem;
      margin-bottom: 1.25rem;
      box-shadow: 0 1px 3px rgba(11, 31, 75, 0.04);
    }

    .voice-strip-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.85rem;
      flex-wrap: wrap;
    }

    .voice-strip-icon {
      width: 42px;
      height: 42px;
      border-radius: 12px;
      background: #eff6ff;
      color: var(--wiz-blue, #2563eb);
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 1.1rem;
      flex-shrink: 0;
    }

    .voice-trigger-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      background: #ffffff;
      color: #2563eb;
      border: 1.5px solid #bfdbfe;
      border-radius: 10px;
      padding: 0.5rem 1.05rem;
      font-size: 0.84rem;
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

    /* Step 1: AI Assistance Card */
    .ai-assist-card {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      padding: 0.95rem 1.15rem;
      margin-bottom: 1rem;
    }

    .ai-assist-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 0.85rem;
      flex-wrap: wrap;
    }

    .ai-assist-btn {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      background: #ffffff;
      color: var(--wiz-blue, #2563eb);
      border: 1.5px solid #bfdbfe;
      border-radius: 10px;
      padding: 0.48rem 0.95rem;
      font-size: 0.83rem;
      font-weight: 700;
      cursor: pointer;
      white-space: nowrap;
      transition: all 160ms cubic-bezier(0.4, 0, 0.2, 1);
      box-shadow: 0 1px 2px rgba(11, 31, 75, 0.04);
    }

    .ai-assist-btn:hover:not(:disabled) {
      background: #eff6ff;
      border-color: #93c5fd;
      transform: translateY(-1px);
    }

    .ai-assist-btn:disabled {
      opacity: 0.65;
      cursor: wait;
    }

    .ai-assist-results {
      margin-top: 0.85rem;
      padding-top: 0.85rem;
      border-top: 1px solid #e2e8f0;
      display: flex;
      flex-direction: column;
      gap: 0.7rem;
      animation: panelFadeDown 200ms ease-out;
    }

    .ai-class-badge {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      padding: 0.22rem 0.7rem;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 700;
    }

    .ai-class-badge.is-community { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .ai-class-badge.is-incident { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .ai-class-badge.is-complaint { background: #f3e8ff; color: #6b21a8; border: 1px solid #d8b4fe; }
    .ai-class-badge.is-uncertain { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }

    .ai-assist-alert {
      border-radius: 10px;
      padding: 0.65rem 0.85rem;
      font-size: 0.82rem;
      line-height: 1.45;
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
      position: relative;
      background: #e2e8f0;
      width: 100%;
    }

    #map.map-frame {
      width: 100%;
      height: 320px;
      min-height: 280px;
      position: relative;
      background: #e2e8f0;
    }

    #map .leaflet-tile {
      max-width: none !important;
      max-height: none !important;
      width: 256px !important;
      height: 256px !important;
    }

    #map .leaflet-container {
      width: 100% !important;
      height: 100% !important;
    }

    .detected-location-card {
      border: 1.5px solid #cbd5e1;
      border-radius: 12px;
      background: #ffffff;
      padding: 0.85rem 1rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }

    .detected-location-grid {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 0.65rem 1rem;
    }

    @media (max-width: 480px) {
      .detected-location-grid {
        grid-template-columns: 1fr;
        gap: 0.5rem;
      }
    }

    /* Map Boundaries & Puroks Legend */
    .map-boundary-legend {
      background: #ffffff;
      border: 1px solid #e2e8f0;
      border-radius: 10px;
      padding: 0.55rem 0.75rem;
      margin-top: 0.55rem;
      font-size: 0.78rem;
      color: var(--wiz-ink);
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    .map-boundary-legend .legend-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      margin-bottom: 0.4rem;
      padding-bottom: 0.35rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .map-boundary-legend .legend-title {
      font-weight: 700;
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      color: var(--wiz-ink);
    }

    .map-boundary-legend .legend-simulated-pill {
      font-size: 0.68rem;
      font-weight: 700;
      color: #92400e;
      background: #fef3c7;
      border: 1px solid #fde68a;
      border-radius: 999px;
      padding: 0.12rem 0.5rem;
      display: inline-flex;
      align-items: center;
    }

    .map-boundary-legend .legend-items-list {
      display: flex;
      flex-direction: column;
      gap: 0.35rem;
    }

    .map-boundary-legend .legend-item {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      font-size: 0.74rem;
      color: var(--wiz-muted);
    }

    .map-boundary-legend .legend-swatch {
      width: 14px;
      height: 10px;
      border-radius: 2px;
      display: inline-block;
      flex-shrink: 0;
      border: 1.5px solid transparent;
    }

    .map-boundary-legend .swatch-calarian {
      background: rgba(100, 116, 139, 0.15);
      border: 1.5px dashed #334155;
    }

    .map-boundary-legend .legend-purok-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 0.3rem;
      margin-top: 0.15rem;
    }

    .map-boundary-legend .legend-purok-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.3rem;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 999px;
      padding: 0.15rem 0.5rem;
      font-size: 0.7rem;
      font-weight: 600;
      color: #334155;
      cursor: pointer;
      user-select: none;
      transition: all 0.15s ease;
    }

    .map-boundary-legend .legend-purok-chip:hover {
      border-color: #94a3b8;
      background: #f1f5f9;
    }

    .map-boundary-legend .legend-purok-chip.is-active {
      background: #eff6ff;
      border-color: #3b82f6;
      color: #1d4ed8;
      box-shadow: 0 0 0 1px #3b82f6;
    }

    .map-boundary-legend .legend-hint {
      margin: 0.35rem 0 0;
      font-size: 0.71rem;
      color: var(--wiz-muted);
      font-style: italic;
    }

    /* Leaflet polygon labels and tooltips */
    .purok-polygon-label {
      background: rgba(255, 255, 255, 0.94) !important;
      border: 1px solid rgba(15, 23, 42, 0.2) !important;
      border-radius: 4px !important;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12) !important;
      color: #0f172a !important;
      font-size: 0.69rem !important;
      font-weight: 700 !important;
      padding: 1px 5px !important;
      pointer-events: none !important;
      white-space: nowrap !important;
    }

    .purok-polygon-label::before {
      display: none !important;
    }

    .barangay-boundary-tooltip {
      background: rgba(15, 23, 42, 0.92) !important;
      border: 1px solid rgba(255, 255, 255, 0.25) !important;
      color: #ffffff !important;
      font-size: 0.72rem !important;
      font-weight: 600 !important;
      border-radius: 4px !important;
      padding: 3px 7px !important;
      pointer-events: none !important;
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
      .card-topbar {
        padding: 0.75rem 1rem;
        gap: 0.5rem;
      }
      .card-topbar .dispute-pill-link {
        order: 3;
        width: 100%;
        justify-content: center;
        font-size: 0.78rem;
        padding: 0.35rem 0.75rem;
      }
      .wizard-stepper-wrap {
        padding: 0.85rem 0.75rem;
      }
      .wizard-stepper-row {
        gap: 0.4rem;
      }
      .wizard-step-label {
        font-size: 0.8rem;
      }
      .wizard-step-line {
        width: 16px;
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
      <!-- Left: Red Emergency Pill -->
      <button type="button" class="emg-action-pill" onclick="showEmergency()">
        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
        <span data-i18n="emg_strip">Emergency? Call 911</span>
      </button>

      <!-- Center: Slim Dispute Redirect Pill -->
      <a href="{{ route('complaint.create') }}" class="dispute-pill-link">
        <i class="fa-solid fa-scale-balanced" aria-hidden="true"></i>
        <span data-i18n="dispute_prompt_pill">Neighbor dispute? File a Dispute Complaint instead &rarr;</span>
      </a>

      <!-- Right: Language Segmented Switcher -->
      <div class="lang-segmented-control" role="group" aria-label="Select Language">
        <button type="button" class="lang-segmented-btn js-lang-btn-en active" onclick="setLanguage('en')">EN</button>
        <button type="button" class="lang-segmented-btn js-lang-btn-fil" onclick="setLanguage('fil')">FIL</button>
      </div>
    </div>

    <!-- Zone 2: 3-Step Interactive Progress Stepper -->
    <div class="wizard-stepper-wrap" aria-label="Report progress">
      <div class="wizard-stepper-row">
        <button type="button" class="wizard-step-tab is-active" data-step="1" onclick="handleStepTabClick(1)" aria-current="step">
          <span class="wizard-step-circle" data-step-num="1">1</span>
          <span class="wizard-step-label" data-i18n="wiz_step_1">Issue Details</span>
        </button>

        <div class="wizard-step-line" aria-hidden="true"></div>

        <button type="button" class="wizard-step-tab" data-step="2" onclick="handleStepTabClick(2)">
          <span class="wizard-step-circle" data-step-num="2">2</span>
          <span class="wizard-step-label" data-i18n="wiz_step_2">Location &amp; Photo</span>
        </button>

        <div class="wizard-step-line" aria-hidden="true"></div>

        <button type="button" class="wizard-step-tab" data-step="3" onclick="handleStepTabClick(3)">
          <span class="wizard-step-circle" data-step-num="3">3</span>
          <span class="wizard-step-label" data-i18n="wiz_step_3">Contact &amp; Submit</span>
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
                <div style="display:flex;align-items:center;gap:0.85rem;min-width:0;">
                  <div class="voice-strip-icon" aria-hidden="true">
                    <i class="fa-solid fa-microphone"></i>
                  </div>
                  <div style="min-width:0;">
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                      <strong style="font-size:0.95rem;color:var(--wiz-ink);" data-i18n="voice_title">Prefer to speak? Quick Report by Voice</strong>
                      <span id="voice-saved-pill" class="voice-saved-pill">
                        <i class="fa-solid fa-circle-check"></i>
                        <span data-i18n="voice_saved_badge">Recording saved</span>
                      </span>
                    </div>
                    <p style="margin:0.15rem 0 0;font-size:0.82rem;color:var(--wiz-muted);line-height:1.35;" data-i18n="voice_desc">
                      AI transcribes English, Filipino, Bisaya, or Chavacano to prefill fields.
                    </p>
                  </div>
                </div>

                <div style="flex-shrink:0;">
                  <button type="button" id="btn-open-voice-modal" class="voice-trigger-btn" onclick="openVoiceModal()">
                    <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                    <span id="btn-open-voice-label" data-i18n="voice_btn_trigger">Record / Try Demo</span>
                  </button>
                </div>
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
                <span id="description-label" data-i18n="lbl_description">What is your concern?</span>
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
              <span data-i18n="err_step1_desc">Please enter at least 10 characters describing your concern.</span>
            </div>
            @error('description')
              <p class="field-error" style="margin-top:0.35rem;">{{ $message }}</p>
            @enderror
          </div>

          <!-- AI Assistance (Manual Trigger, Advisory Only) -->
          <div id="ai-assist-section" class="ai-assist-card">
            <div class="ai-assist-header">
              <div style="display:flex;align-items:flex-start;gap:0.7rem;min-width:0;flex:1;">
                <div class="card-section-icon" style="margin-top:0.1rem;" aria-hidden="true">
                  <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <div style="min-width:0;">
                  <div style="display:flex;align-items:center;gap:0.45rem;flex-wrap:wrap;">
                    <strong style="font-size:0.92rem;color:var(--wiz-ink);" data-i18n="ai_assist_title">AI Assistance</strong>
                    <span class="optional-pill-badge" data-i18n="lbl_optional_pill">Optional</span>
                  </div>
                  <p style="margin:0.18rem 0 0;font-size:0.79rem;color:var(--wiz-muted);line-height:1.4;" data-i18n="ai_assist_desc">
                    Need help understanding or describing your concern? Let AI review your description and suggest a possible classification. You can review or ignore its suggestions.
                  </p>
                </div>
              </div>

              <button type="button" id="btn-analyze-concern" class="ai-assist-btn" onclick="analyzeConcernText()">
                <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
                <span id="btn-analyze-concern-label" data-i18n="ai_assist_btn">Analyze Concern</span>
              </button>
            </div>

            <!-- Inline Validation Error -->
            <div id="ai-assist-validation-error" class="step-inline-error" role="alert" style="margin-top:0.65rem;">
              <i class="fa-solid fa-circle-exclamation"></i>
              <span id="ai-assist-validation-text"></span>
            </div>

            <!-- Loading State -->
            <div id="ai-assist-loading" style="display:none;margin-top:0.75rem;padding:0.65rem 0.85rem;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;font-size:0.83rem;color:#1d4ed8;font-weight:600;" aria-live="polite">
              <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.45rem;" aria-hidden="true"></i>
              <span data-i18n="ai_assist_loading">Analyzing your concern description...</span>
            </div>

            <!-- Error / Unavailable State -->
            <div id="ai-assist-error" class="ai-assist-alert" style="display:none;margin-top:0.75rem;background:#fffbeb;border:1px solid #fde68a;color:#92400e;" role="status" aria-live="polite">
              <i class="fa-solid fa-circle-info" style="margin-right:0.35rem;" aria-hidden="true"></i>
              <span id="ai-assist-error-text" data-i18n="ai_assist_unavailable">AI assistance is temporarily unavailable. You can still continue and submit your concern manually.</span>
            </div>

            <!-- Stale Analysis Warning -->
            <div id="ai-assist-stale-banner" class="ai-assist-alert" style="display:none;margin-top:0.75rem;background:#fef3c7;border:1px solid #fcd34d;color:#92400e;" role="status">
              <i class="fa-solid fa-rotate" style="margin-right:0.35rem;" aria-hidden="true"></i>
              <span data-i18n="ai_assist_stale">Your description or selected category has changed since the last analysis. These earlier suggestions may no longer apply — click Analyze Concern to refresh.</span>
            </div>

            <!-- AI Results Panel -->
            <div id="ai-assist-results" class="ai-assist-results" style="display:none;" aria-live="polite">
              <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;">
                <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                  <span style="font-size:0.78rem;font-weight:700;color:var(--wiz-muted);" data-i18n="ai_lbl_classification">Suggested type:</span>
                  <span id="ai-assist-class-badge" class="ai-class-badge is-community"></span>
                </div>
                <span id="ai-assist-confidence" style="font-size:0.75rem;color:var(--wiz-muted);font-weight:600;"></span>
              </div>

              <!-- Emergency Indication (Advisory Only) -->
              <div id="ai-assist-emergency-note" class="ai-assist-alert" style="display:none;background:#fef2f2;border:1px solid #fecaca;border-left:3.5px solid var(--wiz-red);color:#991b1b;">
                <strong style="display:block;margin-bottom:0.2rem;">
                  <i class="fa-solid fa-triangle-exclamation" style="margin-right:0.3rem;" aria-hidden="true"></i>
                  <span data-i18n="ai_emg_title">Possible urgent safety concern</span>
                </strong>
                <span data-i18n="ai_emg_body">If anyone is in immediate danger, please call 911 or the barangay emergency hotlines directly. AI cannot dispatch emergency services.</span>
              </div>

              <!-- Possible Formal Complaint Guidance -->
              <div id="ai-assist-complaint-note" class="ai-assist-alert" style="display:none;background:#faf5ff;border:1px solid #e9d5ff;color:#581c87;">
                <p style="margin:0 0 0.4rem;" data-i18n="ai_complaint_body">
                  This concern may be more appropriate for the formal complaint process. You may review the separate File a Complaint option if you are seeking formal barangay intervention.
                </p>
                <a href="{{ route('complaint.create') }}" style="font-weight:700;color:#6b21a8;text-decoration:underline;font-size:0.82rem;" data-i18n="ai_complaint_link">
                  Review File a Complaint &rarr;
                </a>
              </div>

              <!-- Summary (Separate from Resident Description) -->
              <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:0.65rem 0.85rem;">
                <div style="font-size:0.75rem;font-weight:700;color:var(--wiz-muted);text-transform:uppercase;letter-spacing:0.03em;margin-bottom:0.2rem;" data-i18n="ai_lbl_summary">
                  AI Summary (for your review)
                </div>
                <p id="ai-assist-summary" style="margin:0;font-size:0.85rem;color:var(--wiz-ink);line-height:1.45;"></p>
              </div>

              <!-- Suggested Category Review Row -->
              <div id="ai-assist-category-row" style="display:flex;align-items:center;justify-content:space-between;gap:0.6rem;flex-wrap:wrap;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:0.6rem 0.85rem;">
                <div style="font-size:0.83rem;color:var(--wiz-ink);">
                  <span style="font-weight:700;color:var(--wiz-muted);font-size:0.78rem;" data-i18n="ai_lbl_category">AI Suggested Category:</span>
                  <strong id="ai-assist-category-name" style="margin-left:0.25rem;"></strong>
                  <span id="ai-assist-category-status" style="margin-left:0.35rem;font-size:0.75rem;font-weight:600;color:#059669;"></span>
                </div>
                <div id="ai-assist-category-actions" style="display:flex;align-items:center;gap:0.45rem;">
                  <button type="button" id="btn-accept-ai-category" onclick="acceptAiCategorySuggestion()" style="background:var(--wiz-blue);color:#fff;border:none;border-radius:8px;padding:0.3rem 0.7rem;font-size:0.77rem;font-weight:700;cursor:pointer;" data-i18n="ai_btn_accept_cat">
                    Accept Suggestion
                  </button>
                  <button type="button" id="btn-dismiss-ai-category" onclick="dismissAiCategorySuggestion()" style="background:#f1f5f9;color:var(--wiz-muted);border:1px solid #cbd5e1;border-radius:8px;padding:0.3rem 0.65rem;font-size:0.77rem;font-weight:600;cursor:pointer;" data-i18n="ai_btn_dismiss_cat">
                    Dismiss
                  </button>
                </div>
              </div>

              <!-- Clarification Questions (Guidance Only) -->
              <div id="ai-assist-questions-wrap" style="display:none;background:#ffffff;border:1px solid #e2e8f0;border-radius:10px;padding:0.65rem 0.85rem;">
                <div style="font-size:0.76rem;font-weight:700;color:var(--wiz-ink);margin-bottom:0.3rem;">
                  <i class="fa-solid fa-circle-question" style="color:var(--wiz-blue);margin-right:0.3rem;" aria-hidden="true"></i>
                  <span data-i18n="ai_lbl_questions">Helpful details you may want to add (optional):</span>
                </div>
                <ul id="ai-assist-questions-list" style="margin:0;padding-left:1.15rem;font-size:0.81rem;color:var(--wiz-muted);line-height:1.5;"></ul>
              </div>
            </div>
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
                <div id="map" class="map-frame" data-custom-map="true" style="height:320px;min-height:280px;" aria-label="Choose the report location on the map"></div>
              </div>

              <!-- Map Boundaries & Puroks Legend -->
              <div id="map-boundary-legend" class="map-boundary-legend" role="region" aria-label="Map Boundary Legend">
                <div class="legend-header">
                  <span class="legend-title">
                    <i class="fa-solid fa-layer-group" style="color:var(--wiz-blue);margin-right:0.35rem;" aria-hidden="true"></i>
                    <span data-i18n="legend_title">Map Boundaries &amp; Puroks</span>
                  </span>
                  <span class="legend-simulated-pill">
                    <i class="fa-solid fa-flask" style="font-size:0.68rem;margin-right:0.25rem;" aria-hidden="true"></i>
                    <span data-i18n="legend_preview_badge">Simulated Preview</span>
                  </span>
                </div>
                <div class="legend-items-list">
                  <div class="legend-item boundary-item">
                    <span class="legend-swatch swatch-calarian"></span>
                    <span class="legend-label" data-i18n="legend_calarian_boundary">Provisional Calarian Boundary (Unverified)</span>
                  </div>
                  <div class="legend-purok-chips" id="legend-purok-chips">
                    <!-- Populated dynamically from API data -->
                  </div>
                </div>
                <p class="legend-hint" data-i18n="legend_hint">Click anywhere on the map or inside any Purok to place your pin.</p>
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
                    <option value="{{ $purok->id }}" @selected(old('purok_id') == $purok->id)>{{ $purok->display_name }}</option>
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

          @php
            $hasMockPuroks = app()->environment('local', 'testing')
                && $puroks->contains(fn ($p): bool => str_starts_with((string) $p->code, 'mock-'));
          @endphp

          <!-- Step 2: Geographic Boundary & Purok Detection Assistance -->
          <div id="location-detection-panel" style="margin-top:0.9rem;display:flex;flex-direction:column;gap:0.6rem;">
            @if($hasMockPuroks)
              <div id="mock-purok-disclosure" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;padding:0.55rem 0.85rem;font-size:0.78rem;color:#92400e;display:flex;align-items:center;gap:0.45rem;" role="note">
                <i class="fa-solid fa-flask" aria-hidden="true"></i>
                <span data-i18n="loc_mock_notice">Development Preview: Purok boundaries are simulated for this prototype and have not yet been verified by Barangay Calarian.</span>
              </div>
            @endif

            <!-- Detected Location Information Panel -->
            <div id="detected-location-card" class="detected-location-card">
              <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:0.55rem;border-bottom:1px solid #f1f5f9;padding-bottom:0.45rem;">
                <strong style="color:var(--wiz-ink);font-size:0.88rem;display:flex;align-items:center;gap:0.35rem;">
                  <i class="fa-solid fa-map-location-dot" style="color:var(--wiz-blue);"></i>
                  <span data-i18n="loc_info_heading">Detected Location Information</span>
                </strong>
                <span id="loc-info-status-badge" style="font-size:0.72rem;font-weight:700;padding:0.15rem 0.5rem;border-radius:999px;background:#f1f5f9;color:#64748b;">
                  <span data-i18n="loc_info_waiting">Awaiting pin</span>
                </span>
              </div>
              <div class="detected-location-grid">
                <div>
                  <span style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--wiz-muted);" data-i18n="loc_info_barangay">Barangay</span>
                  <span id="detected-barangay-val" style="font-size:0.85rem;font-weight:700;color:var(--wiz-ink);">—</span>
                </div>
                <div>
                  <span style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--wiz-muted);" data-i18n="loc_info_city">City</span>
                  <span id="detected-city-val" style="font-size:0.85rem;font-weight:700;color:var(--wiz-ink);">Zamboanga City</span>
                </div>
                <div>
                  <span style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--wiz-muted);" data-i18n="loc_info_purok">Purok</span>
                  <span id="detected-purok-val" style="font-size:0.85rem;font-weight:700;color:var(--wiz-ink);">Not yet identified</span>
                </div>
                <div>
                  <span style="display:block;font-size:0.72rem;font-weight:700;text-transform:uppercase;letter-spacing:0.03em;color:var(--wiz-muted);" data-i18n="loc_info_coords">Coordinates</span>
                  <span id="detected-coords-val" style="font-size:0.82rem;font-weight:600;font-family:ui-monospace,SFMono-Regular,Menlo,Monaco,Consolas,monospace;color:var(--wiz-ink);">—</span>
                </div>
              </div>
            </div>

            <div id="loc-detect-loading" style="display:none;background:#eff6ff;border:1px solid #bfdbfe;border-radius:10px;padding:0.6rem 0.85rem;font-size:0.81rem;color:#1d4ed8;font-weight:600;" aria-live="polite">
              <i class="fa-solid fa-spinner fa-spin" style="margin-right:0.4rem;" aria-hidden="true"></i>
              <span data-i18n="loc_detect_loading">Checking Barangay &amp; Purok boundaries...</span>
            </div>

            <div id="loc-detect-result" style="display:none;border-radius:12px;padding:0.75rem 0.95rem;font-size:0.83rem;border:1px solid #e2e8f0;background:#f8fafc;" aria-live="polite">
              <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
                <div style="display:flex;align-items:flex-start;gap:0.55rem;min-width:0;flex:1;">
                  <i id="loc-detect-icon" class="fa-solid fa-location-dot" style="margin-top:0.15rem;flex-shrink:0;" aria-hidden="true"></i>
                  <div style="min-width:0;">
                    <strong id="loc-detect-title" style="display:block;font-size:0.85rem;"></strong>
                    <p id="loc-detect-desc" style="margin:0.15rem 0 0;font-size:0.79rem;line-height:1.4;"></p>
                  </div>
                </div>
                <div id="loc-detect-actions" style="display:none;align-items:center;gap:0.45rem;flex-shrink:0;">
                  <button type="button" id="btn-confirm-detected-purok" onclick="confirmDetectedPurok()" style="background:var(--wiz-blue);color:#fff;border:none;border-radius:8px;padding:0.35rem 0.75rem;font-size:0.78rem;font-weight:700;cursor:pointer;" data-i18n="loc_btn_confirm_purok">
                    Confirm Purok
                  </button>
                  <button type="button" id="btn-dismiss-detected-purok" onclick="dismissDetectedPurok()" style="background:#ffffff;color:var(--wiz-muted);border:1px solid #cbd5e1;border-radius:8px;padding:0.35rem 0.65rem;font-size:0.78rem;font-weight:600;cursor:pointer;" data-i18n="loc_btn_dismiss">
                    Dismiss
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Photo Upload Section in Step 2 -->
          <div style="margin-top:1.25rem;">
            <div class="card-section-heading">
              <div class="card-section-icon"><i class="fa-solid fa-camera"></i></div>
              <h2 class="card-section-title" data-i18n="photo_title">Photos</h2>
              <span class="optional-pill-badge" data-i18n="lbl_optional_pill">Optional</span>
            </div>

            <!-- Interactive Photo Upload Dropzone -->
            <div class="field" style="margin-bottom:0.5rem;">
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
          </div>

          <div id="step2-error" class="step-inline-error" role="alert">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span data-i18n="err_step2_loc">Please drop a map pin, choose a purok, or enter a landmark.</span>
          </div>
        </div>

        <!-- STEP 3: Contact & Submit -->
        <div class="wizard-panel" data-wizard-panel="3">
          <div class="card-section-heading">
            <div class="card-section-icon"><i class="fa-solid fa-user-shield"></i></div>
            <h2 class="card-section-title" data-i18n="contact_heading">Contact &amp; Submit</h2>
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
          <span data-i18n="btn_next_loc">Next: Location &amp; Photo</span>
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
        </button>

        <!-- Step 2 Footer -->
        <button type="button" class="wizard-btn-outline" id="btn-prev-2" data-step-footer="2" onclick="goToWizardStep(1)" style="display:none;">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
          <span data-i18n="btn_back">Back</span>
        </button>
        <button type="button" class="wizard-btn-primary" id="btn-next-2" data-step-footer="2" onclick="nextFromStep2()" style="display:none;">
          <span data-i18n="btn_next_submit">Next: Contact &amp; Submit</span>
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
        hero_title: "Report a concern in Barangay Calarian",
        hero_desc: "Send a report with category details, photo, and location. Barangay staff will review it, route it to the proper office, and keep you updated with a tracking code.",
        hero_btn_report: "Report a concern",
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
        dispute_prompt_pill: "Neighbor dispute? File a Dispute Complaint instead →",
        dispute_link: "File a Dispute Complaint →",
        wiz_step_1: "Issue Details",
        wiz_step_2: "Location & Photo",
        wiz_step_3: "Contact & Submit",
        btn_quick_locate: "Use GPS",
        voice_title: "Prefer to speak? Quick Report by Voice",
        voice_desc: "AI transcribes English, Filipino, Bisaya, or Chavacano to prefill fields.",
        voice_btn_trigger: "Record / Try Demo",
        voice_btn_edit: "Review / Re-record",
        voice_saved_badge: "Recording saved",
        voice_modal_sub: "Record your concern or upload an audio file to prefill the form.",
        voice_save_btn: "Save & Use Recording",
        btn_close: "Close",
        where_is_it: "Where is it?",
        photo_title: "Photos",
        photo_updates: "Photo and updates",
        contact_heading: "Contact & Submit",
        choose_category: "Choose a category",
        lbl_optional: "(optional)",
        lbl_optional_pill: "Optional",
        lbl_description: "What is your concern?",
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
        btn_next_loc: "Next: Location & Photo",
        btn_next_submit: "Next: Contact & Submit",
        err_step1_desc: "Please enter at least 10 characters describing your concern.",
        err_step2_loc: "Please drop a map pin, choose a purok, or enter a landmark.",
        consent_agree: "I agree to the",
        consent_certify: "I hereby certify that the information provided is true and correct to the best of my knowledge. In accordance with the",
        consent_body: "I consent to the collection and processing of these details by authorized Barangay Calarian officials solely for official community problem reporting, verification, and resolution.",
        privacy_link: "Data Privacy Notice",
        anon_notice_text: "Anonymous reports will not receive status updates via SMS or email. You will need to check the website using your tracking code.",
        desc_char_min: "min",
        desc_char_reached: "reached",
        ai_assist_title: "AI Assistance",
        ai_assist_desc: "Need help understanding or describing your concern? Let AI review your description and suggest a possible classification. You can review or ignore its suggestions.",
        ai_assist_btn: "Analyze Concern",
        ai_assist_loading: "Analyzing your concern description...",
        ai_assist_unavailable: "AI assistance is temporarily unavailable. You can still continue and submit your concern manually.",
        ai_assist_rate_limit: "Too many analysis requests. Please wait a moment or continue submitting your concern manually.",
        ai_assist_stale: "Your description or selected category has changed since the last analysis. These earlier suggestions may no longer apply — click Analyze Concern to refresh.",
        ai_assist_max_len: "Your concern description cannot exceed 2000 characters.",
        ai_lbl_classification: "Suggested type:",
        ai_lbl_summary: "AI Summary (for your review)",
        ai_lbl_category: "AI Suggested Category:",
        ai_lbl_questions: "Helpful details you may want to add (optional):",
        ai_btn_accept_cat: "Accept Suggestion",
        ai_btn_dismiss_cat: "Dismiss",
        ai_cat_none: "No specific category suggested — you may choose one above or leave it unselected.",
        ai_cat_accepted: "Accepted",
        ai_cat_dismissed: "Dismissed",
        ai_emg_title: "Possible urgent safety concern",
        ai_emg_body: "If anyone is in immediate danger, please call 911 or the barangay emergency hotlines directly. AI cannot dispatch emergency services.",
        ai_complaint_body: "This concern may be more appropriate for the formal complaint process. You may review the separate File a Complaint option if you are seeking formal barangay intervention.",
        ai_complaint_link: "Review File a Complaint →",
        ai_class_community_problem: "Community Concern",
        ai_class_incident: "Incident",
        ai_class_possible_formal_complaint: "May Require a Formal Complaint",
        ai_class_uncertain: "Needs More Information",
        ai_confidence_prefix: "AI Confidence:",
        ai_confidence_suffix: "(advisory only)",
        loc_mock_notice: "Development Preview: Purok boundaries are simulated for this prototype and have not yet been verified by Barangay Calarian.",
        loc_detect_loading: "Checking Barangay & Purok boundaries...",
        loc_btn_confirm_purok: "Confirm Purok",
        loc_btn_dismiss: "Dismiss",
        loc_exact_title: "Suggested Purok:",
        loc_exact_desc: "Please confirm to apply this Purok, or dismiss to choose manually.",
        loc_exact_confirmed: "Confirmed and selected.",
        loc_exact_dismissed: "Suggestion dismissed. You can select your Purok manually.",
        loc_ambiguous_title: "Near a Purok boundary",
        loc_ambiguous_desc: "This pin is near a boundary or overlaps multiple areas. Please select your Purok manually.",
        loc_outside_title: "Outside provisional Barangay Calarian boundary",
        loc_outside_desc: "This pin appears outside the provisional Barangay Calarian boundary. You may adjust the pin or still submit for staff review.",
        loc_unresolved_title: "Purok could not be identified automatically",
        loc_unresolved_desc: "Please select your Purok from the dropdown or enter a landmark.",
        loc_in_provisional_title: "Inside provisional Barangay Calarian boundary",
        loc_in_provisional_desc: "Automatic Purok identification is unavailable because Purok boundary data is not configured. Please select your Purok or enter a landmark manually.",
        loc_unavailable_title: "Automatic Purok detection unavailable",
        loc_unavailable_desc: "Please select your Purok or enter a landmark manually.",
        loc_info_heading: "Detected Location Information",
        loc_info_barangay: "Barangay",
        loc_info_city: "City",
        loc_info_purok: "Purok",
        loc_info_coords: "Coordinates",
        loc_info_waiting: "Awaiting pin",
        loc_info_detecting: "Detecting...",
        loc_info_matched: "Purok matched",
        loc_info_unresolved: "Unresolved",
        loc_info_ambiguous: "Near boundary",
        loc_info_outside: "Outside boundary",
        loc_info_not_identified: "Not yet identified",
        loc_info_manual_selection: "Needs manual selection",
        loc_info_outside_boundary: "Outside provisional boundary",
        loc_info_simulated: "(Simulated)",
        loc_info_calarian: "Calarian",
        legend_title: "Map Boundaries & Puroks",
        legend_preview_badge: "Simulated Preview",
        legend_calarian_boundary: "Provisional Calarian Boundary (Unverified)",
        legend_mock_puroks: "Mock Puroks 1–7 (Simulated)",
        legend_hint: "Click anywhere on the map or inside any Purok to place your pin."
      },
      fil: {
        hero_title: "Mag-ulat ng concern sa Barangay Calarian",
        hero_desc: "Magsumite ng ulat na may kategorya, larawan, at lokasyon. Susuriin ito ng mga kawani ng barangay, ipapadala sa tamang tanggapan, at bibigyan ka ng tracking code.",
        hero_btn_report: "Mag-ulat ng concern",
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
        dispute_prompt_pill: "Alitan sa kapitbahay? Mag-file ng Dispute Complaint →",
        dispute_link: "Mag-file ng Dispute Complaint →",
        wiz_step_1: "Detalye",
        wiz_step_2: "Lokasyon at Larawan",
        wiz_step_3: "Impormasyon at Isumite",
        btn_quick_locate: "Gamitin ang GPS",
        voice_title: "Mas gustong magsalita? Mabilis na Ulat Gamit ang Boses",
        voice_desc: "Isinasalin ng AI ang English, Filipino, Bisaya, o Chavacano upang punan ang mga patlang.",
        voice_btn_trigger: "Mag-record / Subukan",
        voice_btn_edit: "Suriin / Mag-record Muli",
        voice_saved_badge: "Nai-save ang recording",
        voice_modal_sub: "I-record ang iyong ulat o mag-upload ng audio upang awtomatikong mapunan ang form.",
        voice_save_btn: "I-save at Gamitin",
        btn_close: "Isara",
        where_is_it: "Saan ito?",
        photo_title: "Mga Larawan",
        photo_updates: "Larawan at updates",
        contact_heading: "Impormasyon at Isumite",
        choose_category: "Pumili ng kategorya",
        lbl_optional: "(opsyonal)",
        lbl_optional_pill: "Opsyonal",
        lbl_description: "Ano ang iyong concern?",
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
        btn_next_loc: "Susunod: Lokasyon at Larawan",
        btn_next_submit: "Susunod: Impormasyon at Isumite",
        err_step1_desc: "Mangyaring maglagay ng hindi bababa sa 10 titik na naglalarawan sa iyong concern.",
        err_step2_loc: "Mangyaring mag-pin sa mapa, pumili ng purok, o maglagay ng landmark.",
        consent_agree: "Sumasang-ayon ako sa",
        consent_certify: "Pinatutunayan ko na ang impormasyong ibinigay ay totoo at tama sa abot ng aking kaalaman. Alinsunod sa",
        consent_body: "pumapayag ako sa pagkolekta at pagproseso ng mga detalyeng ito ng mga awtorisadong opisyal ng Barangay Calarian para lamang sa opisyal na pag-uulat at pagresolba ng suliraning ito.",
        privacy_link: "Paunawa sa Data Privacy",
        anon_notice_text: "Ang mga di-nagpapakilalang ulat ay hindi makakatanggap ng updates sa SMS o email. Kakailanganin mong suriin ang website gamit ang tracking code.",
        desc_char_min: "pinakamababa",
        desc_char_reached: "naabot na",
        ai_assist_title: "Tulong ng AI",
        ai_assist_desc: "Kailangan ng tulong sa pag-unawa o paglalarawan ng iyong concern? Hayaang suriin ng AI ang iyong paglalarawan upang magmungkahi ng posibleng klasipikasyon. Maaari mong suriin o balewalain ang mga mungkahi.",
        ai_assist_btn: "Suriin ang Concern",
        ai_assist_loading: "Sinusuri ang iyong paglalarawan...",
        ai_assist_unavailable: "Pansamantalang hindi magagamit ang tulong ng AI. Maaari ka pa ring magpatuloy at magsumite nang manu-mano.",
        ai_assist_rate_limit: "Masyadong maraming kahilingan. Maghintay sandali o magpatuloy sa manu-manong pagsusumite.",
        ai_assist_stale: "Nagbago ang iyong paglalarawan o napiling kategorya mula nang huling suriin. Maaaring hindi na tugma ang mga naunang mungkahi — pindutin ang Suriin ang Concern upang i-refresh.",
        ai_assist_max_len: "Hindi maaaring lumagpas sa 2000 titik ang paglalarawan.",
        ai_lbl_classification: "Iminungkahing uri:",
        ai_lbl_summary: "Buod ng AI (para sa iyong pagsusuri)",
        ai_lbl_category: "Iminungkahing Kategorya ng AI:",
        ai_lbl_questions: "Mga detalyeng maaaring makatulong (opsyonal):",
        ai_btn_accept_cat: "Tanggapin",
        ai_btn_dismiss_cat: "Balewalain",
        ai_cat_none: "Walang tiyak na kategoryang iminungkahi — maaari kang pumili sa itaas o iwanang walang nakapili.",
        ai_cat_accepted: "Tinanggap",
        ai_cat_dismissed: "Binalewala",
        ai_emg_title: "Posibleng agarang panganib sa kaligtasan",
        ai_emg_body: "Kung may nasa agarang panganib, tumawag agad sa 911 o sa mga emergency hotline ng barangay. Hindi makapagpapadala ng serbisyong pang-emergency ang AI.",
        ai_complaint_body: "Ang concern na ito ay maaaring mas angkop sa pormal na proseso ng reklamo. Maaari mong tingnan ang hiwalay na opsyon na File a Complaint kung nais ng pormal na aksyon ng barangay.",
        ai_complaint_link: "Tingnan ang File a Complaint →",
        ai_class_community_problem: "Suliranin sa Komunidad",
        ai_class_incident: "Insidente",
        ai_class_possible_formal_complaint: "Maaaring Nangangailangan ng Pormal na Reklamo",
        ai_class_uncertain: "Kailangan ng Karagdagang Impormasyon",
        ai_confidence_prefix: "Kumpiyansa ng AI:",
        ai_confidence_suffix: "(gabay lamang)",
        loc_mock_notice: "Paunang Bersyon: Ginaya lamang ang mga hangganan ng Purok para sa prototype na ito at hindi pa beripikado ng Barangay Calarian.",
        loc_detect_loading: "Sinusuri ang hangganan ng Barangay at Purok...",
        loc_btn_confirm_purok: "Kumpirmahin ang Purok",
        loc_btn_dismiss: "Balewalain",
        loc_exact_title: "Iminungkahing Purok:",
        loc_exact_desc: "Paki-kumpirma upang gamitin ang Purok na ito, o balewalain upang pumili nang manu-mano.",
        loc_exact_confirmed: "Nakumpirma at napili na.",
        loc_exact_dismissed: "Binalewala ang mungkahi. Maaari kang pumili ng Purok nang manu-mano.",
        loc_ambiguous_title: "Malapit sa hangganan ng Purok",
        loc_ambiguous_desc: "Ang pin ay malapit sa hangganan o sumasaklaw sa higit sa isang lugar. Paki-pili ang iyong Purok nang manu-mano.",
        loc_outside_title: "Labas sa pansamantalang hangganan ng Barangay Calarian",
        loc_outside_desc: "Mukhang nasa labas ito ng pansamantalang hangganan ng Barangay Calarian. Maaari mong ilipat ang pin o isumite pa rin para sa pagsusuri.",
        loc_unresolved_title: "Hindi matukoy nang awtomatiko ang Purok",
        loc_unresolved_desc: "Paki-pili ang iyong Purok sa listahan o maglagay ng landmark.",
        loc_in_provisional_title: "Nasa loob ng pansamantalang hangganan ng Barangay Calarian",
        loc_in_provisional_desc: "Hindi magagamit ang awtomatikong pagtukoy ng Purok dahil hindi pa naka-configure ang datos ng hangganan ng Purok. Paki-pili ang iyong Purok o maglagay ng landmark nang manu-mano.",
        loc_unavailable_title: "Hindi magagamit ang awtomatikong pagtukoy ng Purok",
        loc_unavailable_desc: "Paki-pili ang iyong Purok o maglagay ng landmark nang manu-mano.",
        loc_info_heading: "Impormasyon ng Natukoy na Lokasyon",
        loc_info_barangay: "Barangay",
        loc_info_city: "Lungsod",
        loc_info_purok: "Purok",
        loc_info_coords: "Mga Coordinate",
        loc_info_waiting: "Naghihintay ng pin",
        loc_info_detecting: "Tinutukoy...",
        loc_info_matched: "Tugmang Purok",
        loc_info_unresolved: "Hindi matukoy",
        loc_info_ambiguous: "Malapit sa hangganan",
        loc_info_outside: "Nasa labas ng hangganan",
        loc_info_not_identified: "Hindi pa natukoy",
        loc_info_manual_selection: "Kailangan ng manu-manong pagpili",
        loc_info_outside_boundary: "Nasa labas ng pansamantalang hangganan",
        loc_info_simulated: "(Pansamantala)",
        loc_info_calarian: "Calarian",
        legend_title: "Mga Hangganan at Purok sa Mapa",
        legend_preview_badge: "Pansamantalang Silip",
        legend_calarian_boundary: "Pansamantalang Hangganan ng Calarian (Hindi Opisyal)",
        legend_mock_puroks: "Mga Mock Purok 1–7 (Pansamantala)",
        legend_hint: "Mag-click kahit saan sa mapa o sa loob ng anumang Purok upang ilagay ang iyong pin."
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

      // Leaflet hidden-tab resize fix: invalidate size with rAF and staged timeouts
      if (targetStep === 2) {
        if (!mapInstance && typeof initReportMap === 'function') {
          initReportMap();
        }
        requestAnimationFrame(() => {
          if (mapInstance) {
            mapInstance.invalidateSize({ animate: false });
            if (typeof resetMapToDefaultCalarian === 'function') resetMapToDefaultCalarian();
          }
          setTimeout(() => {
            if (mapInstance) {
              mapInstance.invalidateSize({ animate: false });
              if (typeof resetMapToDefaultCalarian === 'function') resetMapToDefaultCalarian();
            }
          }, 100);
          setTimeout(() => {
            if (mapInstance) {
              mapInstance.invalidateSize({ animate: false });
              if (typeof resetMapToDefaultCalarian === 'function') resetMapToDefaultCalarian();
            }
          }, 300);
        });
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
      if (typeof renderAiAnalysisResult === 'function' && aiLastResultData) renderAiAnalysisResult();
      if (typeof renderLocationDetectionResult === 'function' && locDetectLastResult) renderLocationDetectionResult();
      if (typeof renderDetectedLocationCard === 'function') renderDetectedLocationCard();
      if (typeof updateMapBoundaryTooltips === 'function') updateMapBoundaryTooltips();
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
    let placePin = null;
    const CALARIAN_CENTER = [6.9250, 122.0300];

    function resetMapToDefaultCalarian() {
      if (!mapInstance) return;
      const currentLat = parseFloat(document.getElementById('lat')?.value);
      const currentLng = parseFloat(document.getElementById('lng')?.value);
      if (!Number.isNaN(currentLat) && !Number.isNaN(currentLng)) {
        mapInstance.setView([currentLat, currentLng], 16);
      } else if (calarianBoundaryLayer && typeof calarianBoundaryLayer.getBounds === 'function') {
        try {
          mapInstance.fitBounds(calarianBoundaryLayer.getBounds().pad(0.04), { animate: false });
        } catch (e) {
          mapInstance.setView(CALARIAN_CENTER, 15, { animate: false });
        }
      } else {
        mapInstance.setView(CALARIAN_CENTER, 15, { animate: false });
      }
    }
    window.resetMapToDefaultCalarian = resetMapToDefaultCalarian;

    function initReportMap() {
      const mapEl = document.getElementById('map');
      if (!mapEl || typeof L === 'undefined') return;

      if (mapEl._leaflet_id && window.leafletMapInstance) {
        mapInstance = window.leafletMapInstance;
        return;
      }

      if (mapEl._leaflet_id && !window.leafletMapInstance) {
        return;
      }

      const CALARIAN_BOUNDS = [[6.885, 121.980], [6.965, 122.135]];

      mapInstance = L.map('map', {
        scrollWheelZoom: false,
        fadeAnimation: true,
        zoomAnimation: true,
      }).setView(CALARIAN_CENTER, 15);

      window.leafletMapInstance = mapInstance;

      if (window.setupCooperativeMapGestures) {
        window.setupCooperativeMapGestures(mapInstance, mapEl);
      }

      const tileLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        subdomains: ['a', 'b', 'c'],
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright" target="_blank" rel="noopener">OpenStreetMap</a> contributors',
        crossOrigin: true,
        keepBuffer: 6,
        updateWhenIdle: false,
        updateWhenZooming: true,
        tileSize: 256,
        zoomOffset: 0
      });
      tileLayer.addTo(mapInstance);

      tileLayer.on('tileerror', function(error) {
        const tile = error && error.tile;
        if (tile && !tile._retryCount) {
          tile._retryCount = 1;
          setTimeout(() => {
            const originalSrc = tile.src;
            tile.src = '';
            tile.src = originalSrc;
          }, 400);
        }
      });

      mapInstance.setMaxBounds(CALARIAN_BOUNDS);
      mapInstance.options.maxBoundsViscosity = 0.6;

      loadMapBoundaries(mapInstance);

      let marker = null;
      placePin = function(lat, lng) {
        if (marker) {
          marker.setLatLng([lat, lng]);
          marker.setZIndexOffset(1000);
        } else {
          marker = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);
          window.markerInstance = marker;
          marker.setZIndexOffset(1000);
          marker.on('dragend', e => {
            const p = e.target.getLatLng();
            document.getElementById('lat').value = p.lat;
            document.getElementById('lng').value = p.lng;
            updateLocationStatusPill();
            const step2Err = document.getElementById('step2-error');
            if (step2Err) step2Err.classList.remove('is-visible');
            triggerNearbyReportsCheck();
            scheduleLocationDetection(p.lat, p.lng);
          });
        }
        window.markerInstance = marker;
        document.getElementById('lat').value = lat;
        document.getElementById('lng').value = lng;
        updateLocationStatusPill();
        const step2Err = document.getElementById('step2-error');
        if (step2Err) step2Err.classList.remove('is-visible');
        triggerNearbyReportsCheck();
        scheduleLocationDetection(lat, lng);
      };
      window.placePin = placePin;

      const oldLat = parseFloat(document.getElementById('lat')?.value);
      const oldLng = parseFloat(document.getElementById('lng')?.value);
      if (!Number.isNaN(oldLat) && !Number.isNaN(oldLng)) {
        placePin(oldLat, oldLng);
        mapInstance.setView([oldLat, oldLng], 16);
      }

      mapInstance.on('click', e => {
        if (placePin) placePin(e.latlng.lat, e.latlng.lng);
      });

      // ResizeObserver to detect when map container becomes visible in Step 2 or changes size
      if (typeof ResizeObserver !== 'undefined' && mapEl) {
        let roTimer = null;
        const ro = new ResizeObserver(entries => {
          for (const entry of entries) {
            if (entry.contentRect.width > 0 && entry.contentRect.height > 0) {
              clearTimeout(roTimer);
              roTimer = setTimeout(() => {
                if (mapInstance) {
                  mapInstance.invalidateSize({ animate: false });
                  const cLat = parseFloat(document.getElementById('lat')?.value);
                  const cLng = parseFloat(document.getElementById('lng')?.value);
                  if (Number.isNaN(cLat) || Number.isNaN(cLng)) {
                    resetMapToDefaultCalarian();
                  }
                }
              }, 50);
            }
          }
        });
        ro.observe(mapEl);
      }

      window.addEventListener('resize', () => {
        if (mapInstance && mapEl && mapEl.offsetWidth > 0) {
          mapInstance.invalidateSize({ animate: false });
        }
      });
    }

    let purokLayersMap = new Map();
    let calarianBoundaryLayer = null;
    let boundariesLoaded = false;
    let mapBoundariesData = null;

    const PUROK_PALETTE = [
      { stroke: '#1d4ed8', fill: '#3b82f6' }, // Purok 1: Blue
      { stroke: '#047857', fill: '#10b981' }, // Purok 2: Emerald
      { stroke: '#6d28d9', fill: '#8b5cf6' }, // Purok 3: Purple
      { stroke: '#b45309', fill: '#f59e0b' }, // Purok 4: Amber
      { stroke: '#be123c', fill: '#f43f5e' }, // Purok 5: Rose
      { stroke: '#0e7490', fill: '#06b6d4' }, // Purok 6: Cyan
      { stroke: '#4338ca', fill: '#6366f1' }, // Purok 7: Indigo
    ];

    function formatResidentPurokName(name) {
      if (!name) return '';
      return String(name)
        .replace(/^MOCK\s+/i, 'Purok ')
        .replace(/\s*\(Simulated\)/i, '')
        .replace(/\s*\(Pansamantala\)/i, '')
        .replace(/PUROK\s+(\d+)/i, 'Purok $1')
        .trim();
    }

    async function loadMapBoundaries(map) {
      if (!map) return;
      if (boundariesLoaded) return;

      try {
        const res = await fetch('{{ route('api.purok-boundaries') }}', {
          headers: { 'Accept': 'application/json' }
        });
        if (!res.ok) return;
        const data = await res.json();
        mapBoundariesData = data;
        boundariesLoaded = true;

        // 1. Draw Provisional Barangay Calarian Boundary
        if (data.barangay && data.barangay.geometry) {
          calarianBoundaryLayer = L.geoJSON(data.barangay.geometry, {
            style: {
              color: '#334155',
              weight: 2.5,
              dashArray: '6, 6',
              fillColor: '#64748b',
              fillOpacity: 0.04,
            },
            interactive: true,
          });

          calarianBoundaryLayer.on('click', function(e) {
            if (typeof placePin === 'function') placePin(e.latlng.lat, e.latlng.lng);
          });

          calarianBoundaryLayer.bindTooltip(function() {
            return currentLang === 'fil'
              ? 'Pansamantalang Hangganan ng Calarian (Hindi Opisyal)'
              : 'Provisional Calarian Boundary (Unverified)';
          }, { sticky: true, className: 'barangay-boundary-tooltip' });

          calarianBoundaryLayer.addTo(map);
          window.calarianBoundaryLayer = calarianBoundaryLayer;

          // Center/fit bounds if no pin has been placed yet
          const oldLatVal = parseFloat(document.getElementById('lat')?.value);
          const oldLngVal = parseFloat(document.getElementById('lng')?.value);
          if (Number.isNaN(oldLatVal) || Number.isNaN(oldLngVal)) {
            resetMapToDefaultCalarian();
          }
        }

        // 2. Draw 7 Mock Puroks with Distinct Visual Styles
        if (Array.isArray(data.puroks)) {
          data.puroks.forEach((purok, index) => {
            const pal = PUROK_PALETTE[index % PUROK_PALETTE.length];
            const defaultStyle = {
              color: pal.stroke,
              weight: 2,
              opacity: 0.9,
              fillColor: pal.fill,
              fillOpacity: 0.18,
              dashArray: null
            };

            const layer = L.geoJSON(purok.geometry, {
              style: defaultStyle,
              interactive: true,
            });

            layer.on('click', function(e) {
              if (typeof placePin === 'function') {
                placePin(e.latlng.lat, e.latlng.lng);
              }
            });

            const rawLabel = formatResidentPurokName(purok.name);
            layer.bindTooltip(rawLabel, {
              permanent: true,
              direction: 'center',
              className: 'purok-polygon-label',
              interactive: false,
            });

            layer.addTo(map);
            purokLayersMap.set(purok.id, { layer, purok, defaultStyle, pal });
          });

          renderLegendPurokChips(data.puroks, PUROK_PALETTE);

          // If a purok is already selected or detected, highlight it
          const selPurok = document.getElementById('purok_id')?.value;
          if (selPurok) {
            highlightPurokPolygon(parseInt(selPurok, 10));
          }
        }
      } catch (err) {
        console.warn('Could not load map boundaries:', err);
      }
    }

    function renderLegendPurokChips(puroks, paletteList) {
      const container = document.getElementById('legend-purok-chips');
      if (!container || !Array.isArray(puroks)) return;
      container.innerHTML = '';
      puroks.forEach((purok, index) => {
        const pal = paletteList ? paletteList[index % paletteList.length] : { stroke: '#3b82f6', fill: '#3b82f6' };
        const chip = document.createElement('div');
        chip.className = 'legend-purok-chip';
        chip.setAttribute('data-legend-purok-id', String(purok.id));
        const displayName = formatResidentPurokName(purok.name);
        chip.innerHTML = `
          <span class="legend-swatch" style="background:${pal.fill};border-color:${pal.stroke};"></span>
          <span class="purok-chip-name">${displayName}</span>
        `;
        chip.addEventListener('click', () => {
          const entry = purokLayersMap.get(purok.id);
          if (entry && mapInstance) {
            try {
              mapInstance.fitBounds(entry.layer.getBounds().pad(0.18));
            } catch (e) {}
            highlightPurokPolygon(purok.id);
          }
        });
        container.appendChild(chip);
      });
    }

    function highlightPurokPolygon(targetId) {
      purokLayersMap.forEach((entry, id) => {
        const isMatch = targetId != null && (
          id === targetId ||
          entry.purok.id == targetId ||
          entry.purok.code === targetId ||
          String(entry.purok.id) === String(targetId)
        );
        if (isMatch) {
          entry.layer.setStyle({
            color: entry.pal.stroke,
            weight: 3.5,
            opacity: 1.0,
            fillColor: entry.pal.fill,
            fillOpacity: 0.45,
          });
          entry.layer.bringToFront();
          if (window.markerInstance) window.markerInstance.setZIndexOffset(1000);
        } else {
          entry.layer.setStyle(entry.defaultStyle);
        }

        const chip = document.querySelector(`[data-legend-purok-id="${id}"]`);
        if (chip) chip.classList.toggle('is-active', isMatch);
      });
    }

    function updateMapBoundaryTooltips() {
      if (calarianBoundaryLayer) {
        calarianBoundaryLayer.unbindTooltip();
        calarianBoundaryLayer.bindTooltip(function() {
          return currentLang === 'fil'
            ? 'Pansamantalang Hangganan ng Calarian (Hindi Opisyal)'
            : 'Provisional Calarian Boundary (Unverified)';
        }, { sticky: true, className: 'barangay-boundary-tooltip' });
      }
      purokLayersMap.forEach((entry) => {
        const rawLabel = formatResidentPurokName(entry.purok.name);
        entry.layer.unbindTooltip();
        entry.layer.bindTooltip(rawLabel, {
          permanent: true,
          direction: 'center',
          className: 'purok-polygon-label',
          interactive: false,
        });
      });
    }

    window.loadMapBoundaries = loadMapBoundaries;
    window.purokLayersMap = purokLayersMap;
    window.highlightPurokPolygon = highlightPurokPolygon;
    window.updateMapBoundaryTooltips = updateMapBoundaryTooltips;

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
          if (!mapInstance && typeof initReportMap === 'function') {
            initReportMap();
          }
          if (placePin) {
            placePin(pos.coords.latitude, pos.coords.longitude);
          }
          if (mapInstance) {
            mapInstance.setView([pos.coords.latitude, pos.coords.longitude], 16);
            mapInstance.invalidateSize({ animate: false });
          }
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

    document.addEventListener('DOMContentLoaded', () => {
      initReportMap();

      const btnLocate = document.getElementById('btn-locate');
      if (btnLocate) {
        btnLocate.addEventListener('click', () => requestGeolocation(btnLocate));
      }
      const btnQuickLocate = document.getElementById('btn-quick-locate');
      if (btnQuickLocate) {
        btnQuickLocate.addEventListener('click', () => requestGeolocation(btnQuickLocate));
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

      if (typeof renderDetectedLocationCard === 'function') {
        renderDetectedLocationCard();
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

    // --- Step 1 Pre-Submission AI Assistance (Manual Trigger Only) ---
    let aiAnalysisInFlight = false;
    let aiAnalysisRequestSeq = 0;
    let aiLastAnalyzedDescription = '';
    let aiLastAnalyzedCategoryId = null;
    let aiLastResultData = null;
    let aiIsStale = false;
    let aiCategoryDecision = null; // null | 'accepted' | 'dismissed'

    function getCurrentSelectedCategoryId() {
      const raw = document.getElementById('category_id_input')?.value || '';
      return /^\d+$/.test(raw) ? parseInt(raw, 10) : null;
    }

    function refreshAiStaleState() {
      if (!aiLastResultData) return;
      const currentDesc = (document.getElementById('description')?.value || '').trim();
      const currentCatId = getCurrentSelectedCategoryId();
      const nextStale = (currentDesc !== aiLastAnalyzedDescription) || (currentCatId !== aiLastAnalyzedCategoryId);
      if (nextStale !== aiIsStale) {
        aiIsStale = nextStale;
        renderAiAnalysisResult();
      }
    }

    function getAiDict() {
      return i18n[currentLang] || i18n.en;
    }

    function setAiButtonLoading(isLoading) {
      const btn = document.getElementById('btn-analyze-concern');
      const loadingEl = document.getElementById('ai-assist-loading');
      if (btn) btn.disabled = isLoading;
      if (loadingEl) loadingEl.style.display = isLoading ? 'block' : 'none';
    }

    function hideAiStatusBanners() {
      const valErr = document.getElementById('ai-assist-validation-error');
      const errEl = document.getElementById('ai-assist-error');
      const staleEl = document.getElementById('ai-assist-stale-banner');
      if (valErr) valErr.classList.remove('is-visible');
      if (errEl) errEl.style.display = 'none';
      if (staleEl) staleEl.style.display = 'none';
    }

    function renderAiAnalysisResult() {
      const resultsEl = document.getElementById('ai-assist-results');
      if (!resultsEl || !aiLastResultData) return;

      const dict = getAiDict();
      const data = aiLastResultData;

      // 1. Classification badge (never expose raw enum values)
      const badgeEl = document.getElementById('ai-assist-class-badge');
      const classMap = {
        community_problem: { label: dict.ai_class_community_problem || 'Community Concern', cls: 'is-community' },
        incident: { label: dict.ai_class_incident || 'Incident', cls: 'is-incident' },
        possible_formal_complaint: { label: dict.ai_class_possible_formal_complaint || 'May Require a Formal Complaint', cls: 'is-complaint' },
        uncertain: { label: dict.ai_class_uncertain || 'Needs More Information', cls: 'is-uncertain' }
      };
      const mapped = classMap[data.classification] || classMap.uncertain;
      if (badgeEl) {
        badgeEl.className = 'ai-class-badge ' + mapped.cls;
        badgeEl.textContent = mapped.label;
      }

      // 2. Confidence indicator (advisory only)
      const confEl = document.getElementById('ai-assist-confidence');
      if (confEl) {
        if (typeof data.confidence === 'number' && !Number.isNaN(data.confidence) && data.confidence >= 0 && data.confidence <= 1) {
          const pct = Math.round(data.confidence * 100);
          confEl.textContent = `${dict.ai_confidence_prefix || 'AI Confidence:'} ${pct}% ${dict.ai_confidence_suffix || '(advisory only)'}`;
          confEl.style.display = 'inline';
        } else {
          confEl.textContent = '';
          confEl.style.display = 'none';
        }
      }

      // 3. Emergency indication
      const emgEl = document.getElementById('ai-assist-emergency-note');
      if (emgEl) emgEl.style.display = data.is_emergency === true ? 'block' : 'none';

      // 4. Possible formal complaint guidance (non-blocking, no auto-redirect)
      const complaintEl = document.getElementById('ai-assist-complaint-note');
      if (complaintEl) complaintEl.style.display = data.classification === 'possible_formal_complaint' ? 'block' : 'none';

      // 5. Summary (separate from resident description)
      const summaryEl = document.getElementById('ai-assist-summary');
      if (summaryEl) summaryEl.textContent = typeof data.summary === 'string' ? data.summary : '';

      // 6. Suggested category review
      const catNameEl = document.getElementById('ai-assist-category-name');
      const catStatusEl = document.getElementById('ai-assist-category-status');
      const catActionsEl = document.getElementById('ai-assist-category-actions');
      const acceptBtn = document.getElementById('btn-accept-ai-category');

      if (data.suggested_category && data.suggested_category.id && data.suggested_category.name) {
        if (catNameEl) {
          catNameEl.textContent = data.suggested_category.name;
          catNameEl.style.color = 'var(--wiz-ink)';
        }
        if (aiCategoryDecision === 'accepted') {
          if (catStatusEl) {
            catStatusEl.textContent = `(${dict.ai_cat_accepted || 'Accepted'})`;
            catStatusEl.style.color = '#059669';
          }
          if (catActionsEl) catActionsEl.style.display = 'none';
        } else if (aiCategoryDecision === 'dismissed') {
          if (catStatusEl) {
            catStatusEl.textContent = `(${dict.ai_cat_dismissed || 'Dismissed'})`;
            catStatusEl.style.color = 'var(--wiz-muted)';
          }
          if (catActionsEl) catActionsEl.style.display = 'none';
        } else {
          if (catStatusEl) catStatusEl.textContent = '';
          if (catActionsEl) catActionsEl.style.display = aiIsStale ? 'none' : 'flex';
          if (acceptBtn) acceptBtn.disabled = aiIsStale;
        }
      } else {
        if (catNameEl) {
          catNameEl.textContent = dict.ai_cat_none || 'No specific category suggested — you may choose one above or leave it unselected.';
          catNameEl.style.color = 'var(--wiz-muted)';
          catNameEl.style.fontWeight = '500';
        }
        if (catStatusEl) catStatusEl.textContent = '';
        if (catActionsEl) catActionsEl.style.display = 'none';
      }

      // 7. Clarification questions (optional guidance)
      const qWrap = document.getElementById('ai-assist-questions-wrap');
      const qList = document.getElementById('ai-assist-questions-list');
      if (qWrap && qList) {
        qList.innerHTML = '';
        const questions = Array.isArray(data.clarification_questions)
          ? data.clarification_questions.filter(q => typeof q === 'string' && q.trim() !== '')
          : [];
        if (questions.length > 0) {
          questions.forEach(q => {
            const li = document.createElement('li');
            li.textContent = q;
            qList.appendChild(li);
          });
          qWrap.style.display = 'block';
        } else {
          qWrap.style.display = 'none';
        }
      }

      // Stale banner state
      const staleEl = document.getElementById('ai-assist-stale-banner');
      if (staleEl) staleEl.style.display = aiIsStale ? 'block' : 'none';

      resultsEl.style.display = 'flex';
    }

    async function analyzeConcernText() {
      if (aiAnalysisInFlight) return;

      const descEl = document.getElementById('description');
      const valErr = document.getElementById('ai-assist-validation-error');
      const valText = document.getElementById('ai-assist-validation-text');
      const dict = getAiDict();
      const descVal = descEl ? descEl.value.trim() : '';

      hideAiStatusBanners();

      if (descVal.length < 10) {
        if (valText) valText.textContent = dict.err_step1_desc || 'Please enter at least 10 characters describing your concern.';
        if (valErr) valErr.classList.add('is-visible');
        if (descEl) {
          descEl.focus();
          triggerShake(descEl);
        }
        return;
      }

      if (descVal.length > 2000) {
        if (valText) valText.textContent = dict.ai_assist_max_len || 'Your concern description cannot exceed 2000 characters.';
        if (valErr) valErr.classList.add('is-visible');
        if (descEl) descEl.focus();
        return;
      }

      const catValRaw = document.getElementById('category_id_input')?.value || '';
      const catId = /^\d+$/.test(catValRaw) ? parseInt(catValRaw, 10) : null;
      const payload = { description: descVal };
      if (catId !== null) payload.category_id = catId;

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const requestSeq = ++aiAnalysisRequestSeq;

      aiAnalysisInFlight = true;
      setAiButtonLoading(true);

      try {
        const res = await fetch('{{ route('reports.analyze-concern') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify(payload)
        });

        if (requestSeq !== aiAnalysisRequestSeq) return;

        let json = null;
        try {
          json = await res.json();
        } catch (parseErr) {
          json = null;
        }

        if (res.status === 422 && json && json.errors && json.errors.description && json.errors.description[0]) {
          if (valText) valText.textContent = json.errors.description[0];
          if (valErr) valErr.classList.add('is-visible');
          return;
        }

        const errEl = document.getElementById('ai-assist-error');
        const errText = document.getElementById('ai-assist-error-text');

        if (res.status === 429) {
          if (errText) errText.textContent = dict.ai_assist_rate_limit || 'Too many analysis requests. Please wait a moment or continue submitting your concern manually.';
          if (errEl) errEl.style.display = 'block';
          return;
        }

        if (!res.ok || !json || json.success !== true || !json.data || typeof json.data !== 'object') {
          if (errText) errText.textContent = dict.ai_assist_unavailable || 'AI assistance is temporarily unavailable. You can still continue and submit your concern manually.';
          if (errEl) errEl.style.display = 'block';
          return;
        }

        aiLastAnalyzedDescription = descVal;
        aiLastAnalyzedCategoryId = catId;
        aiLastResultData = json.data;
        aiCategoryDecision = null;

        const currentDesc = (document.getElementById('description')?.value || '').trim();
        const currentCatId = getCurrentSelectedCategoryId();
        aiIsStale = (currentDesc !== aiLastAnalyzedDescription) || (currentCatId !== aiLastAnalyzedCategoryId);

        renderAiAnalysisResult();
      } catch (networkErr) {
        if (requestSeq !== aiAnalysisRequestSeq) return;
        const errEl = document.getElementById('ai-assist-error');
        const errText = document.getElementById('ai-assist-error-text');
        if (errText) errText.textContent = dict.ai_assist_unavailable || 'AI assistance is temporarily unavailable. You can still continue and submit your concern manually.';
        if (errEl) errEl.style.display = 'block';
      } finally {
        if (requestSeq === aiAnalysisRequestSeq) {
          aiAnalysisInFlight = false;
          setAiButtonLoading(false);
        }
      }
    }

    function acceptAiCategorySuggestion() {
      if (aiIsStale || !aiLastResultData || !aiLastResultData.suggested_category || !aiLastResultData.suggested_category.id) {
        return;
      }
      const targetId = parseInt(aiLastResultData.suggested_category.id, 10);
      if (Number.isNaN(targetId)) return;

      // Ensure suggested category is active and present on the form
      const chipExists = document.querySelector(`.cat-chip[data-cat-id="${targetId}"]`);
      if (!chipExists) return;

      const currentId = getCurrentSelectedCategoryId();
      if (currentId !== targetId) {
        selectCategory(targetId);
      }
      aiLastAnalyzedCategoryId = targetId;
      aiCategoryDecision = 'accepted';
      renderAiAnalysisResult();
    }

    function dismissAiCategorySuggestion() {
      if (!aiLastResultData) return;
      aiCategoryDecision = 'dismissed';
      renderAiAnalysisResult();
    }

    // Mark AI results as stale if resident edits description or changes category after analysis
    const aiDescInput = document.getElementById('description');
    if (aiDescInput) {
      aiDescInput.addEventListener('input', refreshAiStaleState);
    }

    document.querySelectorAll('.cat-chip').forEach(chip => {
      chip.addEventListener('click', () => {
        setTimeout(refreshAiStaleState, 0);
      });
    });

    // --- Step 2: Geographic Boundary & Purok Detection Assistance ---
    let locDetectTimer = null;
    let locDetectSeq = 0;
    let locDetectLastResult = null;
    let locDetectDecision = null; // null | 'confirmed' | 'dismissed'
    let locPurokManuallyEdited = false;

    const purokDropdownEl = document.getElementById('purok_id');
    if (purokDropdownEl) {
      purokDropdownEl.addEventListener('change', () => {
        locPurokManuallyEdited = true;
        if (typeof highlightPurokPolygon === 'function') {
          highlightPurokPolygon(purokDropdownEl.value ? parseInt(purokDropdownEl.value, 10) : null);
        }
        if (locDetectLastResult && locDetectLastResult.status === 'exact' && locDetectDecision !== 'confirmed') {
          locDetectDecision = 'dismissed';
          renderLocationDetectionResult();
        } else {
          renderDetectedLocationCard();
        }
      });
    }

    function updateDetectedLocationCoords(lat, lng) {
      const coordsVal = document.getElementById('detected-coords-val');
      const badge = document.getElementById('loc-info-status-badge');
      const dict = getAiDict();
      if (!coordsVal) return;

      if (lat != null && lng != null && !Number.isNaN(Number(lat)) && !Number.isNaN(Number(lng))) {
        coordsVal.textContent = `${Number(lat).toFixed(6)}, ${Number(lng).toFixed(6)}`;
        if (badge) {
          badge.textContent = dict.loc_info_detecting || 'Detecting...';
          badge.style.background = '#eff6ff';
          badge.style.color = '#1d4ed8';
        }
      } else {
        coordsVal.textContent = '—';
        if (badge) {
          badge.textContent = dict.loc_info_waiting || 'Awaiting pin';
          badge.style.background = '#f1f5f9';
          badge.style.color = '#64748b';
        }
      }
    }

    function renderDetectedLocationCard() {
      const barVal = document.getElementById('detected-barangay-val');
      const cityVal = document.getElementById('detected-city-val');
      const purokVal = document.getElementById('detected-purok-val');
      const coordsVal = document.getElementById('detected-coords-val');
      const badge = document.getElementById('loc-info-status-badge');
      if (!barVal || !purokVal) return;

      const dict = getAiDict();
      const res = locDetectLastResult;
      const latInp = document.getElementById('lat');
      const lngInp = document.getElementById('lng');
      const lat = latInp ? parseFloat(latInp.value) : null;
      const lng = lngInp ? parseFloat(lngInp.value) : null;

      if (cityVal) {
        cityVal.textContent = 'Zamboanga City';
      }

      if (lat != null && lng != null && !Number.isNaN(lat) && !Number.isNaN(lng)) {
        if (coordsVal) coordsVal.textContent = `${lat.toFixed(6)}, ${lng.toFixed(6)}`;
      } else {
        if (coordsVal) coordsVal.textContent = '—';
        barVal.textContent = '—';
        purokVal.textContent = dict.loc_info_not_identified || 'Not yet identified';
        purokVal.style.color = 'var(--wiz-muted)';
        if (badge) {
          badge.textContent = dict.loc_info_waiting || 'Awaiting pin';
          badge.style.background = '#f1f5f9';
          badge.style.color = '#64748b';
        }
        return;
      }

      if (!res) {
        barVal.textContent = '—';
        purokVal.textContent = dict.loc_info_not_identified || 'Not yet identified';
        purokVal.style.color = 'var(--wiz-muted)';
        return;
      }

      if (res.status === 'exact' && res.purok) {
        barVal.textContent = dict.loc_info_calarian || 'Calarian';
        barVal.style.color = 'var(--wiz-ink)';
        purokVal.textContent = formatResidentPurokName(res.purok.name);
        purokVal.style.color = '#065f46';
        if (badge) {
          badge.textContent = dict.loc_info_matched || 'Purok matched';
          badge.style.background = '#ecfdf5';
          badge.style.color = '#065f46';
        }
      } else if (res.status === 'boundary_ambiguous') {
        barVal.textContent = dict.loc_info_calarian || 'Calarian';
        barVal.style.color = 'var(--wiz-ink)';
        purokVal.textContent = dict.loc_info_manual_selection || 'Needs manual selection';
        purokVal.style.color = '#92400e';
        if (badge) {
          badge.textContent = dict.loc_info_ambiguous || 'Near boundary';
          badge.style.background = '#fffbeb';
          badge.style.color = '#92400e';
        }
      } else if (res.status === 'outside_barangay') {
        barVal.textContent = dict.loc_info_outside_boundary || 'Outside provisional boundary';
        barVal.style.color = '#b45309';
        purokVal.textContent = '—';
        purokVal.style.color = 'var(--wiz-muted)';
        if (badge) {
          badge.textContent = dict.loc_info_outside || 'Outside boundary';
          badge.style.background = '#fffbeb';
          badge.style.color = '#b45309';
        }
      } else if (res.status === 'purok_unresolved') {
        barVal.textContent = dict.loc_info_calarian || 'Calarian';
        barVal.style.color = 'var(--wiz-ink)';
        purokVal.textContent = dict.loc_info_not_identified || 'Not yet identified';
        purokVal.style.color = 'var(--wiz-muted)';
        if (badge) {
          badge.textContent = dict.loc_info_unresolved || 'Unresolved';
          badge.style.background = '#eff6ff';
          badge.style.color = '#1e40af';
        }
      } else if (res.status === 'boundary_data_unavailable' && res.in_barangay === true) {
        barVal.textContent = dict.loc_info_calarian || 'Calarian';
        barVal.style.color = 'var(--wiz-ink)';
        purokVal.textContent = dict.loc_info_not_identified || 'Not yet identified';
        purokVal.style.color = 'var(--wiz-muted)';
        if (badge) {
          badge.textContent = dict.loc_info_unresolved || 'Unresolved';
          badge.style.background = '#eff6ff';
          badge.style.color = '#1e40af';
        }
      } else {
        barVal.textContent = '—';
        purokVal.textContent = dict.loc_info_not_identified || 'Not yet identified';
        purokVal.style.color = 'var(--wiz-muted)';
        if (badge) {
          badge.textContent = dict.loc_info_waiting || 'Awaiting pin';
          badge.style.background = '#f1f5f9';
          badge.style.color = '#64748b';
        }
      }

      if (typeof highlightPurokPolygon === 'function') {
        if (res && res.status === 'exact' && res.purok) {
          highlightPurokPolygon(res.purok.id);
        } else {
          const selPurok = document.getElementById('purok_id')?.value;
          highlightPurokPolygon(selPurok ? parseInt(selPurok, 10) : null);
        }
      }
    }

    function scheduleLocationDetection(lat, lng) {
      if (locDetectTimer) clearTimeout(locDetectTimer);
      locDetectSeq += 1;
      const currentSeq = locDetectSeq;
      locDetectDecision = null;
      updateDetectedLocationCoords(lat, lng);

      locDetectTimer = setTimeout(() => {
        runLocationDetection(lat, lng, currentSeq);
      }, 300);
    }

    async function runLocationDetection(lat, lng, seq) {
      const loadingEl = document.getElementById('loc-detect-loading');
      const resultEl = document.getElementById('loc-detect-result');
      if (loadingEl) loadingEl.style.display = 'block';
      if (resultEl) resultEl.style.display = 'none';

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

      try {
        const res = await fetch('{{ route('api.detect-location') }}', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
          },
          body: JSON.stringify({ lat: Number(lat), lng: Number(lng) })
        });

        if (seq !== locDetectSeq) return;

        let json = null;
        try { json = await res.json(); } catch (e) { json = null; }

        if (!res.ok || !json || typeof json.status !== 'string') {
          locDetectLastResult = { status: 'boundary_data_unavailable', candidates: [] };
        } else {
          locDetectLastResult = json;
        }
        renderLocationDetectionResult();
      } catch (err) {
        if (seq !== locDetectSeq) return;
        locDetectLastResult = { status: 'boundary_data_unavailable', candidates: [] };
        renderLocationDetectionResult();
      } finally {
        if (seq === locDetectSeq && loadingEl) {
          loadingEl.style.display = 'none';
        }
      }
    }

    function renderLocationDetectionResult() {
      const box = document.getElementById('loc-detect-result');
      const iconEl = document.getElementById('loc-detect-icon');
      const titleEl = document.getElementById('loc-detect-title');
      const descEl = document.getElementById('loc-detect-desc');
      const actionsEl = document.getElementById('loc-detect-actions');
      if (!box || !locDetectLastResult) return;

      const dict = getAiDict();
      const res = locDetectLastResult;
      if (actionsEl) actionsEl.style.display = 'none';

      if (res.status === 'no_coordinates') {
        box.style.display = 'none';
        renderDetectedLocationCard();
        return;
      }

      if (res.status === 'exact' && res.purok) {
        box.style.background = '#ecfdf5';
        box.style.borderColor = '#a7f3d0';
        box.style.color = '#065f46';
        if (iconEl) iconEl.className = 'fa-solid fa-circle-check';
        if (titleEl) titleEl.textContent = `${dict.loc_exact_title || 'Suggested Purok:'} ${formatResidentPurokName(res.purok.name)}`;
        if (locDetectDecision === 'confirmed') {
          if (descEl) descEl.textContent = dict.loc_exact_confirmed || 'Confirmed and selected.';
        } else if (locDetectDecision === 'dismissed') {
          if (descEl) descEl.textContent = dict.loc_exact_dismissed || 'Suggestion dismissed. You can select your Purok manually.';
        } else {
          if (descEl) descEl.textContent = dict.loc_exact_desc || 'Please confirm to apply this Purok, or dismiss to choose manually.';
          if (actionsEl) actionsEl.style.display = 'flex';
        }
      } else if (res.status === 'boundary_ambiguous') {
        box.style.background = '#fffbeb';
        box.style.borderColor = '#fde68a';
        box.style.color = '#92400e';
        if (iconEl) iconEl.className = 'fa-solid fa-circle-half-stroke';
        if (titleEl) titleEl.textContent = dict.loc_ambiguous_title || 'Near a Purok boundary';
        const candidateNames = Array.isArray(res.candidates) ? res.candidates.map(c => formatResidentPurokName(c.name)).filter(Boolean).join(', ') : '';
        if (descEl) descEl.textContent = (dict.loc_ambiguous_desc || 'This pin is near a boundary or overlaps multiple areas. Please select your Purok manually.') + (candidateNames ? ` (${candidateNames})` : '');
      } else if (res.status === 'outside_barangay') {
        box.style.background = '#fffbeb';
        box.style.borderColor = '#fcd34d';
        box.style.color = '#92400e';
        if (iconEl) iconEl.className = 'fa-solid fa-triangle-exclamation';
        if (titleEl) titleEl.textContent = dict.loc_outside_title || 'Outside provisional Barangay Calarian boundary';
        if (descEl) descEl.textContent = dict.loc_outside_desc || 'This pin appears outside the provisional Barangay Calarian boundary. You may adjust the pin or still submit for staff review.';
      } else if (res.status === 'purok_unresolved') {
        box.style.background = '#eff6ff';
        box.style.borderColor = '#bfdbfe';
        box.style.color = '#1e40af';
        if (iconEl) iconEl.className = 'fa-solid fa-location-dot';
        if (titleEl) titleEl.textContent = dict.loc_unresolved_title || 'Purok could not be identified automatically';
        if (descEl) descEl.textContent = dict.loc_unresolved_desc || 'Please select your Purok from the dropdown or enter a landmark.';
      } else if (res.status === 'boundary_data_unavailable' && res.in_barangay === true) {
        box.style.background = '#eff6ff';
        box.style.borderColor = '#bfdbfe';
        box.style.color = '#1e40af';
        if (iconEl) iconEl.className = 'fa-solid fa-location-dot';
        if (titleEl) titleEl.textContent = dict.loc_in_provisional_title || 'Inside provisional Barangay Calarian boundary';
        if (descEl) descEl.textContent = dict.loc_in_provisional_desc || 'Automatic Purok identification is unavailable because Purok boundary data is not configured. Please select your Purok or enter a landmark manually.';
      } else {
        // boundary_data_unavailable (in_barangay null) or invalid_boundary_data
        box.style.background = '#f8fafc';
        box.style.borderColor = '#e2e8f0';
        box.style.color = '#475569';
        if (iconEl) iconEl.className = 'fa-solid fa-circle-info';
        if (titleEl) titleEl.textContent = dict.loc_unavailable_title || 'Automatic Purok detection unavailable';
        if (descEl) descEl.textContent = dict.loc_unavailable_desc || 'Please select your Purok or enter a landmark manually.';
      }

      box.style.display = 'block';
      renderDetectedLocationCard();
    }

    function confirmDetectedPurok() {
      if (!locDetectLastResult || locDetectLastResult.status !== 'exact' || !locDetectLastResult.purok) return;
      const sel = document.getElementById('purok_id');
      if (sel) {
        const targetVal = String(locDetectLastResult.purok.id);
        const opt = Array.from(sel.options).find(o => o.value === targetVal);
        if (opt) {
          sel.value = targetVal;
          updateLocationStatusPill();
          const step2Err = document.getElementById('step2-error');
          if (step2Err) step2Err.classList.remove('is-visible');
        }
      }
      locDetectDecision = 'confirmed';
      renderLocationDetectionResult();
    }

    function dismissDetectedPurok() {
      if (!locDetectLastResult) return;
      locDetectDecision = 'dismissed';
      renderLocationDetectionResult();
    }
  </script>
@endsection
