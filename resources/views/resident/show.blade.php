@extends('layouts.app')
@section('title', 'Report ' . $report->tracking_code)

@section('hero')
  <section class="hero hero-compact">
    <div class="hero-inner">
      <div>
        <h1>Report Progress & Status</h1>
        <p>Live tracking and updates for your report in Barangay Calarian.</p>
        <div class="hero-actions">
          <a class="btn btn-ghost" href="{{ route('track.index') }}">
            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Track another report
          </a>
        </div>
      </div>
    </div>
  </section>
@endsection

@section('content')
  <style>
    /* Resident Tracking Cards Styling */
    .track-card {
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      padding: 1.75rem;
      background: #ffffff;
      box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 10px 15px -3px rgba(11, 31, 75, 0.05);
      margin-bottom: 1.5rem;
      position: relative;
    }

    @media (max-width: 640px) {
      .track-card {
        padding: 1.25rem;
        border-radius: 16px;
      }
    }

    /* Header Card */
    .track-header-card {
      border-top: 4px solid var(--blue);
    }

    .track-code-hero {
      font-size: 1.85rem;
      font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
      font-weight: 800;
      color: var(--ink);
      letter-spacing: 0.04em;
      margin: 0.2rem 0;
      display: inline-flex;
      align-items: center;
      gap: 0.65rem;
    }

    .btn-copy-code {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.78rem;
      font-family: var(--font-sans, system-ui);
      font-weight: 700;
      padding: 0.25rem 0.65rem;
      border-radius: 8px;
      background: #f1f5f9;
      border: 1px solid #cbd5e1;
      color: var(--tx);
      cursor: pointer;
      transition: all 0.2s;
    }

    .btn-copy-code:hover {
      background: #e2e8f0;
      color: var(--blue);
    }

    .track-meta-chips {
      display: flex;
      flex-wrap: wrap;
      align-items: center;
      gap: 0.65rem;
      margin-top: 0.85rem;
      font-size: 0.88rem;
      color: #64748b;
    }

    .track-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      background: #f8fafc;
      border: 1px solid #e2e8f0;
      border-radius: 999px;
      padding: 0.25rem 0.75rem;
      font-weight: 600;
      color: var(--tx);
    }

    /* Action Needed Card */
    .track-action-card {
      border: 2px solid #f59e0b;
      background: linear-gradient(to bottom, #fffbeb 0%, #ffffff 100%);
      box-shadow: 0 4px 15px rgba(245, 158, 11, 0.12);
    }

    .staff-quote-box {
      background: #ffffff;
      border: 1px solid #fde68a;
      border-left: 4px solid #f59e0b;
      padding: 1rem 1.25rem;
      border-radius: 10px;
      margin-bottom: 1.25rem;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.03);
    }

    /* Resolution Card */
    .track-resolution-card {
      border: 2px solid #22c55e;
      background: linear-gradient(to bottom, #f0fdf4 0%, #ffffff 100%);
      box-shadow: 0 4px 15px rgba(34, 197, 94, 0.12);
    }

    .proof-box {
      background: #ffffff;
      padding: 1.15rem;
      border-radius: 12px;
      border: 1px solid #bbf7d0;
      margin-bottom: 1.25rem;
    }

    /* Interactive Rating Stars */
    .interactive-star {
      font-size: 2.2rem;
      color: #cbd5e1;
      cursor: pointer;
      transition: color 0.15s ease, transform 0.15s ease;
      display: inline-block;
      padding: 0 0.15rem;
    }

    .interactive-star:hover {
      transform: scale(1.15);
    }

    /* Referral Card */
    .track-referral-card {
      border: 2px solid #3b82f6;
      background: linear-gradient(to bottom, #eff6ff 0%, #ffffff 100%);
    }

    /* Status Timeline */
    .timeline-clean {
      margin: 1.5rem 0 0 0.5rem;
      border-left: 2px solid #e2e8f0;
      padding: 0;
      list-style: none;
    }

    .timeline-clean li {
      position: relative;
      padding: 0 0 1.5rem 1.5rem;
    }

    .timeline-clean li:last-child {
      padding-bottom: 0;
    }

    .timeline-clean li::before {
      content: '';
      position: absolute;
      left: -7px;
      top: 4px;
      width: 12px;
      height: 12px;
      border-radius: 50%;
      background: var(--blue);
      border: 2px solid #ffffff;
      box-shadow: 0 0 0 2px var(--blue);
    }

    .timeline-clean li:first-child::before {
      background: #22c55e;
      box-shadow: 0 0 0 3px rgba(34, 197, 94, 0.25);
    }
  </style>

  @if(session('status'))
    <div class="track-card alert" style="border: 1px solid #86efac; background: #f0fdf4; margin-bottom: 1.25rem; display: flex; align-items: center; gap: 0.75rem;">
      <span style="color: #16a34a; font-size: 1.3rem;" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
      <div>
        <strong style="color: #15803d; font-size: 0.95rem;">{{ session('status') }}</strong>
      </div>
    </div>
  @endif

  @if($errors->any())
    <div class="track-card alert" style="border: 1px solid #fca5a5; background: #fef2f2; margin-bottom: 1.25rem; display: flex; align-items: flex-start; gap: 0.75rem;">
      <span style="color: var(--red); font-size: 1.3rem; margin-top: 0.1rem;" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
      <div>
        <strong style="color: #991b1b; display: block; margin-bottom: 0.25rem;">Please review the following:</strong>
        <ul style="margin: 0; padding-left: 1.2rem; color: #b91c1c;">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    </div>
  @endif

  <!-- Report Header Card -->
  <section class="track-card track-header-card">
    <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;">
      <div>
        <span style="font-size:0.8rem;font-weight:700;text-transform:uppercase;letter-spacing:0.05em;color:#64748b;display:block;">
          Report Tracking Code
        </span>
        <div class="track-code-hero">
          <span id="report-tracking-code">{{ $report->tracking_code }}</span>
          <button type="button" class="btn-copy-code" onclick="copyTrackingCode()" title="Copy tracking code">
            <i class="fa-solid fa-copy" id="copy-icon"></i>
            <span id="copy-text">Copy</span>
          </button>
        </div>
      </div>
      <div>
        <x-status-badge :status="$report->status" />
      </div>
    </div>

    <div class="track-meta-chips">
      <span class="track-chip">
        <i class="fa-regular fa-clock" style="color:var(--blue);"></i>
        Submitted {{ $report->created_at->format('M d, Y \a\t h:i A') }}
      </span>

      @if($report->purok)
        <span class="track-chip">
          <i class="fa-solid fa-location-dot" style="color:#e0352b;"></i>
          {{ $report->purok->name }}
        </span>
      @endif

      @if($report->category)
        <span class="track-chip">
          <i class="fa-solid fa-tag" style="color:var(--blue);"></i>
          {{ $report->category->name }}
        </span>
      @endif

      @if($report->reopened_count > 0)
        <span class="track-chip" style="background:#fef3c7;border-color:#fde68a;color:#92400e;font-weight:700;">
          <i class="fa-solid fa-rotate-left"></i> Reopened {{ $report->reopened_count }}x
        </span>
      @endif
    </div>

    @if($report->description)
      <div style="margin-top:1.25rem;padding-top:1rem;border-top:1px solid #f1f5f9;">
        <h4 style="font-size:0.85rem;font-weight:700;text-transform:uppercase;color:#64748b;margin:0 0 0.35rem;">
          Reported Problem Details
        </h4>
        <p style="margin:0;font-size:0.95rem;color:var(--tx);line-height:1.6;white-space:pre-wrap;">{{ $report->description }}</p>
      </div>
    @endif
  </section>

  <!-- 1. PENDING STAFF INFO REQUEST (Action Required) -->
  @if($pendingInfoRequest)
    <section class="track-card track-action-card">
      <div style="display:flex;align-items:flex-start;gap:0.85rem;">
        <div style="width:40px;height:40px;border-radius:10px;background:#fef3c7;display:grid;place-items:center;flex-shrink:0;">
          <i class="fa-solid fa-triangle-exclamation" style="color:#d97706;font-size:1.3rem;"></i>
        </div>
        <div style="flex:1;">
          <h3 style="color:#92400e;margin:0 0 0.4rem;font-size:1.15rem;font-weight:800;">
            Action Needed: Staff Requested Information
          </h3>
          <p style="margin:0 0 0.85rem;font-size:0.88rem;color:#78350f;">
            Barangay staff need additional details from you to continue processing this report.
          </p>

          <div class="staff-quote-box">
            <p style="margin:0;font-weight:600;font-size:0.95rem;color:var(--ink);">"{{ $pendingInfoRequest->question }}"</p>
            <small style="color:#64748b;display:block;margin-top:0.35rem;">
              <i class="fa-solid fa-user-tie"></i> Requested by {{ $pendingInfoRequest->requester?->name ?? 'Barangay Staff' }} on {{ $pendingInfoRequest->created_at->format('M d, Y h:i A') }}
            </small>
          </div>

          <form method="POST" action="{{ route('track.info-response', $report->tracking_code) }}">
            @csrf
            <div class="field" style="margin-bottom:0.85rem;">
              <label for="info-response-text" style="font-weight:700;font-size:0.92rem;color:var(--ink);">
                Your Response / Karagdagang Impormasyon:
              </label>
              <textarea id="info-response-text" name="response" rows="3" required
                placeholder="Type the requested details here to help barangay staff..."></textarea>
            </div>
            <button type="submit" class="btn btn-gold" style="font-weight:800;">
              <i class="fa-solid fa-paper-plane"></i> Submit Response to Staff
            </button>
          </form>
        </div>
      </div>
    </section>
  @endif

  <!-- PAST INFO REQUESTS & RESPONSES -->
  @if($report->infoRequests->whereNotNull('responded_at')->isNotEmpty())
    <section class="track-card">
      <h3 class="card-title" style="font-size:1.1rem;font-weight:700;">
        <i class="fa-solid fa-comments" style="color:var(--blue);"></i> Information Requests History
      </h3>
      <div style="display:grid;gap:0.85rem;margin-top:1rem;">
        @foreach($report->infoRequests->whereNotNull('responded_at') as $req)
          <div style="border:1px solid #e2e8f0;border-radius:12px;padding:1rem 1.15rem;background:#f8fafc;">
            <div style="margin-bottom:0.5rem;">
              <span style="font-weight:700;color:var(--ink);font-size:0.88rem;display:flex;align-items:center;gap:0.4rem;">
                <i class="fa-solid fa-user-pen" style="color:var(--blue);"></i> Staff Question:
              </span>
              <p style="margin:0.2rem 0 0;font-size:0.92rem;color:var(--tx);">{{ $req->question }}</p>
            </div>
            <div style="padding-top:0.5rem;border-top:1px dashed #e2e8f0;">
              <span style="font-weight:700;color:#16a34a;font-size:0.88rem;display:flex;align-items:center;gap:0.4rem;">
                <i class="fa-solid fa-reply"></i> Your Answer:
              </span>
              <p style="margin:0.2rem 0 0;font-size:0.92rem;color:var(--tx);">{{ $req->response }}</p>
              <small style="color:#64748b;display:block;margin-top:0.35rem;">
                Answered on {{ $req->responded_at->format('M d, Y h:i A') }}
              </small>
            </div>
          </div>
        @endforeach
      </div>
    </section>
  @endif

  <!-- NO ACTION NOTICE -->
  @if($report->status === 'no_action')
    <section class="track-card" style="border-left: 4px solid #64748b; background: #f8fafc;">
      <div style="display:flex;align-items:flex-start;gap:0.85rem;">
        <div style="width:40px;height:40px;border-radius:10px;background:#f1f5f9;display:grid;place-items:center;flex-shrink:0;">
          <i class="fa-solid fa-circle-xmark" style="color:#64748b;font-size:1.4rem;"></i>
        </div>
        <div style="flex:1;">
          <h3 style="color:#334155;margin:0 0 0.35rem;font-size:1.15rem;font-weight:800;">
            Case Closed: No Further Action
          </h3>
          <p style="margin:0 0 0.75rem;font-size:0.88rem;color:#64748b;">
            The barangay evaluated this report and concluded that no further action will be taken.
          </p>
          <div style="background:#ffffff;border:1px solid #e2e8f0;border-radius:8px;padding:0.85rem 1rem;">
            @if($report->no_action_reason)
              <div style="margin-bottom:0.5rem;">
                <span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:0.05em;display:block;">Reason</span>
                <strong style="color:var(--ink);font-size:0.95rem;">{{ \App\Models\Report::NO_ACTION_REASONS[$report->no_action_reason] ?? ucfirst(str_replace('_', ' ', $report->no_action_reason)) }}</strong>
              </div>
            @endif
            @php
              $noActionLog = $report->statusLogs->where('to_status', 'no_action')->last();
            @endphp
            @if($noActionLog?->note)
              <div>
                <span style="font-size:0.75rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:0.05em;display:block;">Staff Explanation</span>
                <p style="margin:0.25rem 0 0;font-size:0.92rem;color:var(--tx);line-height:1.5;">{{ $noActionLog->note }}</p>
              </div>
            @endif
          </div>
        </div>
      </div>
    </section>
  @endif

  <!-- 2. RESOLUTION DETAILS & RESIDENT CONFIRMATION / REOPEN -->
  @if($report->status === 'awaiting_confirmation' || $report->status === 'resolved' || $latestResolutionProof)
    <section class="track-card track-resolution-card">
      <div style="display:flex;align-items:flex-start;gap:0.85rem;">
        <div style="width:40px;height:40px;border-radius:10px;background:#dcfce7;display:grid;place-items:center;flex-shrink:0;">
          <i class="fa-solid fa-circle-check" style="color:#16a34a;font-size:1.4rem;"></i>
        </div>
        <div style="flex:1;">
          <h3 style="color:#15803d;margin:0 0 0.35rem;font-size:1.15rem;font-weight:800;">
            Staff Resolution Details
          </h3>
          <p style="margin:0 0 1rem;font-size:0.88rem;color:#166534;">
            Barangay personnel have worked on this report. Please review the details below.
          </p>

          @if($latestResolutionProof)
            <div class="proof-box">
              <strong style="color:var(--ink);font-size:0.88rem;display:block;margin-bottom:0.25rem;">Staff Resolution Note:</strong>
              <p style="margin:0;font-size:0.95rem;color:var(--tx);line-height:1.5;">{{ $latestResolutionProof->note }}</p>

              @if($latestResolutionProof->photo_path)
                <div style="margin-top:0.75rem;">
                  <strong style="font-size:0.82rem;color:#64748b;display:block;margin-bottom:0.35rem;">Proof of Work:</strong>
                  <a href="{{ Storage::disk('local')->url($latestResolutionProof->photo_path) }}" target="_blank" rel="noopener">
                    <img src="{{ Storage::disk('local')->url($latestResolutionProof->photo_path) }}" alt="Resolution Proof"
                      style="max-width:240px;border-radius:8px;border:1px solid #cbd5e1;box-shadow:0 2px 4px rgba(0,0,0,0.06);transition:transform 0.2s;"
                      onmouseover="this.style.transform='scale(1.02)'" onmouseout="this.style.transform='scale(1)'">
                  </a>
                </div>
              @endif

              <small style="color:#64748b;display:block;margin-top:0.6rem;padding-top:0.5rem;border-top:1px solid #e2e8f0;">
                <i class="fa-solid fa-user-check" style="color:#16a34a;"></i>
                Resolved by {{ $latestResolutionProof->user?->name ?? 'Barangay Staff' }} on {{ $latestResolutionProof->created_at->format('M d, Y h:i A') }}
              </small>
            </div>
          @endif

          <!-- Confirmation or Reopen Choice -->
          @if(!$report->resolution_confirmed_at)
            <div style="background:#ffffff;border:1.5px solid #86efac;border-radius:14px;padding:1.35rem;margin-top:1rem;box-shadow:0 2px 6px rgba(34,197,94,0.08);">
              <h4 style="margin:0 0 0.4rem;color:var(--ink);font-size:1.05rem;font-weight:800;">
                <i class="fa-solid fa-clipboard-question" style="color:var(--blue);margin-right:0.35rem;"></i>
                Please verify: Is the problem actually fixed?
              </h4>
              <p style="font-size:0.88rem;color:#64748b;margin-bottom:1.25rem;">
                Barangay staff marked this report as resolved. Please confirm whether the issue in your area has been properly addressed to help us maintain high service quality.
              </p>

              <!-- Option 1: Confirm Fixed & Rate -->
              <form method="POST" action="{{ route('track.confirm', $report->tracking_code) }}" style="margin-bottom:1.25rem;">
                @csrf
                <div class="field" style="margin-bottom:0.85rem;">
                  <label style="font-weight:700;font-size:0.95rem;display:block;margin-bottom:0.4rem;color:var(--ink);">
                    Rate our service (1 to 5 stars):
                  </label>

                  <input type="hidden" id="service-rating-input" name="rating" value="{{ old('rating', '') }}">

                  <div id="service-star-container" onmouseleave="restoreRating()"
                    style="display:inline-flex;align-items:center;gap:0.5rem;font-size:2.3rem;cursor:pointer;user-select:none;margin-bottom:0.35rem;">
                    <span class="interactive-star rate-star" data-star="1" onclick="setSequentialRating(1)" onmouseenter="previewSequentialRating(1)" title="1 star">
                      <i class="fa-solid fa-star"></i>
                    </span>
                    <span class="interactive-star rate-star" data-star="2" onclick="setSequentialRating(2)" onmouseenter="previewSequentialRating(2)" title="2 stars">
                      <i class="fa-solid fa-star"></i>
                    </span>
                    <span class="interactive-star rate-star" data-star="3" onclick="setSequentialRating(3)" onmouseenter="previewSequentialRating(3)" title="3 stars">
                      <i class="fa-solid fa-star"></i>
                    </span>
                    <span class="interactive-star rate-star" data-star="4" onclick="setSequentialRating(4)" onmouseenter="previewSequentialRating(4)" title="4 stars">
                      <i class="fa-solid fa-star"></i>
                    </span>
                    <span class="interactive-star rate-star" data-star="5" onclick="setSequentialRating(5)" onmouseenter="previewSequentialRating(5)" title="5 stars">
                      <i class="fa-solid fa-star"></i>
                    </span>

                    <span id="rate-star-text" style="font-size:0.95rem;font-weight:700;color:#b45309;margin-left:0.65rem;"></span>
                  </div>
                </div>

                <div class="field" style="margin-bottom:1rem;">
                  <label for="confirm-comment" style="font-size:0.88rem;font-weight:700;color:var(--ink);">
                    Optional Comment / Feedback:
                  </label>
                  <textarea id="confirm-comment" name="comment" rows="2"
                    placeholder="Let us know how the barangay team did..."></textarea>
                </div>

                <button type="submit" class="btn btn-gold" style="background:#16a34a;border-color:#16a34a;color:#ffffff;font-weight:800;padding:0.65rem 1.35rem;box-shadow:0 4px 10px rgba(22,163,74,0.25);">
                  <i class="fa-solid fa-circle-check"></i> Yes, Confirm Problem is Fixed
                </button>
              </form>

              <hr style="border:0;border-top:1px dashed #cbd5e1;margin:1.35rem 0;">

              <!-- Option 2: Reopen Case -->
              <div style="background:#fef2f2;border:1px solid #fecaca;border-radius:10px;padding:1rem 1.15rem;">
                <p style="font-size:0.9rem;font-weight:800;color:#991b1b;margin:0 0 0.35rem;">
                  <i class="fa-solid fa-circle-question"></i> Not satisfied or problem is still there?
                </p>
                <p style="font-size:0.82rem;color:#7f1d1d;margin:0 0 0.85rem;">
                  If the problem has not been resolved or occurred again, let the team know why so they can dispatch further assistance.
                </p>
                <form method="POST" action="{{ route('track.reopen', $report->tracking_code) }}">
                  @csrf
                  <div class="field" style="margin-bottom:0.75rem;">
                    <textarea name="reason" rows="2" required placeholder="Explain why the issue is not yet resolved..."></textarea>
                  </div>
                  <button type="submit" class="btn btn-outline" style="color:var(--red);border-color:var(--red);font-weight:700;background:#ffffff;">
                    <i class="fa-solid fa-rotate-left"></i> No, Reopen This Case
                  </button>
                </form>
              </div>
            </div>
          @else
            <!-- Already Confirmed Notice -->
            <div style="background:#ffffff;border:1.5px solid #86efac;border-radius:12px;padding:1.15rem;margin-top:1rem;">
              <p style="margin:0;color:#16a34a;font-weight:800;font-size:0.98rem;display:flex;align-items:center;gap:0.45rem;">
                <i class="fa-solid fa-circle-check"></i> You confirmed the resolution of this report on {{ $report->resolution_confirmed_at->format('M d, Y \a\t h:i A') }}.
              </p>
              @if($report->feedback)
                <div style="margin-top:0.75rem;padding-top:0.75rem;border-top:1px solid #e2e8f0;">
                  <span style="font-weight:700;color:var(--ink);">Your Rating:</span>
                  <span style="color:#eab308;margin-left:0.35rem;font-size:1.1rem;">
                    @for($i = 1; $i <= 5; $i++)
                      <i class="fa-{{ $i <= $report->feedback->rating ? 'solid' : 'regular' }} fa-star"></i>
                    @endfor
                  </span>
                  @if($report->feedback->comment)
                    <p style="margin:0.35rem 0 0;font-size:0.92rem;color:var(--tx);font-style:italic;">
                      "{{ $report->feedback->comment }}"
                    </p>
                  @endif
                </div>
              @endif
            </div>
          @endif
        </div>
      </div>
    </section>
  @endif

  <!-- REFERRAL CARD -->
  @if($report->activeReferral && $report->activeReferral->office)
    @php
      $residentReferral = $report->activeReferral;
      $residentReferralStatus = match ($residentReferral->status) {
        'prepared' => 'Being prepared for sending',
        'sent' => 'Sent to Agency',
        'acknowledged' => 'Acknowledged by the agency',
        'action_taken' => 'The agency has taken action',
        'closed' => $residentReferral->outcome === 'resolved' ? 'Completed' : 'Closed',
        default => 'In progress',
      };
      $residentReferralUpdates = $residentReferral->updates;
      $latestResidentUpdate = $residentReferralUpdates->first();
    @endphp
    <section class="track-card track-referral-card">
      <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:0.75rem;">
        <div style="width:36px;height:36px;border-radius:10px;background:#dbeafe;display:grid;place-items:center;">
          <i class="fa-solid fa-share-nodes" style="color:var(--blue);"></i>
        </div>
        <h3 class="card-title" style="margin:0;font-size:1.1rem;font-weight:800;color:var(--ink);">
          Referred to Another Office
        </h3>
      </div>

      <div style="background:#ffffff;border:1px solid #bfdbfe;border-radius:12px;padding:1rem 1.15rem;margin-bottom:0.75rem;">
        <p style="margin:0 0 0.35rem;font-size:0.95rem;">
          Referred to <strong>{{ $residentReferral->office->name }}</strong>
          @if($residentReferral->sent_at)
            on {{ $residentReferral->sent_at->format('M d, Y') }}.
          @else
            and being prepared for transmission.
          @endif
        </p>
        <p style="margin:0;font-size:0.9rem;">
          <strong>Current Status:</strong> <span class="track-chip" style="margin-left:0.35rem;">{{ $residentReferralStatus }}</span>
        </p>
      </div>

      @if($latestResidentUpdate)
        <div style="background:#ffffff;border:1px solid #bfdbfe;border-radius:12px;padding:0.85rem 1rem;margin-bottom:0.75rem;">
          <strong style="color:var(--blue);font-size:0.85rem;display:block;">Latest Agency Update:</strong>
          <span style="font-size:0.9rem;color:var(--tx);">{{ $latestResidentUpdate->note }}</span>
          <small style="color:#64748b;display:block;margin-top:0.25rem;">
            {{ ($latestResidentUpdate->occurred_at ?? $latestResidentUpdate->created_at)?->format('M d, Y h:i A') }}
          </small>
        </div>
      @endif

      @if($residentReferralUpdates->count() > 1)
        <ol class="timeline-clean" style="margin-top:0.75rem;">
          @foreach($residentReferralUpdates->skip(1) as $residentUpdate)
            <li>
              <strong style="font-size:0.88rem;color:var(--ink);">{{ ($residentUpdate->occurred_at ?? $residentUpdate->created_at)?->format('M d, Y') }}</strong>
              <p style="margin:0.2rem 0 0;font-size:0.85rem;color:var(--tx);">{{ $residentUpdate->note }}</p>
            </li>
          @endforeach
        </ol>
      @endif

      <p style="font-size:0.82rem;color:#64748b;margin:0.5rem 0 0;display:flex;align-items:center;gap:0.35rem;">
        <i class="fa-solid fa-circle-info"></i> The barangay actively follows up on this referral and will post updates here.
      </p>
    </section>
  @endif

  <!-- 3. STATUS TIMELINE CARD -->
  <section class="track-card">
    <div style="display:flex;align-items:center;gap:0.65rem;margin-bottom:1.25rem;">
      <div style="width:36px;height:36px;border-radius:10px;background:#f1f5f9;display:grid;place-items:center;">
        <i class="fa-solid fa-clock-rotate-left" style="color:var(--blue);"></i>
      </div>
      <h3 class="card-title" style="margin:0;font-size:1.15rem;font-weight:800;color:var(--ink);">
        Status History & Activity
      </h3>
    </div>

    @php
      $publicStatusLogs = $report->statusLogs->filter(fn ($log) => $log->visible_to_resident !== false);
    @endphp

    <ol class="timeline-clean">
      @forelse($publicStatusLogs as $log)
        <li>
          <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;margin-bottom:0.25rem;">
            <strong style="font-size:0.95rem;color:var(--ink);">{{ $log->update_title }}</strong>
            @if($log->update_type)
              <span class="track-chip" style="font-size:0.75rem;padding:0.15rem 0.55rem;">
                {{ \App\Models\Report::STATUSES[$log->to_status] ?? ucfirst(str_replace('_', ' ', $log->to_status)) }}
              </span>
            @endif
          </div>

          <div style="font-size:0.82rem;color:#64748b;margin-bottom:0.35rem;">
            <i class="fa-regular fa-clock"></i> {{ $log->created_at->format('M d, Y h:i A') }}
            @if($log->office || ($log->update_type && $report->office))
              · <span style="font-weight:600;color:var(--tx);"><i class="fa-solid fa-building"></i> {{ $log->office?->name ?? $report->office?->name }}</span>
            @endif
          </div>

          @if($log->note)
            <div style="font-size:0.9rem;color:var(--tx);background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:0.6rem 0.85rem;margin-top:0.35rem;line-height:1.5;">
              {{ $log->note }}
            </div>
          @endif

          @if($log->expected_completion_date)
            <div style="font-size:0.82rem;font-weight:600;color:#b45309;margin-top:0.35rem;">
              <i class="fa-regular fa-calendar-check"></i>
              Expected completion: {{ $log->expected_completion_date->format('M d, Y') }}
            </div>
          @endif

          @if(!empty($log->attachments) && is_array($log->attachments))
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;margin-top:0.6rem;">
              @foreach($log->attachments as $photoPath)
                <a href="{{ Storage::disk('public')->url($photoPath) }}" target="_blank" rel="noopener">
                  <img src="{{ Storage::disk('public')->url($photoPath) }}" alt="Progress update photo"
                    style="width:72px;height:72px;object-fit:cover;border-radius:8px;border:1px solid #cbd5e1;box-shadow:0 1px 3px rgba(0,0,0,0.06);transition:transform 0.15s;"
                    onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                </a>
              @endforeach
            </div>
          @endif
        </li>
      @empty
        <li>
          <p style="color:#64748b;font-size:0.9rem;margin:0;">No public status updates recorded yet.</p>
        </li>
      @endforelse
    </ol>
  </section>

  <script>
  function copyTrackingCode() {
    const code = document.getElementById('report-tracking-code').textContent.trim();
    if (!code) return;
    navigator.clipboard.writeText(code).then(() => {
      const copyText = document.getElementById('copy-text');
      const copyIcon = document.getElementById('copy-icon');
      copyText.textContent = 'Copied!';
      copyIcon.className = 'fa-solid fa-check';
      setTimeout(() => {
        copyText.textContent = 'Copy';
        copyIcon.className = 'fa-solid fa-copy';
      }, 2500);
    });
  }

  (function() {
    var inputEl = document.getElementById('service-rating-input');
    var stars = document.querySelectorAll('#service-star-container .rate-star');
    var textEl = document.getElementById('rate-star-text');
    var currentSelectedRating = inputEl && inputEl.value ? parseInt(inputEl.value, 10) : 0;

    var ratingDescriptions = {
      1: '⭐ 1 / 5 stars (Poor)',
      2: '⭐⭐ 2 / 5 stars (Fair)',
      3: '⭐⭐⭐ 3 / 5 stars (Good)',
      4: '⭐⭐⭐⭐ 4 / 5 stars (Very Good)',
      5: '⭐⭐⭐⭐⭐ 5 / 5 stars (Excellent!)'
    };

    function displayStarsInOrder(upToNumber) {
      stars.forEach(function(star) {
        var starNum = parseInt(star.getAttribute('data-star'), 10);
        if (starNum <= upToNumber) {
          star.style.setProperty('color', '#eab308', 'important');
          star.style.textShadow = '0 0 10px rgba(234, 179, 8, 0.5)';
          star.style.transform = 'scale(1.15)';
        } else {
          star.style.setProperty('color', '#cbd5e1', 'important');
          star.style.textShadow = 'none';
          star.style.transform = 'scale(1)';
        }
      });

      if (textEl) {
        textEl.textContent = upToNumber > 0 ? (ratingDescriptions[upToNumber] || (upToNumber + ' stars')) : '';
      }
    }

    window.setSequentialRating = function(n) {
      currentSelectedRating = n;
      if (inputEl) {
        inputEl.value = n;
      }
      displayStarsInOrder(n);
    };

    window.previewSequentialRating = function(n) {
      displayStarsInOrder(n);
    };

    window.restoreRating = function() {
      displayStarsInOrder(currentSelectedRating);
    };

    if (currentSelectedRating > 0) {
      displayStarsInOrder(currentSelectedRating);
    }
  })();
  </script>
@endsection
