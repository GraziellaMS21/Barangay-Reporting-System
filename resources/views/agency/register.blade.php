@extends('layouts.app')

@section('title', 'Partner Agency Registration')
@section('page-wrap-class', 'login-wrap')

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <h1>Partner Agency Registration</h1>
      <p>Request portal access to receive, acknowledge, and action official referrals from {{ setting('barangay.name', 'Barangay Calarian') }}.</p>
    </div>
  </section>
@endsection

@section('content')
<section class="card login-card" style="width: min(100%, 580px); padding: 2rem;">

  {{-- Step Progress Bar --}}
  <div class="wizard-progress-bar">
    <div class="wizard-step-indicator active" id="indicator-1">
      <div class="indicator-badge">1</div>
      <span class="indicator-label">Agency</span>
    </div>
    <div class="wizard-connector" id="connector-1"></div>
    <div class="wizard-step-indicator" id="indicator-2">
      <div class="indicator-badge">2</div>
      <span class="indicator-label">Representative</span>
    </div>
    <div class="wizard-connector" id="connector-2"></div>
    <div class="wizard-step-indicator" id="indicator-3">
      <div class="indicator-badge">3</div>
      <span class="indicator-label">Password</span>
    </div>
    <div class="wizard-connector" id="connector-3"></div>
    <div class="wizard-step-indicator" id="indicator-4">
      <div class="indicator-badge">4</div>
      <span class="indicator-label">Verification</span>
    </div>
  </div>

  {{-- Error Alert --}}
  @if(session('error'))
    <div class="form-errors" role="alert">{{ session('error') }}</div>
  @endif

  @if($errors->any())
    <div class="form-errors" role="alert">
      <strong>Please check the following errors:</strong>
      <ul style="margin: 0.5rem 0 0; padding-left: 1.25rem; font-size: 0.85rem;">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div id="wizard-error-msg" class="form-errors" style="display: none;" role="alert"></div>

  {{-- Registration Form --}}
  <form id="agency-register-form" method="POST" action="{{ route('agency.register.store') }}" enctype="multipart/form-data">
    @csrf

    {{-- ═══════════════ STEP 1: Agency Information ═══════════════ --}}
    <div class="wizard-step" id="step-1">
      <div class="wizard-step-title">
        <div class="step-icon-wrap"><i class="fa-solid fa-building-shield"></i></div>
        <div>
          <h3>Agency Information</h3>
          <p>Specify the government agency, unit, or department you represent.</p>
        </div>
      </div>

      <div class="login-form">
        <div>
          <label for="office_id">
            <i class="fa-solid fa-landmark text-blue-600"></i> Designated Partner Office
            <small style="font-weight: 500; color: var(--mut);">(optional)</small>
          </label>
          <select id="office_id" name="office_id">
            <option value="">Select recognized partner office (if listed)</option>
            @foreach($offices as $office)
              <option value="{{ $office->id }}" @selected(old('office_id') == $office->id)>
                {{ $office->name }}
              </option>
            @endforeach
          </select>
          <p class="field-hint">If your office is not listed in the options above, leave it unselected.</p>
        </div>

        <div>
          <label for="agency_name_claimed">
            <i class="fa-solid fa-id-card text-blue-600"></i> Official Agency / Department Name <span style="color: var(--red);">*</span>
          </label>
          <input type="text" id="agency_name_claimed" name="agency_name_claimed"
            value="{{ old('agency_name_claimed') }}"
            placeholder="e.g. Philippine National Police - Station 8, CSWDO Zamboanga" required>
        </div>
      </div>
    </div>

    {{-- ═══════════════ STEP 2: Representative Details ═══════════════ --}}
    <div class="wizard-step" id="step-2" style="display: none;">
      <div class="wizard-step-title">
        <div class="step-icon-wrap"><i class="fa-solid fa-user-tie"></i></div>
        <div>
          <h3>Representative Details</h3>
          <p>Contact person authorized to handle official barangay referrals.</p>
        </div>
      </div>

      <div class="login-form">
        <div>
          <label for="name">
            <i class="fa-solid fa-user text-blue-600"></i> Full Name <span style="color: var(--red);">*</span>
          </label>
          <input type="text" id="name" name="name" value="{{ old('name') }}"
            placeholder="e.g. Officer Juan Dela Cruz" required>
        </div>

        <div>
          <label for="position_title">
            <i class="fa-solid fa-briefcase text-blue-600"></i> Position / Official Title <span style="color: var(--red);">*</span>
          </label>
          <input type="text" id="position_title" name="position_title" value="{{ old('position_title') }}"
            placeholder="e.g. Desk Investigator / Social Welfare Officer" required>
        </div>

        <div>
          <label for="email">
            <i class="fa-solid fa-envelope text-blue-600"></i> Official Email Address <span style="color: var(--red);">*</span>
          </label>
          <input type="email" id="email" name="email" value="{{ old('email') }}"
            placeholder="official.email@agency.gov.ph" required>
          <p class="field-hint">Used for sign-in and referral notification alerts.</p>
        </div>

        <div>
          <label for="contact_number">
            <i class="fa-solid fa-phone text-blue-600"></i> Official Contact / Hotline Number <span style="color: var(--red);">*</span>
          </label>
          <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}"
            placeholder="09123456789 or (062) 991-0000" required>
        </div>
      </div>
    </div>

    {{-- ═══════════════ STEP 3: Password ═══════════════ --}}
    <div class="wizard-step" id="step-3" style="display: none;">
      <div class="wizard-step-title">
        <div class="step-icon-wrap"><i class="fa-solid fa-lock"></i></div>
        <div>
          <h3>Account Security</h3>
          <p>Set a secure password for your partner agency portal account.</p>
        </div>
      </div>

      <div class="login-form">
        <div>
          <label for="password">
            <i class="fa-solid fa-key text-blue-600"></i> Password <span style="color: var(--red);">*</span>
          </label>
          <input type="password" id="password" name="password" minlength="10"
            placeholder="At least 10 characters" required>
          <p class="field-hint">Must be at least 10 characters long.</p>
        </div>

        <div>
          <label for="password_confirmation">
            <i class="fa-solid fa-key text-blue-600"></i> Confirm Password <span style="color: var(--red);">*</span>
          </label>
          <input type="password" id="password_confirmation" name="password_confirmation" minlength="10"
            placeholder="Re-type password to confirm" required>
        </div>
      </div>
    </div>

    {{-- ═══════════════ STEP 4: Verification & Consent ═══════════════ --}}
    <div class="wizard-step" id="step-4" style="display: none;">
      <div class="wizard-step-title">
        <div class="step-icon-wrap"><i class="fa-solid fa-file-shield"></i></div>
        <div>
          <h3>Verification & Authorization</h3>
          <p>Upload credentials for barangay secretary review and approval.</p>
        </div>
      </div>

      <div class="login-form">
        <div>
          <label for="id_document">
            <i class="fa-solid fa-cloud-arrow-up text-blue-600"></i> Official Agency ID or Endorsement Letter <span style="color: var(--red);">*</span>
          </label>
          <input type="file" id="id_document" name="id_document" accept=".jpg,.jpeg,.png,.pdf"
            style="padding: 0.65rem 0.8rem;" required>
          <p class="field-hint">Accepted formats: JPG, PNG, PDF (up to 5 MB). Stored securely on private disk.</p>
        </div>

        <div style="background: var(--mist); padding: 1rem 1.15rem; border-radius: 12px; border: 1.5px solid var(--line); margin-top: 0.5rem;">
          <label style="display: flex; align-items: flex-start; gap: 0.75rem; cursor: pointer; margin: 0; font-weight: 500;">
            <input type="checkbox" id="consent" name="consent" value="1" @checked(old('consent'))
              style="width: 18px; min-height: 18px; margin-top: 2px; accent-color: var(--blue);" required>
            <span style="font-size: 0.84rem; color: var(--tx); line-height: 1.5;">
              I confirm that I am an authorized representative of the agency stated above. I consent to the verification of my agency credentials in accordance with the Data Privacy Act (RA 10173).
            </span>
          </label>
        </div>
      </div>
    </div>

    {{-- ═══════════════ Wizard Navigation Buttons ═══════════════ --}}
    <div class="wizard-btn-row">
      <a href="{{ route('login') }}" id="btn-back-to-login" class="btn btn-outline btn-small">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to Login
      </a>
      <button type="button" id="btn-prev-step" class="btn btn-outline btn-small" style="display: none;" onclick="wizardPrev()">
        <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back
      </button>

      <div style="flex: 1;"></div>

      <button type="button" id="btn-next-step" class="btn btn-gold btn-small" onclick="wizardNext()">
        Next Step <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
      </button>
      <button type="submit" id="btn-submit-form" class="btn btn-gold btn-small" style="display: none;">
        <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit Access Request
      </button>
    </div>
  </form>
