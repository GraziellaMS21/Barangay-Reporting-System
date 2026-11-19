@extends('layouts.app')
@section('title', 'Report Submitted - Barangay Calarian')

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <h1 data-i18n="hero_title">Thank you for letting us know</h1>
      <p data-i18n="hero_desc">Your report has been received by Barangay Calarian. Save the tracking code below to check for updates.</p>
    </div>
  </section>
@endsection

@section('content')
<style>
  .success-result-card {
    border: 1px solid #e2e8f0;
    border-top: 5px solid #22c55e;
    border-radius: 20px;
    padding: 2.5rem 2rem;
    background: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 20px 25px -5px rgba(11, 31, 75, 0.08);
    text-align: center;
    max-width: 680px;
    margin: 0 auto;
  }

  .success-icon-badge {
    width: 68px;
    height: 68px;
    border-radius: 50%;
    background: #dcfce7;
    color: #16a34a;
    display: inline-grid;
    place-items: center;
    font-size: 2.2rem;
    margin-bottom: 1.25rem;
    box-shadow: 0 0 0 8px #f0fdf4;
    animation: pulseCheck 1.5s infinite alternate;
  }

  @keyframes pulseCheck {
    from { transform: scale(1); box-shadow: 0 0 0 8px #f0fdf4; }
    to { transform: scale(1.04); box-shadow: 0 0 0 12px #dcfce7; }
  }

  .tracking-code-container {
    background: #f8fafc;
    border: 1.5px dashed #cbd5e1;
    border-radius: 14px;
    padding: 1.25rem;
    margin: 1.25rem auto;
    max-width: 440px;
  }

  .tracking-code-display {
    font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
    font-size: 1.85rem;
    font-weight: 800;
    color: var(--ink);
    letter-spacing: 0.05em;
    margin: 0.25rem 0 0.5rem;
  }

  .btn-copy-code-lg {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    background: #ffffff;
    border: 1.5px solid #cbd5e1;
    border-radius: 8px;
    min-height: 44px;
    padding: 0.5rem 1.15rem;
    font-size: 0.88rem;
    font-weight: 700;
    color: var(--ink);
    cursor: pointer;
    transition: all 0.2s;
  }

  .btn-copy-code-lg:hover {
    background: #eff6ff;
    border-color: #3b82f6;
    color: #1d4ed8;
    transform: translateY(-1px);
  }

  .success-actions-wrap {
    display: flex;
    justify-content: center;
    gap: 0.85rem;
    flex-wrap: wrap;
    margin-top: 1.75rem;
  }

  .emergency-disclaimer-card {
    background: #fff8f8;
    border: 1px solid #fee2e2;
    border-radius: 12px;
    padding: 1.15rem 1.25rem;
    margin-top: 2rem;
    text-align: left;
  }

  .emergency-chip-link {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: #ffffff;
    border: 1.5px solid #fecaca;
    border-radius: 10px;
    min-height: 44px;
    padding: 0.5rem 0.9rem;
    font-size: 0.88rem;
    color: #991b1b;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.2s;
  }

  .emergency-chip-link:hover {
    background: #fef2f2;
    border-color: #f87171;
    transform: translateY(-1px);
  }
</style>

<section class="card success-result-card">
  <div class="success-icon-badge" aria-hidden="true">
    <i class="fa-solid fa-circle-check"></i>
  </div>

  <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--ink); margin: 0 0 0.5rem;" data-i18n="success_title">
    Report Submitted Successfully!
  </h2>
  <p style="color: #64748b; font-size: 0.95rem; margin: 0;" data-i18n="success_desc">
    Your concern has been queued for barangay staff review.
  </p>

  <div class="tracking-code-container">
    <span style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; color: #64748b;" data-i18n="lbl_tracking_code">
      Your Unique Tracking Code
    </span>
    <div class="tracking-code-display" id="submitted-tracking-code">{{ $report->tracking_code }}</div>
    <button type="button" class="btn-copy-code-lg" onclick="copySuccessCode()" id="copy-btn-success" aria-label="Copy tracking code">
      <i class="fa-solid fa-copy" id="copy-icon-success" aria-hidden="true"></i>
      <span id="copy-text-success" data-i18n="btn_copy_code">Copy Tracking Code</span>
    </button>
    <div id="copy-aria-status" class="sr-only" aria-live="polite"></div>
  </div>

  <p style="font-size: 0.88rem; color: #64748b; margin: 0 auto; max-width: 420px;" data-i18n="save_code_hint">
    Save or take a screenshot of this code. You will need it to track resolution progress or communicate with staff.
  </p>

  <div class="success-actions-wrap">
    <a class="btn btn-gold" href="{{ route('track.show', $report->tracking_code) }}" style="font-weight: 800; min-height: 44px; padding: 0.65rem 1.4rem; display: inline-flex; align-items: center; gap: 0.5rem;" data-i18n="btn_track">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> <span>Track this report</span>
    </a>
    <a class="btn btn-outline" href="{{ route('reports.create') }}" style="font-weight: 700; min-height: 44px; padding: 0.65rem 1.4rem; display: inline-flex; align-items: center; gap: 0.5rem;" data-i18n="btn_new_report">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> <span>Submit another report</span>
    </a>
  </div>

  <div class="emergency-disclaimer-card">
    <div style="display: flex; align-items: center; gap: 0.5rem; color: #b91c1c; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;" data-i18n="urgent_notice_title">
      <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Urgent Notice:
    </div>
    <p style="font-size: 0.84rem; color: #7f1d1d; margin: 0 0 0.75rem; line-height: 1.4;" data-i18n="urgent_notice_text">
      This online system is not monitored in real time. If this is an active emergency or life-threatening event, call 911 or official hotlines directly.
    </p>

    @if($contacts->isNotEmpty())
      <div style="display: flex; flex-wrap: wrap; gap: 0.5rem;" aria-label="Emergency contacts">
        @foreach($contacts as $contact)
          @php $num = trim((string) $contact->number); @endphp
          @if($num !== '' && stripos($num, 'N/A') === false)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}" class="emergency-chip-link">
              <i class="fa-solid fa-phone" style="font-size: 0.8rem; color: var(--red);" aria-hidden="true"></i>
              <span><strong>{{ $contact->name }}</strong>: {{ $num }}</span>
            </a>
          @endif
        @endforeach
      </div>
    @endif
  </div>
