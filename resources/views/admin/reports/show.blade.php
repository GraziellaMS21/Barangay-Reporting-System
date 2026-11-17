@extends('layouts.admin')
@section('title', 'Review ' . $report->tracking_code)
@section('page-heading', 'Review report')

@section('content')
@php
  $aiResult = $report->aiResult;
  $hasAiSuggestion = $report->ai_status === 'done' && $aiResult !== null && $aiResult->error === null;
  $suggestedCategory = $hasAiSuggestion ? $categories->firstWhere('slug', $aiResult->suggested_category) : null;
  $confidence = $hasAiSuggestion ? (float) $aiResult->confidence : null;
  $confidenceLabel = $confidence === null ? null : ($confidence >= 0.8 ? 'High' : ($confidence >= 0.5 ? 'Medium' : 'Low'));
  $categoryDefault = $report->category_id ?: ($hasAiSuggestion ? $suggestedCategory?->id : null);
  $urgencyDefault = $report->urgency ?: ($hasAiSuggestion ? $aiResult->suggested_urgency : null);
  $officeDefault = $report->office_id ?: ($report->category ? optional($report->getRecommendedOffice())->id : null);
  $assignedDefault = $report->assigned_to;
  $descriptionDefault = $report->staff_description ?: ($hasAiSuggestion ? ($aiResult->summary ?? $report->description) : $report->description);
  $isOverdue = $report->due_at && $report->due_at->isPast() && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true);
  $steps = ['Received','Review','Routed','In progress','Resolved'];
  $statusStep = match($report->status) {
    'submitted','under_review','reopened' => 1,
    'routed' => 2,
    'in_progress' => 3,
    'needs_info','awaiting_confirmation' => 2,
    'resolved' => 4,
    default => 4,
  };
  $oneLineSummary = $hasAiSuggestion ? ($aiResult->summary ?? $report->description) : $report->description;
  $autoCloseDays = $autoCloseDays ?? (int) \App\Models\Setting::value('auto_close_days', 5);
@endphp

<a href="{{ route('admin.reports.index') }}" class="back-link">
  <i class="fa-solid fa-arrow-left" aria-hidden="true"></i> Back to report queue
</a>

@if(session('success'))
  <div role="status" class="flash-success" data-toast>{{ session('success') }}</div>
@endif
@if($errors->any())
  <div role="alert" class="form-errors">{{ $errors->first() }}</div>
@endif

<div class="page-heading flex items-center justify-between flex-wrap gap-4">
  <div>
    <div class="flex items-center gap-3 flex-wrap">
      <h2 class="tracking-code">{{ $report->tracking_code }}</h2>
      <x-status-badge :status="$report->status" />
      @if($report->is_emergency)
        <span class="status-badge b-red"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Emergency</span>
      @endif
      @if($isOverdue)
        <span class="status-badge b-red" title="Due on {{ $report->due_at->format('M d, Y h:i A') }}"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Overdue</span>
      @endif
      @if($report->escalated_at)
        <span class="status-badge b-red"><i class="fa-solid fa-fire" aria-hidden="true"></i> Escalated</span>
      @endif
      @if($report->reopened_count > 0)
        <span class="status-badge b-gold"><i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Reopened</span>
      @endif
    </div>
    <p>Submitted {{ $report->created_at->format('M d, Y \a\t h:i A') }}
      @if($report->due_at)
        · <span class="{{ $isOverdue ? 'text-red-600' : '' }}">SLA Due: {{ $report->due_at->format('M d, Y h:i A') }}</span>
      @endif
    </p>
  </div>
</div>

{{-- progress strip --}}
<div class="review-progress" role="group" aria-label="Case progress">
  @foreach($steps as $i => $step)
    @php
      $done = $i < $statusStep;
      $current = $i === $statusStep;
      $waiting = in_array($report->status, ['needs_info','awaiting_confirmation']) && $i === $statusStep;
    @endphp
    <span class="review-progress-step {{ $done ? 'is-done' : '' }} {{ $current ? 'is-current' : '' }} {{ $waiting ? 'is-waiting' : '' }}">
      @if($done)<i class="fa-solid fa-check" aria-hidden="true"></i>@endif
      {{ $step }}@if($waiting) (waiting)@endif
    </span>
  @endforeach
</div>

