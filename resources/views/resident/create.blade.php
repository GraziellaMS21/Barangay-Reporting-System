@extends('layouts.app')
@section('title', 'Report a Problem - Barangay Calarian')

@section('hero')
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1 data-i18n="hero_title">Report a problem in Barangay Calarian</h1>
        <p data-i18n="hero_desc">Send a report with category details, photo, and location. Barangay staff will review it, route it to the proper office, and keep you updated with a tracking code.</p>
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
<!-- Emergency Hotlines Overlay Screen (Shown if Emergency is selected) -->
<section id="emergency-screen" class="emergency-hotline-screen" style="display: none;">
  <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 0.75rem; margin-bottom: 1rem;">
    <div style="display: flex; align-items: center; gap: 0.75rem; color: var(--red);">
      <i class="fa-solid fa-triangle-exclamation" style="font-size: 2rem;"></i>
      <div>
        <h2 style="color: var(--red); margin: 0;" data-i18n="emg_title">Emergency Detected</h2>
        <p style="margin: 0.2rem 0 0;" data-i18n="emg_subtitle">For immediate life-threatening danger, ongoing crimes, or active fires, call official hotlines directly.</p>
      </div>
    </div>

    <!-- Language Toggle (Emergency Screen) -->
    <div style="display: inline-flex; align-items: center; gap: 0.5rem; background: #fff; padding: 0.35rem 0.65rem; border-radius: 9999px; border: 1.5px solid #cbd5e1;">
      <span style="font-size: 0.85rem; font-weight: 700; color: #0f172a; display: inline-flex; align-items: center; gap: 0.35rem;">
        <i class="fa-solid fa-language" style="color: #2456d6;"></i> Language / Wika:
      </span>
      <button type="button" class="lang-toggle-btn js-lang-btn-en active" onclick="setLanguage('en')">English</button>
      <button type="button" class="lang-toggle-btn js-lang-btn-fil" onclick="setLanguage('fil')">Filipino</button>
    </div>
  </div>

  <div class="card alert" style="margin-top: 1.25rem;">
    <p style="font-weight: 700; color: var(--red);" data-i18n="emg_not_monitored">
      <i class="fa-solid fa-bell-slash"></i> THIS ONLINE SYSTEM IS NOT MONITORED IN REAL-TIME.
    </p>
    <p style="margin: 0.25rem 0 0;" data-i18n="emg_call_prompt">Please stop filling out this form and contact emergency services immediately using the numbers below:</p>
  </div>

  <div class="emergency-hotline-grid">
    <a href="tel:911" class="hotline-card" style="border-color: var(--red); background: #fee2e2;">
      <span><i class="fa-solid fa-phone-volume"></i> National Emergency Hotline</span>
      <span style="color: var(--red); font-size: 1.2rem;">911</span>
    </a>
    @foreach($contacts as $contact)
      <a href="tel:{{ preg_replace('/[^0-9+]/', '', $contact->number) }}" class="hotline-card">
        <span><i class="fa-solid fa-phone"></i> {{ $contact->name }}</span>
        <span>{{ $contact->number }}</span>
      </a>
    @endforeach
  </div>

  <div style="margin-top: 1.5rem; text-align: center;">
    <button type="button" class="btn btn-outline" onclick="selectReportType('non_emergency')">
      <i class="fa-solid fa-arrow-left"></i> <span data-i18n="emg_back_btn">Return to Non-Emergency Report</span>
    </button>
  </div>
</section>