</section>

<script>
const successI18n = {
  en: {
    hero_title: "Thank you for letting us know",
    hero_desc: "Your report has been received by Barangay Calarian. Save the tracking code below to check for updates.",
    success_title: "Report Submitted Successfully!",
    success_desc: "Your concern has been queued for barangay staff review.",
    lbl_tracking_code: "Your Unique Tracking Code",
    btn_copy_code: "Copy Tracking Code",
    copied_text: "Copied to Clipboard!",
    copied_aria: "Tracking code copied to clipboard",
    save_code_hint: "Save or take a screenshot of this code. You will need it to track resolution progress or communicate with staff.",
    btn_track: "Track this report",
    btn_new_report: "Submit another report",
    urgent_notice_title: "Urgent Notice:",
    urgent_notice_text: "This online system is not monitored in real time. If this is an active emergency or life-threatening event, call 911 or official hotlines directly."
  },
  fil: {
    hero_title: "Salamat sa pagpapaalam sa amin",
    hero_desc: "Natanggap na ng Barangay Calarian ang iyong ulat. I-save ang tracking code sa ibaba upang masubaybayan ang mga update.",
    success_title: "Matagumpay na Naisumite ang Ulat!",
    success_desc: "Ang iyong ulat ay nakapila na para sa pagsusuri ng kawani ng barangay.",
    lbl_tracking_code: "Ang Iyong Natatanging Tracking Code",
    btn_copy_code: "Kopyahin ang Tracking Code",
    copied_text: "Nakopya na sa Clipboard!",
    copied_aria: "Nakopya na ang tracking code sa clipboard",
    save_code_hint: "I-save o kumuha ng screenshot ng code na ito. Kakailanganin mo ito upang masubaybayan ang progreso o makipag-ugnayan sa kawani.",
    btn_track: "Subaybayan ang ulat na ito",
    btn_new_report: "Magsumite ng isa pang ulat",
    urgent_notice_title: "Mahalagang Paunawa:",
    urgent_notice_text: "Ang online system na ito ay hindi minomonitor nang real time. Kung ito ay emergency o may panganib sa buhay, tumawag agad sa 911 o sa opisyal na hotlines."
  }
};

let successLang = 'en';
try {
  const saved = localStorage.getItem('calarian_lang');
  if (saved && (saved === 'en' || saved === 'fil')) {
    successLang = saved;
  }
} catch (e) { }

function applySuccessTranslations(lang) {
  const dict = successI18n[lang] || successI18n.en;
  document.querySelectorAll('[data-i18n]').forEach((el) => {
    const key = el.getAttribute('data-i18n');
    if (dict[key]) {
      const span = el.querySelector('span');
      if (span) span.textContent = dict[key];
      else el.textContent = dict[key];
    }
  });
}

document.addEventListener('DOMContentLoaded', () => {
  applySuccessTranslations(successLang);

  try {
    const codeEl = document.getElementById('submitted-tracking-code');
    if (codeEl) {
      const code = codeEl.textContent.trim().toUpperCase();
      if (code) {
        let codes = JSON.parse(localStorage.getItem('calarian_recent_codes') || '[]');
        if (!Array.isArray(codes)) codes = [];
        codes = [code, ...codes.filter(c => c !== code)].slice(0, 5);
        localStorage.setItem('calarian_recent_codes', JSON.stringify(codes));
      }
    }
  } catch (e) { }
});

function copySuccessCode() {
  const codeEl = document.getElementById('submitted-tracking-code');
  const code = codeEl ? codeEl.textContent.trim() : '';
  if (!code) return;

  const dict = successI18n[successLang] || successI18n.en;
  const copyText = document.getElementById('copy-text-success');
  const copyIcon = document.getElementById('copy-icon-success');
  const ariaStatus = document.getElementById('copy-aria-status');

  const onCopySuccess = () => {
    if (copyText) copyText.textContent = dict.copied_text;
    if (copyIcon) copyIcon.className = 'fa-solid fa-check';
    if (ariaStatus) ariaStatus.textContent = dict.copied_aria;
    setTimeout(() => {
      if (copyText) copyText.textContent = dict.btn_copy_code;
      if (copyIcon) copyIcon.className = 'fa-solid fa-copy';
      if (ariaStatus) ariaStatus.textContent = '';
    }, 2500);
  };

  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(code).then(onCopySuccess).catch(() => fallbackCopy(code, onCopySuccess));
  } else {
    fallbackCopy(code, onCopySuccess);
  }
}

function fallbackCopy(text, callback) {
  const textarea = document.createElement('textarea');
  textarea.value = text;
  textarea.style.position = 'fixed';
  textarea.style.top = '-9999px';
  document.body.appendChild(textarea);
  textarea.focus();
  textarea.select();
  try {
    document.execCommand('copy');
    if (callback) callback();
  } catch (err) { }
  document.body.removeChild(textarea);
}
</script>
@endsection
