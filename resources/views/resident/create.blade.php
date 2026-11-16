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
<div class="card form-card" style="margin-bottom: 1rem;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.75rem;">
    <div style="display:flex;align-items:center;gap:.75rem;">
      <x-brand-seal />
      <div>
        <h1 style="margin:0;font-size:1.25rem;" data-i18n="form_heading">Report a problem in Barangay Calarian</h1>
        <small style="color:var(--mut);" data-i18n="form_subheading">Barangay Calarian Community Reporting</small>
      </div>
    </div>
    <div style="display:inline-flex;align-items:center;gap:.4rem;background:#f8fafc;padding:.35rem .65rem;border-radius:9999px;border:1.5px solid #cbd5e1;">
      <span style="font-size:.88rem;font-weight:700;color:#0f172a;display:inline-flex;align-items:center;gap:.35rem;">
        <i class="fa-solid fa-language" style="color:#2456d6;font-size:1.1rem;"></i> Language / Wika:
      </span>
      <button type="button" class="lang-toggle-btn js-lang-btn-en active" onclick="setLanguage('en')">English</button>
      <button type="button" class="lang-toggle-btn js-lang-btn-fil" onclick="setLanguage('fil')">Filipino</button>
    </div>
  </div>
</div>

<!-- Emergency strip -->
<div class="card" style="background:#fee2e2;border:1px solid #fecaca;padding:.6rem .85rem;border-radius:10px;display:flex;justify-content:space-between;align-items:center;gap:.75rem;margin-bottom:1rem;">
  <span style="color:#b91c1c;font-weight:700;" data-i18n="emg_strip">Emergency? Call 911</span>
  <button type="button" class="btn btn-small" style="background:#dc2626;color:#fff;" onclick="showEmergency()" data-i18n="emg_btn">Emergency / Crime</button>
</div>

<!-- Emergency screen -->
<section id="emergency-screen" class="card" style="display:none;">
  <div style="display:flex;align-items:center;gap:.75rem;color:var(--red);margin-bottom:1rem;">
    <i class="fa-solid fa-triangle-exclamation" style="font-size:2rem;"></i>
    <div>
      <h2 style="color:var(--red);margin:0;" data-i18n="emg_title">Emergency Detected</h2>
      <p style="margin:.2rem 0 0;" data-i18n="emg_subtitle">For immediate life-threatening danger, call official hotlines directly.</p>
    </div>
  </div>
  <div class="card alert" style="margin-top:1rem;">
    <p style="font-weight:700;color:var(--red);" data-i18n="emg_not_monitored"><i class="fa-solid fa-bell-slash"></i> THIS ONLINE SYSTEM IS NOT MONITORED IN REAL-TIME.</p>
    <p style="margin:.25rem 0 0;" data-i18n="emg_call_prompt">Please contact emergency services immediately using the numbers below:</p>
  </div>
  <div class="emergency-hotline-grid" style="margin-top:1rem;">
    <a href="tel:911" class="hotline-card" style="border-color:var(--red);background:#fee2e2;">
      <span><i class="fa-solid fa-phone-volume"></i> National Emergency Hotline</span>
      <span style="color:var(--red);font-size:1.2rem;">911</span>
    </a>
    @foreach($contacts as $contact)
      @php $num = trim((string) $contact->number); @endphp
      @if($num !== '' && stripos($num, 'N/A') === false && $num !== '911')
        <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}" class="hotline-card">
          <span><i class="fa-solid fa-phone"></i> {{ $contact->name }}</span>
          <span>{{ $num }}</span>
        </a>
      @endif
    @endforeach
  </div>
  <div style="margin-top:1.5rem;text-align:center;">
    <button type="button" class="btn btn-outline" onclick="hideEmergency()" data-i18n="emg_back_btn">Return to report</button>
  </div>
</section>