<!-- Main Reporting Form Card -->
<section id="report-form" class="card form-card">
  <!-- Card Header with Title and Language Toggle -->
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.25rem; padding-bottom: 0.85rem; border-bottom: 1.5px solid var(--line); flex-wrap: wrap; gap: 0.75rem;">
    <div>
      <span style="font-size: 1.15rem; font-weight: 800; color: var(--ink); display: flex; align-items: center; gap: 0.5rem;">
        <i class="fa-solid fa-file-pen" style="color: var(--gold);"></i>
        <span data-i18n="form_heading">Resident Incident Report</span>
      </span>
      <small style="color: var(--mut); display: block; margin-top: 0.15rem;" data-i18n="form_subheading">Barangay Calarian Community Reporting</small>
    </div>

    <!-- Language Toggle (High Contrast & Clear) -->
    <div style="display: inline-flex; align-items: center; gap: 0.4rem; background: #f8fafc; padding: 0.35rem 0.65rem; border-radius: 9999px; border: 1.5px solid #cbd5e1;">
      <span style="font-size: 0.88rem; font-weight: 700; color: #0f172a; display: inline-flex; align-items: center; gap: 0.35rem;">
        <i class="fa-solid fa-language" style="color: #2456d6; font-size: 1.1rem;"></i> Language / Wika:
      </span>
      <button type="button" class="lang-toggle-btn js-lang-btn-en active" onclick="setLanguage('en')">English</button>
      <button type="button" class="lang-toggle-btn js-lang-btn-fil" onclick="setLanguage('fil')">Filipino</button>
    </div>
  </div>

  <!-- Stepper Indicators -->
  <div class="step-indicator-bar" id="stepper">
    <div class="step-item active" data-step="1"><span class="step-num">1</span> <span data-i18n="step1">Type</span></div>
    <div class="step-item" data-step="2"><span class="step-num">2</span> <span data-i18n="step2">Category</span></div>
    <div class="step-item" data-step="3"><span class="step-num">3</span> <span data-i18n="step3">Situation</span></div>
    <div class="step-item" data-step="4"><span class="step-num">4</span> <span data-i18n="step4">Location</span></div>
    <div class="step-item" data-step="5"><span class="step-num">5</span> <span data-i18n="step5">Photos</span></div>
    <div class="step-item" data-step="6"><span class="step-num">6</span> <span data-i18n="step6">Contact</span></div>
    <div class="step-item" data-step="7"><span class="step-num">7</span> <span data-i18n="step7">Review</span></div>
  </div>

  @if($errors->any())
    <div role="alert" class="form-errors" style="margin-bottom: 1.25rem;">
      <ul style="margin: 0; padding-left: 1.2rem;">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <form id="multi-step-form" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="has_consent_field" value="1">
    <input type="hidden" name="report_type" id="report_type_input" value="{{ old('report_type', 'non_emergency') }}">
    <input type="hidden" name="category_id" id="category_id_input" value="{{ old('category_id') }}">

    <!-- STEP 1: REPORT TYPE -->
    <div class="step-pane" id="step-pane-1">
      <h2 data-i18n="step1_title"><i class="fa-solid fa-list-check"></i> What type of issue are you reporting?</h2>
      <p class="field-hint" data-i18n="step1_desc">Choose whether your report is an urgent emergency requiring dispatch or a community maintenance concern.</p>

      <div class="report-type-cards">
        <div class="type-card type-emergency" onclick="selectReportType('emergency')">
          <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--red);">
            <i class="fa-solid fa-truck-medical" style="font-size: 1.4rem;"></i>
            <h3 style="color: var(--red); margin: 0;" data-i18n="type_emergency_title">Emergency / Crime</h3>
          </div>
          <p style="font-size: 0.88rem; color: var(--mut); margin: 0;" data-i18n="type_emergency_desc">
            Immediate danger, active fire, ongoing crime, severe accident, or medical threat. Shows 911 contacts immediately.
          </p>
        </div>

        <div class="type-card type-non-emergency selected" id="card-non-emergency" onclick="selectReportType('non_emergency')">
          <div style="display: flex; align-items: center; gap: 0.5rem; color: var(--blue);">
            <i class="fa-solid fa-clipboard-list" style="font-size: 1.4rem;"></i>
            <h3 style="color: var(--blue); margin: 0;" data-i18n="type_non_emergency_title">Community Concern</h3>
          </div>
          <p style="font-size: 0.88rem; color: var(--mut); margin: 0;" data-i18n="type_non_emergency_desc">
            Drainage issues, garbage collection, streetlights, potholes, or barangay maintenance.
          </p>
        </div>
      </div>

      <div style="display: flex; justify-content: flex-end; margin-top: 1.5rem;">
        <button type="button" class="btn btn-gold" onclick="goToStep(2)">
          <span data-i18n="btn_continue">Next: Select Category</span> <i class="fa-solid fa-arrow-right"></i>
        </button>
      </div>
    </div>

    <!-- STEP 2: CATEGORY & CATEGORY QUESTIONS -->
    <div class="step-pane" id="step-pane-2" style="display: none;">
      <h2 data-i18n="step2_title"><i class="fa-solid fa-tags"></i> Select Category & Describe</h2>
      <p class="field-hint" data-i18n="step2_desc">Pick the category that best describes the problem. Specific questions will appear below.</p>

      <div class="category-picker-grid">
        @foreach($categories as $category)
          <div class="category-option-card {{ old('category_id') == $category->id ? 'selected' : '' }}"
               data-cat-id="{{ $category->id }}"
               data-cat-slug="{{ $category->slug }}"
               onclick="selectCategory({{ $category->id }})">
            <span class="cat-icon">
              @if($category->slug === 'drainage') <i class="fa-solid fa-water"></i>
              @elseif($category->slug === 'garbage') <i class="fa-solid fa-trash-can"></i>
              @elseif($category->slug === 'road-damage') <i class="fa-solid fa-road"></i>
              @elseif($category->slug === 'streetlight') <i class="fa-solid fa-lightbulb"></i>
              @elseif($category->slug === 'flooding') <i class="fa-solid fa-house-flood-water"></i>
              @else <i class="fa-solid fa-circle-question"></i>
              @endif
            </span>
            <strong style="font-size: 0.9rem;">{{ $category->name }}</strong>
          </div>
        @endforeach
      </div>

      <!-- Category Specific Questions Container (Rendered dynamically) -->
      <div id="dynamic-questions-container" style="display: none; background: #f8faff; border: 1px solid #dbeafe; border-radius: 12px; padding: 1.25rem; margin-bottom: 1.25rem;">
        <h3 style="font-size: 1rem; color: var(--blue); margin-bottom: 0.75rem;" data-i18n="specific_questions_title">
          <i class="fa-solid fa-circle-info"></i> Specific Questions
        </h3>
        <div id="dynamic-questions-fields"></div>
      </div>

      <div class="field">
        <label for="description">
          <i class="fa-solid fa-align-left" aria-hidden="true"></i>
          <span data-i18n="lbl_description">What is the problem? Describe the issue in detail</span> *
        </label>
        <textarea id="description" name="description" rows="4" required minlength="10" maxlength="2000"
          placeholder="Example: The drainage in front of the community hall is blocked by debris, causing water to overflow into the road.">{{ old('description') }}</textarea>
        <p class="field-hint" data-i18n="hint_description">You may write in English, Filipino, Bisaya, or Chavacano (minimum 10 characters).</p>
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
        <button type="button" class="btn btn-outline" onclick="goToStep(1)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Back</span></button>
        <button type="button" class="btn btn-gold" onclick="validateAndGoStep(2, 3)"><span data-i18n="btn_continue">Next: Situation</span> <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 3: SITUATION ASSESSMENT -->
    <div class="step-pane" id="step-pane-3" style="display: none;">
      <h2 data-i18n="step3_title"><i class="fa-solid fa-clock"></i> Situation Assessment</h2>
      <p class="field-hint" data-i18n="step3_desc">These extra details help our barangay team prioritize response time and safety hazards.</p>

      <div class="situation-field-group">
        <!-- Happening Now -->
        <div class="toggle-row">
          <div>
            <strong data-i18n="q_ongoing">Is this happening right now?</strong>
            <p style="margin: 0; font-size: 0.8rem; color: var(--mut);" data-i18n="q_ongoing_desc">Active overflowing, ongoing commotion, or currently live issue.</p>
          </div>
          <div class="toggle-btns">
            <input type="hidden" name="is_ongoing" id="is_ongoing" value="{{ old('is_ongoing', 0) }}">
            <button type="button" class="toggle-btn {{ old('is_ongoing') ? 'active' : '' }}" onclick="setBinaryField('is_ongoing', 1)" data-i18n="btn_yes">Yes</button>
            <button type="button" class="toggle-btn {{ !old('is_ongoing') ? 'active' : '' }}" onclick="setBinaryField('is_ongoing', 0)" data-i18n="btn_no">No</button>
          </div>
        </div>

        <!-- Anyone at Risk -->
        <div class="toggle-row">
          <div>
            <strong data-i18n="q_risk">Is anyone currently at risk or in danger?</strong>
            <p style="margin: 0; font-size: 0.8rem; color: var(--mut);" data-i18n="q_risk_desc">Risk of electrocution, open deep pits, slipping, or health hazards.</p>
          </div>
          <div class="toggle-btns">
            <input type="hidden" name="people_at_risk" id="people_at_risk" value="{{ old('people_at_risk', 0) }}">
            <button type="button" class="toggle-btn {{ old('people_at_risk') ? 'active' : '' }}" onclick="setBinaryField('people_at_risk', 1)" data-i18n="btn_yes">Yes</button>
            <button type="button" class="toggle-btn {{ !old('people_at_risk') ? 'active' : '' }}" onclick="setBinaryField('people_at_risk', 0)" data-i18n="btn_no">No</button>
          </div>
        </div>

        <!-- Recurring vs One-time -->
        <div class="toggle-row">
          <div>
            <strong data-i18n="q_recurring">Is this a recurring or one-time problem?</strong>
            <p style="margin: 0; font-size: 0.8rem; color: var(--mut);" data-i18n="q_recurring_desc">Has this been happening repeatedly over weeks or months?</p>
          </div>
          <div class="toggle-btns">
            <input type="hidden" name="is_recurring" id="is_recurring" value="{{ old('is_recurring', 0) }}">
            <button type="button" class="toggle-btn {{ old('is_recurring') ? 'active' : '' }}" onclick="setBinaryField('is_recurring', 1)" data-i18n="btn_recurring">Recurring</button>
            <button type="button" class="toggle-btn {{ !old('is_recurring') ? 'active' : '' }}" onclick="setBinaryField('is_recurring', 0)" data-i18n="btn_onetime">One-time</button>
          </div>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
        <button type="button" class="btn btn-outline" onclick="goToStep(2)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Back</span></button>
        <button type="button" class="btn btn-gold" onclick="goToStep(4)"><span data-i18n="btn_continue">Next: Location</span> <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 4: LOCATION & NEARBY CHECK -->
    <div class="step-pane" id="step-pane-4" style="display: none;">
      <h2 data-i18n="step4_title"><i class="fa-solid fa-map-location-dot"></i> Where is the problem located?</h2>
      <p class="field-hint" data-i18n="step4_desc">Tap on the map, choose a purok, or type a landmark. At least one location method is required.</p>

      <div class="field">
        <div id="map" class="map-frame" aria-label="Choose the report location on the map"></div>
        <div class="map-controls">
          <button type="button" id="btn-locate" class="btn btn-outline btn-small">
            <i class="fa-solid fa-crosshairs" aria-hidden="true"></i> <span data-i18n="btn_use_location">Use my current location</span>
          </button>
        </div>
        <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
        <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
      </div>

      <!-- Nearby Reports Banner container (populated via fetch /api/nearby-reports) -->
      <div id="nearby-reports-container" class="nearby-reports-box" style="display: none;">
        <div style="display: flex; align-items: flex-start; gap: 0.5rem;">
          <i class="fa-solid fa-circle-question" style="color: #d97706; font-size: 1.25rem; margin-top: 0.1rem;"></i>
          <div style="flex: 1;">
            <strong style="color: #92400e;" data-i18n="nearby_title">Is this the same problem?</strong>
            <p style="margin: 0.2rem 0 0.5rem; font-size: 0.85rem; color: #78350f;" data-i18n="nearby_desc">
              We found reports near this pin. If your issue is already listed, barangay staff are already aware. You can still submit a new report if yours is different.
            </p>
            <div id="nearby-reports-list"></div>
          </div>
        </div>
      </div>

      <div class="form-grid" style="margin-top: 1rem;">
        <div class="field">
          <label for="purok_id"><i class="fa-solid fa-map" aria-hidden="true"></i> <span data-i18n="lbl_purok">Purok (optional)</span></label>
          <select id="purok_id" name="purok_id">
            <option value="" data-i18n="opt_select_purok">Select purok</option>
            @foreach($puroks as $purok)
              <option value="{{ $purok->id }}" @selected(old('purok_id') == $purok->id)>{{ $purok->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label for="landmark"><i class="fa-solid fa-landmark" aria-hidden="true"></i> <span data-i18n="lbl_landmark">Landmark or Street (optional)</span></label>
          <input type="text" id="landmark" name="landmark" value="{{ old('landmark') }}"
            placeholder="Near the chapel, beside school">
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
        <button type="button" class="btn btn-outline" onclick="goToStep(3)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Back</span></button>
        <button type="button" class="btn btn-gold" onclick="validateAndGoStep(4, 5)"><span data-i18n="btn_continue">Next: Photos</span> <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 5: PHOTOS WITH GUIDANCE & COMPRESSION -->
    <div class="step-pane" id="step-pane-5" style="display: none;">
      <h2 data-i18n="step5_title"><i class="fa-solid fa-camera"></i> Attach Photos (Optional)</h2>
      <p class="field-hint" data-i18n="step5_desc">Photos help staff assess the problem quickly and bring the right equipment.</p>

      <!-- Photo Guidance Alert -->
      <div class="photo-guidance-box">
        <strong style="color: var(--blue);" data-i18n="photo_guidance_head"><i class="fa-solid fa-circle-check"></i> Helpful Photo Tips:</strong>
        <ul style="margin: 0.35rem 0 0; padding-left: 1.25rem;">
          <li data-i18n="photo_tip1">Capture the problem clearly alongside a recognizable landmark (building, sign, post).</li>
          <li data-i18n="photo_tip2" style="color: #b91c1c; font-weight: 600;">Privacy notice: Avoid capturing recognizable human faces or vehicle license plates.</li>
          <li data-i18n="photo_tip3">Images are automatically optimized on your device for low data usage.</li>
        </ul>
      </div>

      <div class="field">
        <div class="photo-preview-area">
          <p id="photo-empty-state" class="photo-empty-state" data-i18n="photo_empty">No photos selected yet. Tap the button below to add photos.</p>
          <div id="photo-previews" class="photo-preview-grid" aria-live="polite"></div>
        </div>
        <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png" multiple hidden>
        <button type="button" id="add-photos" class="btn btn-outline btn-small photo-add-button">
          <i class="fa-solid fa-plus" aria-hidden="true"></i> <span data-i18n="btn_add_photos">Tap to select photos</span>
        </button>
        <p id="photo-upload-error" class="field-hint" role="alert" style="color: var(--red);" hidden></p>
        <p id="photo-total-size" class="field-hint" aria-live="polite"></p>
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
        <button type="button" class="btn btn-outline" onclick="goToStep(4)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Back</span></button>
        <button type="button" class="btn btn-gold" onclick="goToStep(6)"><span data-i18n="btn_continue">Next: Contact & Privacy</span> <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 6: CONTACT & DATA PRIVACY CONSENT -->
    <div class="step-pane" id="step-pane-6" style="display: none;">
      <h2 data-i18n="step6_title"><i class="fa-solid fa-shield-halved"></i> Contact & Privacy Consent</h2>
      <p class="field-hint" data-i18n="step6_desc">We respect your privacy under Republic Act 10173 (Data Privacy Act of 2012).</p>

      <!-- Anonymous Option -->
      <div class="field" style="background: var(--mist); padding: 0.9rem; border-radius: 10px; margin-bottom: 1.25rem;">
        <label style="display: flex; align-items: center; gap: 0.6rem; cursor: pointer; font-weight: 600;">
          <input type="checkbox" name="is_anonymous" id="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }} onchange="toggleAnonymousFields(this.checked)">
          <span data-i18n="chk_anonymous">Submit this report anonymously (Hide my identity)</span>
        </label>
        <p class="field-hint" style="margin-left: 1.6rem;" data-i18n="hint_anonymous">If checked, your name, contact number, and email will not be saved with the report.</p>
      </div>

      <div id="contact-fields-section">
        <div class="field">
          <label data-i18n="lbl_preferred_contact"><i class="fa-solid fa-headset"></i> Preferred Contact Method</label>
          <div style="display: flex; gap: 1rem; flex-wrap: wrap; margin-top: 0.35rem;">
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
              <input type="radio" name="preferred_contact" value="phone" {{ old('preferred_contact', 'phone') === 'phone' ? 'checked' : '' }} onchange="handlePreferredContactChange('phone')">
              <span data-i18n="opt_phone">Phone / SMS</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
              <input type="radio" name="preferred_contact" value="email" {{ old('preferred_contact') === 'email' ? 'checked' : '' }} onchange="handlePreferredContactChange('email')">
              <span data-i18n="opt_email">Email</span>
            </label>
            <label style="display: flex; align-items: center; gap: 0.35rem; cursor: pointer;">
              <input type="radio" name="preferred_contact" value="none" {{ old('preferred_contact') === 'none' ? 'checked' : '' }} onchange="handlePreferredContactChange('none')">
              <span data-i18n="opt_none">None (No contact)</span>
            </label>
          </div>
        </div>

        <div class="form-grid">
          <div class="field">
            <label for="reporter_name"><i class="fa-solid fa-user" aria-hidden="true"></i> <span data-i18n="lbl_name">Name (optional)</span></label>
            <input type="text" id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}" maxlength="100" placeholder="Juan Dela Cruz">
          </div>
          <div class="field" id="field-wrap-contact">
            <label for="reporter_contact">
              <i class="fa-solid fa-phone" aria-hidden="true"></i> <span data-i18n="lbl_phone">Mobile Number</span>
              <span id="contact-req-indicator" style="color: var(--red);">*</span>
            </label>
            <input type="tel" id="reporter_contact" name="reporter_contact" value="{{ old('reporter_contact') }}"
              maxlength="20" placeholder="09171234567 or +639171234567">
            <p class="field-hint" data-i18n="hint_phone">Standard Philippine format. Spaces and dashes are fine.</p>
          </div>
        </div>

        <div class="field" id="field-wrap-email">
          <label for="reporter_email"><i class="fa-solid fa-envelope" aria-hidden="true"></i> <span data-i18n="lbl_email">Email Address</span></label>
          <input type="email" id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}" placeholder="name@example.com">
          <p class="field-hint" data-i18n="hint_email">Only barangay staff can view contact info for updates. Never shown publicly.</p>
        </div>
      </div>

      <!-- Data Privacy Notice Box -->
      <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 12px; padding: 1.25rem; margin-top: 1.5rem;">
        <h3 style="font-size: 0.95rem; color: var(--ink); margin-bottom: 0.4rem;" data-i18n="privacy_title">
          <i class="fa-solid fa-user-shield"></i> Data Privacy Notice (RA 10173)
        </h3>
        <p style="font-size: 0.82rem; color: var(--mut); line-height: 1.5; margin-bottom: 0.75rem;" data-i18n="privacy_text">
          By submitting this report, you acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly to investigate, route, and resolve community problems. Your information will be processed securely, protected under the Philippine Data Privacy Act, and never sold or published on public maps.
        </p>
        <label style="display: flex; align-items: flex-start; gap: 0.6rem; cursor: pointer; font-weight: 700; font-size: 0.88rem; color: var(--tx);">
          <input type="checkbox" name="consent" id="consent_checkbox" value="1" required {{ old('consent') ? 'checked' : '' }} style="margin-top: 0.2rem;">
          <span data-i18n="privacy_checkbox">I have read the Data Privacy Notice and give my voluntary consent to the processing of this report.</span> *
        </label>
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 1.5rem;">
        <button type="button" class="btn btn-outline" onclick="goToStep(5)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Back</span></button>
        <button type="button" class="btn btn-gold" onclick="validateAndGoStep(6, 7)"><span data-i18n="btn_review">Next: Review & Confirm</span> <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 7: CONFIRMATION SCREEN (SUMMARY BEFORE SUBMISSION) -->
    <div class="step-pane" id="step-pane-7" style="display: none;">
      <h2 data-i18n="step7_title"><i class="fa-solid fa-file-invoice"></i> Confirm & Review Your Report</h2>
      <p class="field-hint" data-i18n="step7_desc">Please review the details below before finally submitting your report to Barangay Calarian.</p>

      <div class="review-summary-card">
        <div class="review-row">
          <div class="review-label" data-i18n="sum_type">Report Type:</div>
          <div class="review-val" id="sum-val-type">Non-Emergency</div>
        </div>
        <div class="review-row">
          <div class="review-label" data-i18n="sum_category">Category:</div>
          <div class="review-val" id="sum-val-category">-</div>
        </div>
        <div class="review-row" id="sum-row-specifics">
          <div class="review-label" data-i18n="sum_answers">Specifics:</div>
          <div class="review-val" id="sum-val-answers">None</div>
        </div>
        <div class="review-row">
          <div class="review-label" data-i18n="sum_description">Description:</div>
          <div class="review-val" id="sum-val-description">-</div>
        </div>
        <div class="review-row">
          <div class="review-label" data-i18n="sum_situation">Situation:</div>
          <div class="review-val" id="sum-val-situation">-</div>
        </div>
        <div class="review-row">
          <div class="review-label" data-i18n="sum_location">Location:</div>
          <div class="review-val" id="sum-val-location">-</div>
        </div>
        <div class="review-row">
          <div class="review-label" data-i18n="sum_photos">Photos:</div>
          <div class="review-val" id="sum-val-photos">0 attached</div>
        </div>
        <div class="review-row">
          <div class="review-label" data-i18n="sum_contact">Reporter:</div>
          <div class="review-val" id="sum-val-contact">-</div>
        </div>
        <div class="review-row">
          <div class="review-label" data-i18n="sum_privacy">Privacy Consent:</div>
          <div class="review-val" style="color: #107c41; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Agreed & Verified</div>
        </div>
      </div>

      <div style="display: flex; justify-content: space-between; margin-top: 1.5rem; gap: 0.75rem; flex-wrap: wrap;">
        <button type="button" class="btn btn-outline" onclick="goToStep(6)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_edit">Edit Report</span></button>
        <button type="submit" id="btn-final-submit" class="btn btn-gold" style="font-size: 1.05rem; padding: 0.85rem 1.75rem;">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> <span data-i18n="btn_submit_final">Confirm & Submit Report</span>
        </button>
      </div>
    </div>
  </form>
