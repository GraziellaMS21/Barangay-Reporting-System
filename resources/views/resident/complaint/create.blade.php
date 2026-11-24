@extends('layouts.app')
@section('title', 'File a Dispute Complaint - Barangay Calarian')

@section('hero')
  <section class="hero">
    <div class="hero-inner">
      <div>
        <h1>File a Dispute Complaint</h1>
        <p>Submit a dispute complaint request under the Katarungang Pambarangay Law (RA 7160). Barangay officials will review your submission and prepare it for official in-person signing and summons issuance.</p>
        <em class="hero-motto">Katarungang Pambarangay · Pusong May Malasakit, Serbisyong Tapat sa Mamamayan</em>
        <div class="hero-actions">
          <a class="btn btn-ghost" href="{{ route('reports.create') }}">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to problem reporting
          </a>
          <a class="btn btn-ghost" href="{{ route('track.index') }}">
            <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Track a community report
          </a>
        </div>
      </div>
      <div class="hero-seal" aria-hidden="true"><img src="{{ asset('storage/calarian-seal.png') }}" alt=""></div>
    </div>
  </section>
@endsection

@section('content')
  <style>
    .complaint-card {
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      padding: 2.25rem;
      background: #ffffff;
      box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 20px 30px -4px rgba(11, 31, 75, 0.08);
      position: relative;
    }

    @media (max-width: 640px) {
      .complaint-card {
        padding: 1.25rem;
        border-radius: 16px;
      }
    }

    .vawc-screening-box {
      border: 2px solid #fecaca;
      background: #fef2f2;
      border-radius: 16px;
      padding: 1.5rem;
      margin-bottom: 2rem;
    }

    .vawc-guidance-box {
      border: 2px solid #ef4444;
      background: #fff5f5;
      border-radius: 16px;
      padding: 1.75rem;
      margin-bottom: 2rem;
      animation: fadeIn 0.25s ease-out;
    }

    .quick-exit-bar {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 1rem;
    }

    .btn-quick-exit {
      background: #b91c1c;
      color: #ffffff;
      border: none;
      border-radius: 999px;
      padding: 0.45rem 1.1rem;
      font-size: 0.82rem;
      font-weight: 700;
      cursor: pointer;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      transition: background 0.15s, transform 0.15s;
    }

    .btn-quick-exit:hover {
      background: #991b1b;
      transform: translateY(-1px);
    }

    .form-section-title {
      font-size: 1.15rem;
      font-weight: 700;
      color: var(--ink);
      display: flex;
      align-items: center;
      gap: 0.6rem;
      padding-bottom: 0.6rem;
      border-bottom: 1px solid #f1f5f9;
      margin-top: 1.75rem;
      margin-bottom: 1.25rem;
    }

    .form-section-title:first-of-type {
      margin-top: 0;
    }

    .form-grid-2 {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 1.25rem;
    }

    @media (max-width: 640px) {
      .form-grid-2 {
        grid-template-columns: 1fr;
        gap: 0.85rem;
      }
    }

    .field-group {
      margin-bottom: 1rem;
    }

    .field-group label {
      display: block;
      font-size: 0.875rem;
      font-weight: 600;
      color: var(--ink);
      margin-bottom: 0.35rem;
    }

    .field-group input[type="text"],
    .field-group input[type="number"],
    .field-group input[type="datetime-local"],
    .field-group select,
    .field-group textarea {
      width: 100%;
      padding: 0.65rem 0.85rem;
      border: 1.5px solid #cbd5e1;
      border-radius: 10px;
      font-size: 0.92rem;
      color: var(--ink);
      background: #ffffff;
      transition: border-color 0.15s, box-shadow 0.15s;
    }

    .field-group input:focus,
    .field-group select:focus,
    .field-group textarea:focus {
      outline: none;
      border-color: var(--blue);
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
    }

    .hotline-link-card {
      display: flex;
      align-items: center;
      justify-content: space-between;
      padding: 0.85rem 1.1rem;
      border-radius: 10px;
      border: 1.5px solid #fecaca;
      background: #ffffff;
      color: #991b1b;
      font-weight: 700;
      text-decoration: none;
      transition: all 0.15s ease;
      margin-bottom: 0.6rem;
    }

    .hotline-link-card:hover {
      background: #fee2e2;
      border-color: #ef4444;
      transform: translateY(-1px);
    }
  </style>

  <!-- Quick Exit Floating Bar for Safety -->
  <div class="quick-exit-bar">
    <button type="button" class="btn-quick-exit" onclick="quickExit()" title="Leave this page immediately and clear history">
      <i class="fa-solid fa-person-running" aria-hidden="true"></i> Quick Exit (Leave page now)
    </button>
  </div>

  <!-- Confidential Matters VAWC Screening Box -->
  <section id="vawc-screening-step" class="vawc-screening-box">
    <div style="display:flex;align-items:flex-start;gap:0.85rem;">
      <i class="fa-solid fa-shield-halved text-red-600 text-2xl mt-0.5 flex-shrink-0" aria-hidden="true"></i>
      <div>
        <h2 style="font-size:1.15rem;font-weight:800;color:#991b1b;margin:0 0 0.4rem;">
          Mandatory Safety & Confidentiality Screening
        </h2>
        <p style="font-size:0.9rem;color:#7f1d1d;line-height:1.5;margin:0 0 1rem;">
          Does this complaint involve <strong>domestic violence, violence against women or their children (VAWC - RA 9262)</strong>, or <strong>child abuse/exploitation (RA 7610)</strong>?
        </p>
        <div style="display:flex;gap:0.75rem;flex-wrap:wrap;">
          <button type="button" class="btn" style="background:#dc2626;color:#ffffff;font-weight:700;border:none;padding:0.5rem 1.25rem;border-radius:8px;" onclick="handleVawcSelection(true)">
            <i class="fa-solid fa-triangle-exclamation"></i> YES — This involves domestic violence or child protection
          </button>
          <button type="button" class="btn" style="background:#0f172a;color:#ffffff;font-weight:700;border:none;padding:0.5rem 1.25rem;border-radius:8px;" onclick="handleVawcSelection(false)">
            <i class="fa-solid fa-check"></i> NO — This is a neighbor / property / civil dispute
          </button>
        </div>
      </div>
    </div>
  </section>

  <!-- Confidentiality Guidance Display (Hidden by default; shown if user selects YES) -->
  <section id="vawc-guidance-display" class="vawc-guidance-box" style="display:none;">
    <div style="display:flex;align-items:center;gap:0.85rem;color:#b91c1c;margin-bottom:1rem;padding-bottom:0.75rem;border-bottom:1px solid #fee2e2;">
      <i class="fa-solid fa-hand-holding-heart text-2xl" aria-hidden="true"></i>
      <div>
        <h2 style="color:#b91c1c;margin:0;font-size:1.25rem;font-weight:800;">VAWC & Child Protection Emergency Support</h2>
        <p style="margin:0.2rem 0 0;color:#7f1d1d;font-size:0.88rem;">
          Under Philippine law (RA 9262 & RA 7610), cases involving domestic violence or child abuse are strictly confidential and exempt from public web submission.
        </p>
      </div>
    </div>

    <div style="background:#ffffff;border:1px solid #fca5a5;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.25rem;">
      <p style="font-weight:700;color:#991b1b;margin:0 0 0.35rem;">
        <i class="fa-solid fa-phone-volume"></i> Immediate Assistance Hotlines:
      </p>
      <p style="font-size:0.85rem;color:#64748b;margin:0 0 1rem;">
        Please call any of the official helplines below or visit the Barangay Calarian VAW Desk in person:
      </p>

      <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotlines['vawc_national']) }}" class="hotline-link-card" style="background:#fee2e2;border-color:#f87171;">
        <span><i class="fa-solid fa-phone text-red-600 mr-2"></i> National Emergency Hotline</span>
        <span style="font-family:monospace;font-size:1.05rem;">{{ $hotlines['vawc_national'] }}</span>
      </a>

      <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotlines['vawc_pnp']) }}" class="hotline-link-card">
        <span><i class="fa-solid fa-shield-cat text-blue-600 mr-2"></i> PNP Aleng Pulis Hotline (VAWC Desk)</span>
        <span style="font-family:monospace;">{{ $hotlines['vawc_pnp'] }}</span>
      </a>

      <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotlines['bcpc_national']) }}" class="hotline-link-card">
        <span><i class="fa-solid fa-child text-indigo-600 mr-2"></i> Bantay Bata Helpline (Child Abuse)</span>
        <span style="font-family:monospace;">{{ $hotlines['bcpc_national'] }}</span>
      </a>

      <a href="tel:{{ preg_replace('/[^0-9+]/', '', $hotlines['vawc_barangay']) }}" class="hotline-link-card">
        <span><i class="fa-solid fa-building text-slate-600 mr-2"></i> Barangay Calarian VAW Desk (Local)</span>
        <span style="font-family:monospace;">{{ $hotlines['vawc_barangay'] }}</span>
      </a>
    </div>

    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
      <button type="button" class="btn btn-quick-exit" onclick="quickExit()">
        <i class="fa-solid fa-arrow-right-from-bracket"></i> Leave this page immediately
      </button>
      <button type="button" class="btn btn-outline" onclick="handleVawcSelection(false)" style="font-size:0.85rem;">
        Wait, my issue is NOT a domestic or child abuse case
      </button>
    </div>
  </section>

  <!-- Main Complaint Form Card (Revealed only when user passes confidentiality screen) -->
  <section id="complaint-form-wrapper" class="complaint-card" style="display:none;">
    @if($errors->any())
      <div role="alert" style="background:#fef2f2;border:1px solid #fecaca;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.5rem;">
        <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;color:#b91c1c;margin-bottom:0.4rem;">
          <i class="fa-solid fa-circle-exclamation"></i> Please review and correct the errors below:
        </div>
        <ul style="margin:0;padding-left:1.4rem;color:#991b1b;font-size:0.88rem;">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;padding:1rem 1.25rem;margin-bottom:1.75rem;">
      <p style="font-size:0.88rem;color:#1e40af;margin:0;line-height:1.5;">
        <i class="fa-solid fa-scale-balanced mr-1"></i>
        <strong>Katarungang Pambarangay Notice:</strong>
        This online form records a <em>pending complaint intake</em>. In accordance with RA 7160, you must appear in person at the Barangay Calarian Hall to sign the formal complaint before summonses can be served.
      </p>
    </div>

    <form method="POST" action="{{ route('complaint.store') }}" id="complaint-intake-form">
      @csrf

      <!-- Honeypot spam trap -->
      <div style="display:none !important;" aria-hidden="true">
        <input type="text" name="honeypot_website" value="" tabindex="-1" autocomplete="off">
        <input type="text" name="sp_check" value="" tabindex="-1" autocomplete="off">
      </div>

      <!-- 1. Complainant Section -->
      <h2 class="form-section-title">
        <i class="fa-solid fa-user-tag text-blue-600" aria-hidden="true"></i>
        <span>1. Complainant Information (Your Details)</span>
      </h2>

      <div class="form-grid-2">
        <div class="field-group">
          <label for="complainant_name">Full Legal Name <span class="text-red-600">*</span></label>
          <input type="text" name="complainant_name" id="complainant_name" value="{{ old('complainant_name') }}" required placeholder="e.g. Juan Dela Cruz">
        </div>
        <div class="field-group">
          <label for="complainant_alias">Alias / Nickname (Optional)</label>
          <input type="text" name="complainant_alias" id="complainant_alias" value="{{ old('complainant_alias') }}" placeholder="e.g. Johnny">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="field-group">
          <label for="complainant_contact">Phone Number or Email <span class="text-red-600">*</span></label>
          <input type="text" name="complainant_contact" id="complainant_contact" value="{{ old('complainant_contact') }}" required placeholder="e.g. 09171234567 or email@domain.com">
        </div>
        <div class="field-group">
          <label for="complainant_age">Age (Optional)</label>
          <input type="number" name="complainant_age" id="complainant_age" value="{{ old('complainant_age') }}" min="1" max="130" placeholder="e.g. 35">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="field-group">
          <label for="complainant_address">Complete Residential Address <span class="text-red-600">*</span></label>
          <input type="text" name="complainant_address" id="complainant_address" value="{{ old('complainant_address') }}" required placeholder="House No., Street, Purok, Calarian">
        </div>
        <div class="field-group">
          <label for="complainant_residency_claim">Residency Status</label>
          <select name="complainant_residency_claim" id="complainant_residency_claim">
            <option value="resident" @selected(old('complainant_residency_claim') === 'resident' || !old('complainant_residency_claim'))>Calarian Resident</option>
            <option value="non_resident" @selected(old('complainant_residency_claim') === 'non_resident')>Non-Resident</option>
            <option value="transient" @selected(old('complainant_residency_claim') === 'transient')>Transient / Temporary</option>
          </select>
        </div>
      </div>

      <!-- 2. Respondent Section -->
      <h2 class="form-section-title">
        <i class="fa-solid fa-user-xmark text-slate-700" aria-hidden="true"></i>
        <span>2. Respondent Information (Person Being Complained Against)</span>
      </h2>

      <div class="form-grid-2">
        <div class="field-group">
          <label for="respondent_name">Respondent Full Name <span class="text-red-600">*</span></label>
          <input type="text" name="respondent_name" id="respondent_name" value="{{ old('respondent_name') }}" required placeholder="e.g. Pedro Santos">
        </div>
        <div class="field-group">
          <label for="respondent_alias">Alias / Nickname (Optional)</label>
          <input type="text" name="respondent_alias" id="respondent_alias" value="{{ old('respondent_alias') }}" placeholder="e.g. Peter">
        </div>
      </div>

      <div class="form-grid-2">
        <div class="field-group">
          <label for="respondent_address">Respondent Address (For Summons Delivery) <span class="text-red-600">*</span></label>
          <input type="text" name="respondent_address" id="respondent_address" value="{{ old('respondent_address') }}" required placeholder="Street address or nearest landmark in Calarian">
        </div>
        <div class="field-group">
          <label for="respondent_contact">Respondent Contact Number (If known)</label>
          <input type="text" name="respondent_contact" id="respondent_contact" value="{{ old('respondent_contact') }}" placeholder="Mobile number or N/A">
        </div>
      </div>

      <!-- 3. Incident Narrative (The 5Ws) -->
      <h2 class="form-section-title">
        <i class="fa-solid fa-clipboard-question text-indigo-600" aria-hidden="true"></i>
        <span>3. Incident Narrative (The 5Ws)</span>
      </h2>

      <div class="field-group">
        <label for="what_happened">What Happened? (Nature of the Incident) <span class="text-red-600">*</span></label>
        <textarea name="what_happened" id="what_happened" rows="3" required placeholder="Describe the incident clearly (minimum 10 characters)...">{{ old('what_happened') }}</textarea>
      </div>

      <div class="form-grid-2">
        <div class="field-group">
          <label for="who_involved">Who Was Involved? (Witnesses, other persons) <span class="text-red-600">*</span></label>
          <input type="text" name="who_involved" id="who_involved" value="{{ old('who_involved') }}" required placeholder="Names of other persons or witnesses present">
        </div>
        <div class="field-group">
          <label for="when_happened">When Did It Happen? (Date and Time) <span class="text-red-600">*</span></label>
          <input type="datetime-local" name="when_happened" id="when_happened" value="{{ old('when_happened') }}" required>
        </div>
      </div>

      <div class="form-grid-2">
        <div class="field-group">
          <label for="where_happened">Where Did It Happen? (Location / Landmark) <span class="text-red-600">*</span></label>
          <input type="text" name="where_happened" id="where_happened" value="{{ old('where_happened') }}" required placeholder="Specific sitio, street, or landmark in Calarian">
        </div>
        <div class="field-group">
          <label for="case_type_suggestion">Dispute Category (Optional)</label>
          <select name="case_type_suggestion" id="case_type_suggestion">
            <option value="">-- Choose dispute type (optional) --</option>
            @foreach($caseTypes as $ct)
              <option value="{{ $ct->name }}" @selected(old('case_type_suggestion') === $ct->name)>{{ $ct->name }}</option>
            @endforeach
            <option value="Neighborhood Dispute" @selected(old('case_type_suggestion') === 'Neighborhood Dispute')>Neighborhood Dispute</option>
            <option value="Unpaid Debt / Financial" @selected(old('case_type_suggestion') === 'Unpaid Debt / Financial')>Unpaid Debt / Financial</option>
            <option value="Property / Boundary" @selected(old('case_type_suggestion') === 'Property / Boundary')>Property / Boundary</option>
            <option value="Physical Injury / Threat" @selected(old('case_type_suggestion') === 'Physical Injury / Threat')>Physical Injury / Threat</option>
            <option value="Other" @selected(old('case_type_suggestion') === 'Other')>Other Concern</option>
          </select>
        </div>
      </div>

      <div class="field-group">
        <label for="why_how_happened">Why or How Did It Happen? (Circumstances) <span class="text-red-600">*</span></label>
        <textarea name="why_how_happened" id="why_how_happened" rows="2" required placeholder="Explain the context or reason why this conflict occurred...">{{ old('why_how_happened') }}</textarea>
      </div>

      <!-- 4. Legal Grounds & Relief Requested -->
      <h2 class="form-section-title">
        <i class="fa-solid fa-gavel text-slate-800" aria-hidden="true"></i>
        <span>4. Rights Violated & Relief Requested</span>
      </h2>

      <div class="field-group">
        <label for="rights_violated">What Rights Were Violated or Damage Suffered? <span class="text-red-600">*</span></label>
        <textarea name="rights_violated" id="rights_violated" rows="2" required placeholder="State how this incident affected you or damaged your rights/property...">{{ old('rights_violated') }}</textarea>
      </div>

      <div class="field-group">
        <label for="relief_requested">What Remedy or Settlement Terms Do You Request? <span class="text-red-600">*</span></label>
        <textarea name="relief_requested" id="relief_requested" rows="2" required placeholder="What action, repayment, apology, or settlement are you seeking from the respondent?">{{ old('relief_requested') }}</textarea>
      </div>

      <!-- 5. Data Privacy Consent -->
      <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:1.15rem;margin-top:1.5rem;margin-bottom:1.5rem;">
        <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;font-weight:600;font-size:0.88rem;color:var(--ink);">
          <input type="checkbox" name="consent" id="consent_checkbox" value="1" required style="margin-top:0.2rem;width:1.15rem;height:1.15rem;accent-color:var(--blue);">
          <span>
            I hereby certify that the information provided is true and correct to the best of my knowledge. In accordance with the <strong>Data Privacy Act of 2012 (RA 10173)</strong>, I consent to the collection and processing of these details by authorized Barangay Calarian officials solely for official Katarungang Pambarangay proceedings.
          </span>
        </label>
      </div>

      <div style="display:flex;justify-content:flex-end;gap:1rem;align-items:center;padding-top:1rem;border-top:1px solid #f1f5f9;">
        <a href="{{ route('reports.create') }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary" style="background:#1d4ed8;color:#ffffff;font-weight:700;padding:0.75rem 1.75rem;border-radius:10px;">
          <i class="fa-solid fa-paper-plane mr-1.5"></i> Submit Complaint Request
        </button>
      </div>
    </form>
  </section>

  <script>
    function handleVawcSelection(isVawc) {
      const screeningStep = document.getElementById('vawc-screening-step');
      const guidanceDisplay = document.getElementById('vawc-guidance-display');
      const formWrapper = document.getElementById('complaint-form-wrapper');

      if (isVawc) {
        guidanceDisplay.style.display = 'block';
        formWrapper.style.display = 'none';
        guidanceDisplay.scrollIntoView({ behavior: 'smooth', block: 'start' });
      } else {
        guidanceDisplay.style.display = 'none';
        formWrapper.style.display = 'block';
        formWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    }

    function quickExit() {
      // Instantly replace URL with Google to protect privacy without browser history
      window.location.replace('https://www.google.com');
    }

    // Auto-reveal form if validation errors were returned from server
    @if($errors->any())
      document.addEventListener('DOMContentLoaded', function() {
        handleVawcSelection(false);
      });
    @endif
  </script>
@endsection