<section id="report-form" class="card form-card">
  @if($errors->any())
    <div role="alert" class="form-errors" style="margin-bottom:1.25rem;">
      <ul style="margin:0;padding-left:1.2rem;">
        @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
      </ul>
    </div>
  @endif

  <!-- Stepper -->
  <div id="stepper" style="display:flex;gap:.5rem;align-items:center;margin-bottom:1rem;flex-wrap:wrap;">
    <div class="step-progress-mobile" style="display:none;width:100%;">
      <span id="step-counter" data-i18n="step_counter">Step 1 of 4</span>
      <div style="height:6px;background:#e2e8f0;border-radius:999px;margin-top:.35rem;"><div id="step-progress-bar" style="height:6px;width:25%;background:var(--blue);border-radius:999px;"></div></div>
    </div>
    <div class="step-item active" data-step="1"><span class="step-num">1</span> <span data-i18n="step1">Problem</span></div>
    <div class="step-item" data-step="2"><span class="step-num">2</span> <span data-i18n="step2">Location</span></div>
    <div class="step-item" data-step="3"><span class="step-num">3</span> <span data-i18n="step3">Photo</span></div>
    <div class="step-item" data-step="4"><span class="step-num">4</span> <span data-i18n="step4">Review</span></div>
  </div>
  <style>
    @media (max-width: 640px) {
      #stepper .step-item { display: none; }
      #stepper .step-progress-mobile { display: block !important; }
    }
  </style>

  <form id="multi-step-form" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
    @csrf
    <input type="hidden" name="has_consent_field" value="1">
    <input type="hidden" name="report_type" id="report_type_input" value="non_emergency">
    <input type="hidden" name="category_id" id="category_id_input" value="{{ old('category_id') }}">
    <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
    <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
    <input type="hidden" name="is_ongoing" id="is_ongoing" value="{{ old('is_ongoing', 0) ? 1 : 0 }}">
    <input type="hidden" name="is_recurring" id="is_recurring" value="{{ old('is_recurring', 0) ? 1 : 0 }}">
    <input type="hidden" name="people_at_risk" id="people_at_risk" value="{{ old('people_at_risk', 0) ? 1 : 0 }}">

    <!-- STEP 1: PROBLEM -->
    <div class="step-pane" id="step-pane-1">
      <h2 data-i18n="step1_title"><i class="fa-solid fa-tags"></i> Problem</h2>
      <p class="field-hint" data-i18n="step1_desc">Pick the category and describe the issue.</p>

      <div class="category-picker-grid">
        @foreach($categories as $category)
          <div class="category-option-card {{ old('category_id') == $category->id ? 'selected' : '' }}"
               data-cat-id="{{ $category->id }}" data-cat-slug="{{ $category->slug }}"
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
            <strong style="font-size:.9rem;">{{ $category->name }}</strong>
          </div>
        @endforeach
      </div>

      <div id="dynamic-questions-container" style="display:none;background:#f8faff;border:1px solid #dbeafe;border-radius:12px;padding:1.25rem;margin-bottom:1.25rem;">
        <h3 style="font-size:1rem;color:var(--blue);margin-bottom:.75rem;" data-i18n="specific_questions_title"><i class="fa-solid fa-circle-info"></i> Specific Questions</h3>
        <div id="dynamic-questions-fields"></div>
      </div>

      <div class="field">
        <label for="description"><i class="fa-solid fa-align-left"></i> <span data-i18n="lbl_description">What is the problem?</span> *</label>
        <textarea id="description" name="description" rows="4" required minlength="10" maxlength="2000">{{ old('description') }}</textarea>
        <p class="field-hint" data-i18n="hint_description">You may write in English, Filipino, Bisaya, or Chavacano (minimum 10 characters).</p>
      </div>

      <div class="field">
        <label data-i18n="q_risk">Is anyone in danger right now?</label>
        <div class="toggle-btns" style="margin-top:.35rem;">
          <button type="button" class="toggle-btn" data-safety-btn="yes" onclick="setSafety('yes')" data-i18n="btn_yes">Yes</button>
          <button type="button" class="toggle-btn" data-safety-btn="no" onclick="setSafety('no')" data-i18n="btn_no">No</button>
          <button type="button" class="toggle-btn" data-safety-btn="unsure" onclick="setSafety('unsure')" data-i18n="btn_unsure">Not sure</button>
        </div>
      </div>
      <div id="inline-emergency" style="display:none;background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:.75rem;margin-top:.75rem;">
        <p style="color:#b91c1c;font-weight:700;margin:0 0 .5rem;" data-i18n="emg_inline">Emergency contacts</p>
        <a href="tel:911" class="hotline-card" style="border-color:var(--red);background:#fee2e2;display:block;margin-bottom:.5rem;">911</a>
        @foreach($contacts as $contact)
          @php $num = trim((string)$contact->number); @endphp
          @if($num !== '' && stripos($num,'N/A') === false && $num !== '911')
            <a href="tel:{{ preg_replace('/[^0-9+]/','',$num) }}" class="hotline-card" style="display:block;margin-bottom:.5rem;">{{ $contact->name }} — {{ $num }}</a>
          @endif
        @endforeach
      </div>

      <details style="margin-top:1rem;">
        <summary data-i18n="more_details">More details (optional)</summary>
        <div style="margin-top:.75rem;">
          <div class="toggle-row">
            <strong data-i18n="q_ongoing">Is this happening right now?</strong>
            <div class="toggle-btns">
              <button type="button" class="toggle-btn {{ old('is_ongoing') ? 'active' : '' }}" onclick="setBinaryField('is_ongoing',1)" data-i18n="btn_yes">Yes</button>
              <button type="button" class="toggle-btn {{ !old('is_ongoing') ? 'active' : '' }}" onclick="setBinaryField('is_ongoing',0)" data-i18n="btn_no">No</button>
            </div>
          </div>
          <div class="toggle-row" style="margin-top:.75rem;">
            <strong data-i18n="q_recurring">Recurring or one-time?</strong>
            <div class="toggle-btns">
              <button type="button" class="toggle-btn {{ old('is_recurring') ? 'active' : '' }}" onclick="setBinaryField('is_recurring',1)" data-i18n="btn_recurring">Recurring</button>
              <button type="button" class="toggle-btn {{ !old('is_recurring') ? 'active' : '' }}" onclick="setBinaryField('is_recurring',0)" data-i18n="btn_onetime">One-time</button>
            </div>
          </div>
        </div>
      </details>

      <div style="display:flex;justify-content:flex-end;margin-top:1.5rem;">
        <button type="button" class="btn btn-gold" onclick="goToStep(2)" data-i18n="btn_continue">Next: Location <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 2: LOCATION -->
    <div class="step-pane" id="step-pane-2" style="display:none;">
      <h2 data-i18n="step4_title"><i class="fa-solid fa-map-location-dot"></i> Location</h2>
      <p class="field-hint" data-i18n="step4_desc">Tap on the map, choose a purok, or type a landmark. At least one location method is required.</p>
      <p class="field-hint" data-i18n="loc_help">Choose at least one: pin, purok, or landmark.</p>

      <div class="field">
        <div id="map" class="map-frame" style="min-height:300px;" aria-label="Choose the report location on the map"></div>
        <div class="map-controls" style="margin-top:.5rem;">
          <button type="button" id="btn-locate" class="btn btn-outline btn-small"><i class="fa-solid fa-crosshairs"></i> <span data-i18n="btn_use_location">Use my current location</span></button>
        </div>
      </div>

      <div id="nearby-reports-container" class="nearby-reports-box" style="display:none;margin-top:.75rem;">
        <strong data-i18n="nearby_title">Is this the same problem?</strong>
        <p style="margin:.2rem 0 .5rem;font-size:.85rem;" data-i18n="nearby_desc">We found reports near this pin. You can still submit a new report.</p>
        <div id="nearby-reports-list"></div>
      </div>

      <div class="form-grid" style="margin-top:1rem;">
        <div class="field">
          <label for="purok_id" data-i18n="lbl_purok">Purok</label>
          <select id="purok_id" name="purok_id">
            <option value="" data-i18n="opt_select_purok">Select purok</option>
            @foreach($puroks as $purok)
              <option value="{{ $purok->id }}" @selected(old('purok_id') == $purok->id)>{{ $purok->name }}</option>
            @endforeach
          </select>
        </div>
        <div class="field">
          <label for="landmark" data-i18n="lbl_landmark">Landmark or Street</label>
          <input type="text" id="landmark" name="landmark" value="{{ old('landmark') }}" placeholder="Near the chapel, beside school">
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;margin-top:1.5rem;">
        <button type="button" class="btn btn-outline" onclick="goToStep(1)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Back</span></button>
        <button type="button" class="btn btn-gold" onclick="goToStep(3)" data-i18n="btn_continue">Next: Photo <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 3: PHOTO & UPDATES -->
    <div class="step-pane" id="step-pane-3" style="display:none;">
      <h2 data-i18n="step5_title"><i class="fa-solid fa-camera"></i> Photo and updates</h2>
      <p class="field-hint" data-i18n="photo_guidance_head">Show the problem and a landmark; avoid faces and license plates.</p>

      <div class="field">
        <div class="photo-preview-area">
          <p id="photo-empty-state" class="photo-empty-state" data-i18n="photo_empty">No photos selected yet.</p>
          <div id="photo-previews" class="photo-preview-grid" aria-live="polite"></div>
        </div>
        <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png" multiple hidden>
        <div style="display:flex;gap:.5rem;flex-wrap:wrap;margin-top:.5rem;">
          <button type="button" id="add-photos" class="btn btn-outline btn-small"><i class="fa-solid fa-plus"></i> <span data-i18n="btn_add_photos">Add photos</span></button>
          <button type="button" class="btn btn-outline btn-small" onclick="goToStep(4)" data-i18n="btn_skip">Skip</button>
        </div>
        <p id="photo-upload-error" class="field-hint" role="alert" style="color:var(--red);" hidden></p>
        <p id="photo-total-size" class="field-hint" aria-live="polite"></p>
      </div>

      <div class="field" style="background:var(--mist);padding:.9rem;border-radius:10px;">
        <label style="display:flex;align-items:center;gap:.6rem;cursor:pointer;font-weight:600;">
          <input type="checkbox" name="is_anonymous" id="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }} onchange="toggleAnonymousFields(this.checked)">
          <span data-i18n="chk_anonymous">Submit this report anonymously</span>
        </label>
      </div>

      <div id="contact-fields-section">
        <p data-i18n="want_updates">Want updates?</p>
        <div class="form-grid">
          <div class="field">
            <label for="reporter_name" data-i18n="lbl_name">Name (optional)</label>
            <input type="text" id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}" maxlength="100">
          </div>
          <div class="field">
            <label for="reporter_contact" data-i18n="lbl_phone">Mobile number (optional)</label>
            <input type="tel" id="reporter_contact" name="reporter_contact" value="{{ old('reporter_contact') }}" maxlength="20" placeholder="09171234567">
            <p class="field-hint" data-i18n="hint_phone">Barangay staff may call you. Philippine format.</p>
          </div>
        </div>
        <div class="field">
          <label for="reporter_email" data-i18n="lbl_email">Email (optional)</label>
          <input type="email" id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}">
        </div>
      </div>

      <div style="display:flex;justify-content:space-between;margin-top:1.5rem;">
        <button type="button" class="btn btn-outline" onclick="goToStep(2)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Back</span></button>
        <button type="button" class="btn btn-gold" onclick="goToStep(4)" data-i18n="btn_continue">Next: Review <i class="fa-solid fa-arrow-right"></i></button>
      </div>
    </div>

    <!-- STEP 4: REVIEW -->
    <div class="step-pane" id="step-pane-4" style="display:none;">
      <h2 data-i18n="step7_title"><i class="fa-solid fa-file-invoice"></i> Review and send</h2>
      <div class="review-summary-card">
        <div class="review-row"><div class="review-label" data-i18n="sum_category">Category:</div><div class="review-val" id="sum-val-category">-</div><button type="button" onclick="goToStep(1)">Edit</button></div>
        <div class="review-row"><div class="review-label" data-i18n="sum_description">Description:</div><div class="review-val" id="sum-val-description">-</div><button type="button" onclick="goToStep(1)">Edit</button></div>
        <div class="review-row"><div class="review-label" data-i18n="sum_location">Location:</div><div class="review-val" id="sum-val-location">-</div><button type="button" onclick="goToStep(2)">Edit</button></div>
        <div class="review-row"><div class="review-label" data-i18n="sum_photos">Photos:</div><div class="review-val" id="sum-val-photos">0 attached</div><button type="button" onclick="goToStep(3)">Edit</button></div>
        <div class="review-row"><div class="review-label" data-i18n="sum_contact">Reporter:</div><div class="review-val" id="sum-val-contact">-</div><button type="button" onclick="goToStep(3)">Edit</button></div>
      </div>

      <div style="background:#f8fafc;border:1px solid #cbd5e1;border-radius:12px;padding:1.25rem;margin-top:1.5rem;">
        <h3 data-i18n="privacy_title">Data Privacy Notice (RA 10173)</h3>
        <p style="font-size:.82rem;color:var(--mut);line-height:1.5;" data-i18n="privacy_text">By submitting this report, you acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly to investigate, route, and resolve community problems.</p>
        <label style="display:flex;align-items:flex-start;gap:.6rem;cursor:pointer;font-weight:700;font-size:.88rem;">
          <input type="checkbox" name="consent" id="consent_checkbox" value="1" required {{ old('consent') ? 'checked' : '' }}>
          <span data-i18n="privacy_checkbox">I have read the Data Privacy Notice and give my voluntary consent.</span> *
        </label>
      </div>

      <div style="display:flex;justify-content:space-between;margin-top:1.5rem;gap:.75rem;flex-wrap:wrap;">
        <button type="button" class="btn btn-outline" onclick="goToStep(3)"><i class="fa-solid fa-arrow-left"></i> <span data-i18n="btn_back">Back</span></button>
        <button type="submit" id="btn-final-submit" class="btn btn-gold"><i class="fa-solid fa-paper-plane"></i> <span data-i18n="btn_submit_final">Submit report</span></button>
      </div>
    </div>
  </form>
