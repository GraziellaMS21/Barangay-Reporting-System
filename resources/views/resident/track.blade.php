@extends('layouts.app')
@section('title', 'Track a Report - Barangay Calarian')

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <h1 data-i18n="hero_title">Track a report</h1>
      <p data-i18n="hero_desc">Enter the tracking code you received after submitting your report to see its latest status and updates.</p>
      <div class="hero-actions">
        <a class="btn btn-ghost" href="{{ route('reports.create') }}">
          <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> <span data-i18n="hero_btn_back">Back to report form</span>
        </a>
      </div>
    </div>
  </section>
@endsection

@section('content')
  <style>
    .track-search-card {
      border: 1px solid #e2e8f0;
      border-radius: 20px;
      padding: 2.25rem 2rem;
      background: #ffffff;
      box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 20px 25px -5px rgba(11, 31, 75, 0.08);
      max-width: 680px;
      margin: 0 auto 1.75rem;
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

    .track-input-group {
      display: flex;
      gap: 0.65rem;
      align-items: stretch;
      margin-top: 1rem;
    }

    @media (max-width: 600px) {
      .track-input-group {
        flex-direction: column;
      }
    }

    .track-input-field {
      flex: 1;
      min-height: 52px;
      border: 1.5px solid #cbd5e1;
      border-radius: 12px;
      padding: 0.75rem 1.15rem;
      font-size: 1.1rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      color: var(--ink);
      text-transform: uppercase;
      letter-spacing: 0.04em;
      transition: all 0.2s;
    }

    .track-input-field:focus {
      border-color: var(--blue);
      box-shadow: 0 0 0 4px rgba(36, 86, 214, 0.12);
      outline: none;
    }

    .recent-code-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      min-height: 44px;
      padding: 0.4rem 0.85rem;
      border: 1px solid #cbd5e1;
      border-radius: 999px;
      background: #f8fafc;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-size: 0.85rem;
      font-weight: 700;
      color: var(--blue);
      cursor: pointer;
      transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    }

    .recent-code-chip:hover {
      background: #eff6ff;
      border-color: #93c5fd;
      color: #1d4ed8;
      transform: translateY(-1px);
      box-shadow: 0 2px 5px rgba(37, 99, 235, 0.1);
    }

    .track-help-card {
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      padding: 1.75rem;
      background: #ffffff;
      box-shadow: 0 2px 4px rgba(11, 31, 75, 0.03);
      max-width: 680px;
      margin: 0 auto;
    }

    .help-step-item {
      display: flex;
      align-items: flex-start;
      gap: 0.85rem;
      padding: 0.75rem 0;
      border-bottom: 1px solid #f1f5f9;
    }

    .help-step-item:last-child {
      border-bottom: none;
      padding-bottom: 0;
    }

    .help-step-num {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: #eff6ff;
      color: var(--blue);
      font-weight: 800;
      font-size: 0.82rem;
      display: grid;
      place-items: center;
      flex-shrink: 0;
      margin-top: 0.1rem;
    }
  </style>

  @if($errors->any())
    <div role="alert" class="form-errors" style="max-width: 680px; margin: 0 auto 1.25rem; border-radius: 12px; padding: 1rem 1.25rem;">
      <div style="display:flex;align-items:center;gap:0.5rem;font-weight:700;">
        <i class="fa-solid fa-circle-exclamation"></i> {{ $errors->first() }}
      </div>
    </div>
  @endif

  <!-- Search Card -->
  <section class="card track-search-card">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.75rem;flex-wrap:wrap;gap:0.75rem;">
      <div style="display:flex;align-items:center;gap:0.75rem;">
        <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;display:grid;place-items:center;color:var(--blue);font-size:1.1rem;">
          <i class="fa-solid fa-magnifying-glass"></i>
        </div>
        <div>
          <h2 style="font-size:1.25rem;font-weight:800;color:var(--ink);margin:0;" data-i18n="track_title">Look Up Your Report</h2>
          <p style="margin:0.15rem 0 0;font-size:0.88rem;color:#64748b;" data-i18n="track_desc">Check live review, office assignments, and resolution proof</p>
        </div>
      </div>

      <div class="lang-segmented-control" role="group" aria-label="Select Language">
        <button type="button" class="lang-segmented-btn js-track-lang-en active" onclick="setTrackLanguage('en')">EN</button>
        <button type="button" class="lang-segmented-btn js-track-lang-fil" onclick="setTrackLanguage('fil')">FIL</button>
      </div>
    </div>

    <form id="track-form" method="POST" action="{{ route('track.search') }}" style="margin-top:1.25rem;">
      @csrf
      <label class="sr-only" for="tracking_code" data-i18n="lbl_sr_tracking_code">Tracking code</label>
      <div class="track-input-group">
        <input id="tracking_code" class="track-input-field" type="text" name="tracking_code" value="{{ old('tracking_code') }}"
          placeholder="e.g. BRG-AB12CD" autocomplete="off" required maxlength="30"
          oninput="this.value = this.value.toUpperCase().replace(/\s+/g, '')">
        <button type="submit" id="btn-track-submit" class="btn btn-navy" style="min-height:52px;padding:0 1.5rem;font-weight:800;font-size:0.95rem;">
          <i class="fa-solid fa-arrow-right" aria-hidden="true"></i> <span id="track-btn-text" data-i18n="btn_check_status">Check Status</span>
        </button>
      </div>
      <p class="field-hint" style="margin-top:0.6rem;font-size:0.82rem;color:#64748b;">
        <span data-i18n="hint_example">Example format:</span> <code style="background:#f1f5f9;padding:0.15rem 0.4rem;border-radius:4px;color:var(--blue);font-weight:600;">BRG-XXXXXX</code>
      </p>

      <!-- Recent Tracking Codes on this device -->
      <div id="recent-codes-wrap" style="display:none;margin-top:1.25rem;padding-top:0.85rem;border-top:1px dashed #e2e8f0;">
        <span style="font-size:0.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:0.04em;display:flex;align-items:center;gap:0.35rem;margin-bottom:0.5rem;" data-i18n="recent_codes_label">
          <i class="fa-solid fa-clock-rotate-left"></i> Recently checked on this device:
        </span>
        <div id="recent-codes-list" style="display:flex;flex-wrap:wrap;gap:0.5rem;"></div>
      </div>
    </form>
  </section>

  <!-- Help & Instructions Card -->
  <section class="card track-help-card">
    <h3 class="card-title" style="font-size:1.1rem;font-weight:800;color:var(--ink);margin-bottom:1rem;" data-i18n="help_title">
      <i class="fa-solid fa-circle-question" style="color:var(--blue);"></i> Where can I find my tracking code?
    </h3>

    <div>
      <div class="help-step-item">
        <div class="help-step-num">1</div>
        <div>
          <strong style="color:var(--ink);font-size:0.92rem;display:block;" data-i18n="step1_title">Confirmation Screen</strong>
          <span style="font-size:0.86rem;color:#64748b;" data-i18n="step1_desc">Your tracking code was shown on the success screen immediately after you submitted your report.</span>
        </div>
      </div>

      <div class="help-step-item">
        <div class="help-step-num">2</div>
        <div>
          <strong style="color:var(--ink);font-size:0.92rem;display:block;" data-i18n="step2_title">Personal Notes or Screenshot</strong>
          <span style="font-size:0.86rem;color:#64748b;" data-i18n="step2_desc">If you took a screenshot or copied the code when reporting, check your saved photos or clipboard.</span>
        </div>
      </div>

      <div class="help-step-item">
        <div class="help-step-num">3</div>
        <div>
          <strong style="color:var(--ink);font-size:0.92rem;display:block;" data-i18n="step3_title">Contact the Barangay Hall</strong>
          <span style="font-size:0.86rem;color:#64748b;" data-i18n="step3_desc">If you misplaced your code, you can contact the Barangay Calarian office with your name and reported location.</span>
        </div>
      </div>
    </div>
  </section>

  <script>
    const trackI18n = {
      en: {
        hero_title: "Track a report",
        hero_desc: "Enter the tracking code you received after submitting your report to see its latest status and updates.",
        hero_btn_back: "Back to report form",
        track_title: "Look Up Your Report",
        track_desc: "Check live review, office assignments, and resolution proof",
        lbl_sr_tracking_code: "Tracking code",
        placeholder_code: "e.g. BRG-AB12CD",
        btn_check_status: "Check Status",
        btn_checking: "Checking...",
        hint_example: "Example format:",
        recent_codes_label: "Recently checked on this device:",
        help_title: "Where can I find my tracking code?",
        step1_title: "Confirmation Screen",
        step1_desc: "Your tracking code was shown on the success screen immediately after you submitted your report.",
        step2_title: "Personal Notes or Screenshot",
        step2_desc: "If you took a screenshot or copied the code when reporting, check your saved photos or clipboard.",
        step3_title: "Contact the Barangay Hall",
        step3_desc: "If you misplaced your code, you can contact the Barangay Calarian office with your name and reported location."
      },
      fil: {
        hero_title: "Subaybayan ang ulat",
        hero_desc: "Ilagay ang tracking code na natanggap mo matapos magsumite ng ulat upang makita ang pinakabagong estado at mga update.",
        hero_btn_back: "Bumalik sa form ng ulat",
        track_title: "Hanapin ang Iyong Ulat",
        track_desc: "Suriin ang pagsusuri ng kawani, itinalagang tanggapan, at patunay ng pagkakaayos",
        lbl_sr_tracking_code: "Tracking code",
        placeholder_code: "hal. BRG-AB12CD",
        btn_check_status: "Suriin ang Estado",
        btn_checking: "Sinusuri...",
        hint_example: "Halimbawa ng format:",
        recent_codes_label: "Kamakailang sinuri sa device na ito:",
        help_title: "Saan ko mahahanap ang aking tracking code?",
        step1_title: "Screen ng Pagkumpirma",
        step1_desc: "Ang iyong tracking code ay ipinakita sa success screen matapos mong isumite ang iyong ulat.",
        step2_title: "Mga Tala o Screenshot",
        step2_desc: "Kung kumuha ka ng screenshot o kinopya ang code noong nag-ulat ka, tingnan ang iyong mga naka-save na larawan o clipboard.",
        step3_title: "Makipag-ugnayan sa Barangay Hall",
        step3_desc: "Kung naiwala mo ang iyong code, maaari kang makipag-ugnayan sa tanggapan ng Barangay Calarian kasama ang iyong pangalan at lokasyon."
      }
    };

    let trackLang = 'en';

    function setTrackLanguage(lang) {
      trackLang = lang;
      document.querySelectorAll('.js-track-lang-en').forEach(b => b.classList.toggle('active', lang === 'en'));
      document.querySelectorAll('.js-track-lang-fil').forEach(b => b.classList.toggle('active', lang === 'fil'));

      const dict = trackI18n[lang] || trackI18n.en;
      document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (dict[key]) {
          const span = el.querySelector('span');
          if (span) span.textContent = dict[key];
          else el.textContent = dict[key];
        }
      });

      const inp = document.getElementById('tracking_code');
      if (inp) {
        inp.placeholder = dict.placeholder_code;
      }

      try {
        localStorage.setItem('calarian_lang', lang);
      } catch (e) { }
    }

    function renderRecentCodes() {
      const wrap = document.getElementById('recent-codes-wrap');
      const listEl = document.getElementById('recent-codes-list');
      if (!wrap || !listEl) return;

      let codes = [];
      try {
        codes = JSON.parse(localStorage.getItem('calarian_recent_codes') || '[]');
      } catch (e) { }

      if (!Array.isArray(codes) || codes.length === 0) {
        wrap.style.display = 'none';
        return;
      }

      listEl.innerHTML = '';
      codes.slice(0, 4).forEach(code => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'recent-code-chip';
        btn.innerHTML = `<i class="fa-solid fa-clock-rotate-left" style="color:var(--blue);font-size:0.78rem;"></i> <span>${code}</span>`;
        btn.addEventListener('click', () => {
          const inp = document.getElementById('tracking_code');
          if (inp) {
            inp.value = code;
            document.getElementById('track-form').submit();
          }
        });
        listEl.appendChild(btn);
      });
      wrap.style.display = 'block';
    }

    function saveRecentCode(code) {
      if (!code) return;
      code = code.toUpperCase().trim();
      try {
        let codes = JSON.parse(localStorage.getItem('calarian_recent_codes') || '[]');
        if (!Array.isArray(codes)) codes = [];
        codes = [code, ...codes.filter(c => c !== code)].slice(0, 5);
        localStorage.setItem('calarian_recent_codes', JSON.stringify(codes));
      } catch (e) { }
    }

    document.addEventListener('DOMContentLoaded', () => {
      try {
        const savedLang = localStorage.getItem('calarian_lang');
        if (savedLang && (savedLang === 'en' || savedLang === 'fil')) {
          setTrackLanguage(savedLang);
        }
      } catch (e) { }

      renderRecentCodes();

      const trackForm = document.getElementById('track-form');
      if (trackForm) {
        let isSubmitting = false;
        trackForm.addEventListener('submit', (e) => {
          if (isSubmitting) {
            e.preventDefault();
            return;
          }
          const inp = document.getElementById('tracking_code');
          if (inp && inp.value) {
            saveRecentCode(inp.value);
            isSubmitting = true;
            const submitBtn = document.getElementById('btn-track-submit');
            const dict = trackI18n[trackLang] || trackI18n.en;
            if (submitBtn) {
              submitBtn.style.pointerEvents = 'none';
              submitBtn.style.opacity = '0.85';
              submitBtn.innerHTML = `<i class="fa-solid fa-spinner fa-spin" aria-hidden="true"></i> <span>${dict.btn_checking}</span>`;
            }
          }
        });
      }
    });
  </script>
@endsection
