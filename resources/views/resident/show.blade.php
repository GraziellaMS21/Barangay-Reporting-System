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
@if(session('status'))
  <div class="card alert" style="border-color: #107c41; background: #f0fdf4; margin-bottom: 1.25rem;">
    <span class="alert-icon" style="color: #107c41;" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
    <div>
      <strong style="color: #15803d;">{{ session('status') }}</strong>
    </div>
  </div>
@endif

@if($errors->any())
  <div class="card alert" style="border-color: var(--red); background: #fee2e2; margin-bottom: 1.25rem;">
    <span class="alert-icon" style="color: var(--red);" aria-hidden="true"><i class="fa-solid fa-circle-exclamation"></i></span>
    <div>
      <ul style="margin: 0; padding-left: 1.2rem; color: #991b1b;">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  </div>
@endif

<!-- Report Header Card -->
<section class="card" style="margin-bottom: 1.25rem;">
  <div class="flex flex-wrap items-start justify-between gap-3">
    <div>
      <p class="field-hint" style="margin: 0;">Tracking code</p>
      <h2 class="tracking-code" style="margin: 0.2rem 0;">{{ $report->tracking_code }}</h2>
    </div>
    <x-status-badge :status="$report->status" />
  </div>

  <p class="tracking-meta" style="margin-top: 0.5rem;">
    Submitted {{ $report->created_at->format('M d, Y \a\t h:i A') }}
    @if($report->purok)
      · {{ $report->purok->name }}
    @endif
    @if($report->category)
      · <span class="badge badge-outline">{{ $report->category->name }}</span>
    @endif
    @if($report->reopened_count > 0)
      · <span class="badge badge-warning" style="background:#fef3c7; color:#92400e; padding: 0.2rem 0.5rem; border-radius: 4px; font-weight: 700;">Reopened {{ $report->reopened_count }}x</span>
    @endif
  </p>
</section>

<!-- 1. PENDING STAFF INFO REQUEST (Action Required) -->
@if($pendingInfoRequest)
  <section class="card alert" style="border: 2px solid #f59e0b; background: #fffbeb; margin-bottom: 1.25rem;">
    <span class="alert-icon" style="color: #d97706;" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
    <div style="flex: 1;">
      <h3 style="color: #92400e; margin: 0 0 0.5rem;">Action Needed: Barangay Staff Requested More Information</h3>
      <div style="background: #fff; border-left: 4px solid #f59e0b; padding: 0.75rem 1rem; border-radius: 4px; margin-bottom: 1rem;">
        <p style="margin: 0; font-weight: 600; color: var(--tx);">"{{ $pendingInfoRequest->question }}"</p>
        <small style="color: var(--mut);">Requested by {{ $pendingInfoRequest->requester?->name ?? 'Staff' }} on {{ $pendingInfoRequest->created_at->format('M d, Y h:i A') }}</small>
      </div>

      <form method="POST" action="{{ route('track.info-response', $report->tracking_code) }}">
        @csrf
        <div class="field">
          <label for="info-response-text" style="font-weight: 600;">Your Response / Karagdagang Impormasyon:</label>
          <textarea id="info-response-text" name="response" rows="3" required placeholder="Type the requested details here..."></textarea>
        </div>
        <button type="submit" class="btn btn-gold">
          <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Submit Response to Staff
        </button>
      </form>
    </div>
  </section>
@endif

<!-- PAST INFO REQUESTS & RESPONSES -->
@if($report->infoRequests->whereNotNull('responded_at')->isNotEmpty())
  <section class="card" style="margin-bottom: 1.25rem;">
    <h3 class="card-title" style="font-size: 1.05rem;"><i class="fa-solid fa-comments"></i> Information Requests History</h3>
    <div style="display: grid; gap: 0.75rem; margin-top: 0.75rem;">
      @foreach($report->infoRequests->whereNotNull('responded_at') as $req)
        <div style="border: 1px solid var(--line); border-radius: 8px; padding: 0.75rem 1rem; background: var(--mist);">
          <p style="margin: 0; font-size: 0.88rem;"><strong>Staff Question:</strong> {{ $req->question }}</p>
          <p style="margin: 0.35rem 0 0; font-size: 0.88rem; color: #107c41;"><strong>Your Answer:</strong> {{ $req->response }}</p>
          <small style="color: var(--mut); display: block; margin-top: 0.2rem;">Answered on {{ $req->responded_at->format('M d, Y h:i A') }}</small>
        </div>
      @endforeach
    </div>
  </section>
@endif