</section>

<script id="categories-data" type="application/json">
  {!! json_encode($categories->map(fn($c) => ['id'=>$c->id,'name'=>$c->name,'slug'=>$c->slug,'question_schema'=>$c->question_schema ?? []])) !!}
</script>

<script>
const i18n = {
  en: {
    hero_title: "Report a problem in Barangay Calarian",
    hero_desc: "Send a report with category details, photo, and location. Barangay staff will review it, route it to the proper office, and keep you updated with a tracking code.",
    hero_btn_report: "Report a problem",
    hero_btn_track: "Track a report",
    form_heading: "Report a problem in Barangay Calarian", form_subheading: "Barangay Calarian Community Reporting",
    emg_strip: "Emergency? Call 911", emg_btn: "Emergency / Crime",
    emg_title: "Emergency Detected", emg_subtitle: "For immediate life-threatening danger, call official hotlines directly.",
    emg_not_monitored: "THIS ONLINE SYSTEM IS NOT MONITORED IN REAL-TIME.",
    emg_call_prompt: "Please contact emergency services immediately using the numbers below:",
    emg_back_btn: "Return to report", emg_inline: "Emergency contacts",
    step1: "Problem", step2: "Location", step3: "Photo", step4: "Review", step_counter: "Step 1 of 4",
    step1_title: "Problem", step1_desc: "Pick the category and describe the issue.",
    specific_questions_title: "Specific Questions",
    lbl_description: "What is the problem?", hint_description: "You may write in English, Filipino, Bisaya, or Chavacano (minimum 10 characters).",
    q_risk: "Is anyone in danger right now?", btn_yes: "Yes", btn_no: "No", btn_unsure: "Not sure",
    more_details: "More details (optional)", q_ongoing: "Is this happening right now?", q_recurring: "Recurring or one-time?", btn_recurring: "Recurring", btn_onetime: "One-time",
    step4_title: "Location", step4_desc: "Tap on the map, choose a purok, or type a landmark. At least one location method is required.",
    loc_help: "Choose at least one: pin, purok, or landmark.", btn_use_location: "Use my current location",
    nearby_title: "Is this the same problem?", nearby_desc: "We found reports near this pin. You can still submit a new report.",
    lbl_purok: "Purok", opt_select_purok: "Select purok", lbl_landmark: "Landmark or Street",
    step5_title: "Photo and updates", photo_guidance_head: "Show the problem and a landmark; avoid faces and license plates.",
    photo_empty: "No photos selected yet.", btn_add_photos: "Add photos", btn_skip: "Skip",
    chk_anonymous: "Submit this report anonymously", want_updates: "Want updates?",
    lbl_name: "Name (optional)", lbl_phone: "Mobile number (optional)", hint_phone: "Barangay staff may call you. Philippine format.",
    lbl_email: "Email (optional)",
    privacy_title: "Data Privacy Notice (RA 10173)", privacy_text: "By submitting this report, you acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly to investigate, route, and resolve community problems.",
    privacy_checkbox: "I have read the Data Privacy Notice and give my voluntary consent.",
    step7_title: "Review and send", sum_category: "Category:", sum_description: "Description:", sum_location: "Location:", sum_photos: "Photos:", sum_contact: "Reporter:",
    btn_back: "Back", btn_continue: "Continue", btn_submit_final: "Submit report"
  },
  fil: {
    hero_title: "Mag-ulat ng problema sa Barangay Calarian",
    hero_desc: "Magsumite ng ulat na may kategorya, larawan, at lokasyon. Susuriin ito ng mga kawani ng barangay, ipapadala sa tamang tanggapan, at bibigyan ka ng tracking code.",
    hero_btn_report: "Mag-ulat ng problema",
    hero_btn_track: "Subaybayan ang ulat",
    form_heading: "Mag-ulat ng problema sa Barangay Calarian", form_subheading: "Sistema ng Pag-uulat sa Barangay Calarian",
    emg_strip: "Emergency? Tumawag sa 911", emg_btn: "Emergency / Krimen",
    emg_title: "Pang-emergency na Alerto", emg_subtitle: "Para sa banta sa buhay, tumawag agad sa hotlines.",
    emg_not_monitored: "ANG ONLINE SYSTEM NA ITO AY HINDI MINUMONITOR NANG REAL-TIME.",
    emg_call_prompt: "Makipag-ugnayan kaagad sa mga numero sa ibaba:",
    emg_back_btn: "Bumalik sa ulat", emg_inline: "Mga emergency contact",
    step1: "Problema", step2: "Lokasyon", step3: "Larawan", step4: "Suriin", step_counter: "Hakbang 1 ng 4",
    step1_title: "Problema", step1_desc: "Piliin ang kategorya at ilarawan ang suliranin.",
    specific_questions_title: "Mga Tiyak na Tanong",
    lbl_description: "Ano ang problema?", hint_description: "Maaari kang sumulat sa Tagalog/Filipino, English, Bisaya, o Chavacano (hindi bababa sa 10 titik).",
    q_risk: "May nasa panganib ba ngayon?", btn_yes: "Oo", btn_no: "Hindi", btn_unsure: "Hindi sigurado",
    more_details: "Karagdagang detalye (opsyonal)", q_ongoing: "Kasalukuyan ba itong nangyayari?", q_recurring: "Paulit-ulit o minsan lang?", btn_recurring: "Paulit-ulit", btn_onetime: "Minsan lang",
    step4_title: "Lokasyon", step4_desc: "Pumindot sa mapa, pumili ng purok, o maglagay ng landmark. Kinakailangan ang kahit isang lokasyon.",
    loc_help: "Pumili ng kahit isa: pin, purok, o landmark.", btn_use_location: "Gamitin ang aking kasalukuyang lokasyon",
    nearby_title: "Ito ba ang parehong problema?", nearby_desc: "May nakitang mga ulat malapit sa pin. Maaari ka pa ring magsumite.",
    lbl_purok: "Purok", opt_select_purok: "Pumili ng purok", lbl_landmark: "Landmark o Kalye",
    step5_title: "Larawan at updates", photo_guidance_head: "Ipakita ang problema at landmark; iwasan ang mukha at plaka.",
    photo_empty: "Wala pang napiling larawan.", btn_add_photos: "Magdagdag ng larawan", btn_skip: "Laktawan",
    chk_anonymous: "Isumite nang hindi nagpapakilala", want_updates: "Gusto ng updates?",
    lbl_name: "Pangalan (opsyonal)", lbl_phone: "Numero ng telepono (opsyonal)", hint_phone: "Maaaring tumawag ang barangay. Format sa Pilipinas.",
    lbl_email: "Email (opsyonal)",
    privacy_title: "Paunawa sa Data Privacy (RA 10173)", privacy_text: "Sa pagsusumite ng ulat na ito, kinikilala mo na ang Barangay Calarian ay nangongolekta ng detalye ng suliranin, lokasyon, at opsyonal na impormasyon.",
    privacy_checkbox: "Nabasa at sinasang-ayunan ko ang Data Privacy Notice at nagbibigay ako ng pahintulot.",
    step7_title: "Suriin at isumite", sum_category: "Kategorya:", sum_description: "Deskripsyon:", sum_location: "Lokasyon:", sum_photos: "Mga Larawan:", sum_contact: "Nag-ulat:",
    btn_back: "Bumalik", btn_continue: "Magpatuloy", btn_submit_final: "Isumite ang ulat"
  }
};
let currentLang = 'en';
function setLanguage(lang) {
  currentLang = lang;
  document.querySelectorAll('.js-lang-btn-en').forEach(b=>b.classList.toggle('active',lang==='en'));
  document.querySelectorAll('.js-lang-btn-fil').forEach(b=>b.classList.toggle('active',lang==='fil'));
  document.querySelectorAll('[data-i18n]').forEach(el=>{
    const key=el.getAttribute('data-i18n');
    if (i18n[lang] && i18n[lang][key]) el.textContent = i18n[lang][key];
  });
  try { localStorage.setItem('calarian_lang', lang); } catch(e){}
}
let currentStep = 1; const totalSteps = 4;
function goToStep(step) {
  for (let i=1;i<=totalSteps;i++){
    const pane=document.getElementById(`step-pane-${i}`);
    if (pane) pane.style.display = (i===step)?'block':'none';
  }
  document.querySelectorAll('#stepper .step-item').forEach(item=>{
    const s = parseInt(item.getAttribute('data-step'),10);
    item.classList.toggle('active', s===step);
    item.classList.toggle('done', s<step);
  });
  const counter=document.getElementById('step-counter');
  if (counter) counter.textContent = (currentLang==='fil' ? `Hakbang ${step} ng ${totalSteps}` : `Step ${step} of ${totalSteps}`);
  const bar=document.getElementById('step-progress-bar');
  if (bar) bar.style.width = `${(step/totalSteps)*100}%`;
  currentStep=step;
  if (step===2 && window.leafletMapInstance) setTimeout(()=>window.leafletMapInstance.invalidateSize(),200);
  if (step===4) populateReviewSummary();
  document.getElementById('report-form').scrollIntoView({behavior:'smooth',block:'start'});
}
function setSafety(val) {
  const input=document.getElementById('people_at_risk');
  input.value = (val==='yes') ? '1' : '0';
  const btns = document.querySelectorAll('[data-safety-btn]');
  btns.forEach(b=>b.classList.remove('active'));
  const active = document.querySelector(`[data-safety-btn="${val}"]`);
  if (active) active.classList.add('active');
  const inline = document.getElementById('inline-emergency');
  inline.style.display = (val==='yes') ? 'block' : 'none';
}
function setBinaryField(fieldId, val) {
  document.getElementById(fieldId).value = val;
  const input = document.getElementById(fieldId);
  const row = input.closest('.toggle-row');
  if (row) row.querySelectorAll('.toggle-btn').forEach((btn, idx) => {
    btn.classList.toggle('active', (val === 1 && idx === 0) || (val === 0 && idx === 1));
  });
}
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
function showEmergency() {
  document.getElementById('emergency-screen').style.display = 'block';
  document.getElementById('report-form').style.display = 'none';
  document.getElementById('emergency-screen').scrollIntoView({behavior:'smooth',block:'start'});
}
function hideEmergency() {
  document.getElementById('emergency-screen').style.display = 'none';
  document.getElementById('report-form').style.display = 'block';
}

