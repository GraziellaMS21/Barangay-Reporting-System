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
  </style>

  <!-- Main Complaint Form Card (Direct Form) -->
  <section id="complaint-form-wrapper" class="complaint-card">
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

      <!-- 3. Incident Narrative -->
      <h2 class="form-section-title">
        <i class="fa-solid fa-clipboard-question text-indigo-600" aria-hidden="true"></i>
        <span>3. Incident Narrative</span>
      </h2>

      <div class="field-group" style="margin-bottom:1.25rem;">
        <label for="incident_narrative">Incident Narrative (Explain who, where, what, why, when) <span class="text-red-600">*</span></label>
        <textarea name="incident_narrative" id="incident_narrative" rows="5" required minlength="10" maxlength="5000" placeholder="Tell us what happened in your own words. Include who was involved, where and when it happened, what happened and why.">{{ old('incident_narrative') }}</textarea>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;margin-top:0.35rem;">
          <p class="field-hint" style="margin:0;font-size:0.8rem;color:#64748b;">
            You may write in English, Filipino, Bisaya, or Chavacano (minimum 10 characters).
          </p>
          <div id="narrative-char-counter" class="field-hint" style="margin:0;font-size:0.8rem;font-weight:700;color:#64748b;" aria-live="polite">
            <span id="narrative-char-count">0</span> / 10 <span id="narrative-char-status">min</span>
          </div>
        </div>
        @error('incident_narrative')
          <p class="field-error text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
      </div>

      <div class="field-group" style="margin-bottom:1.5rem;">
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

      <!-- 4. Rights Violated & Relief Requested -->
      <h2 class="form-section-title">
        <i class="fa-solid fa-gavel text-slate-800" aria-hidden="true"></i>
        <span>4. Rights Violated & Relief Requested</span>
      </h2>

      <div class="field-group" style="margin-bottom:1.25rem;">
        <label for="rights_and_relief">Rights Violated & Relief Requested (Explain how this affected you and what you want to happen) <span class="text-red-600">*</span></label>
        <textarea name="rights_and_relief" id="rights_and_relief" rows="4" required minlength="10" maxlength="5000" placeholder="Describe how this incident affected you or damaged your rights or property, and what remedy you are asking for (apology, repayment, settlement, etc.).">{{ old('rights_and_relief') }}</textarea>
        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.5rem;margin-top:0.35rem;">
          <p class="field-hint" style="margin:0;font-size:0.8rem;color:#64748b;">
            You may write in English, Filipino, Bisaya, or Chavacano (minimum 10 characters).
          </p>
          <div id="relief-char-counter" class="field-hint" style="margin:0;font-size:0.8rem;font-weight:700;color:#64748b;" aria-live="polite">
            <span id="relief-char-count">0</span> / 10 <span id="relief-char-status">min</span>
          </div>
        </div>
        @error('rights_and_relief')
          <p class="field-error text-xs text-red-600 mt-1">{{ $message }}</p>
        @enderror
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
    document.addEventListener('DOMContentLoaded', function () {
      function setupCharCounter(inputId, countId, statusId, minLength) {
        const input = document.getElementById(inputId);
        const countEl = document.getElementById(countId);
        const statusEl = document.getElementById(statusId);
        if (!input || !countEl) return;

        function update() {
          const len = input.value.trim().length;
          countEl.textContent = len;
          if (len >= minLength) {
            if (statusEl) statusEl.textContent = 'chars';
            countEl.style.color = '#16a34a';
          } else {
            if (statusEl) statusEl.textContent = 'min';
            countEl.style.color = '#64748b';
          }
        }

        input.addEventListener('input', update);
        update();
      }

      setupCharCounter('incident_narrative', 'narrative-char-count', 'narrative-char-status', 10);
      setupCharCounter('rights_and_relief', 'relief-char-count', 'relief-char-status', 10);
    });
  </script>
@endsection