<!-- 2. RESOLUTION DETAILS & RESIDENT CONFIRMATION / REOPEN -->
@if($report->status === 'awaiting_confirmation' || $report->status === 'resolved' || $latestResolutionProof)
  <section class="card" style="border: 2px solid #22c55e; background: #f0fdf4; margin-bottom: 1.25rem;">
    <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
      <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 1.75rem; margin-top: 0.1rem;"></i>
      <div style="flex: 1;">
        <h3 style="color: #15803d; margin: 0 0 0.4rem;">Staff Resolution Details</h3>

        @if($latestResolutionProof)
          <div style="background: #fff; padding: 0.9rem; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 1rem;">
            <p style="margin: 0; font-weight: 500;">{{ $latestResolutionProof->note }}</p>
            @if($latestResolutionProof->photo_path)
              <div style="margin-top: 0.5rem;">
                <img src="{{ Storage::disk('local')->url($latestResolutionProof->photo_path) }}" alt="Resolution Proof" style="max-width: 200px; border-radius: 6px; border: 1px solid var(--line);">
              </div>
            @endif
            <small style="color: var(--mut); display: block; margin-top: 0.4rem;">
              Resolved by {{ $latestResolutionProof->user?->name ?? 'Barangay Staff' }} on {{ $latestResolutionProof->created_at->format('M d, Y h:i A') }}
            </small>
          </div>
        @endif

        <!-- Confirmation or Reopen Choice -->
        @if(!$report->resolution_confirmed_at)
          <div style="background: #fff; border: 1px solid #86efac; border-radius: 10px; padding: 1.25rem; margin-top: 1rem;">
            <h4 style="margin: 0 0 0.5rem; color: var(--ink);">Please verify: Is the problem actually fixed?</h4>
            <p style="font-size: 0.88rem; color: var(--mut); margin-bottom: 1rem;">
              Barangay staff have marked this report as resolved. Please confirm whether the issue in your area has been properly addressed.
            </p>

            <!-- Option 1: Confirm Fixed & Rate -->
            <form method="POST" action="{{ route('track.confirm', $report->tracking_code) }}" style="margin-bottom: 1.25rem;">
              @csrf
              <div class="field" style="margin-bottom: 0.75rem;">
                <label style="font-weight: 600; font-size: 0.95rem; display: block; margin-bottom: 0.4rem;">Rate our service (1 to 5 stars):</label>
                
                <input type="hidden" id="service-rating-input" name="rating" value="{{ old('rating', '') }}">
                
                <div id="service-star-container" onmouseleave="restoreRating()" style="display: inline-flex; align-items: center; gap: 0.5rem; font-size: 2.2rem; cursor: pointer; user-select: none;">
                  <span class="rate-star" data-star="1" onclick="setSequentialRating(1)" onmouseenter="previewSequentialRating(1)" title="1 star" style="color: #d1d5db; transition: color 0.15s, transform 0.15s; display: inline-block;">
                    <i class="fa-solid fa-star"></i>
                  </span>
                  <span class="rate-star" data-star="2" onclick="setSequentialRating(2)" onmouseenter="previewSequentialRating(2)" title="2 stars" style="color: #d1d5db; transition: color 0.15s, transform 0.15s; display: inline-block;">
                    <i class="fa-solid fa-star"></i>
                  </span>
                  <span class="rate-star" data-star="3" onclick="setSequentialRating(3)" onmouseenter="previewSequentialRating(3)" title="3 stars" style="color: #d1d5db; transition: color 0.15s, transform 0.15s; display: inline-block;">
                    <i class="fa-solid fa-star"></i>
                  </span>
                  <span class="rate-star" data-star="4" onclick="setSequentialRating(4)" onmouseenter="previewSequentialRating(4)" title="4 stars" style="color: #d1d5db; transition: color 0.15s, transform 0.15s; display: inline-block;">
                    <i class="fa-solid fa-star"></i>
                  </span>
                  <span class="rate-star" data-star="5" onclick="setSequentialRating(5)" onmouseenter="previewSequentialRating(5)" title="5 stars" style="color: #d1d5db; transition: color 0.15s, transform 0.15s; display: inline-block;">
                    <i class="fa-solid fa-star"></i>
                  </span>
                  
                  <span id="rate-star-text" style="font-size: 0.95rem; font-weight: 700; color: #b45309; margin-left: 0.5rem;"></span>
                </div>
              </div>

              <div class="field" style="margin-bottom: 0.75rem;">
                <label for="confirm-comment" style="font-size: 0.88rem; font-weight: 600;">Optional Comment / Feedback:</label>
                <textarea id="confirm-comment" name="comment" rows="2" placeholder="Let us know how the barangay team did..."></textarea>
              </div>

              <button type="submit" class="btn btn-gold" style="background: #16a34a; border-color: #16a34a; color: #fff;">
                <i class="fa-solid fa-circle-check"></i> Yes, Confirm Problem is Fixed
              </button>
            </form>

            <hr style="border: 0; border-top: 1px dashed var(--line); margin: 1.25rem 0;">

            <!-- Option 2: Reopen Case -->
            <div>
              <p style="font-size: 0.88rem; font-weight: 600; color: #991b1b; margin-bottom: 0.5rem;">
                Not satisfied or problem is still there?
              </p>
              <form method="POST" action="{{ route('track.reopen', $report->tracking_code) }}">
                @csrf
                <div class="field" style="margin-bottom: 0.75rem;">
                  <textarea name="reason" rows="2" required placeholder="Explain why the issue is not yet resolved..."></textarea>
                </div>
                <button type="submit" class="btn btn-outline" style="color: var(--red); border-color: var(--red);">
                  <i class="fa-solid fa-rotate-left"></i> No, Reopen This Case
                </button>
              </form>
            </div>
          </div>
        @else
          <!-- Already Confirmed Notice -->
          <div style="background: #fff; border: 1px solid #86efac; border-radius: 8px; padding: 0.9rem; margin-top: 0.75rem;">
            <p style="margin: 0; color: #16a34a; font-weight: 700;">
              <i class="fa-solid fa-circle-check"></i> You confirmed the resolution of this report on {{ $report->resolution_confirmed_at->format('M d, Y \a\t h:i A') }}.
            </p>
            @if($report->feedback)
              <div style="margin-top: 0.5rem; padding-top: 0.5rem; border-top: 1px solid var(--line);">
                <strong>Your Rating:</strong>
                <span style="color: #f59e0b; margin-left: 0.25rem;">
                  @for($i = 1; $i <= 5; $i++)
                    <i class="fa-{{ $i <= $report->feedback->rating ? 'solid' : 'regular' }} fa-star"></i>
                  @endfor
                </span>
                @if($report->feedback->comment)
                  <p style="margin: 0.25rem 0 0; font-size: 0.88rem; color: var(--tx);">"{{ $report->feedback->comment }}"</p>
                @endif
              </div>
            @endif
          </div>
        @endif
      </div>
    </div>
  </section>