<div class="review-layout">
  {{-- LEFT --}}
  <div class="review-primary">
    <section class="card review-card">
      <h2 class="card-title">What happened</h2>
      <p class="review-summary">{{ $oneLineSummary }}</p>

      {{-- flag banners --}}
      @if($report->duplicateSuggestions->where('decision','pending')->isNotEmpty())
        <div class="review-banner review-banner-warn" role="status">
          <span>Possible duplicate: {{ $report->duplicateSuggestions->where('decision','pending')->first()?->candidate?->tracking_code }}. <a href="#">Merge</a> | <a href="#">Not the same</a></span>
        </div>
      @endif
      @if($hasAiSuggestion && $aiResult->photo_mismatch)
        <div class="review-banner review-banner-mismatch" role="status">
          <i class="fa-solid fa-image" aria-hidden="true"></i> Photo mismatch flagged.
        </div>
      @endif
      @if($hasAiSuggestion && $aiResult->emergency_flag)
        <div class="review-banner review-banner-emergency" role="status">
          <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Possible emergency. <a href="#">Confirm emergency</a> | <a href="#">Not an emergency</a>
        </div>
      @endif

      {{-- Description tabs --}}
      <x-field label="Description" name="staff_description" :error="$errors->first('staff_description')">
        @if($hasAiSuggestion)
          <div class="review-description-tabs" role="tablist">
            <button type="button" role="tab" data-review-tab="ai" aria-selected="true">AI suggested</button>
            <button type="button" role="tab" data-review-tab="original" aria-selected="false" tabindex="-1">Original</button>
          </div>
          <div data-review-panel="ai">
            <p class="review-source-text">{{ $aiResult->summary ?: 'No AI suggested description.' }}</p>
            <button type="button" class="btn btn-outline btn-small" data-copy-description="ai">Use AI version</button>
          </div>
          <div data-review-panel="original" hidden>
            <p class="review-source-text">{{ $report->description }}</p>
            <button type="button" class="btn btn-outline btn-small" data-copy-description="original">Use original</button>
          </div>
        @endif
        <textarea id="staff_description" name="staff_description" rows="4" maxlength="2000">{{ old('staff_description', $descriptionDefault) }}</textarea>
      </x-field>

      {{-- Photo --}}
      @if($report->photos->isNotEmpty() || $report->photo_path)
        <div class="photo-preview-grid">
          @foreach($report->photos as $index => $photo)
            <a href="{{ route('admin.reports.photos.show', [$report, $photo]) }}" target="_blank"><img src="{{ route('admin.reports.photos.show', [$report, $photo]) }}" alt="Photo" class="report-photo"></a>
          @endforeach
          @if($report->photo_path && $report->photos->isEmpty())
            <a href="{{ route('admin.reports.photo', $report) }}" target="_blank"><img src="{{ route('admin.reports.photo', $report) }}" alt="Photo" class="report-photo"></a>
          @endif
        </div>
      @else
        <p class="field-hint">No photo attached</p>
      @endif

      {{-- Map --}}
      @if($report->lat !== null && $report->lng !== null)
        <div id="report-location-map" class="map-frame location-map" data-lat="{{ $report->lat }}" data-lng="{{ $report->lng }}"></div>
        <p class="field-hint">
          Purok: {{ $report->purok?->name ?? '—' }} · Landmark: {{ $report->landmark ?: '—' }} ·
          <a href="https://www.google.com/maps?q={{ $report->lat }},{{ $report->lng }}" target="_blank" rel="noopener">Open in Google Maps</a>
        </p>
      @else
        <p class="field-hint">Purok: {{ $report->purok?->name ?? '—' }} · Landmark: {{ $report->landmark ?: '—' }}</p>
      @endif
    </section>
  </div>

  {{-- RIGHT sticky action card --}}
  <aside class="review-sidebar">
    <section class="card review-card review-action-card">
      <h2 class="card-title">Next step</h2>
      @php $status = $report->status; @endphp

      @if(in_array($status, ['submitted','under_review','reopened']))
        <form method="POST" action="{{ route('admin.reports.review', $report) }}" class="review-form">
          @csrf @method('PATCH')
          <x-field label="Category" name="category_id" hint="(required)" :error="$errors->first('category_id')">
            <select id="category_id" name="category_id" required>
              <option value="">Select a category</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) old('category_id', $categoryDefault) === (string) $category->id)>{{ $category->name }}</option>
              @endforeach
            </select>
          </x-field>
          <x-field label="Urgency" name="urgency" hint="(required)" :error="$errors->first('urgency')">
            <select id="urgency" name="urgency" required>
              <option value="">Select urgency</option>
              @foreach(['Low','Medium','High','Critical'] as $u)
                <option value="{{ $u }}" @selected(old('urgency', $urgencyDefault) === $u)>{{ $u }}</option>
              @endforeach
            </select>
          </x-field>
          <x-field label="Office" name="office_id">
            <select id="office_id" name="office_id">
              <option value="">Auto recommended office</option>
              @foreach($offices as $office)
                <option value="{{ $office->id }}" @selected((string) old('office_id', $officeDefault) === (string) $office->id)>{{ $office->name }}</option>
              @endforeach
            </select>
          </x-field>
          <x-field label="Assigned to" name="assigned_to">
            <select id="assigned_to" name="assigned_to">
              <option value="">-- Unassigned --</option>
              @foreach($staffUsers as $user)
                <option value="{{ $user->id }}" @selected((string) old('assigned_to', $assignedDefault) === (string) $user->id)>{{ $user->name }}</option>
              @endforeach
            </select>
          </x-field>
          <input type="hidden" name="staff_description" value="{{ old('staff_description', $descriptionDefault) }}">
          <input type="hidden" name="photo_mismatch" value="0">
          <input type="hidden" name="is_emergency" value="{{ $report->is_emergency ? 1 : 0 }}">
          <x-field label="Message to resident" name="review_note" hint="(optional)">
            <textarea id="review_note" name="review_note" rows="3" maxlength="1000">{{ old('review_note') }}</textarea>
          </x-field>
          <div class="review-button-row">
            <button type="submit" class="btn btn-gold">Confirm and route</button>
          </div>
        </form>
      @elseif($status === 'routed')
        <p class="review-summary">Drainage, High, {{ $report->office?->name ?? '—' }}, {{ $report->assignee?->name ?? 'Unassigned' }}. <a href="#" onclick="return false;">Edit</a></p>
        <form method="POST" action="{{ route('admin.reports.status', $report) }}">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="in_progress">
          <input type="hidden" name="note" value="Work started.">
          <div class="review-button-row">
            <button type="submit" class="btn btn-gold">Start work</button>
          </div>
        </form>
      @elseif($status === 'in_progress')
        <p class="review-summary">{{ $oneLineSummary }}</p>
        <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" enctype="multipart/form-data">
          @csrf
          <x-field label="Resolution note" name="note" hint="(required)" :error="$errors->first('note')">
            <textarea id="note" name="note" rows="4" required maxlength="2000">{{ old('note') }}</textarea>
          </x-field>
          <x-field label="Proof photo" name="photo" hint="(optional)">
            <input type="file" id="photo" name="photo" accept="image/*">
          </x-field>
          <div class="review-button-row">
            <button type="submit" class="btn btn-gold">Mark resolved</button>
          </div>
        </form>
      @elseif($status === 'needs_info')
        <p>Waiting for the resident.</p>
        @foreach($report->infoRequests as $ir)
          @if($ir->responded_at)
            <div class="review-reply"><strong>Resident reply:</strong> {{ $ir->response }}</div>
          @endif
        @endforeach
        <form method="POST" action="{{ route('admin.reports.status', $report) }}">
          @csrf @method('PATCH')
          <input type="hidden" name="status" value="under_review">
          <input type="hidden" name="note" value="Resumed after resident reply.">
          <div class="review-button-row">
            <button type="submit" class="btn btn-outline">Resume</button>
          </div>
        </form>
      @elseif($status === 'awaiting_confirmation')
        <p>Waiting for the resident.</p>
        @if($report->due_at && $report->due_at->copy()->addDays($autoCloseDays)->isPast())
          <form method="POST" action="{{ route('admin.reports.close', $report) }}">
            @csrf
            <div class="review-button-row">
              <button type="submit" class="btn btn-outline">Close case</button>
            </div>
          </form>
        @endif
      @elseif($status === 'emergency_flagged')
        <div class="review-banner review-banner-emergency" role="status">
          <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Emergency flagged. Staff must confirm.
        </div>
      @else
        <p class="review-summary">{{ $oneLineSummary }}</p>
        <p class="field-hint">Status: {{ \App\Models\Report::STATUSES[$status] ?? $status }}</p>
      @endif

      {{-- More menu --}}
      <details class="review-more">
        <summary class="btn btn-outline btn-small">More</summary>
        <div class="review-more-items">
          <form method="POST" action="{{ route('admin.reports.info-requests.store', $report) }}" class="review-inline-form">
            @csrf
            <x-field label="Ask resident for info" name="question" hint="(optional)">
              <textarea id="question" name="question" rows="2" maxlength="2000" placeholder="Question for resident"></textarea>
            </x-field>
            <div class="review-button-row"><button type="submit" class="btn btn-outline btn-small">Send</button></div>
          </form>
          @if($isOverdue && !$report->escalated_at)
            <form method="POST" action="{{ route('admin.reports.escalate', $report) }}">
              @csrf
              <div class="review-button-row"><button type="submit" class="btn btn-red btn-small">Escalate</button></div>
            </form>
          @endif
        </div>
      </details>
    </section>
  </aside>
