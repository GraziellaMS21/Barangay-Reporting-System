@extends('layouts.app')
@section('title', 'Report Submitted - Barangay Calarian')

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <h1>Thank you for letting us know</h1>
      <p>Your report has been received by Barangay Calarian. Save the tracking code below to check for updates.</p>
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
    padding: 0.4rem 1rem;
    font-size: 0.85rem;
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
    padding: 1rem 1.25rem;
    margin-top: 2rem;
    text-align: left;
  }
</style>

<section class="card success-result-card">
  <div class="success-icon-badge" aria-hidden="true">
    <i class="fa-solid fa-circle-check"></i>
  </div>

  <h2 style="font-size: 1.5rem; font-weight: 800; color: var(--ink); margin: 0 0 0.5rem;">
    Report Submitted Successfully!
  </h2>
  <p style="color: #64748b; font-size: 0.95rem; margin: 0;">
    Your concern has been queued for barangay staff review.
  </p>

  <div class="tracking-code-container">
    <span style="font-size: 0.78rem; text-transform: uppercase; font-weight: 700; letter-spacing: 0.05em; color: #64748b;">
      Your Unique Tracking Code
    </span>
    <div class="tracking-code-display" id="submitted-tracking-code">{{ $report->tracking_code }}</div>
    <button type="button" class="btn-copy-code-lg" onclick="copySuccessCode()" id="copy-btn-success">
      <i class="fa-solid fa-copy" id="copy-icon-success"></i>
      <span id="copy-text-success">Copy Tracking Code</span>
    </button>
  </div>

  <p style="font-size: 0.88rem; color: #64748b; margin: 0 auto; max-width: 420px;">
    Save or take a screenshot of this code. You will need it to track resolution progress or communicate with staff.
  </p>

  <div class="success-actions-wrap">
    <a class="btn btn-gold" href="{{ route('track.show', $report->tracking_code) }}" style="font-weight: 800; padding: 0.65rem 1.4rem;">
      <i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Track this report
    </a>
    <a class="btn btn-outline" href="{{ route('reports.create') }}" style="font-weight: 700; padding: 0.65rem 1.4rem;">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> Submit another report
    </a>
  </div>

  <div class="emergency-disclaimer-card">
    <div style="display: flex; align-items: center; gap: 0.5rem; color: #b91c1c; font-weight: 700; font-size: 0.85rem; margin-bottom: 0.35rem;">
      <i class="fa-solid fa-triangle-exclamation"></i> Urgent Notice:
    </div>
    <p style="font-size: 0.84rem; color: #7f1d1d; margin: 0 0 0.65rem; line-height: 1.4;">
      This online system is not monitored in real time. If this is an active emergency or life-threatening event, call 911 or official hotlines directly.
    </p>

    @if($contacts->isNotEmpty())
      <div style="display: flex; flex-wrap: wrap; gap: 0.4rem;" aria-label="Emergency contacts">
        @foreach($contacts as $contact)
          @php $num = trim((string) $contact->number); @endphp
          @if($num !== '' && stripos($num, 'N/A') === false)
            <a href="tel:{{ preg_replace('/[^0-9+]/', '', $num) }}" style="display: inline-flex; align-items: center; gap: 0.35rem; background: #ffffff; border: 1px solid #fecaca; border-radius: 6px; padding: 0.2rem 0.55rem; font-size: 0.78rem; color: #991b1b; text-decoration: none; font-weight: 600;">
              <i class="fa-solid fa-phone" style="font-size: 0.7rem;"></i>
              <span>{{ $contact->name }}: {{ $num }}</span>
            </a>
          @endif
        @endforeach
      </div>
    @endif
  </div>
</section>

<script>
function copySuccessCode() {
  const code = document.getElementById('submitted-tracking-code').textContent.trim();
  if (!code) return;
  navigator.clipboard.writeText(code).then(() => {
    const copyText = document.getElementById('copy-text-success');
    const copyIcon = document.getElementById('copy-icon-success');
    copyText.textContent = 'Copied to Clipboard!';
    copyIcon.className = 'fa-solid fa-check';
    setTimeout(() => {
      copyText.textContent = 'Copy Tracking Code';
      copyIcon.className = 'fa-solid fa-copy';
    }, 2500);
  });
}
</script>
@endsection