@endif

<!-- REFERRAL CARD (plain language; no staff names, emails or letter text) -->
@if($report->activeReferral && $report->activeReferral->office)
  @php
    $residentReferral = $report->activeReferral;
    $residentReferralStatus = match ($residentReferral->status) {
      'prepared' => 'Being prepared for sending',
      'sent' => 'Sent',
      'acknowledged' => 'Acknowledged by the agency',
      'action_taken' => 'The agency has taken action',
      'closed' => $residentReferral->outcome === 'resolved' ? 'Completed' : 'Closed',
      default => 'In progress',
    };
    $residentReferralUpdates = $residentReferral->updates;
    $latestResidentUpdate = $residentReferralUpdates->first();
  @endphp
  <section class="card" style="border: 2px solid #3b82f6; background: #eff6ff; margin-bottom: 1.25rem;">
    <h3 class="card-title resident-report-text" style="font-size: 1.05rem;">
      <i class="fa-solid fa-share" aria-hidden="true"></i> Referred to another office
    </h3>
    <p style="margin: .35rem 0 0;">
      Referred to <strong>{{ $residentReferral->office->name }}</strong>
      @if($residentReferral->sent_at)
        on {{ $residentReferral->sent_at->format('M d, Y') }}.
      @else
        and being prepared for sending.
      @endif
    </p>
    <p style="margin: .35rem 0 0;">
      <strong>Status:</strong> {{ $residentReferralStatus }}
    </p>
    @if($latestResidentUpdate)
      <p style="margin: .35rem 0 0;">
        <strong>Last update:</strong>
        {{ ($latestResidentUpdate->occurred_at ?? $latestResidentUpdate->created_at)?->format('M d, Y') }}
        &mdash; {{ $latestResidentUpdate->note }}
      </p>
    @endif
    @if($residentReferralUpdates->count() > 1)
      <ol class="timeline" style="margin-top: .75rem;">
        @foreach($residentReferralUpdates->skip(1) as $residentUpdate)
          <li>
            <strong>{{ ($residentUpdate->occurred_at ?? $residentUpdate->created_at)?->format('M d, Y') }}</strong>
            <small>{{ $residentUpdate->note }}</small>
          </li>
        @endforeach
      </ol>
    @endif
    <p class="text-sm text-muted" style="margin: .5rem 0 0;">
      The barangay keeps track of this referral and will post updates here.
    </p>
  </section>
@endif

<!-- 3. STATUS TIMELINE -->
<section class="card">
  <h3 class="card-title resident-report-text">
    <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Status history
  </h3>
  <ol class="timeline">
    @forelse($report->statusLogs as $log)
      <li>
        <strong>{{ \App\Models\Report::STATUSES[$log->to_status] ?? ucfirst(str_replace('_', ' ', $log->to_status)) }}</strong>
        <small>{{ $log->created_at->format('M d, Y h:i A') }}</small>
        @if($log->note)
          <small>{{ $log->note }}</small>
        @endif
      </li>
    @empty
      <li><strong>No status updates yet</strong></li>
    @endforelse
  </ol>
</section>

<script>
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
        star.style.setProperty('color', '#facc15', 'important'); // Bright Yellow
        star.style.textShadow = '0 0 8px rgba(250, 204, 21, 0.7)';
        star.style.transform = 'scale(1.12)';
      } else {
        star.style.setProperty('color', '#d1d5db', 'important'); // Grey
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
