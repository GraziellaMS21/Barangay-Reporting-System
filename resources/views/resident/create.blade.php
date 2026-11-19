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
        <div class="hero-actions">
          <a class="btn btn-gold" href="#report-form" data-i18n="hero_btn_report">
            <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Report a problem
          </a>
          <a class="btn btn-ghost" href="{{ route('track.index') }}" data-i18n="hero_btn_track">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Track a report
          </a>
        </div>
      </div>
      <div class="hero-seal" aria-hidden="true"><img src="{{ asset('storage/calarian-seal.png') }}" alt=""></div>
    </div>
  </section>
@endsection

@section('content')
  @php
    // Ensure "Other" / "Others" category is always at the last position
    $sortedCategories = $categories->sortBy(function($c) {
        $slug = strtolower($c->slug ?? '');
        $name = strtolower($c->name ?? '');
        return ($slug === 'other' || $slug === 'others' || $name === 'other' || $name === 'others') ? 1 : 0;
    })->values();
  @endphp

  <style>
    /* Card Container Enhancements */
    .report-card {
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      padding: 2.25rem;
      background: #ffffff;
      box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 20px 30px -4px rgba(11, 31, 75, 0.08);
      transition: box-shadow 0.25s ease;
      position: relative;
    }

    @media (max-width: 640px) {
      .report-card {
        padding: 1.25rem;
        border-radius: 16px;
      }
    }

    /* Top Action Bar */
    .card-topbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
      padding-bottom: 1.25rem;
      margin-bottom: 1.5rem;
      border-bottom: 1px solid #f1f5f9;
    }

    .emergency-pill-strip {
      display: inline-flex;
      align-items: center;
      gap: 0.65rem;
      background: #fef2f2;
      border: 1px solid #fecaca;
      border-radius: 999px;
      padding: 0.3rem 0.5rem 0.3rem 0.85rem;
      font-size: 0.85rem;
    }

    .emergency-pill-strip .emg-label {
      color: var(--red);
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }

    .emergency-pill-strip .emg-action-btn {
      background: var(--red);
      color: #ffffff;
      border: none;
      padding: 0.25rem 0.75rem;
      border-radius: 999px;
      font-size: 0.78rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      transition: background 0.15s, transform 0.15s;
    }

    .emergency-pill-strip .emg-action-btn:hover {
      background: #b91c1c;
      transform: translateY(-1px);
    }

    .lang-segmented-control {
      display: inline-flex;
      background: #f1f5f9;
      border: 1px solid #e2e8f0;
      border-radius: 999px;
      padding: 3px;
      gap: 2px;
    }

    .lang-segmented-btn {
      border: none;
      background: transparent;
      padding: 0.3rem 0.85rem;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 700;
      color: #64748b;
      cursor: pointer;
      transition: all 0.2s ease;
    }

    .lang-segmented-btn:hover:not(.active) {
      color: var(--ink);
    }

    .lang-segmented-btn.active {
      background: var(--ink);
      color: #ffffff;
      box-shadow: 0 2px 4px rgba(11, 31, 75, 0.2);
    }

    /* Section Headings */
    .card-section-heading {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      margin-bottom: 0.85rem;
    }

    .card-section-icon {
      display: inline-grid;
      place-items: center;
      width: 34px;
      height: 34px;
      border-radius: 10px;
      background: #eff6ff;
      color: var(--blue);
      font-size: 0.95rem;
      flex-shrink: 0;
    }

    .card-section-title {
      font-size: 1.12rem;
      font-weight: 700;
      color: var(--ink);
      margin: 0;
      letter-spacing: -0.01em;
    }

    .badge-optional {
      font-size: 0.78rem;
      font-weight: 500;
      color: #64748b;
      margin-left: 0.25rem;
    }

    /* Category Chips */
    .cat-chips-wrap {
      display: flex;
      flex-wrap: wrap;
      gap: 0.6rem;
      margin-bottom: 1.25rem;
    }

    .cat-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      padding: 0.55rem 1.05rem;
      border: 1.5px solid #e2e8f0;
      border-radius: 999px;
      background: #f8fafc;
      color: var(--tx);
      font-size: 0.875rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
      user-select: none;
      box-shadow: 0 1px 2px rgba(11, 31, 75, 0.03);
    }

    .cat-chip i {
      color: #64748b;
      font-size: 0.95rem;
      transition: color 0.2s, transform 0.2s;
    }

    .cat-chip:hover {
      background: #eff6ff;
      border-color: #93c5fd;
      color: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 4px 6px -1px rgba(37, 99, 235, 0.08);
    }

    .cat-chip:hover i {
      color: #2563eb;
      transform: scale(1.1);
    }

    .cat-chip.selected {
      background: linear-gradient(135deg, #1e40af 0%, #2563eb 100%);
      color: #ffffff;
      border-color: #1e40af;
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
      transform: translateY(-1px);
    }

    .cat-chip.selected i {
      color: #fbbf24;
    }

    /* Dynamic Questions Container */
    #dynamic-questions-container {
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-left: 4px solid var(--blue);
      border-radius: 14px;
      padding: 1.25rem 1.5rem;
      margin-bottom: 1.25rem;
      animation: fadeIn 0.25s ease-out;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-4px); }
      to { opacity: 1; transform: translateY(0); }
    }

    /* Section Dividers */
    .card-divider {
      border: 0;
      height: 1px;
      background: linear-gradient(to right, transparent, #e2e8f0 10%, #e2e8f0 90%, transparent);
      margin: 1.75rem 0;
    }

    /* Danger Callout Box */
    .danger-callout-box {
      background: #fff8f8;
      border: 1px solid #fee2e2;
      border-radius: 12px;
      padding: 0.85rem 1.15rem;
      margin-bottom: 1.25rem;
      display: flex;
      align-items: center;
      gap: 0.75rem;
      cursor: pointer;
      transition: background 0.2s, border-color 0.2s;
    }

    .danger-callout-box:hover {
      background: #fef2f2;
      border-color: #fca5a5;
    }

    /* Custom Collapsible Card Sections */
    .card-collapsible {
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      margin-bottom: 1.25rem;
      background: #ffffff;
      overflow: hidden;
      transition: border-color 0.2s, box-shadow 0.2s;
    }

    .card-collapsible[open] {
      border-color: #cbd5e1;
      box-shadow: 0 2px 5px rgba(11, 31, 75, 0.04);
    }

    .card-collapsible summary {
      padding: 1rem 1.25rem;
      font-weight: 700;
      font-size: 0.95rem;
      color: var(--ink);
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
      font-size: 0.8rem;
      color: #94a3b8;
      transition: transform 0.2s ease;
    }

    .card-collapsible[open] summary::after {
      transform: rotate(180deg);
    }

    .card-collapsible-content {
      padding: 1.25rem;
      border-top: 1px solid #f1f5f9;
      background: #ffffff;
    }

    /* Toggle Buttons */
    .toggle-row {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 1rem;
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
    }

    .toggle-btn {
      padding: 0.4rem 1rem;
      border: none;
      background: transparent;
      cursor: pointer;
      font-size: 0.85rem;
      font-weight: 700;
      color: #64748b;
      border-radius: 8px;
      transition: all 0.2s ease;
    }

    .toggle-btn.active {
      background: var(--blue);
      color: #ffffff;
      box-shadow: 0 2px 4px rgba(36, 86, 214, 0.2);
    }

    .toggle-btn:focus-within {
      outline: 2px solid var(--blue);
      outline-offset: 2px;
    }

    @media (max-width: 640px) {
      .card-submit-bar {
        flex-direction: column;
        align-items: stretch;
        gap: 1.25rem;
      }
      #btn-final-submit {
        width: 100% !important;
        min-height: 48px;
        justify-content: center;
      }
    }

    /* Map Box */
    .map-container-box {
      border: 1.5px solid #e2e8f0;
      border-radius: 14px;
      overflow: hidden;
      box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.05);
    }

    /* Photo Guidance Box */
    .photo-guidance-card {
      background: #eff6ff;
      border-left: 4px solid var(--blue);
      border-radius: 0 10px 10px 0;
      padding: 0.85rem 1rem;
      margin-bottom: 1rem;
      font-size: 0.85rem;
      color: #1e3a8a;
      display: flex;
      align-items: flex-start;
      gap: 0.65rem;
    }

    /* Footer & Submit Area */
    .card-submit-bar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1.25rem;
      flex-wrap: wrap;
      padding-top: 0.5rem;
    }

    .consent-checkbox-wrap {
      display: flex;
      align-items: center;
      gap: 0.65rem;
      font-size: 0.88rem;
      font-weight: 600;
      color: var(--tx);
      cursor: pointer;
    }

    .consent-checkbox-wrap input[type="checkbox"] {
      width: 1.15rem;
      height: 1.15rem;
      accent-color: var(--blue);
      cursor: pointer;
    }

    /* Emergency Hotlines Screen Card */
    .emergency-hero-card {
      border: 2px solid #ef4444;
      border-radius: 20px;
      background: #ffffff;
      padding: 2.25rem;
      box-shadow: 0 10px 25px -5px rgba(224, 53, 43, 0.15);
      animation: fadeIn 0.25s ease-out;
    }

    .hotline-card-enhanced {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 1rem 1.25rem;
      border-radius: 12px;
      border: 1.5px solid #e2e8f0;
      background: #f8fafc;
      color: var(--ink);
      font-weight: 700;
      text-decoration: none;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
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
      font-size: 1.1rem;
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
    @if($errors->any())
      <div role="alert" class="form-errors" style="margin-bottom:1.5rem;border-radius:12px;padding:1rem 1.25rem;">
        <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;margin-bottom:0.4rem;color:#b91c1c;">
          <i class="fa-solid fa-circle-exclamation"></i> Please correct the errors below:
        </div>
        <ul style="margin:0;padding-left:1.4rem;">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <!-- Top Action Bar -->
    <div class="card-topbar">
      <div class="emergency-pill-strip">
        <span class="emg-label" data-i18n="emg_strip">
          <i class="fa-solid fa-triangle-exclamation"></i> Emergency? Call 911
        </span>
        <button type="button" class="emg-action-btn" onclick="showEmergency()" data-i18n="emg_btn">
          <i class="fa-solid fa-phone-flip"></i> Emergency / Crime
        </button>
      </div>

      <div class="lang-segmented-control" role="group" aria-label="Select Language">
        <button type="button" class="lang-segmented-btn js-lang-btn-en active" onclick="setLanguage('en')">EN</button>
        <button type="button" class="lang-segmented-btn js-lang-btn-fil" onclick="setLanguage('fil')">FIL</button>
      </div>
    </div>

    <form id="report-form-el" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="has_consent_field" value="1">
      <input type="hidden" name="report_type" id="report_type_input" value="non_emergency">
      <input type="hidden" name="category_id" id="category_id_input" value="{{ old('category_id') }}" required>
      <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
      <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
      <input type="hidden" name="is_ongoing" id="is_ongoing" value="{{ old('is_ongoing', 0) ? 1 : 0 }}">
      <input type="hidden" name="is_recurring" id="is_recurring" value="{{ old('is_recurring', 0) ? 1 : 0 }}">

      <!-- 1. Category Selection (Others guaranteed at last) -->
      <div class="card-section-heading">
        <div class="card-section-icon"><i class="fa-solid fa-tags"></i></div>
        <h2 class="card-section-title">
          <span data-i18n="choose_category">Choose a category</span> <span style="color:var(--red);" aria-hidden="true">*</span>
        </h2>
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
        <p class="field-error" style="margin-top:-0.5rem;margin-bottom:1rem;">{{ $message }}</p>
      @enderror

      <!-- Dynamic Questions (Rendered on Category Selection) -->
      <div id="dynamic-questions-container" style="display:none;">
        <div id="dynamic-questions-fields"></div>
      </div>

      <!-- 2. Problem Description -->
      <div class="field" style="margin-bottom:1.25rem;">
        <label for="description" style="font-size:0.95rem;font-weight:700;">
          <i class="fa-solid fa-align-left" style="color:var(--blue);"></i>
          <span id="description-label" data-i18n="lbl_description">What is the problem?</span> <span style="color:var(--red);">*</span>
        </label>
        <textarea id="description" name="description" rows="4" required minlength="10" maxlength="2000"
          placeholder="Please describe the issue in detail...">{{ old('description') }}</textarea>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;margin-top:0.35rem;">
          <p class="field-hint" data-i18n="hint_description" style="margin:0;">
            You may write in English, Filipino, Bisaya, or Chavacano (minimum 10 characters).
          </p>
          <div id="description-char-counter" class="field-hint" style="margin:0;font-size:0.8rem;font-weight:700;color:#64748b;" aria-live="polite">
            <span id="desc-char-count">0</span> / 10 <span id="desc-char-status">min</span>
          </div>
        </div>
        @error('description')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <!-- Danger Right Now Checkbox -->
      <label class="danger-callout-box">
        <input type="checkbox" id="people_at_risk_cb" name="people_at_risk" value="1"
          {{ old('people_at_risk') ? 'checked' : '' }} onchange="toggleDanger(this.checked)"
          style="width:1.15rem;height:1.15rem;accent-color:var(--red);cursor:pointer;flex-shrink:0;">
        <div style="flex:1;">
          <span style="font-weight:700;color:#991b1b;font-size:0.92rem;" data-i18n="danger_checkbox">
            Someone is in danger right now
          </span>
          <p style="margin:0.15rem 0 0;font-size:0.8rem;color:#b91c1c;">
            Check this if there is immediate risk to health, safety, or life.
          </p>
        </div>
      </label>

      <!-- Inline Emergency Warning -->
      <div id="inline-emergency"
        style="display:none;background:#fef2f2;border:1.5px solid #fecaca;border-left:4px solid var(--red);border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.5rem;">
        <p style="color:#b91c1c;font-weight:800;margin:0 0 0.5rem;display:flex;align-items:center;gap:0.5rem;" data-i18n="emg_inline">
          <i class="fa-solid fa-phone-volume"></i> Emergency contacts
        </p>
        <a href="tel:911" class="hotline-card-enhanced hotline-card-primary" style="margin-bottom:0.5rem;">
          <span>911 — National Emergency Hotline</span>
          <span style="font-size:0.8rem;background:var(--red);color:#fff;padding:0.2rem 0.6rem;border-radius:999px;">CALL NOW</span>
        </a>
        @foreach($contacts as $contact)
          @php $num = trim((string) $contact->number); @endphp
          @if($num !== '' && stripos($num, 'N/A') === false && $num !== '911')
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}" class="hotline-card-enhanced" style="margin-bottom:0.4rem;padding:0.65rem 1rem;">
              <span>{{ $contact->name }}</span>
              <span style="color:var(--blue);font-family:monospace;">{{ $num }}</span>
            </a>
          @endif
        @endforeach
      </div>

      <!-- More Details (Collapsible Card Section) -->
      <details class="card-collapsible">
        <summary>
          <span style="display:flex;align-items:center;gap:0.5rem;">
            <i class="fa-solid fa-sliders" style="color:var(--blue);"></i>
            <span data-i18n="more_details">More details (optional)</span>
          </span>
        </summary>
        <div class="card-collapsible-content">
          <div class="toggle-row" style="margin-bottom:1rem;">
            <div>
              <strong style="color:var(--ink);display:block;font-size:0.92rem;" data-i18n="q_ongoing">Is this happening right now?</strong>
              <small style="color:#64748b;">Indicate whether the incident is currently active</small>
            </div>
            <div class="toggle-btns" id="ongoing-toggle-btns">
              <button type="button" class="toggle-btn {{ old('is_ongoing') == 1 ? 'active' : '' }}"
                onclick="setBinaryField('is_ongoing', 1, this)" data-i18n="btn_yes">Yes</button>
              <button type="button" class="toggle-btn {{ old('is_ongoing') == 0 ? 'active' : '' }}"
                onclick="setBinaryField('is_ongoing', 0, this)" data-i18n="btn_no">No</button>
            </div>
          </div>

          <div class="toggle-row" style="padding-top:0.75rem;border-top:1px dashed #e2e8f0;">
            <div>
              <strong style="color:var(--ink);display:block;font-size:0.92rem;" data-i18n="q_recurring">Recurring or one-time?</strong>
              <small style="color:#64748b;">Has this problem happened repeatedly in your area?</small>
            </div>
            <div class="toggle-btns" id="recurring-toggle-btns">
              <button type="button" class="toggle-btn {{ old('is_recurring') == 1 ? 'active' : '' }}"
                onclick="setBinaryField('is_recurring', 1, this)" data-i18n="btn_recurring">Recurring</button>
              <button type="button" class="toggle-btn {{ old('is_recurring') == 0 ? 'active' : '' }}"
                onclick="setBinaryField('is_recurring', 0, this)" data-i18n="btn_onetime">One-time</button>
            </div>
          </div>
        </div>
      </details>

      <hr class="card-divider">

      <!-- 3. Where is it? (Location) -->
      <div class="card-section-heading" style="justify-content:space-between;flex-wrap:wrap;">
        <div style="display:flex;align-items:center;gap:0.65rem;">
          <div class="card-section-icon"><i class="fa-solid fa-map-location-dot"></i></div>
          <h2 class="card-section-title" data-i18n="where_is_it">Where is it?</h2>
        </div>
        <button type="button" id="btn-quick-locate" class="btn btn-outline" style="min-height:38px;padding:0.35rem 0.85rem;font-size:0.82rem;font-weight:700;border-radius:999px;display:inline-flex;align-items:center;gap:0.4rem;">
          <i class="fa-solid fa-crosshairs" style="color:var(--blue);"></i>
          <span data-i18n="btn_quick_locate">Use GPS</span>
        </button>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4" style="margin-bottom:1.5rem;">
        <div>
          <div class="map-container-box">
            <div id="map" class="map-frame" style="min-height:280px;" aria-label="Choose the report location on the map"></div>
          </div>
          <p class="field-hint" style="margin-top:0.45rem;display:flex;align-items:center;gap:0.35rem;" data-i18n="loc_help">
            <i class="fa-solid fa-circle-info" style="color:var(--blue);"></i> Choose at least one: pin, purok, or landmark.
          </p>
          <div id="nearby-line" style="display:none;margin-top:0.6rem;font-size:0.85rem;"></div>
          <div id="nearby-list" style="display:none;margin-top:0.35rem;font-size:0.85rem;"></div>
        </div>

        <div style="display:flex;flex-direction:column;justify-content:space-between;">
          <div>
            <div class="field" style="margin-bottom:1rem;">
              <label for="purok_id" data-i18n="lbl_purok"><i class="fa-solid fa-building-user"></i> Purok</label>
              <select id="purok_id" name="purok_id">
                <option value="" data-i18n="opt_select_purok">Select purok</option>
                @foreach($puroks as $purok)
                  <option value="{{ $purok->id }}" @selected(old('purok_id') == $purok->id)>{{ $purok->name }}</option>
                @endforeach
              </select>
            </div>

            <div class="field" style="margin-bottom:1rem;">
              <label for="landmark" data-i18n="lbl_landmark"><i class="fa-solid fa-signs-post"></i> Landmark or Street</label>
              <input type="text" id="landmark" name="landmark" value="{{ old('landmark') }}"
                placeholder="e.g. Near the chapel, beside school">
            </div>
          </div>

          <div>
            <button type="button" id="btn-locate" class="btn btn-outline" style="width:100%;min-height:44px;font-weight:600;">
              <i class="fa-solid fa-crosshairs" style="color:var(--blue);"></i>
              <span data-i18n="btn_use_location">Use my current location</span>
            </button>
            @error('location')
              <p class="field-error" style="margin-top:0.4rem;">{{ $message }}</p>
            @enderror
          </div>
        </div>
      </div>

      <hr class="card-divider">

      <!-- 4. Photo and updates -->
      <div class="card-section-heading">
        <div class="card-section-icon"><i class="fa-solid fa-camera"></i></div>
        <h2 class="card-section-title" data-i18n="photo_updates">Photo and updates</h2>
      </div>

      <div class="field" style="margin-bottom:1.5rem;">
        <div class="photo-guidance-card">
          <i class="fa-solid fa-lightbulb" style="color:var(--blue);font-size:1.1rem;margin-top:0.1rem;"></i>
          <span data-i18n="photo_tip">Tip: show the problem near a landmark; avoid faces and license plates.</span>
        </div>

        <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png" multiple hidden>
        <button type="button" id="add-photos" class="btn btn-outline" style="min-height:44px;font-weight:600;">
          <i class="fa-solid fa-cloud-arrow-up" style="color:var(--blue);"></i>
          <span data-i18n="btn_add_photos">Add photo</span>
        </button>

        <div id="photo-previews" class="photo-preview-grid" style="margin-top:0.75rem;" aria-live="polite"></div>
        <p id="photo-empty-state" class="field-hint" style="display:none;">No photos selected yet.</p>
        <p id="photo-upload-error" class="field-hint" role="alert" style="color:var(--red);font-weight:700;" hidden></p>
        <p id="photo-total-size" class="field-hint" aria-live="polite" style="font-weight:600;"></p>
        @error('photos')
          <p class="field-error">{{ $message }}</p>
        @enderror
      </div>

      <!-- Updates & Anonymity Collapsible -->
      <details class="card-collapsible">
        <summary>
          <span style="display:flex;align-items:center;gap:0.5rem;">
            <i class="fa-solid fa-envelope-open-text" style="color:var(--blue);"></i>
            <span data-i18n="updates_optional">Get updates on this report (optional)</span>
          </span>
        </summary>
        <div class="card-collapsible-content">
          <label style="display:flex;align-items:center;gap:0.65rem;font-weight:700;color:var(--ink);margin-bottom:0.75rem;cursor:pointer;">
            <input type="checkbox" name="is_anonymous" id="is_anonymous" value="1"
              {{ old('is_anonymous') ? 'checked' : '' }} onchange="toggleAnonymousFields(this.checked)"
              style="width:1.15rem;height:1.15rem;accent-color:var(--blue);cursor:pointer;">
            <span data-i18n="chk_anonymous">Submit this report anonymously</span>
          </label>

          <div id="anon-notice-banner" style="display:none;background:#fef3c7;border:1px solid #fde68a;border-radius:10px;padding:0.65rem 0.85rem;color:#92400e;font-size:0.84rem;font-weight:600;margin-bottom:1rem;" role="status">
            <i class="fa-solid fa-circle-info" style="margin-right:0.35rem;"></i>
            <span data-i18n="anon_notice_text">Anonymous reports will not receive status updates via SMS or email. You will need to check the website using your tracking code.</span>
          </div>

          <div id="contact-fields-section" style="transition:opacity 0.2s ease;">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div class="field" style="margin-bottom:0.75rem;">
                <label for="reporter_name" data-i18n="lbl_name"><i class="fa-solid fa-user"></i> Name (optional)</label>
                <input type="text" id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}"
                  maxlength="100" placeholder="Juan Dela Cruz">
              </div>

              <div class="field" style="margin-bottom:0.75rem;">
                <label for="reporter_contact" data-i18n="lbl_phone"><i class="fa-solid fa-phone"></i> Mobile number (optional)</label>
                <input type="tel" id="reporter_contact" name="reporter_contact" value="{{ old('reporter_contact') }}"
                  maxlength="20" placeholder="09171234567">
                <p class="field-hint" data-i18n="hint_phone">Barangay staff may call you. Philippine format.</p>
              </div>
            </div>

            <div class="field" style="margin-top:0.5rem;margin-bottom:0;">
              <label for="reporter_email" data-i18n="lbl_email"><i class="fa-solid fa-at"></i> Email (optional)</label>
              <input type="email" id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}"
                placeholder="juan@example.com">
            </div>
          </div>
        </div>
      </details>

      <hr class="card-divider">

      <!-- Card Footer: Consent & Submit -->
      <div class="card-submit-bar">
        <label class="consent-checkbox-wrap">
          <input type="checkbox" name="consent" id="consent_checkbox" value="1" required
            {{ old('consent') ? 'checked' : '' }}>
          <span>
            <span data-i18n="consent_agree">I agree to the</span>
            <a href="#" onclick="openPrivacy(event)" data-i18n="privacy_link" style="text-decoration:underline;font-weight:700;">Data Privacy Notice</a> (RA 10173)
          </span>
        </label>
        @error('consent')
          <p class="field-error" style="width:100%;">{{ $message }}</p>
        @enderror

        <button type="submit" id="btn-final-submit" class="btn btn-gold" style="min-width:200px;font-weight:800;font-size:1rem;padding:0.75rem 1.75rem;box-shadow:0 4px 12px rgba(255, 194, 26, 0.35);">
          <i class="fa-solid fa-paper-plane"></i> <span data-i18n="btn_submit_final">Submit report</span>
        </button>
      </div>
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
        hero_btn_track: "Track a report",
        emg_strip: "Emergency? Call 911", emg_btn: "Emergency / Crime",
        emg_title: "Emergency Detected", emg_subtitle: "For immediate life-threatening danger, call official hotlines directly.",
        emg_not_monitored: "THIS ONLINE SYSTEM IS NOT MONITORED IN REAL-TIME.",
        emg_call_prompt: "Please contact emergency services immediately using the numbers below:",
        emg_back_btn: "Return to report", emg_inline: "Emergency contacts",
        where_is_it: "Where is it?", photo_updates: "Photo and updates",
        choose_category: "Choose a category", lbl_optional: "(optional)",
        lbl_description: "What is the problem?", lbl_explain_more: "Explain more",
        placeholder_explain_more: "Add any other details that can help the staff.",
        hint_description: "You may write in English, Filipino, Bisaya, or Chavacano (minimum 10 characters).",
        danger_checkbox: "Someone is in danger right now",
        more_details: "More details (optional)", q_ongoing: "Is this happening right now?", q_recurring: "Recurring or one-time?", btn_yes: "Yes", btn_no: "No", btn_recurring: "Recurring", btn_onetime: "One-time",
        loc_help: "Choose at least one: pin, purok, or landmark.", btn_use_location: "Use my current location",
        lbl_purok: "Purok", opt_select_purok: "Select purok", lbl_landmark: "Landmark or Street",
        btn_add_photos: "Add photo", photo_tip: "Tip: show the problem near a landmark; avoid faces and license plates.",
        chk_anonymous: "Submit this report anonymously", lbl_name: "Name (optional)", lbl_phone: "Mobile number (optional)", hint_phone: "Barangay staff may call you. Philippine format.", lbl_email: "Email (optional)",
        updates_optional: "Get updates on this report (optional)",
        privacy_title: "Data Privacy Notice (RA 10173)", privacy_text: "By submitting this report, you acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly to investigate, route, and resolve community problems. Your information will never be shared with unauthorized parties.",
        btn_submit_final: "Submit report",
        consent_agree: "I agree to the", privacy_link: "Data Privacy Notice",
        btn_quick_locate: "Use GPS",
        anon_notice_text: "Anonymous reports will not receive status updates via SMS or email. You will need to check the website using your tracking code.",
        desc_char_min: "min",
        desc_char_reached: "reached"
      },
      fil: {
        hero_title: "Mag-ulat ng problema sa Barangay Calarian",
        hero_desc: "Magsumite ng ulat na may kategorya, larawan, at lokasyon. Susuriin ito ng mga kawani ng barangay, ipapadala sa tamang tanggapan, at bibigyan ka ng tracking code.",
        hero_btn_report: "Mag-ulat ng problema",
        hero_btn_track: "Subaybayan ang ulat",
        emg_strip: "Emergency? Tumawag sa 911", emg_btn: "Emergency / Krimen",
        emg_title: "Pang-emergency na Alerto", emg_subtitle: "Para sa banta sa buhay, tumawag agad sa hotlines.",
        emg_not_monitored: "ANG ONLINE SYSTEM NA ITO AY HINDI MINUMONITOR NANG REAL-TIME.",
        emg_call_prompt: "Makipag-ugnayan kaagad sa mga numero sa ibaba:",
        emg_back_btn: "Bumalik sa ulat", emg_inline: "Mga emergency contact",
        where_is_it: "Saan ito?", photo_updates: "Larawan at updates",
        choose_category: "Pumili ng kategorya", lbl_optional: "(opsyonal)",
        lbl_description: "Ano ang problema?", lbl_explain_more: "Ipaliwanag pa",
        placeholder_explain_more: "Magdagdag ng iba pang detalye na makakatulong sa kawani.",
        hint_description: "Maaari kang sumulat sa Tagalog/Filipino, English, Bisaya, o Chavacano (hindi bababa sa 10 titik).",
        danger_checkbox: "May nasa panganib ba ngayon?",
        more_details: "Karagdagang detalye (opsyonal)", q_ongoing: "Kasalukuyan ba itong nangyayari?", q_recurring: "Paulit-ulit o minsan lang?", btn_yes: "Oo", btn_no: "Hindi", btn_recurring: "Paulit-ulit", btn_onetime: "Minsan lang",
        loc_help: "Pumili ng kahit isa: pin, purok, o landmark.", btn_use_location: "Gamitin ang aking kasalukuyang lokasyon",
        lbl_purok: "Purok", opt_select_purok: "Pumili ng purok", lbl_landmark: "Landmark o Kalye",
        btn_add_photos: "Magdagdag ng larawan", photo_tip: "Tip: ipakita ang problema at landmark; iwasan ang mukha at plaka.",
        chk_anonymous: "Isumite nang hindi nagpapakilala", lbl_name: "Pangalan (opsyonal)", lbl_phone: "Numero ng telepono (opsyonal)", hint_phone: "Maaaring tumawag ang barangay. Format sa Pilipinas.", lbl_email: "Email (opsyonal)",
        updates_optional: "Gusto ng updates? (opsyonal)",
        privacy_title: "Paunawa sa Data Privacy (RA 10173)", privacy_text: "Sa pagsusumite ng ulat na ito, kinikilala mo na ang Barangay Calarian ay nangongolekta ng detalye ng suliranin, lokasyon, at opsyonal na impormasyon.",
        btn_submit_final: "Isumite ang ulat",
        consent_agree: "Sumasang-ayon ako sa", privacy_link: "Paunawa sa Data Privacy",
        btn_quick_locate: "Gamitin ang GPS",
        anon_notice_text: "Ang mga di-nagpapakilalang ulat ay hindi makakatanggap ng updates sa SMS o email. Kakailanganin mong suriin ang website gamit ang tracking code.",
        desc_char_min: "pinakamababa",
        desc_char_reached: "naabot na"
      }
    };

    let currentLang = 'en';

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
    }

    function toggleDanger(checked) {
      document.getElementById('inline-emergency').style.display = checked ? 'block' : 'none';
    }

    function setBinaryField(fieldId, val, btnEl) {
      const input = document.getElementById(fieldId);
      if (input) input.value = val;
      if (btnEl) {
        const parent = btnEl.closest('.toggle-btns');
        if (parent) {
          parent.querySelectorAll('.toggle-btn').forEach(b => b.classList.remove('active'));
          btnEl.classList.add('active');
        }
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
        if (statusSpan) statusSpan.textContent = dict.desc_char_reached || 'reached';
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

    const questionI18n = {
      water_level: { label: { en: 'How high is the water?', fil: 'Gaano kalalim ang tubig?' }, placeholder: { en: 'Select water level', fil: 'Pumili ng taas' } },
      blocked_drain: { label: { en: 'Is the drain blocked?', fil: 'Nakabara ba ang kanal?' }, placeholder: { en: '', fil: '' } },
      garbage_type: { label: { en: 'Type of garbage', fil: 'Uri ng basura' }, placeholder: { en: 'Select type', fil: 'Pumili ng uri' } },
      volume: { label: { en: 'Approximate volume', fil: 'Tantyang dami' }, placeholder: { en: 'e.g. 2 sacks', fil: 'hal. 2 sako' } },
      damage_type: { label: { en: 'Type of damage', fil: 'Uri ng pinsala' }, placeholder: { en: 'Select type', fil: 'Pumili ng uri' } },
      size: { label: { en: 'Size', fil: 'Laki' }, placeholder: { en: 'e.g. 1 meter wide', fil: 'hal. 1 metro ang lapad' } },
      not_working: { label: { en: 'Is it not working?', fil: 'Hindi ba gumagana?' }, placeholder: { en: '', fil: '' } },
      pole_number: { label: { en: 'Pole number', fil: 'Numero ng poste' }, placeholder: { en: 'e.g. P-123', fil: 'hal. P-123' } },
      water_depth: { label: { en: 'Water depth', fil: 'Lalim ng tubig' }, placeholder: { en: 'Select depth', fil: 'Pumili ng lalim' } },
      area_affected: { label: { en: 'Area affected', fil: 'Naapektuhang lugar' }, placeholder: { en: 'e.g. Main road in front of school', fil: 'hal. pangunahing kalsada sa harap ng paaralan' } },
      details: { label: { en: 'Additional details', fil: 'Karagdagang detalye' }, placeholder: { en: 'Additional details', fil: 'Karagdagang detalye' } }
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
      const hasCategory = !!document.getElementById('category_id_input').value;
      const labelSpan = document.getElementById('description-label');
      const textarea = document.getElementById('description');
      const dict = i18n[currentLang] || i18n.en;
      if (hasCategory) {
        labelSpan.textContent = dict.lbl_explain_more;
        textarea.placeholder = dict.placeholder_explain_more;
      } else {
        labelSpan.textContent = dict.lbl_description;
        textarea.placeholder = 'Please describe the issue in detail...';
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
      fields.innerHTML = '';
      if (category && category.question_schema && category.question_schema.length > 0) {
        category.question_schema.forEach(q => {
          const qDiv = document.createElement('div');
          qDiv.className = 'field';
          qDiv.style.marginBottom = '0.9rem';
          const label = document.createElement('label');
          label.style.fontWeight = '700';
          label.style.fontSize = '0.88rem';
          label.style.color = 'var(--ink)';
          label.style.marginBottom = '0.35rem';
          const labelText = (typeof qLabel === 'function') ? qLabel(q) : q.label;
          label.innerHTML = `${labelText} ${q.required ? '<span style="color:var(--red);">*</span>' : ''}`;
          qDiv.appendChild(label);
          if (q.type === 'select' && Array.isArray(q.options)) {
            const sel = document.createElement('select');
            sel.name = `answers[${q.key}]`;
            if (q.required) sel.required = true;
            const optDefault = document.createElement('option');
            optDefault.value = ''; optDefault.textContent = currentLang === 'fil' ? '-- Pumili --' : '-- Select --';
            sel.appendChild(optDefault);
            q.options.forEach(opt => { const o = document.createElement('option'); o.value = opt; o.textContent = opt; sel.appendChild(o); });
            qDiv.appendChild(sel);
          } else if (q.type === 'boolean') {
            const wrap = document.createElement('div');
            wrap.className = 'toggle-btns';
            wrap.setAttribute('role', 'radiogroup');
            wrap.setAttribute('aria-label', (typeof qLabel === 'function') ? qLabel(q) : q.label);
            wrap.style.marginTop = '0.35rem';
            
            const yesLabel = currentLang === 'fil' ? 'Oo' : 'Yes';
            const noLabel = currentLang === 'fil' ? 'Hindi' : 'No';
            const btnLabelStyle = 'display:inline-flex;align-items:center;justify-content:center;min-height:44px;min-width:76px;padding:0.45rem 1.15rem;margin:0;cursor:pointer;';

            wrap.innerHTML = `
              <label class="toggle-btn" style="${btnLabelStyle}">
                <input type="radio" class="sr-only" name="answers[${q.key}]" value="1" ${q.required ? 'required' : ''}>
                <span>${yesLabel}</span>
              </label>
              <label class="toggle-btn" style="${btnLabelStyle}">
                <input type="radio" class="sr-only" name="answers[${q.key}]" value="0">
                <span>${noLabel}</span>
              </label>
            `;

            wrap.querySelectorAll('input[type="radio"]').forEach(radio => {
              radio.addEventListener('change', () => {
                wrap.querySelectorAll('.toggle-btn').forEach(btn => btn.classList.remove('active'));
                radio.closest('.toggle-btn').classList.add('active');
              });
            });

            qDiv.appendChild(wrap);
          } else if (q.type === 'textarea') {
            const ta = document.createElement('textarea'); ta.name = `answers[${q.key}]`; ta.rows = 2; if (q.required) ta.required = true; ta.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : ''; qDiv.appendChild(ta);
          } else if (q.type === 'number') {
            const inp = document.createElement('input'); inp.type = 'number'; inp.name = `answers[${q.key}]`; if (q.required) inp.required = true; inp.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : ''; qDiv.appendChild(inp);
          } else {
            const inp = document.createElement('input'); inp.type = 'text'; inp.name = `answers[${q.key}]`; if (q.required) inp.required = true; inp.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : ''; qDiv.appendChild(inp);
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
          if (marker) marker.setLatLng([lat, lng]); else marker = L.marker([lat, lng], { draggable: true }).addTo(mapInstance);
          marker.on('dragend', e => {
            const p = e.target.getLatLng();
            document.getElementById('lat').value = p.lat;
            document.getElementById('lng').value = p.lng;
            triggerNearbyReportsCheck();
          });
          document.getElementById('lat').value = lat;
          document.getElementById('lng').value = lng;
          triggerNearbyReportsCheck();
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

      const firstError = document.querySelector('.field-error');
      if (firstError) firstError.scrollIntoView({ block: 'center' });

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

      // Submit button loading state & double-click protection
      const reportForm = document.getElementById('report-form');
      if (reportForm) {
        let isSubmitting = false;
        reportForm.addEventListener('submit', (e) => {
          if (isSubmitting) {
            e.preventDefault();
            return;
          }
          const catVal = document.getElementById('category_id_input').value;
          if (!catVal) {
            const catWrap = document.querySelector('.cat-chips-wrap');
            if (catWrap) catWrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
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
          line.innerHTML = `<span style="color:#b45309;font-weight:700;"><i class="fa-solid fa-triangle-exclamation"></i> ${reports.length} similar report(s) found nearby.</span> <button type="button" class="btn btn-outline btn-small" style="padding:0.2rem 0.6rem;font-size:0.78rem;margin-left:0.5rem;font-weight:700;" onclick="openNearbyModal()">View details</button>`;
          line.style.display = 'block';
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

    // Photo Compression and Previews
    let selectedPhotosList = [];
    const photosInput = document.getElementById('photos');
    const addPhotosBtn = document.getElementById('add-photos');
    const photoPreviews = document.getElementById('photo-previews');
    const photoTotalSize = document.getElementById('photo-total-size');
    const photoUploadError = document.getElementById('photo-upload-error');

    if (addPhotosBtn && photosInput) {
      addPhotosBtn.addEventListener('click', () => photosInput.click());
      photosInput.addEventListener('change', async () => {
        const files = Array.from(photosInput.files || []);
        if (!files.length) return;
        for (const file of files) {
          const optimized = await compressImageFile(file);
          selectedPhotosList.push(optimized);
        }
        renderPhotoPreviews();
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
        item.style.boxShadow = '0 2px 5px rgba(0,0,0,0.08)';
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
  </script>
@endsection