</section>

@push('head')
<style>
  /* Wizard Progress Steps */
  .wizard-progress-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 2rem;
    padding-bottom: 1.25rem;
    border-bottom: 1.5px solid var(--line);
  }
  .wizard-step-indicator {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.35rem;
    cursor: default;
    user-select: none;
  }
  .indicator-badge {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: grid;
    place-items: center;
    font-size: 0.82rem;
    font-weight: 800;
    background: var(--mist);
    color: var(--mut);
    border: 2px solid var(--line);
    transition: all 0.25s ease;
  }
  .indicator-label {
    font-size: 0.72rem;
    font-weight: 700;
    color: var(--mut);
    transition: color 0.25s ease;
  }
  .wizard-step-indicator.active .indicator-badge {
    background: var(--blue);
    color: #fff;
    border-color: var(--blue);
    box-shadow: 0 0 0 4px rgb(36 86 214 / 18%);
  }
  .wizard-step-indicator.active .indicator-label {
    color: var(--blue);
    font-weight: 800;
  }
  .wizard-step-indicator.done .indicator-badge {
    background: #15803d;
    color: #fff;
    border-color: #15803d;
  }
  .wizard-step-indicator.done .indicator-label {
    color: #15803d;
  }
  .wizard-connector {
    flex: 1;
    height: 2px;
    background: var(--line);
    margin: 0 0.4rem 1rem;
    transition: background 0.25s ease;
  }
  .wizard-connector.done {
    background: #15803d;
  }

  /* Step Header */
  .wizard-step-title {
    display: flex;
    align-items: center;
    gap: 0.85rem;
    margin-bottom: 1.4rem;
  }
  .wizard-step-title h3 {
    margin: 0;
    font-size: 1.2rem;
    color: var(--ink);
  }
  .wizard-step-title p {
    margin: 0.15rem 0 0;
    font-size: 0.84rem;
    color: var(--mut);
  }
  .step-icon-wrap {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    background: #eef3ff;
    color: var(--blue);
    display: grid;
    place-items: center;
    font-size: 1.15rem;
    flex-shrink: 0;
  }

  /* Navigation Button Row */
  .wizard-btn-row {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    margin-top: 1.75rem;
    padding-top: 1.25rem;
    border-top: 1.5px solid var(--line);
  }