// categories
let categoriesData = [];
try { categoriesData = JSON.parse(document.getElementById('categories-data').textContent); } catch(e){}
function selectCategory(catId) {
  document.getElementById('category_id_input').value = catId;
  document.querySelectorAll('.category-option-card').forEach(card => {
    card.classList.toggle('selected', parseInt(card.getAttribute('data-cat-id'),10) === catId);
  });
  const category = categoriesData.find(c => c.id === catId);
  const container = document.getElementById('dynamic-questions-container');
  const fields = document.getElementById('dynamic-questions-fields');
  fields.innerHTML = '';
  if (category && category.question_schema && category.question_schema.length > 0) {
    category.question_schema.forEach(q => {
      const qDiv = document.createElement('div');
      qDiv.className = 'field';
      const label = document.createElement('label');
      label.style.fontWeight = '600';
      label.style.fontSize = '.9rem';
      label.innerHTML = `${q.label} ${q.required ? '<span style="color:var(--red);">*</span>' : ''}`;
      qDiv.appendChild(label);
      if (q.type === 'select' && Array.isArray(q.options)) {
        const sel = document.createElement('select');
        sel.name = `answers[${q.key}]`;
        if (q.required) sel.required = true;
        const optDefault = document.createElement('option');
        optDefault.value = ''; optDefault.textContent = currentLang === 'fil' ? '-- Pumili --' : '-- Select --';
        sel.appendChild(optDefault);
        q.options.forEach(opt => { const o=document.createElement('option'); o.value=opt; o.textContent=opt; sel.appendChild(o); });
        qDiv.appendChild(sel);
      } else if (q.type === 'boolean') {
        const wrap = document.createElement('div');
        wrap.style.display='flex'; wrap.style.gap='1rem'; wrap.style.marginTop='.35rem';
        wrap.innerHTML = `<label style="cursor:pointer;display:flex;align-items:center;gap:.35rem;"><input type="radio" name="answers[${q.key}]" value="1" ${q.required?'required':''}> Yes / Oo</label><label style="cursor:pointer;display:flex;align-items:center;gap:.35rem;"><input type="radio" name="answers[${q.key}]" value="0"> No / Hindi</label>`;
        qDiv.appendChild(wrap);
      } else if (q.type === 'textarea') {
        const ta = document.createElement('textarea'); ta.name=`answers[${q.key}]`; ta.rows=2; if(q.required) ta.required=true; qDiv.appendChild(ta);
      } else if (q.type === 'number') {
        const inp = document.createElement('input'); inp.type='number'; inp.name=`answers[${q.key}]`; if(q.required) inp.required=true; qDiv.appendChild(inp);
      } else {
        const inp = document.createElement('input'); inp.type='text'; inp.name=`answers[${q.key}]`; if(q.required) inp.required=true; qDiv.appendChild(inp);
      }
      fields.appendChild(qDiv);
    });
    container.style.display = 'block';
  } else {
    container.style.display = 'none';
  }
  triggerNearbyReportsCheck();
}