</section>

<!-- Categories Data JSON for Dynamic Schema & Questions -->
<script id="categories-data" type="application/json">
  {!! json_encode($categories->map(fn($c) => [
    'id' => $c->id,
    'name' => $c->name,
    'slug' => $c->slug,
    'question_schema' => $c->question_schema ?? []
  ])) !!}
</script>

<script>
// --- Multi-Language Dictionary (English / Filipino) ---
const i18n = {
  en: {
    hero_title: "Report a problem in Barangay Calarian",
    hero_desc: "Send a report with category details, photo, and location. Barangay staff will review it, route it to the proper office, and keep you updated with a tracking code.",
    hero_btn_report: "Report a problem",
    hero_btn_track: "Track a report",
    step1: "Type", step2: "Category", step3: "Situation", step4: "Location", step5: "Photos", step6: "Contact", step7: "Review",
    emg_title: "Emergency Detected",
    emg_subtitle: "For immediate life-threatening danger, ongoing crimes, or active fires, call official hotlines directly.",
    emg_not_monitored: "THIS ONLINE SYSTEM IS NOT MONITORED IN REAL-TIME.",
    emg_call_prompt: "Please stop filling out this form and contact emergency services immediately using the numbers below:",
    emg_back_btn: "Return to Non-Emergency Report",
    step1_title: "What type of issue are you reporting?",
    step1_desc: "Choose whether your report is an urgent emergency requiring dispatch or a community maintenance concern.",
    type_emergency_title: "Emergency / Crime",
    type_emergency_desc: "Immediate danger, active fire, ongoing crime, severe accident, or medical threat. Shows 911 contacts immediately.",
    type_non_emergency_title: "Community Concern",
    type_non_emergency_desc: "Drainage issues, garbage collection, streetlights, potholes, or barangay maintenance.",
    step2_title: "Select Category & Describe",
    step2_desc: "Pick the category that best describes the problem. Specific questions will appear below.",
    specific_questions_title: "Specific Questions",
    lbl_description: "What is the problem? Describe the issue in detail",
    hint_description: "You may write in English, Filipino, Bisaya, or Chavacano (minimum 10 characters).",
    step3_title: "Situation Assessment",
    step3_desc: "These extra details help our barangay team prioritize response time and safety hazards.",
    q_ongoing: "Is this happening right now?",
    q_ongoing_desc: "Active overflowing, ongoing commotion, or currently live issue.",
    q_risk: "Is anyone currently at risk or in danger?",
    q_risk_desc: "Risk of electrocution, open deep pits, slipping, or health hazards.",
    q_recurring: "Is this a recurring or one-time problem?",
    q_recurring_desc: "Has this been happening repeatedly over weeks or months?",
    btn_yes: "Yes", btn_no: "No", btn_recurring: "Recurring", btn_onetime: "One-time",
    step4_title: "Where is the problem located?",
    step4_desc: "Tap on the map, choose a purok, or type a landmark. At least one location method is required.",
    btn_use_location: "Use my current location",
    nearby_title: "Is this the same problem?",
    nearby_desc: "We found reports near this pin. If your issue is already listed, barangay staff are already aware. You can still submit a new report if yours is different.",
    lbl_purok: "Purok (optional)", opt_select_purok: "Select purok",
    lbl_landmark: "Landmark or Street (optional)",
    step5_title: "Attach Photos (Optional)",
    step5_desc: "Photos help staff assess the problem quickly and bring the right equipment.",
    photo_guidance_head: "Helpful Photo Tips:",
    photo_tip1: "Capture the problem clearly alongside a recognizable landmark (building, sign, post).",
    photo_tip2: "Privacy notice: Avoid capturing recognizable human faces or vehicle license plates.",
    photo_tip3: "Images are automatically optimized on your device for low data usage.",
    photo_empty: "No photos selected yet. Tap the button below to add photos.",
    btn_add_photos: "Tap to select photos",
    step6_title: "Contact & Privacy Consent",
    step6_desc: "We respect your privacy under Republic Act 10173 (Data Privacy Act of 2012).",
    chk_anonymous: "Submit this report anonymously (Hide my identity)",
    hint_anonymous: "If checked, your name, contact number, and email will not be saved with the report.",
    lbl_preferred_contact: "Preferred Contact Method",
    opt_phone: "Phone / SMS", opt_email: "Email", opt_none: "None (No contact)",
    lbl_name: "Name (optional)", lbl_phone: "Mobile Number", hint_phone: "Standard Philippine format. Spaces and dashes are fine.",
    lbl_email: "Email Address", hint_email: "Only barangay staff can view contact info for updates. Never shown publicly.",
    privacy_title: "Data Privacy Notice (RA 10173)",
    privacy_text: "By submitting this report, you acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly to investigate, route, and resolve community problems. Your information will be processed securely, protected under the Philippine Data Privacy Act, and never sold or published on public maps.",
    privacy_checkbox: "I have read the Data Privacy Notice and give my voluntary consent to the processing of this report.",
    step7_title: "Confirm & Review Your Report",
    step7_desc: "Please review the details below before finally submitting your report to Barangay Calarian.",
    sum_type: "Report Type:", sum_category: "Category:", sum_answers: "Specifics:", sum_description: "Description:",
    sum_situation: "Situation:", sum_location: "Location:", sum_photos: "Photos:", sum_contact: "Reporter:", sum_privacy: "Privacy Consent:",
    btn_back: "Back", btn_continue: "Continue", btn_review: "Next: Review & Confirm", btn_edit: "Edit Report", btn_submit_final: "Confirm & Submit Report",
    form_heading: "Resident Incident Report", form_subheading: "Barangay Calarian Community Reporting"
  },
  fil: {
    hero_title: "Mag-ulat ng problema sa Barangay Calarian",
    hero_desc: "Magsumite ng ulat na may kategorya, larawan, at lokasyon. Susuriin ito ng mga kawani ng barangay, ipapadala sa tamang tanggapan, at bibigyan ka ng tracking code.",
    hero_btn_report: "Mag-ulat ng problema",
    hero_btn_track: "Subaybayan ang ulat",
    step1: "Uri", step2: "Kategorya", step3: "Sitwasyon", step4: "Lokasyon", step5: "Larawan", step6: "Kontak", step7: "Suriin",
    emg_title: "Pang-emergency na Alerto",
    emg_subtitle: "Para sa banta sa buhay, sunog, o kasalukuyang krimen, tumawag agad sa opisyal na hotlines.",
    emg_not_monitored: "ANG ONLINE SYSTEM NA ITO AY HINDI MINUMONITOR NANG REAL-TIME.",
    emg_call_prompt: "Huwag nang ipagpatuloy ang form na ito at tumawag kaagad sa mga numero sa ibaba:",
    emg_back_btn: "Bumalik sa Karaniwang Ulat",
    step1_title: "Anong uri ng problema ang iyong iuulat?",
    step1_desc: "Pumili kung ito ay kagyat na emergency o karaniwang suliranin sa komunidad.",
    type_emergency_title: "Emergency / Krimen",
    type_emergency_desc: "Panganib sa buhay, sunog, krimen, matinding aksidente, o banta sa kalusugan. Direktang ipapakita ang 911 hotlines.",
    type_non_emergency_title: "Karaniwang Suliranin",
    type_non_emergency_desc: "Baradong kanal, basura, pundidong ilaw, sira sa kalsada, o pagpapanatili ng barangay.",
    step2_title: "Pumili ng Kategorya at Ilarawan",
    step2_desc: "Piliin ang kategoryang angkop sa problema. May mga tiyak na katanungang lalabas sa ibaba.",
    specific_questions_title: "Mga Tiyak na Tanong",
    lbl_description: "Ano ang problema? Ilarawan nang buo ang suliranin",
    hint_description: "Maaari kang sumulat sa Tagalog/Filipino, English, Bisaya, o Chavacano (hindi bababa sa 10 titik).",
    step3_title: "Pagsusuri sa Sitwasyon",
    step3_desc: "Ang mga karagdagang sagot na ito ay makatutulong sa barangay na malaman ang prayoridad at kaligtasan.",
    q_ongoing: "Kasalukuyan ba itong nangyayari ngayon?",
    q_ongoing_desc: "Umaapaw pa, kasalukuyang gulo, o aktibong nagaganap na problema.",
    q_risk: "Mayroon bang tao na nasa panganib ngayon?",
    q_risk_desc: "Panganib ng kuryente, malalim na hukay, dulas, o banta sa kalusugan.",
    q_recurring: "Ito ba ay paulit-ulit o minsan lang nangyari?",
    q_recurring_desc: "Nangyayari na ba ito nang paulit-ulit sa nagdaang mga linggo o buwan?",
    btn_yes: "Oo", btn_no: "Hindi", btn_recurring: "Paulit-ulit", btn_onetime: "Minsan lang",
    step4_title: "Saan matatagpuan ang problema?",
    step4_desc: "Pumindot sa mapa, pumili ng purok, o maglagay ng landmark. Kinakailangan ang kahit isang lokasyon.",
    btn_use_location: "Gamitin ang aking kasalukuyang lokasyon",
    nearby_title: "Ito ba ang parehong problema?",
    nearby_desc: "May nakitang mga ulat malapit sa pin na ito. Kung nailista na ang iyong problema, inaaksyunan na ito ng barangay. Maaari ka pa ring magsumite kung iba ang iyong ulat.",
    lbl_purok: "Purok (opsyonal)", opt_select_purok: "Pumili ng purok",
    lbl_landmark: "Landmark o Kalye (opsyonal)",
    step5_title: "Maglakip ng Larawan (Opsyonal)",
    step5_desc: "Makatutulong ang larawan upang madaling maihanda ng mga kawani ang tamang kagamitan.",
    photo_guidance_head: "Gabay sa Pagkuha ng Larawan:",
    photo_tip1: "Kunan ang problema nang malinaw kasama ang isang kilalang landmark (gusali, karatula, poste).",
    photo_tip2: "Paunawa sa Privacy: Iwasang makunan ang mukha ng mga tao o plaka ng mga sasakyan.",
    photo_tip3: "Awtomatikong pinapaliit ang larawan upang hindi maubos ang iyong mobile data.",
    photo_empty: "Wala pang napiling larawan. Pindutin ang buton sa ibaba upang magdagdag.",
    btn_add_photos: "Pindutin para magdagdag ng larawan",
    step6_title: "Kontak at Pahintulot sa Privacy",
    step6_desc: "Iginagalang namin ang iyong privacy alinsunod sa Republic Act 10173 (Data Privacy Act of 2012).",
    chk_anonymous: "Isumite ang ulat nang hindi nagpapakilala (Anonymous)",
    hint_anonymous: "Kung pipiliin ito, hindi itatala ang iyong pangalan, numero, at email sa ulat.",
    lbl_preferred_contact: "Gustong Paraan ng Pakikipag-ugnayan",
    opt_phone: "Telepono / SMS", opt_email: "Email", opt_none: "Wala (Walang kontak)",
    lbl_name: "Pangalan (opsyonal)", lbl_phone: "Numero ng Telepono", hint_phone: "Karaniwang format sa Pilipinas (hal. 09171234567).",
    lbl_email: "Email Address", hint_email: "Tanging mga kawani lamang ng barangay ang makakakita nito para sa updates.",
    privacy_title: "Paunawa sa Data Privacy (RA 10173)",
    privacy_text: "Sa pagsusumite ng ulat na ito, kinikilala mo na ang Barangay Calarian ay nangongolekta ng detalye ng suliranin, lokasyon, at opsyonal na impormasyon sa pakikipag-ugnayan upang imbestigahan at lutasin ang mga suliranin. Ang iyong impormasyon ay protektado sa ilalim ng Philippine Data Privacy Act at hinding-hindi ibebenta o ilalathala sa publiko.",
    privacy_checkbox: "Nababasa at sinasang-ayunan ko ang Data Privacy Notice at nagbibigay ako ng pahintulot sa pagproseso ng ulat na ito.",
    step7_title: "Suriin at Kumpirmahin ang Iyong Ulat",
    step7_desc: "Pakisuri ang mga detalye bago tuluyang isumite ang ulat sa Barangay Calarian.",
    sum_type: "Uri ng Ulat:", sum_category: "Kategorya:", sum_answers: "Tiyak na Sagot:", sum_description: "Deskripsyon:",
    sum_situation: "Sitwasyon:", sum_location: "Lokasyon:", sum_photos: "Mga Larawan:", sum_contact: "Nag-ulat:", sum_privacy: "Pahintulot sa Privacy:",
    btn_back: "Bumalik", btn_continue: "Magpatuloy", btn_review: "Suriin at Kumpirmahin", btn_edit: "Baguhin ang Ulat", btn_submit_final: "Kumpirmahin at Isumite ang Ulat",
    form_heading: "Ulat ng Residente sa Insidente", form_subheading: "Sistema ng Pag-uulat sa Barangay Calarian"
  }
};

