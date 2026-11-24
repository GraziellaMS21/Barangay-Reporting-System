@extends('layouts.admin')
@section('title', 'Log Phone Report')
@section('page-heading', 'Log phone report')

@section('content')
<div style="max-width:860px;margin:0 auto;padding-bottom:3rem;">
  {{-- Header with Back Button and Quick Jump --}}
  <div class="page-heading" style="margin-bottom:1.5rem;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
    <div>
      <div style="display:flex;align-items:center;gap:0.75rem;">
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline btn-small" style="text-decoration:none;min-height:44px;display:inline-flex;align-items:center;gap:0.4rem;">
          <i class="fa-solid fa-arrow-left"></i> Back to queue
        </a>
        <h2 style="margin:0;font-size:1.5rem;font-weight:700;color:var(--ink);">Log Hotline Phone Report</h2>
      </div>
      <p style="margin:0.35rem 0 0;color:#64748b;font-size:0.9rem;">
        Record an incoming phone complaint taken by barangay staff. Mandatory caller verbal consent must be obtained before filing.
      </p>
    </div>

    {{-- Secondary Link: Fill in manually instead --}}
    <a href="#step-2-form" id="btn-manual-instead" class="btn btn-outline btn-small" style="text-decoration:none;min-height:44px;display:inline-flex;align-items:center;gap:0.4rem;color:var(--blue);font-weight:600;">
      <i class="fa-solid fa-keyboard"></i> Fill in manually instead
    </a>
  </div>

  {{-- Screen reader live announcement region --}}
  <div id="phone-aria-announcer" class="sr-only" aria-live="polite" aria-atomic="true" style="position:absolute;width:1px;height:1px;padding:0;margin:-1px;overflow:hidden;clip:rect(0,0,0,0);border:0;"></div>

  <form id="phone-report-form" method="POST" action="{{ route('admin.reports.phone.store') }}" style="display:flex;flex-direction:column;gap:1.5rem;">
    @csrf

    {{-- ================================================================= --}}
    {{-- STEP 1: RECORD THE REPORT (AT THE TOP, PRIMARY ACTION)             --}}
    {{-- ================================================================= --}}
    <div class="step-1-card" style="background:#fff;border:1px solid var(--line);border-radius:0.75rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
      <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;margin-bottom:1rem;padding-bottom:0.75rem;border-bottom:1px solid #f1f5f9;">
        <div>
          <span style="display:inline-block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--blue);margin-bottom:0.2rem;">
            Step 1: Primary Action
          </span>
          <h3 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--ink);">
            Record the call (speakerphone) or dictate a summary
          </h3>
        </div>
        <span style="font-size:0.8rem;color:#64748b;background:#f1f5f9;padding:0.25rem 0.6rem;border-radius:9999px;font-weight:500;">
          <i class="fa-solid fa-wand-magic-sparkles" style="color:var(--blue);"></i> AI fills the form
        </span>
      </div>

      {{-- 1. Verbal Consent Gate (Mandatory, strictly ABOVE the recorder) --}}
      <div id="verbal-consent-box" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:0.5rem;padding:1.15rem;margin-bottom:1.25rem;">
        <label style="display:flex;align-items:flex-start;gap:0.75rem;cursor:pointer;">
          <input type="checkbox" name="verbal_consent" id="verbal_consent" value="1" required {{ old('verbal_consent') ? 'checked' : '' }}
                 style="margin-top:0.25rem;accent-color:var(--blue);width:1.25rem;height:1.25rem;cursor:pointer;flex-shrink:0;">
          <div>
            <strong style="color:#1e40af;font-size:0.95rem;display:block;">
              Caller Verbal Consent Obtained <span style="color:var(--red);">*</span>
            </strong>
            <p style="margin:0.25rem 0 0;font-size:0.85rem;color:#1e3a8a;line-height:1.4;">
              I confirm that I informed the caller that this report will be officially recorded and processed by Barangay Calarian, and they gave verbal consent to proceed under Republic Act 10173 (Data Privacy Act).
            </p>
          </div>
        </label>
        @error('verbal_consent')
          <p style="color:var(--red);font-size:0.85rem;margin:0.5rem 0 0;font-weight:600;">{{ $message }}</p>
        @enderror

        {{-- Consent Script for Staff to Read Aloud --}}
        <div style="margin-top:0.85rem;padding:0.75rem;background:#dbeafe;border:1px dashed #93c5fd;border-radius:0.375rem;">
          <strong style="font-size:0.8rem;color:#1e40af;display:flex;align-items:center;gap:0.35rem;">
            <i class="fa-solid fa-bullhorn"></i> Spoken script to read aloud to the caller:
          </strong>
          <p style="margin:0.35rem 0;font-size:0.85rem;color:#1e3a8a;font-style:italic;line-height:1.4;">
            "Barangay Calarian is officially recording this call to log your report and assist with resolution under Republic Act 10173 (Data Privacy Act). Do you give verbal consent to record and process your report?"
          </p>
          <span style="font-size:0.75rem;color:#2563eb;font-weight:500;">
            Wait for the caller's "Yes" before starting recording. The verbal consent confirmation should be audible at the start of the audio.
          </span>
        </div>
      </div>

      {{-- 2. Audio Recorder Container (Disabled until consent checkbox is checked) --}}
      <div id="recorder-container-wrapper" style="padding:1rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.5rem;">
        <div id="consent-lock-hint" style="margin-bottom:0.75rem;padding:0.6rem 0.85rem;background:#fef3c7;border:1px solid #fde68a;border-radius:0.375rem;color:#92400e;font-size:0.85rem;display:flex;align-items:center;gap:0.5rem;">
          <i class="fa-solid fa-lock"></i>
          <span>Check the verbal consent box above to unlock audio recording and file upload.</span>
        </div>

        <p style="margin:0 0 0.75rem;font-size:0.85rem;color:#475569;">
          Record call on speakerphone or dictate a summary after the call. Press <strong>Stop & Transcribe</strong> when finished.
        </p>

        {{-- Embedded Audio Recorder Component configured for Staff Hotline Intake --}}
        <x-voice.recorder
          target-input="phone-description"
          origin="staff"
          :max-seconds="120"
          :analyze-url="route('admin.reports.phone.analyze')"
          consent-checkbox-id="verbal_consent"
        />

        {{-- Dynamic Clarity Banners --}}
        {{-- Banner A: Partial Clarity --}}
        <div id="banner-partial-clarity" style="display:none;margin-top:0.75rem;padding:0.75rem 1rem;border-radius:0.5rem;background:#fffbeb;border:1px solid #fde68a;color:#92400e;font-size:0.875rem;">
          <div style="display:flex;align-items:flex-start;gap:0.5rem;">
            <i class="fa-solid fa-triangle-exclamation" style="margin-top:0.15rem;color:#d97706;flex-shrink:0;"></i>
            <div>
              <strong>Parts of this recording were unclear.</strong>
              <span id="banner-partial-text" style="display:block;margin-top:0.2rem;">Please listen again and check every pre-filled field carefully before submitting.</span>
            </div>
          </div>
        </div>

        {{-- Banner B: Unclear Audio / Needs Verification --}}
        <div id="banner-unclear-clarity" style="display:none;margin-top:0.75rem;padding:0.75rem 1rem;border-radius:0.5rem;background:#fef2f2;border:1px solid #fecaca;color:#991b1b;font-size:0.875rem;">
          <div style="display:flex;align-items:flex-start;gap:0.5rem;">
            <i class="fa-solid fa-circle-exclamation" style="margin-top:0.15rem;color:#dc2626;flex-shrink:0;"></i>
            <div>
              <strong>We couldn't understand this recording clearly.</strong>
              <span style="display:block;margin-top:0.2rem;">Listen again and enter the details. Staff act as the audio verifier before routing.</span>
            </div>
          </div>
        </div>

        {{-- Banner C: Emergency Detected Alert --}}
        <div id="banner-emergency-detected" style="display:none;margin-top:0.75rem;padding:0.85rem 1rem;border-radius:0.5rem;background:#fff1f2;border:1px solid #fda4af;color:#9f1239;font-size:0.875rem;">
          <div style="display:flex;align-items:flex-start;gap:0.5rem;">
            <i class="fa-solid fa-triangle-exclamation" style="margin-top:0.15rem;color:#e11d48;font-size:1.1rem;flex-shrink:0;"></i>
            <div>
              <strong>Life / Safety Emergency Detected in Recording!</strong>
              <span style="display:block;margin-top:0.2rem;">If someone is in danger right now, immediately coordinate with 911 / CDRRMO / Police hotlines.</span>
            </div>
          </div>
        </div>

        {{-- Collapsible Staff-Only "What was said" Transcript Panel --}}
        <details id="staff-transcript-panel" style="display:none;margin-top:0.75rem;background:#ffffff;border:1px solid #cbd5e1;border-radius:0.5rem;padding:0.75rem 1rem;">
          <summary style="cursor:pointer;font-weight:600;font-size:0.875rem;color:var(--ink);display:flex;align-items:center;justify-content:space-between;user-select:none;">
            <span><i class="fa-solid fa-comment-dots" style="color:var(--blue);margin-right:0.4rem;"></i> What was said (Staff-only verbatim transcript)</span>
            <span style="font-size:0.75rem;font-weight:600;background:#e0e7ff;color:#3730a3;padding:0.15rem 0.5rem;border-radius:9999px;">Staff only</span>
          </summary>
          <div id="staff-transcript-content" style="margin-top:0.6rem;padding:0.75rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:0.375rem;font-size:0.875rem;font-family:monospace;white-space:pre-wrap;color:#334155;line-height:1.5;"></div>
          <span style="display:block;margin-top:0.35rem;font-size:0.75rem;color:#64748b;">
            <i class="fa-solid fa-shield-halved"></i> Raw speech transcript is visible to staff only and is never published or re-sent in text triage prompts.
          </span>
        </details>
      </div>
    </div>

    {{-- ================================================================= --}}
    {{-- STEP 2: CHECK THE DETAILS (PRE-FILLED FORM)                        --}}
    {{-- ================================================================= --}}
    <div id="step-2-form" style="background:#fff;border:1px solid var(--line);border-radius:0.75rem;padding:1.5rem;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
      <div style="margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:1px solid #f1f5f9;">
        <span style="display:inline-block;font-size:0.75rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:var(--blue);margin-bottom:0.2rem;">
          Step 2: Verification
        </span>
        <h3 style="margin:0;font-size:1.15rem;font-weight:700;color:var(--ink);">
          Check and confirm the details
        </h3>
        <p style="margin:0.25rem 0 0;font-size:0.85rem;color:#64748b;">
          Every field filled from the recording is labeled <span style="display:inline-flex;align-items:center;gap:0.25rem;font-size:0.75rem;font-weight:600;padding:0.1rem 0.4rem;border-radius:0.25rem;background:#dbeafe;color:#1e40af;"><i class="fa-solid fa-microphone"></i> From recording</span> and stays fully editable. Unstated fields show <em>Not provided</em>.
        </p>
      </div>

      {{-- 1. Caller Information --}}
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;margin-bottom:1.25rem;">
        <div>
          <label for="caller_name" style="display:flex;align-items:center;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
            <span>Caller Name</span>
            <span style="color:#64748b;font-weight:400;margin-left:0.35rem;font-size:0.8rem;">(optional)</span>
            <span class="badge-slot" id="badge_caller_name"></span>
          </label>
          <input type="text" id="caller_name" name="caller_name" value="{{ old('caller_name') }}"
                 placeholder="Not provided"
                 style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;min-height:44px;">
          @error('caller_name')
            <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="caller_phone" style="display:flex;align-items:center;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
            <span>Caller Phone / Mobile</span>
            <span style="color:#64748b;font-weight:400;margin-left:0.35rem;font-size:0.8rem;">(optional)</span>
            <span class="badge-slot" id="badge_caller_phone"></span>
          </label>
          <input type="text" id="caller_phone" name="caller_phone" value="{{ old('caller_phone') }}"
                 placeholder="Not provided"
                 style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;min-height:44px;">
          @error('caller_phone')
            <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
          @enderror
        </div>
      </div>

      {{-- 2. Location --}}
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;margin-bottom:1.25rem;">
        <div>
          <label for="purok_id" style="display:flex;align-items:center;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
            <span>Purok <span style="color:var(--red);">*</span></span>
            <span class="badge-slot" id="badge_purok_id"></span>
          </label>
          <select id="purok_id" name="purok_id" required
                  style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;background:#fff;min-height:44px;">
            <option value="">Select purok</option>
            @foreach($puroks as $purok)
              <option value="{{ $purok->id }}" {{ old('purok_id') == $purok->id ? 'selected' : '' }}>
                {{ $purok->name }}
              </option>
            @endforeach
          </select>
          <p id="helper-purok-not-mentioned" style="display:none;color:#b45309;font-size:0.8rem;margin-top:0.25rem;">
            <i class="fa-solid fa-circle-info"></i> Not mentioned in the recording, please choose it.
          </p>
          @error('purok_id')
            <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
          @enderror
        </div>

        <div>
          <label for="landmark" style="display:flex;align-items:center;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
            <span>Landmark or Specific Street</span>
            <span class="badge-slot" id="badge_landmark"></span>
          </label>
          <input type="text" id="landmark" name="landmark" value="{{ old('landmark') }}"
                 placeholder="Not provided"
                 style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;min-height:44px;">
          @error('landmark')
            <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
          @enderror
        </div>
      </div>

      {{-- 3. Category & Urgency --}}
      <div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(260px, 1fr));gap:1.25rem;margin-bottom:1.25rem;">
        <div>
          <label for="category_id" style="display:flex;align-items:center;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
            <span>Category</span>
            <span class="badge-slot" id="badge_category_id"></span>
          </label>
          <select id="category_id" name="category_id"
                  style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;background:#fff;min-height:44px;">
            <option value="">Select category (optional)</option>
            @foreach($categories as $category)
              <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                {{ $category->name }}
              </option>
            @endforeach
          </select>
        </div>

        <div>
          <label for="urgency" style="display:flex;align-items:center;font-size:0.875rem;font-weight:600;color:var(--ink);margin-bottom:0.35rem;">
            <span>Urgency</span>
            <span class="badge-slot" id="badge_urgency"></span>
          </label>
          <select id="urgency" name="urgency"
                  style="width:100%;padding:0.65rem 0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;background:#fff;min-height:44px;">
            <option value="Low" {{ old('urgency') === 'Low' ? 'selected' : '' }}>Low</option>
            <option value="Medium" {{ old('urgency', 'Medium') === 'Medium' ? 'selected' : '' }}>Medium</option>
            <option value="High" {{ old('urgency') === 'High' ? 'selected' : '' }}>High</option>
            <option value="Critical" {{ old('urgency') === 'Critical' ? 'selected' : '' }}>Critical</option>
          </select>
        </div>
      </div>

      {{-- 4. Problem Description (Receives AI description without PII, never raw transcript) --}}
      <div style="margin-bottom:1.5rem;">
        <label for="phone-description" style="display:flex;align-items:center;font-size:0.875rem;font-weight:700;color:var(--ink);margin-bottom:0.35rem;">
          <span>Incident Description <span style="color:var(--red);">*</span></span>
          <span class="badge-slot" id="badge_description"></span>
        </label>
        <textarea id="phone-description" name="description" rows="4" required minlength="5"
                  placeholder="Not provided"
                  style="width:100%;padding:0.75rem;border:1px solid var(--line);border-radius:0.375rem;font-size:0.9rem;line-height:1.5;">{{ old('description') }}</textarea>
        <p id="helper-description-not-mentioned" style="display:none;color:#b45309;font-size:0.8rem;margin-top:0.25rem;">
          <i class="fa-solid fa-circle-info"></i> Not mentioned in the recording, please enter details.
        </p>
        @error('description')
          <p style="color:var(--red);font-size:0.8rem;margin-top:0.25rem;">{{ $message }}</p>
        @enderror
      </div>

      {{-- 5. Emergency Checkbox --}}
      <div id="emergency-check-wrapper" style="margin-bottom:1.75rem;padding:0.85rem 1rem;background:#fff1f2;border:1px solid #fecdd3;border-radius:0.5rem;">
        <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;">
          <input type="checkbox" id="is_emergency" name="is_emergency" value="1" {{ old('is_emergency') ? 'checked' : '' }}
                 style="accent-color:var(--red);width:1.25rem;height:1.25rem;cursor:pointer;flex-shrink:0;">
          <div>
            <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
              <strong style="color:#9f1239;font-size:0.9rem;">Mark as Emergency</strong>
              <span class="badge-slot" id="badge_emergency"></span>
            </div>
            <span style="display:block;color:#be123c;font-size:0.8rem;margin-top:0.15rem;">
              Flag for immediate attention by Punong Barangay or Tanod.
            </span>
          </div>
        </label>
      </div>

      {{-- Submit / Cancel Actions --}}
      <div style="display:flex;justify-content:flex-end;align-items:center;gap:1rem;border-top:1px solid var(--line);padding-top:1.25rem;">
        <a href="{{ route('admin.reports.index') }}" class="btn btn-outline" style="text-decoration:none;min-height:44px;display:inline-flex;align-items:center;">
          Cancel
        </a>
        <button type="submit" class="btn btn-primary" style="padding:0.75rem 1.75rem;font-weight:700;min-height:44px;display:inline-flex;align-items:center;gap:0.5rem;font-size:0.95rem;">
          <i class="fa-solid fa-phone"></i> Log Phone Report
        </button>
      </div>
    </div>
  </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
  const form = document.getElementById('phone-report-form');
  if (!form) return;

  const verbalConsentCb = document.getElementById('verbal_consent');
  const recorderWrapper = document.getElementById('recorder-container-wrapper');
  const consentLockHint = document.getElementById('consent-lock-hint');
  const announcer = document.getElementById('phone-aria-announcer');

  const bannerPartial = document.getElementById('banner-partial-clarity');
  const bannerUnclear = document.getElementById('banner-unclear-clarity');
  const bannerEmergency = document.getElementById('banner-emergency-detected');
  const transcriptPanel = document.getElementById('staff-transcript-panel');
  const transcriptContent = document.getElementById('staff-transcript-content');

  const inputName = document.getElementById('caller_name');
  const inputPhone = document.getElementById('caller_phone');
  const selectPurok = document.getElementById('purok_id');
  const inputLandmark = document.getElementById('landmark');
  const selectCategory = document.getElementById('category_id');
  const selectUrgency = document.getElementById('urgency');
  const textareaDesc = document.getElementById('phone-description');
  const checkEmergency = document.getElementById('is_emergency');

  const helperPurok = document.getElementById('helper-purok-not-mentioned');
  const helperDesc = document.getElementById('helper-description-not-mentioned');

  // Track user edits so re-recordings never overwrite manually typed fields
  const allInputs = [inputName, inputPhone, selectPurok, inputLandmark, selectCategory, selectUrgency, textareaDesc, checkEmergency];
  allInputs.forEach(el => {
    if (!el) return;
    el.addEventListener('input', () => markFieldEdited(el));
    el.addEventListener('change', () => markFieldEdited(el));
  });

  function markFieldEdited(el) {
    el.dataset.userEdited = 'true';
    removeBadge(el.id);
    if (el === selectPurok && helperPurok && el.value) helperPurok.style.display = 'none';
    if (el === textareaDesc && helperDesc && el.value.trim().length >= 5) helperDesc.style.display = 'none';
  }

  function announce(msg) {
    if (announcer) {
      announcer.textContent = msg;
    }
  }

  function getBadgeSlot(fieldId) {
    return document.getElementById('badge_' + fieldId);
  }

  function showBadge(fieldId, customText = 'From recording') {
    const slot = getBadgeSlot(fieldId);
    if (!slot) return;
    slot.innerHTML = `
      <span class="from-recording-pill" style="display:inline-flex;align-items:center;gap:0.3rem;font-size:0.75rem;font-weight:600;padding:0.15rem 0.45rem;border-radius:0.25rem;background:#dbeafe;color:#1e40af;margin-left:0.5rem;">
        <i class="fa-solid fa-microphone"></i> ${customText}
      </span>
    `;
  }

  function removeBadge(fieldId) {
    const slot = getBadgeSlot(fieldId);
    if (slot) slot.innerHTML = '';
  }

  // Verbal consent gate synchronization
  function syncConsentGate() {
    if (!verbalConsentCb) return;
    const isGranted = verbalConsentCb.checked;
    if (recorderWrapper) {
      recorderWrapper.style.opacity = isGranted ? '1' : '0.5';
      recorderWrapper.style.pointerEvents = isGranted ? 'auto' : 'none';
    }
    if (consentLockHint) {
      consentLockHint.style.display = isGranted ? 'none' : 'flex';
    }
  }

  if (verbalConsentCb) {
    verbalConsentCb.addEventListener('change', syncConsentGate);
    syncConsentGate();
  }

  // Handle Voice Analyzed event from recorder
  window.addEventListener('voice-analyzed', function(e) {
    const data = e.detail;
    if (!data) return;

    // 1. Reset banners
    if (bannerPartial) bannerPartial.style.display = 'none';
    if (bannerUnclear) bannerUnclear.style.display = 'none';
    if (bannerEmergency) bannerEmergency.style.display = 'none';

    // 2. Transcript panel (Staff only)
    if (data.transcript && transcriptContent && transcriptPanel) {
      transcriptContent.textContent = data.transcript;
      transcriptPanel.style.display = 'block';
    }

    // 3. Handle Unclear / Failed Audio
    if (data.status === 'needs_verification' || data.is_unclear || data.clarity === 'unclear' || data.status === 'failed') {
      if (bannerUnclear) bannerUnclear.style.display = 'block';
      announce("We couldn't understand this recording clearly. Please listen again and enter details manually.");

      // Uncertainty rule: do not pre-fill guessed text
      if (helperPurok && (!selectPurok.value || selectPurok.dataset.aiFilled === 'true')) {
        helperPurok.style.display = 'block';
      }
      if (helperDesc && (!textareaDesc.value || textareaDesc.dataset.aiFilled === 'true')) {
        helperDesc.style.display = 'block';
      }
      return;
    }

    // 4. Handle Partial Audio Notice
    if (data.clarity === 'partial' || data.is_partial) {
      if (bannerPartial) bannerPartial.style.display = 'block';
    }

    // 5. Handle Emergency Prompt
    if (data.emergency) {
      if (bannerEmergency) bannerEmergency.style.display = 'block';
      if (checkEmergency && checkEmergency.dataset.userEdited !== 'true') {
        checkEmergency.checked = true;
        showBadge('emergency', 'From recording, please confirm');
        checkEmergency.dataset.aiFilled = 'true';
      }
    }

    let filledCount = 0;

    // Helper to safely fill field
    function prefillField(el, value, fieldId) {
      if (!el) return;
      if (el.dataset.userEdited === 'true') return; // Do not overwrite user edits!

      if (value !== null && value !== undefined && value !== '') {
        el.value = value;
        el.dataset.aiFilled = 'true';
        showBadge(fieldId, 'From recording');
        filledCount++;
      } else {
        if (el.dataset.aiFilled === 'true') {
          el.value = '';
          el.dataset.aiFilled = 'false';
          removeBadge(fieldId);
        }
      }
    }

    // Caller Name
    prefillField(inputName, data.caller_name, 'caller_name');

    // Caller Phone
    prefillField(inputPhone, data.caller_phone, 'caller_phone');

    // Purok
    if (selectPurok && selectPurok.dataset.userEdited !== 'true') {
      if (data.purok_id) {
        selectPurok.value = data.purok_id;
        selectPurok.dataset.aiFilled = 'true';
        showBadge('purok_id', 'From recording');
        if (helperPurok) helperPurok.style.display = 'none';
        filledCount++;
      } else {
        if (selectPurok.dataset.aiFilled === 'true') {
          selectPurok.value = '';
          selectPurok.dataset.aiFilled = 'false';
          removeBadge('purok_id');
        }
        if (helperPurok) helperPurok.style.display = 'block';
      }
    }

    // Landmark
    prefillField(inputLandmark, data.landmark, 'landmark');

    // Category
    if (selectCategory && selectCategory.dataset.userEdited !== 'true') {
      if (data.category_id) {
        selectCategory.value = data.category_id;
        selectCategory.dataset.aiFilled = 'true';
        showBadge('category_id', 'From recording');
        filledCount++;
      }
    }

    // Urgency
    if (selectUrgency && selectUrgency.dataset.userEdited !== 'true') {
      if (data.urgency) {
        selectUrgency.value = data.urgency;
        selectUrgency.dataset.aiFilled = 'true';
        showBadge('urgency', 'From recording');
        filledCount++;
      }
    }

    // Description (receives data.description - PII removed, never raw transcript)
    if (textareaDesc && textareaDesc.dataset.userEdited !== 'true') {
      if (data.description && data.description.trim() !== '') {
        textareaDesc.value = data.description;
        textareaDesc.dataset.aiFilled = 'true';
        showBadge('description', 'From recording');
        if (helperDesc) helperDesc.style.display = 'none';
        filledCount++;
      } else {
        if (helperDesc) helperDesc.style.display = 'block';
      }
    }

    announce(`Filled ${filledCount} fields from your recording; please check them.`);

    // Smooth scroll to Step 2 so staff can review pre-filled fields
    const step2 = document.getElementById('step-2-form');
    if (step2) {
      step2.scrollIntoView({ behavior: 'smooth', block: 'start' });
    }
  });

  // Handle Reset Event
  window.addEventListener('voice-reset', function() {
    if (bannerPartial) bannerPartial.style.display = 'none';
    if (bannerUnclear) bannerUnclear.style.display = 'none';
    if (bannerEmergency) bannerEmergency.style.display = 'none';
    if (transcriptPanel) transcriptPanel.style.display = 'none';

    allInputs.forEach(el => {
      if (el && el.dataset.aiFilled === 'true') {
        el.value = '';
        el.dataset.aiFilled = 'false';
        removeBadge(el.id);
      }
    });
    if (helperPurok) helperPurok.style.display = 'none';
    if (helperDesc) helperDesc.style.display = 'none';
    announce("Recording discarded. Form reset.");
  });

  // Manual jump button
  const btnManual = document.getElementById('btn-manual-instead');
  if (btnManual) {
    btnManual.addEventListener('click', function(e) {
      e.preventDefault();
      const step2 = document.getElementById('step-2-form');
      if (step2) {
        step2.scrollIntoView({ behavior: 'smooth', block: 'start' });
        if (inputName) inputName.focus();
      }
    });
  }
});
</script>
@endsection