// map
let mapInstance = null;
document.addEventListener('DOMContentLoaded', () => {
  const CALARIAN_CENTER = [6.921, 122.078];
  const CALARIAN_BOUNDS = [[6.905,122.060],[6.940,122.098]];
  if (typeof L !== 'undefined') {
    mapInstance = L.map('map').setView(CALARIAN_CENTER, 15);
    window.leafletMapInstance = mapInstance;
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {maxZoom:19, attribution:'&copy; OSM'}).addTo(mapInstance);
    mapInstance.setMaxBounds(CALARIAN_BOUNDS);
    let marker = null;
    function placePin(lat, lng) {
      if (marker) marker.setLatLng([lat,lng]); else marker = L.marker([lat,lng], {draggable:true}).addTo(mapInstance);
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
    const btnLocate = document.getElementById('btn-locate');
    if (btnLocate) btnLocate.addEventListener('click', () => {
      if (navigator.geolocation) navigator.geolocation.getCurrentPosition(pos => {
        placePin(pos.coords.latitude, pos.coords.longitude);
        mapInstance.setView([pos.coords.latitude, pos.coords.longitude], 16);
      });
    });
  }
  try { const saved=localStorage.getItem('calarian_lang'); if(saved && (saved==='en'||saved==='fil')) setLanguage(saved); } catch(e){}
});