let currentLang = 'en';

function setLanguage(lang) {
  currentLang = lang;
  document.querySelectorAll('.js-lang-btn-en').forEach(btn => btn.classList.toggle('active', lang === 'en'));
  document.querySelectorAll('.js-lang-btn-fil').forEach(btn => btn.classList.toggle('active', lang === 'fil'));

  document.querySelectorAll('[data-i18n]').forEach(el => {
    const key = el.getAttribute('data-i18n');
    if (i18n[lang] && i18n[lang][key]) {
      el.textContent = i18n[lang][key];
    }
  });

  try { localStorage.setItem('calarian_lang', lang); } catch (e) {}
}

// --- Step Management ---
let currentStep = 1;
const totalSteps = 7;

function goToStep(step) {
  for (let i = 1; i <= totalSteps; i++) {
    const pane = document.getElementById(`step-pane-${i}`);
    if (pane) pane.style.display = (i === step) ? 'block' : 'none';
  }

  document.querySelectorAll('#stepper .step-item').forEach(item => {
    const s = parseInt(item.getAttribute('data-step'), 10);
    item.classList.toggle('active', s === step);
    item.classList.toggle('done', s < step);
  });

  currentStep = step;
  if (step === 4 && window.leafletMapInstance) {
    setTimeout(() => { window.leafletMapInstance.invalidateSize(); }, 200);
  }
  if (step === 7) {
    populateReviewSummary();
  }
  document.getElementById('report-form').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function validateAndGoStep(fromStep, toStep) {
  if (fromStep === 2) {
    const desc = document.getElementById('description').value.trim();
    if (desc.length < 10) {
      alert(currentLang === 'fil' ? 'Pakiusap ilarawan ang problema nang hindi bababa sa 10 titik.' : 'Please describe the problem with at least 10 characters.');
      document.getElementById('description').focus();
      return;
    }
  }
  if (fromStep === 4) {
    const lat = document.getElementById('lat').value;
    const purok = document.getElementById('purok_id').value;
    const landmark = document.getElementById('landmark').value.trim();
    if (!lat && !purok && !landmark) {
      alert(currentLang === 'fil' ? 'Pumili ng lokasyon sa mapa, pumili ng purok, o maglagay ng landmark.' : 'Please place a pin on the map, select a purok, or enter a landmark.');
      return;
    }
  }
  if (fromStep === 6) {
    const consent = document.getElementById('consent_checkbox');
    if (!consent.checked) {
      alert(currentLang === 'fil' ? 'Kailangan mong sumang-ayon sa Data Privacy Notice upang magpatuloy.' : 'You must agree to the Data Privacy Notice to submit.');
      consent.focus();
      return;
    }
    const isAnon = document.getElementById('is_anonymous').checked;
    if (!isAnon) {
      const prefContact = document.querySelector('input[name="preferred_contact"]:checked')?.value;
      if (prefContact === 'phone') {
        const phone = document.getElementById('reporter_contact').value.trim();
        const phoneRegex = /^(?:0[ -]*9|\+[ -]*6[ -]*3[ -]*9)(?:[ -]*\d){9}$/;
        if (!phoneRegex.test(phone)) {
          alert(currentLang === 'fil' ? 'Maglagay ng wastong numero sa Pilipinas (hal. 09171234567).' : 'Please enter a valid Philippine mobile number (e.g. 09171234567).');
          document.getElementById('reporter_contact').focus();
          return;
        }
      } else if (prefContact === 'email') {
        const email = document.getElementById('reporter_email').value.trim();
        if (!email || !email.includes('@')) {
          alert(currentLang === 'fil' ? 'Maglagay ng wastong email address.' : 'Please enter a valid email address.');
          document.getElementById('reporter_email').focus();
          return;
        }
      }
    }
  }

  goToStep(toStep);
}

// --- Report Type Handler ---
function selectReportType(type) {
  document.getElementById('report_type_input').value = type;
  if (type === 'emergency') {
    document.getElementById('emergency-screen').style.display = 'block';
    document.getElementById('emergency-screen').scrollIntoView({ behavior: 'smooth', block: 'start' });
  } else {
    document.getElementById('emergency-screen').style.display = 'none';
    document.getElementById('card-non-emergency').classList.add('selected');
  }
}

// --- Categories and Dynamic Schema Questions ---
let categoriesData = [];
try {
  categoriesData = JSON.parse(document.getElementById('categories-data').textContent);
} catch (e) {
  console.error("Failed to parse categories data", e);
}

function selectCategory(catId) {
  document.getElementById('category_id_input').value = catId;
  document.querySelectorAll('.category-option-card').forEach(card => {
    card.classList.toggle('selected', parseInt(card.getAttribute('data-cat-id'), 10) === catId);
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
      label.style.fontWeight = '600';
      label.style.fontSize = '0.9rem';
      label.innerHTML = `${q.label} ${q.required ? '<span style="color:var(--red);">*</span>' : ''}`;
      qDiv.appendChild(label);

      if (q.type === 'select' && Array.isArray(q.options)) {
        const sel = document.createElement('select');
        sel.name = `answers[${q.key}]`;
        if (q.required) sel.required = true;
        const optDefault = document.createElement('option');
        optDefault.value = '';
        optDefault.textContent = currentLang === 'fil' ? '-- Pumili --' : '-- Select --';
        sel.appendChild(optDefault);
        q.options.forEach(opt => {
          const o = document.createElement('option');
          o.value = opt;
          o.textContent = opt;
          sel.appendChild(o);
        });
        qDiv.appendChild(sel);
      } else if (q.type === 'boolean') {
        const wrap = document.createElement('div');
        wrap.style.display = 'flex';
        wrap.style.gap = '1rem';
        wrap.style.marginTop = '0.35rem';
        wrap.innerHTML = `
          <label style="cursor:pointer; display:flex; align-items:center; gap:0.35rem;">
            <input type="radio" name="answers[${q.key}]" value="1" ${q.required ? 'required' : ''}> Yes / Oo
          </label>
          <label style="cursor:pointer; display:flex; align-items:center; gap:0.35rem;">
            <input type="radio" name="answers[${q.key}]" value="0"> No / Hindi
          </label>
        `;
        qDiv.appendChild(wrap);
      } else if (q.type === 'textarea') {
        const ta = document.createElement('textarea');
        ta.name = `answers[${q.key}]`;
        ta.rows = 2;
        if (q.required) ta.required = true;
        qDiv.appendChild(ta);
      } else if (q.type === 'number') {
        const inp = document.createElement('input');
        inp.type = 'number';
        inp.name = `answers[${q.key}]`;
        if (q.required) inp.required = true;
        qDiv.appendChild(inp);
      } else {
        const inp = document.createElement('input');
        inp.type = 'text';
        inp.name = `answers[${q.key}]`;
        if (q.required) inp.required = true;
        qDiv.appendChild(inp);
      }

      fields.appendChild(qDiv);
    });
    container.style.display = 'block';
  } else {
    container.style.display = 'none';
  }

  // Trigger nearby reports check if location coordinates exist
  triggerNearbyReportsCheck();
}