</div>

{{-- Tabs below --}}
<div class="review-tabs">
  <div class="review-tablist" role="tablist">
    <button type="button" role="tab" aria-selected="true" data-tab="activity">Activity</button>
    <button type="button" role="tab" aria-selected="false" data-tab="notes">Internal notes</button>
    <button type="button" role="tab" aria-selected="false" data-tab="reporter">Reporter</button>
  </div>

  <div data-tabpanel="activity">
    <ol class="timeline">
      @forelse($report->statusLogs as $log)
        <li>
          <strong>{{ \App\Models\Report::STATUSES[$log->to_status] ?? ucfirst(str_replace('_',' ',$log->to_status)) }}</strong>
          <small>{{ $log->created_at->format('M d, Y h:i A') }} · {{ $log->user?->name ?? 'Resident or system' }}</small>
          @if($log->note)<small>{{ $log->note }}</small>@endif
        </li>
      @empty
        <li>No activity yet.</li>
      @endforelse
      @foreach($report->infoRequests as $ir)
        <li><strong>Info requested</strong><small>{{ $ir->created_at->format('M d, Y h:i A') }}</small><small>{{ $ir->question }}</small>@if($ir->response)<small>Reply: {{ $ir->response }}</small>@endif</li>
      @endforeach
      @foreach($report->internalNotes as $note)
        <li><strong>Internal note</strong><small>{{ $note->created_at->format('M d, Y h:i A') }} · {{ $note->user?->name }}</small><small>{{ $note->body }}</small></li>
      @endforeach
    </ol>
  </div>

  <div data-tabpanel="notes" hidden>
    <form method="POST" action="{{ route('admin.reports.internal-notes.store', $report) }}">
      @csrf
      <x-field label="Add internal note" name="body" hint="(optional)">
        <textarea id="internal_note_body" name="body" rows="3" maxlength="3000">{{ old('body') }}</textarea>
      </x-field>
      <div class="review-button-row"><button type="submit" class="btn btn-navy btn-small">Add internal note</button></div>
    </form>
    @foreach($report->internalNotes as $note)
      <div class="internal-note"><strong>{{ $note->user?->name ?? 'Staff' }}</strong> <small>{{ $note->created_at->format('M d, Y h:i A') }}</small><p>{{ $note->body }}</p></div>
    @endforeach
  </div>

  <div data-tabpanel="reporter" hidden>
    @if($report->is_anonymous || (!$report->reporter_name && !$report->reporter_contact && !$report->reporter_email))
      <p>Anonymous, no contact provided</p>
    @else
      <p>{{ $report->reporter_name ?: '—' }} · {{ $report->reporter_contact ?: '—' }} · {{ $report->reporter_email ?: '—' }}</p>
    @endif
  </div>
</div>
@endsection
@push('scripts')
<script>
document.querySelectorAll('[data-tab]').forEach(b=>{b.addEventListener('click',()=>{const t=b.dataset.tab;document.querySelectorAll('[data-tab]').forEach(x=>x.setAttribute('aria-selected',x===b));document.querySelectorAll('[data-tabpanel]').forEach(p=>p.hidden=p.dataset.tabpanel!==t);});});
document.querySelectorAll('[data-review-tab]').forEach(b=>{b.addEventListener('click',()=>{const t=b.dataset.reviewTab;document.querySelectorAll('[data-review-tab]').forEach(x=>x.setAttribute('aria-selected',x===b));document.querySelectorAll('[data-review-panel]').forEach(p=>p.hidden=p.dataset.reviewPanel!==t);});});
</script>
@endpush