// nearby
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
        item.innerHTML = `<span><b>${r.tracking_code}</b>: ${r.category} (${r.status})</span>`;
        list.appendChild(item);
      });
      box.style.display = 'block';
    } else {
      box.style.display = 'none';
    }
  } catch (e) { console.debug('Nearby reports check error:', e); }
}

// photos
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
async function compressImageFile(file, maxWidth=1600, maxHeight=1600, quality=0.8) {
  if (file.size < 400*1024) return file;
  return new Promise(resolve => {
    const reader = new FileReader();
    reader.readAsDataURL(file);
    reader.onload = ev => {
      const img = new Image();
      img.src = ev.target.result;
      img.onload = () => {
        let width=img.width, height=img.height;
        if (width>height){ if(width>maxWidth){height=Math.round(height*maxWidth/width);width=maxWidth;} }
        else { if(height>maxHeight){width=Math.round(width*maxHeight/height);height=maxHeight;} }
        const canvas=document.createElement('canvas'); canvas.width=width; canvas.height=height;
        const ctx=canvas.getContext('2d'); ctx.drawImage(img,0,0,width,height);
        canvas.toBlob(blob => {
          if (blob && blob.size < file.size) {
            const compressed = new File([blob], file.name.replace(/\.[^/.]+$/,'')+'.jpg', {type:'image/jpeg',lastModified:Date.now()});
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
  selectedPhotosList.forEach(p=>dt.items.add(p));
  photosInput.files = dt.files;
  photoPreviews.innerHTML = '';
  if (photoEmptyState) photoEmptyState.style.display = selectedPhotosList.length > 0 ? 'none' : 'block';
  let totalBytes=0;
  selectedPhotosList.forEach((photo,idx)=>{
    totalBytes += photo.size;
    const url = URL.createObjectURL(photo);
    const item = document.createElement('div');
    item.className = 'photo-preview-item';
    item.innerHTML = `<img src="${url}" alt="Preview" style="width:72px;height:72px;object-fit:cover;border-radius:8px;"><button type="button" class="photo-preview-remove" onclick="removePhoto(${idx})"><i class="fa-solid fa-xmark"></i></button>`;
    photoPreviews.appendChild(item);
  });
  const totalMb = (totalBytes/(1024*1024)).toFixed(2);
  if (photoTotalSize) photoTotalSize.textContent = selectedPhotosList.length > 0 ? `${selectedPhotosList.length} photo(s) (${totalMb} MB total)` : '';
  if (photoUploadError) {
    if (totalBytes > 5*1024*1024) { photoUploadError.textContent='Combined size exceeds 5 MB.'; photoUploadError.hidden=false; }
    else photoUploadError.hidden=true;
  }
}
function removePhoto(index){ selectedPhotosList.splice(index,1); renderPhotoPreviews(); }

function populateReviewSummary() {
  const catId = parseInt(document.getElementById('category_id_input').value,10);
  const cat = categoriesData.find(c=>c.id===catId);
  document.getElementById('sum-val-category').textContent = cat ? cat.name : '-';
  document.getElementById('sum-val-description').textContent = document.getElementById('description').value || '-';
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
  if (isAnon) document.getElementById('sum-val-contact').textContent = 'Anonymous';
  else {
    const name = document.getElementById('reporter_name').value || 'Anonymous';
    const contact = document.getElementById('reporter_contact').value || '';
    const email = document.getElementById('reporter_email').value || '';
    document.getElementById('sum-val-contact').textContent = `${name} ${contact?'· '+contact:''} ${email?'· '+email:''}`;
  }
}
</script>
@endsection