// Auto-select first or previously selected category on load
document.addEventListener('DOMContentLoaded', () => {
  const selectedCatId = parseInt(document.getElementById('category_id_input').value, 10);
  if (selectedCatId) {
    selectCategory(selectedCatId);
  } else if (categoriesData.length > 0) {
    selectCategory(categoriesData[0].id);
  }
  try {
    const saved = localStorage.getItem('calarian_lang');
    if (saved && (saved === 'en' || saved === 'fil')) setLanguage(saved);
  } catch (e) {}
});

// --- Situation Binary Toggles ---
function setBinaryField(fieldId, val) {
  document.getElementById(fieldId).value = val;
  const parent = document.getElementById(fieldId).parentElement;
  parent.querySelectorAll('.toggle-btn').forEach((btn, idx) => {
    btn.classList.toggle('active', (val === 1 && idx === 0) || (val === 0 && idx === 1));
  });
}

// --- Contact and Anonymous Handling ---
function toggleAnonymousFields(isAnon) {
  const section = document.getElementById('contact-fields-section');
  if (isAnon) {
    section.style.opacity = '0.4';
    section.style.pointerEvents = 'none';
    document.getElementById('reporter_name').value = '';
    document.getElementById('reporter_contact').value = '';
    document.getElementById('reporter_email').value = '';
  } else {
    section.style.opacity = '1';
    section.style.pointerEvents = 'auto';
  }
}