</style>
@endpush

@push('scripts')
<script>
  let currentStep = 1;
  const totalSteps = 4;

  function showStep(step) {
    currentStep = step;

    // Show/hide step panels
    for (let i = 1; i <= totalSteps; i++) {
      const stepEl = document.getElementById('step-' + i);
      if (stepEl) {
        stepEl.style.display = (i === currentStep) ? 'block' : 'none';
      }

      // Update indicator
      const indEl = document.getElementById('indicator-' + i);
      if (indEl) {
        indEl.classList.remove('active', 'done');
        if (i === currentStep) {
          indEl.classList.add('active');
        } else if (i < currentStep) {
          indEl.classList.add('done');
        }
      }

      // Update connector
      if (i < totalSteps) {
        const connEl = document.getElementById('connector-' + i);
        if (connEl) {
          connEl.classList.toggle('done', i < currentStep);
        }
      }
    }

    // Toggle navigation buttons
    const btnBackToLogin = document.getElementById('btn-back-to-login');
    const btnPrev = document.getElementById('btn-prev-step');
    const btnNext = document.getElementById('btn-next-step');
    const btnSubmit = document.getElementById('btn-submit-form');

    if (btnBackToLogin) btnBackToLogin.style.display = (currentStep === 1) ? 'inline-flex' : 'none';
    if (btnPrev) btnPrev.style.display = (currentStep > 1) ? 'inline-flex' : 'none';
    if (btnNext) btnNext.style.display = (currentStep < totalSteps) ? 'inline-flex' : 'none';
    if (btnSubmit) btnSubmit.style.display = (currentStep === totalSteps) ? 'inline-flex' : 'none';

    // Clear error
    hideWizardError();
  }

  function showWizardError(msg) {
    const errEl = document.getElementById('wizard-error-msg');
    if (errEl) {
      errEl.textContent = msg;
      errEl.style.display = 'block';
      errEl.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }
  }

  function hideWizardError() {
    const errEl = document.getElementById('wizard-error-msg');
    if (errEl) {
      errEl.style.display = 'none';
      errEl.textContent = '';
    }
  }

  function validateCurrentStep() {
    hideWizardError();

    if (currentStep === 1) {
      const agencyName = document.getElementById('agency_name_claimed').value.trim();
      if (!agencyName) {
        showWizardError('Please enter your official Agency or Department Name.');
        document.getElementById('agency_name_claimed').focus();
        return false;
      }
    } else if (currentStep === 2) {
      const name = document.getElementById('name').value.trim();
      const pos = document.getElementById('position_title').value.trim();
      const email = document.getElementById('email').value.trim();
      const contact = document.getElementById('contact_number').value.trim();

      if (!name) {
        showWizardError('Please enter the representative full name.');
        document.getElementById('name').focus();
        return false;
      }
      if (!pos) {
        showWizardError('Please enter the position or title.');
        document.getElementById('position_title').focus();
        return false;
      }
      if (!email || !email.includes('@')) {
        showWizardError('Please enter a valid official email address.');
        document.getElementById('email').focus();
        return false;
      }
      if (!contact) {
        showWizardError('Please enter an official contact or hotline number.');
        document.getElementById('contact_number').focus();
        return false;
      }
    } else if (currentStep === 3) {
      const pw = document.getElementById('password').value;
      const pwc = document.getElementById('password_confirmation').value;

      if (!pw || pw.length < 10) {
        showWizardError('Password must be at least 10 characters long.');
        document.getElementById('password').focus();
        return false;
      }
      if (pw !== pwc) {
        showWizardError('Passwords do not match. Please re-enter.');
        document.getElementById('password_confirmation').focus();
        return false;
      }
    }

    return true;
  }

  function wizardNext() {
    if (validateCurrentStep()) {
      showStep(Math.min(currentStep + 1, totalSteps));
    }
  }

  function wizardPrev() {
    showStep(Math.max(currentStep - 1, 1));
  }

  // Final submit validation
  document.getElementById('agency-register-form').addEventListener('submit', function (e) {
    const fileInput = document.getElementById('id_document');
    const consentBox = document.getElementById('consent');

    if (!fileInput.files || fileInput.files.length === 0) {
      e.preventDefault();
      showWizardError('Please upload an official Agency ID or Endorsement Document.');
      fileInput.focus();
      return false;
    }

    if (!consentBox.checked) {
      e.preventDefault();
      showWizardError('Please check the consent box to confirm authorized agency representation.');
      consentBox.focus();
      return false;
    }

    return true;
  });

  // Initialize on page load
  document.addEventListener('DOMContentLoaded', function () {
    @if($errors->has('id_document') || $errors->has('consent'))
      showStep(4);
    @elseif($errors->has('password') || $errors->has('password_confirmation'))
      showStep(3);
    @elseif($errors->has('name') || $errors->has('position_title') || $errors->has('email') || $errors->has('contact_number'))
      showStep(2);
    @else
      showStep(1);
    @endif
  });
</script>
@endpush
@endsection
