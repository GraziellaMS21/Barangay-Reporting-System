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
  <style>
    .cat-chip {
      display: inline-flex;
      align-items: center;
      gap: .35rem;
      padding: .35rem .75rem;
      border: 1px solid var(--line);
      border-radius: 999px;
      background: #fff;
      color: var(--tx);
      font-size: .85rem;
      font-weight: 600;
      cursor: pointer;
    }

    .cat-chip.selected {
      background: var(--blue);
      color: #fff;
      border-color: var(--blue);
    }

    .section-divider {
      border: 0;
      border-top: 1px solid var(--line);
      margin: 1.25rem 0;
    }
  </style>

  <!-- Emergency screen -->
  <section id="emergency-screen" class="card" style="display:none;border:1px solid #e5e7eb;box-shadow:none;">
    <div style="display:flex;align-items:center;gap:.75rem;color:var(--red);margin-bottom:1rem;">
      <i class="fa-solid fa-triangle-exclamation" style="font-size:2rem;"></i>
      <div>
        <h2 style="color:var(--red);margin:0;" data-i18n="emg_title">Emergency Detected</h2>
        <p style="margin:.2rem 0 0;" data-i18n="emg_subtitle">For immediate life-threatening danger, call official
          hotlines directly.</p>
      </div>
    </div>
    <div class="card alert" style="margin-top:1rem;">
      <p style="font-weight:700;color:var(--red);" data-i18n="emg_not_monitored"><i class="fa-solid fa-bell-slash"></i>
        THIS ONLINE SYSTEM IS NOT MONITORED IN REAL-TIME.</p>
      <p style="margin:.25rem 0 0;" data-i18n="emg_call_prompt">Please contact emergency services immediately using the
        numbers below:</p>
    </div>
    <div style="margin-top:1rem;">
      <a href="tel:911" class="hotline-card"
        style="border-color:var(--red);background:#fee2e2;display:block;margin-bottom:.5rem;padding:.75rem;border-radius:10px;font-weight:700;color:var(--red);">911
        — National Emergency Hotline</a>
      @foreach($contacts as $contact)
        @php $num = trim((string) $contact->number); @endphp
        @if($num !== '' && stripos($num, 'N/A') === false && $num !== '911')
          <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}" class="hotline-card"
            style="display:block;margin-bottom:.5rem;padding:.75rem;border-radius:10px;border:1px solid var(--line);">{{ $contact->name }}
            — {{ $num }}</a>
        @endif
      @endforeach
    </div>
    <div style="margin-top:1.5rem;text-align:center;">
      <button type="button" class="btn btn-outline" onclick="hideEmergency()" data-i18n="emg_back_btn">Return to
        report</button>
    </div>
  </section>

  <!-- Single card form -->
  <section id="report-form" class="card" style="border:1px solid #e5e7eb;box-shadow:none;">
    @if($errors->any())
      <div role="alert" class="form-errors" style="margin-bottom:1.25rem;">
        <ul style="margin:0;padding-left:1.2rem;">
          @foreach($errors->all() as $error)
          <li>{{ $error }}</li>@endforeach
        </ul>
      </div>
    @endif

    <div
      style="display:flex;justify-content:space-between;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;">
      <div style="display:flex;align-items:center;gap:.5rem;">
        <span style="color:var(--red);font-weight:700;" data-i18n="emg_strip">Emergency? Call 911</span>
        <button type="button" class="btn btn-small" style="background:var(--red);color:#fff;" onclick="showEmergency()"
          data-i18n="emg_btn">Emergency / Crime</button>
      </div>
      <div style="display:inline-flex;gap:.25rem;">
        <button type="button" class="lang-toggle-btn js-lang-btn-en active" onclick="setLanguage('en')">EN</button>
        <button type="button" class="lang-toggle-btn js-lang-btn-fil" onclick="setLanguage('fil')">FIL</button>
      </div>
    </div>

    <form id="report-form-el" method="POST" action="{{ route('reports.store') }}" enctype="multipart/form-data">
      @csrf
      <input type="hidden" name="has_consent_field" value="1">
      <input type="hidden" name="report_type" id="report_type_input" value="non_emergency">
      <input type="hidden" name="category_id" id="category_id_input" value="{{ old('category_id') }}">
      <input type="hidden" name="lat" id="lat" value="{{ old('lat') }}">
      <input type="hidden" name="lng" id="lng" value="{{ old('lng') }}">
      <input type="hidden" name="is_ongoing" id="is_ongoing" value="{{ old('is_ongoing', 0) ? 1 : 0 }}">
      <input type="hidden" name="is_recurring" id="is_recurring" value="{{ old('is_recurring', 0) ? 1 : 0 }}">

      <!-- Choose a category (optional) -->
      <h2 style="font-size:1.1rem;margin-bottom:.5rem;" data-i18n="choose_category">Choose a category (optional)</h2>

      <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem;">
        @foreach($categories as $category)
          <button type="button" class="cat-chip {{ old('category_id') == $category->id ? 'selected' : '' }}"
            data-cat-id="{{ $category->id }}" data-cat-slug="{{ $category->slug }}"
            onclick="selectCategory({{ $category->id }})">
            @if($category->slug === 'drainage') <i class="fa-solid fa-water"></i>
            @elseif($category->slug === 'garbage') <i class="fa-solid fa-trash-can"></i>
            @elseif($category->slug === 'road-damage') <i class="fa-solid fa-road"></i>
            @elseif($category->slug === 'streetlight') <i class="fa-solid fa-lightbulb"></i>
            @elseif($category->slug === 'flooding') <i class="fa-solid fa-house-flood-water"></i>
            @else <i class="fa-solid fa-circle-question"></i>
            @endif
            {{ $category->name }}
          </button>
        @endforeach
      </div>
      @error('category_id')
      <p class="field-error">{{ $message }}</p>@enderror

      <div id="dynamic-questions-container" style="display:none;margin-bottom:1rem;">
        <div id="dynamic-questions-fields"></div>
      </div>

      <div class="field">
        <label for="description"><i class="fa-solid fa-align-left"></i> <span id="description-label"
            data-i18n="lbl_description">What is the problem?</span> *</label>
        <textarea id="description" name="description" rows="4" required minlength="10"
          maxlength="2000">{{ old('description') }}</textarea>
        <p class="field-hint" data-i18n="hint_description">You may write in English, Filipino, Bisaya, or Chavacano
          (minimum 10 characters).</p>
        @error('description')
        <p class="field-error">{{ $message }}</p>@enderror
      </div>

      <div class="field">
        <label style="display:flex;align-items:center;gap:.5rem;font-weight:600;">
          <input type="checkbox" id="people_at_risk_cb" name="people_at_risk" value="1" {{ old('people_at_risk') ? 'checked' : '' }} onchange="toggleDanger(this.checked)"
            style="width:1rem;height:1rem;accent-color:var(--blue);">
          <span data-i18n="danger_checkbox">Someone is in danger right now</span>
        </label>
      </div>
      <div id="inline-emergency"
        style="display:none;background:#fee2e2;border:1px solid #fecaca;border-radius:10px;padding:.75rem;margin-bottom:1rem;">
        <p style="color:#b91c1c;font-weight:700;margin:0 0 .5rem;" data-i18n="emg_inline">Emergency contacts</p>
        <a href="tel:911" style="display:block;margin-bottom:.5rem;color:var(--red);font-weight:700;">911</a>
        @foreach($contacts as $contact)
          @php $num = trim((string) $contact->number); @endphp
          @if($num !== '' && stripos($num, 'N/A') === false && $num !== '911')
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}"
              style="display:block;margin-bottom:.5rem;">{{ $contact->name }} — {{ $num }}</a>
          @endif
        @endforeach
      </div>

      <details style="margin-bottom:1rem;">
        <summary data-i18n="more_details">More details (optional)</summary>
        <div style="margin-top:.75rem;">
          <div class="toggle-row"
            style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;">
            <strong data-i18n="q_ongoing">Is this happening right now?</strong>
            <div class="toggle-btns" style="display:flex;gap:.5rem;">
              <button type="button" class="toggle-btn" onclick="setBinaryField('is_ongoing',1)"
                data-i18n="btn_yes">Yes</button>
              <button type="button" class="toggle-btn" onclick="setBinaryField('is_ongoing',0)"
                data-i18n="btn_no">No</button>
            </div>
          </div>
          <div class="toggle-row"
            style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-top:.75rem;">
            <strong data-i18n="q_recurring">Recurring or one-time?</strong>
            <div class="toggle-btns" style="display:flex;gap:.5rem;">
              <button type="button" class="toggle-btn" onclick="setBinaryField('is_recurring',1)"
                data-i18n="btn_recurring">Recurring</button>
              <button type="button" class="toggle-btn" onclick="setBinaryField('is_recurring',0)"
                data-i18n="btn_onetime">One-time</button>
            </div>
          </div>
        </div>
      </details>

      <hr class="section-divider">

      <!-- Where is it? -->
      <h2 style="font-size:1.1rem;margin-bottom:.5rem;" data-i18n="where_is_it">Where is it?</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4" style="margin-bottom:1rem;">
        <div>
          <div id="map" class="map-frame" style="min-height:280px;" aria-label="Choose the report location on the map">
          </div>
          <p class="field-hint" data-i18n="loc_help">Choose at least one: pin, purok, or landmark.</p>
          <div id="nearby-line" style="display:none;margin-top:.5rem;font-size:.85rem;"></div>
          <div id="nearby-list" style="display:none;margin-top:.25rem;font-size:.85rem;"></div>
        </div>
        <div>
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
            <input type="text" id="landmark" name="landmark" value="{{ old('landmark') }}"
              placeholder="Near the chapel, beside school">
          </div>
          <button type="button" id="btn-locate" class="btn btn-outline btn-small"><i class="fa-solid fa-crosshairs"></i>
            <span data-i18n="btn_use_location">Use my current location</span></button>
          @error('location')
          <p class="field-error">{{ $message }}</p>@enderror
        </div>
      </div>

      <hr class="section-divider">

      <!-- Photo and updates -->
      <h2 style="font-size:1.1rem;margin-bottom:.5rem;" data-i18n="photo_updates">Photo and updates</h2>
      <div class="field">
        <input type="file" id="photos" name="photos[]" accept="image/jpeg,image/png" multiple hidden>
        <button type="button" id="add-photos" class="btn btn-outline btn-small"><i class="fa-solid fa-plus"></i> <span
            data-i18n="btn_add_photos">Add photo</span></button>
        <div id="photo-previews" class="photo-preview-grid" style="margin-top:.5rem;" aria-live="polite"></div>
        <p id="photo-empty-state" class="field-hint" style="display:none;">No photos selected yet.</p>
        <p class="field-hint" data-i18n="photo_tip">Tip: show the problem near a landmark; avoid faces and license plates.
        </p>
        <p id="photo-upload-error" class="field-hint" role="alert" style="color:var(--red);" hidden></p>
        <p id="photo-total-size" class="field-hint" aria-live="polite"></p>
        @error('photos')
        <p class="field-error">{{ $message }}</p>@enderror
      </div>

      <details style="margin-bottom:1rem;">
        <summary data-i18n="updates_optional">Get updates on this report (optional)</summary>
        <div style="margin-top:.75rem;">
          <label style="display:flex;align-items:center;gap:.5rem;font-weight:600;margin-bottom:.75rem;">
            <input type="checkbox" name="is_anonymous" id="is_anonymous" value="1" {{ old('is_anonymous') ? 'checked' : '' }} onchange="toggleAnonymousFields(this.checked)" style="width:1rem;height:1rem;accent-color:var(--blue);">
            <span data-i18n="chk_anonymous">Submit this report anonymously</span>
          </label>
          <div id="contact-fields-section">
            <div class="field">
              <label for="reporter_name" data-i18n="lbl_name">Name (optional)</label>
              <input type="text" id="reporter_name" name="reporter_name" value="{{ old('reporter_name') }}"
                maxlength="100">
            </div>
            <div class="field">
              <label for="reporter_contact" data-i18n="lbl_phone">Mobile number (optional)</label>
              <input type="tel" id="reporter_contact" name="reporter_contact" value="{{ old('reporter_contact') }}"
                maxlength="20" placeholder="09171234567">
              <p class="field-hint" data-i18n="hint_phone">Barangay staff may call you. Philippine format.</p>
            </div>
            <div class="field">
              <label for="reporter_email" data-i18n="lbl_email">Email (optional)</label>
              <input type="email" id="reporter_email" name="reporter_email" value="{{ old('reporter_email') }}">
            </div>
          </div>
        </div>
      </details>

      <hr class="section-divider">

      <!-- Footer -->
      <div style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
        <label style="display:flex;align-items:center;gap:.5rem;font-size:.88rem;font-weight:600;">
          <input type="checkbox" name="consent" id="consent_checkbox" value="1" required {{ old('consent') ? 'checked' : '' }} style="width:1rem;height:1rem;accent-color:var(--blue);">
          <span><span data-i18n="consent_agree">I agree to the</span> <a href="#" onclick="openPrivacy(event)"
              data-i18n="privacy_link">Data Privacy Notice</a> (RA 10173)</span>
        </label>
        @error('consent')
        <p class="field-error">{{ $message }}</p>@enderror
        <button type="submit" id="btn-final-submit" class="btn btn-gold"><i class="fa-solid fa-paper-plane"></i> <span
            data-i18n="btn_submit_final">Submit report</span></button>
      </div>
    </form>
  </section>

  <dialog id="privacy-modal" style="border:none;border-radius:12px;padding:1.5rem;max-width:32rem;">
    <h3 data-i18n="privacy_title">Data Privacy Notice (RA 10173)</h3>
    <p style="font-size:.85rem;color:var(--mut);line-height:1.5;" data-i18n="privacy_text">By submitting this report, you
      acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly
      to investigate, route, and resolve community problems.</p>
    <div style="text-align:right;margin-top:1rem;">
      <button type="button" class="btn btn-outline btn-small"
        onclick="document.getElementById('privacy-modal').close()">Close</button>
    </div>
  </dialog>

  <script id="categories-data" type="application/json">
    {!! json_encode($categories->map(fn($c) => ['id' => $c->id, 'name' => $c->name, 'slug' => $c->slug, 'question_schema' => $c->question_schema ?? []])) !!}
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
        choose_category: "Choose a category (optional)",
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
        privacy_title: "Data Privacy Notice (RA 10173)", privacy_text: "By submitting this report, you acknowledge that Barangay Calarian collects issue details, approximate location, and optional contact info strictly to investigate, route, and resolve community problems.",
        btn_submit_final: "Submit report",
        consent_agree: "I agree to the", privacy_link: "Data Privacy Notice"
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
        choose_category: "Pumili ng kategorya (opsyonal)",
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
        consent_agree: "Sumasang-ayon ako sa", privacy_link: "Paunawa sa Data Privacy"
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
      try { localStorage.setItem('calarian_lang', lang); } catch (e) { }
      const selectedCat = document.getElementById('category_id_input').value;
      if (selectedCat) renderQuestions(parseInt(selectedCat, 10));
      updateDescriptionLabel();
    }

    function toggleDanger(checked) {
      document.getElementById('inline-emergency').style.display = checked ? 'block' : 'none';
    }

    function setBinaryField(fieldId, val) {
      document.getElementById(fieldId).value = val;
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
      // Clicking the selected chip again deselects it (FR-01: no category is required)
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
      document.querySelectorAll('.cat-chip').forEach(card => card.classList.remove('selected'));
      // Remove the extra fields so no stale answers are submitted
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
        textarea.placeholder = '';
      }
    }

    function renderQuestions(catId) {
      document.querySelectorAll('.cat-chip').forEach(card => {
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
          const label = document.createElement('label');
          label.style.fontWeight = '600';
          label.style.fontSize = '.9rem';
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
  wrap.style.display = 'flex';
  wrap.style.gap = '1.5rem';
  wrap.style.marginTop = '.35rem';

  const labelStyle = 'cursor:pointer;display:flex;align-items:center;gap:.4rem;margin:0;font-size:.9rem;font-weight:400;white-space:nowrap;width:auto;';
  const radioStyle = 'width:1rem;height:1rem;padding:0;margin:0;flex:none;accent-color:#2563eb;';

  wrap.innerHTML = `
    <label style="${labelStyle}">
      <input type="radio" style="${radioStyle}" name="answers[${q.key}]" value="1" ${q.required ? 'required' : ''}> Yes / Oo
    </label>
    <label style="${labelStyle}">
      <input type="radio" style="${radioStyle}" name="answers[${q.key}]" value="0"> No / Hindi
    </label>`;
  qDiv.appendChild(wrap);
}else if (q.type === 'textarea') {
            const ta = document.createElement('textarea'); ta.name = `answers[${q.key}]`; ta.rows = 2; if (q.required) ta.required = true; ta.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : ''; qDiv.appendChild(ta);
          } else if (q.type === 'number') {
            const inp = document.createElement('input'); inp.type = 'number'; inp.name = `answers[${q.key}]`; if (q.required) inp.required = true; inp.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : ''; qDiv.appendChild(inp);
          } else if (q.type === 'text') {
            const inp = document.createElement('input'); inp.type = 'text'; inp.name = `answers[${q.key}]`; if (q.required) inp.required = true; inp.placeholder = (typeof qPlaceholder === 'function') ? qPlaceholder(q) : ''; qDiv.appendChild(inp);
          } else {
            const inp = document.createElement('input'); inp.type = 'text'; inp.name = `answers[${q.key}]`; if (q.required) inp.required = true; qDiv.appendChild(inp);
          }
          fields.appendChild(qDiv);
        });
        container.style.display = 'block';
      } else {
        container.style.display = 'none';
      }
      updateDescriptionLabel();
    }

    // map
    let mapInstance = null;
    document.addEventListener('DOMContentLoaded', () => {
      const CALARIAN_CENTER = [6.921, 122.078];
      const CALARIAN_BOUNDS = [[6.905, 122.060], [6.940, 122.098]];
      if (typeof L !== 'undefined') {
        mapInstance = L.map('map').setView(CALARIAN_CENTER, 15);
        window.leafletMapInstance = mapInstance;
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 19, attribution: '&copy; OSM' }).addTo(mapInstance);
        mapInstance.setMaxBounds(CALARIAN_BOUNDS);
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
        const btnLocate = document.getElementById('btn-locate');
        if (btnLocate) btnLocate.addEventListener('click', () => {
          if (navigator.geolocation) navigator.geolocation.getCurrentPosition(pos => {
            placePin(pos.coords.latitude, pos.coords.longitude);
            mapInstance.setView([pos.coords.latitude, pos.coords.longitude], 16);
          });
        });
      }
      try { const saved = localStorage.getItem('calarian_lang'); if (saved && (saved === 'en' || saved === 'fil')) setLanguage(saved); } catch (e) { }
      // Restore state when the form is re-shown after a validation error (old category re-selected)
      const restoredCat = document.getElementById('category_id_input').value;
      if (restoredCat) renderQuestions(parseInt(restoredCat, 10));
      else updateDescriptionLabel();
      const firstError = document.querySelector('.field-error');
      if (firstError) firstError.scrollIntoView({ block: 'center' });
      if (document.getElementById('people_at_risk_cb').checked) toggleDanger(true);
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
          line.innerHTML = `${reports.length} similar reports nearby. <button type="button" onclick="document.getElementById('nearby-list').style.display='block'">View</button>`;
          line.style.display = 'block';
          list.innerHTML = reports.map(r => `<div><b>${r.tracking_code}</b>: ${r.category} (${r.status})</div>`).join('');
        } else {
          line.style.display = 'none'; list.style.display = 'none';
        }
      } catch (e) { line.style.display = 'none'; list.style.display = 'none'; }
    }

    // photos
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
        item.innerHTML = `<img src="${url}" alt="Preview" style="width:72px;height:72px;object-fit:cover;border-radius:8px;"><button type="button" class="photo-preview-remove" onclick="removePhoto(${idx})"><i class="fa-solid fa-xmark"></i></button>`;
        photoPreviews.appendChild(item);
      });
      const totalMb = (totalBytes / (1024 * 1024)).toFixed(2);
      if (photoTotalSize) photoTotalSize.textContent = selectedPhotosList.length > 0 ? `${selectedPhotosList.length} photo(s) (${totalMb} MB total)` : '';
      if (photoUploadError) {
        if (totalBytes > 5 * 1024 * 1024) { photoUploadError.textContent = 'Combined size exceeds 5 MB.'; photoUploadError.hidden = false; }
        else photoUploadError.hidden = true;
      }
    }
    function removePhoto(index) { selectedPhotosList.splice(index, 1); renderPhotoPreviews(); }

    function openPrivacy(e) {
      e.preventDefault();
      document.getElementById('privacy-modal').showModal();
    }
  </script>
@endsection