function handlePreferredContactChange(pref) {
  const contactReq = document.getElementById('contact-req-indicator');
  if (pref === 'phone') {
    contactReq.style.display = 'inline';
    document.getElementById('reporter_contact').setAttribute('required', 'required');
  } else {
    contactReq.style.display = 'none';
    document.getElementById('reporter_contact').removeAttribute('required');
  }
}

// --- Nearby Reports API Check ---
async function triggerNearbyReportsCheck() {
  const lat = document.getElementById('lat').value;
  const lng = document.getElementById('lng').value;
  const catId = document.getElementById('category_id_input').value;
  if (!lat || !lng) return;

  try {
    const res = await fetch(`/api/nearby-reports?lat=${lat}&lng=${lng}&category_id=${catId}`);
    if (!res.ok) return;
    const reports = await res.json();
    const box = document.getElementById('nearby-reports-container');
    const list = document.getElementById('nearby-reports-list');
    list.innerHTML = '';

    if (Array.isArray(reports) && reports.length > 0) {
      reports.forEach(r => {
        const item = document.createElement('div');
        item.className = 'nearby-report-item';
        item.innerHTML = `
          <span><b>${r.tracking_code}</b>: ${r.category} (${r.status})</span>
          <span style="color:var(--mut); font-size:0.8rem;">~${r.distance_m}m away (${r.created_at})</span>
        `;
        list.appendChild(item);
      });
      box.style.display = 'block';
    } else {
      box.style.display = 'none';
    }
  } catch (e) {
    console.debug('Nearby reports check error:', e);
  }
}

// --- Client-Side Photo Optimization (Canvas Compression) ---
let selectedPhotosList = [];
const photosInput = document.getElementById('photos');
const addPhotosBtn = document.getElementById('add-photos');
const photoPreviews = document.getElementById('photo-previews');
const photoEmptyState = document.getElementById('photo-empty-state');
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
  if (file.size < 400 * 1024) return file; // Skip small images

  return new Promise(resolve => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = ev => {
      const img = new Image();
      img.src = ev.target.result;
      img.onload = () => {
        let width = img.width;
        let height = img.height;
        if (width > height) {
          if (width > maxWidth) {
            height = Math.round((height * maxWidth) / width);
            width = maxWidth;
          }
        } else {
          if (height > maxHeight) {
            width = Math.round((width * maxHeight) / height);
            height = maxHeight;
          }
        }

        const canvas = document.createElement('canvas');
        canvas.width = width;
        canvas.height = height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0, width, height);

        canvas.toBlob(blob => {
          if (blob && blob.size < file.size) {
            const compressed = new File([blob], file.name.replace(/\.[^/.]+$/, "") + ".jpg", {
              type: 'image/jpeg',
              lastModified: Date.now()
            });
            resolve(compressed);
          } else {
            resolve(file);
          }
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
  photoEmptyState.style.display = selectedPhotosList.length > 0 ? 'none' : 'block';

  let totalBytes = 0;
  selectedPhotosList.forEach((photo, idx) => {
    totalBytes += photo.size;
    const url = URL.createObjectURL(photo);
    const item = document.createElement('div');
    item.className = 'photo-preview-item';
    item.innerHTML = `
      <img src="${url}" alt="Preview" style="width:72px; height:72px; object-fit:cover; border-radius:8px;">
      <button type="button" class="photo-preview-remove" title="Remove" onclick="removePhoto(${idx})">
        <i class="fa-solid fa-xmark"></i>
      </button>
    `;
    photoPreviews.appendChild(item);
  });

  const totalMb = (totalBytes / (1024 * 1024)).toFixed(2);
  photoTotalSize.textContent = selectedPhotosList.length > 0 ? `${selectedPhotosList.length} photo(s) (${totalMb} MB total)` : '';
  if (totalBytes > 5 * 1024 * 1024) {
    photoUploadError.textContent = 'Combined size exceeds 5 MB. Please remove some photos.';
    photoUploadError.hidden = false;
  } else {
    photoUploadError.hidden = true;
  }
}

function removePhoto(index) {
  selectedPhotosList.splice(index, 1);
  renderPhotoPreviews();
}

// --- Summary & Review Screen ---
function populateReviewSummary() {
  document.getElementById('sum-val-type').textContent = document.getElementById('report_type_input').value === 'emergency' ? 'Emergency' : 'Community Concern / Non-Emergency';

  const catId = parseInt(document.getElementById('category_id_input').value, 10);
  const cat = categoriesData.find(c => c.id === catId);
  document.getElementById('sum-val-category').textContent = cat ? cat.name : 'None';

  // Specific questions answers
  const answers = [];
  const answerInputs = document.querySelectorAll('#dynamic-questions-fields input, #dynamic-questions-fields select, #dynamic-questions-fields textarea');
  answerInputs.forEach(inp => {
    if ((inp.type === 'radio' && !inp.checked) || !inp.name) return;
    const val = inp.value;
    if (val) {
      const cleanKey = inp.name.replace('answers[', '').replace(']', '');
      answers.push(`${cleanKey}: ${val === '1' ? 'Yes' : (val === '0' ? 'No' : val)}`);
    }
  });
  document.getElementById('sum-val-answers').textContent = answers.length > 0 ? answers.join(', ') : 'None';

  document.getElementById('sum-val-description').textContent = document.getElementById('description').value || '-';

  const isOngoing = document.getElementById('is_ongoing').value === '1';
  const isRisk = document.getElementById('people_at_risk').value === '1';
  const isRecurring = document.getElementById('is_recurring').value === '1';
  document.getElementById('sum-val-situation').textContent =
    `Ongoing: ${isOngoing ? 'Yes' : 'No'} | At Risk: ${isRisk ? 'Yes' : 'No'} | ${isRecurring ? 'Recurring' : 'One-time'}`;

  const lat = document.getElementById('lat').value;
  const lng = document.getElementById('lng').value;
  const purokSelect = document.getElementById('purok_id');
  const purokText = purokSelect.options[purokSelect.selectedIndex]?.text || '';
  const landmarkText = document.getElementById('landmark').value || '';
  let locSummary = [];
  if (purokSelect.value) locSummary.push(purokText);
  if (landmarkText) locSummary.push(`Near: ${landmarkText}`);
  if (lat && lng) locSummary.push(`Pin: (${parseFloat(lat).toFixed(4)}, ${parseFloat(lng).toFixed(4)})`);
  document.getElementById('sum-val-location').textContent = locSummary.length > 0 ? locSummary.join(' · ') : 'Not specified';

  document.getElementById('sum-val-photos').textContent = `${selectedPhotosList.length} attached`;

  const isAnon = document.getElementById('is_anonymous').checked;
  if (isAnon) {
    document.getElementById('sum-val-contact').textContent = 'Anonymous';
  } else {
    const name = document.getElementById('reporter_name').value || 'Anonymous';
    const contact = document.getElementById('reporter_contact').value || '';
    const email = document.getElementById('reporter_email').value || '';
    document.getElementById('sum-val-contact').textContent = `${name} ${contact ? '· ' + contact : ''} ${email ? '· ' + email : ''}`;
  }
}
</script>
@endsection
