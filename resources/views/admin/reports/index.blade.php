@extends('layouts.admin')
@section('title', $isMyCases ? 'My Cases' : 'Report Queue')
@section('page-heading', $isMyCases ? 'My assigned cases' : 'Report queue')

@section('content')
<div class="page-heading">
  <h2>{{ $isMyCases ? 'My assigned cases' : 'Resident reports' }}</h2>
  <p>{{ $isMyCases ? 'Reports currently assigned to you for inspection, response, and resolution.' : 'Review incoming reports, narrow the queue, and open a report to manage its status.' }}</p>
</div>

@php
  $preservedFilters = request()->only([
    'q', 'purok_id', 'category_id', 'assigned_to', 'status', 'emergency', 'sort', 'direction', 'pin', 'year',
  ]);

  // Base params every tab / header link keeps so filters survive navigation.
  $baseParams = array_merge($preservedFilters, ['stage' => $currentStage]);
  if ($statusFilter !== null && $statusFilter !== 'all') {
      $baseParams['status'] = $statusFilter;
  }

  $emergencyFilterParams = $baseParams;
  if ($emergencyOnly) {
      unset($emergencyFilterParams['emergency']);
  } else {
      $emergencyFilterParams['emergency'] = '1';
  }

  $clearFilterParams = ['stage' => $currentStage];
  if ($statusFilter !== null && $statusFilter !== 'all') {
      $clearFilterParams['status'] = $statusFilter;
  }
  if ($emergencyOnly) {
      $clearFilterParams['emergency'] = '1';
  }

  // Column-header sort links: clicking the active column flips the direction.
  $sortLink = function (string $column) use ($baseParams, $isMyCases) {
      $active = request('sort') === $column;
      $params = $baseParams;
      $params['sort'] = $column;
      $params['direction'] = $active ? (request('direction') === 'asc' ? 'desc' : 'asc') : 'desc';

      return [
          'url' => route($isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index', $params),
          'active' => $active,
          'ascending' => $active && request('direction') === 'asc',
      ];
  };
  $submittedSort = $sortLink('submitted');
  $statusSort = $sortLink('status');
  $urgencySort = $sortLink('urgency');

  $exportParams = $clearFilterParams;
  foreach (['q', 'purok_id', 'category_id', 'assigned_to', 'sort', 'direction', 'pin', 'year'] as $exportKey) {
      if (request()->filled($exportKey)) {
          $exportParams[$exportKey] = request($exportKey);
      }
  }

  $queueRoute = $isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index';

  // Icon-only status rendering for the queue table cells (mirrors the
  // x-status-badge component, but without the text label).
  $statusIcons = [
    'submitted' => ['fa-paper-plane', 'gold'],
    'under_review' => ['fa-magnifying-glass', 'gold'],
    'routed' => ['fa-route', 'blue'],
    'in_progress' => ['fa-gears', 'blue'],
    'resolved' => ['fa-circle-check', 'green'],
    'no_action' => ['fa-circle-xmark', 'gray'],
    'merged' => ['fa-code-merge', 'gray'],
    'referred' => ['fa-share', 'gray'],
    'emergency_flagged' => ['fa-triangle-exclamation', 'red'],
    'needs_info' => ['fa-clipboard-question', 'gold'],
    'awaiting_confirmation' => ['fa-clock', 'blue'],
    'reopened' => ['fa-arrow-rotate-left', 'red'],
  ];

  $statusMeanings = [
    'submitted' => 'Newly received from a resident; awaiting review.',
    'under_review' => 'A staff member is reviewing this report.',
    'routed' => 'Assigned to a barangay office or department.',
    'in_progress' => 'Work on the issue is underway.',
    'needs_info' => 'Waiting for the resident to answer staff questions.',
    'awaiting_confirmation' => 'Fix reported; waiting for the resident to confirm.',
    'resolved' => 'The issue was fixed and verified.',
    'reopened' => 'The resident reported the same issue again.',
    'referred' => 'Forwarded to an external agency for action.',
    'merged' => 'Duplicate of another report; hidden from the queue.',
    'no_action' => 'No further action taken by the barangay.',
    'emergency_flagged' => 'Possible emergency; needs immediate staff review.',
  ];

  $getInitials = function (?string $name): string {
      if (!$name) return '—';
      $clean = preg_replace('/^(hon\.|dr\.|engr\.|atty\.)\s+/i', '', trim($name));
      $words = preg_split('/\s+/', $clean);
      if (count($words) >= 2) {
          return strtoupper(mb_substr($words[0], 0, 1) . mb_substr(end($words), 0, 1));
      }
      return strtoupper(mb_substr($clean, 0, 2));
  };

  $getShortAge = function (\Illuminate\Support\Carbon $dt): string {
      $sec = max(1, now()->diffInSeconds($dt));
      if ($sec < 3600) return max(1, (int) round($sec / 60)) . 'm ago';
      if ($sec < 86400) return (int) round($sec / 3600) . 'h ago';
      if ($sec < 2592000) return (int) round($sec / 86400) . 'd ago';
      return $dt->format('M j');
  };
@endphp

<x-ui.queue-filters
  :current-stage="$currentStage"
  :stage-counts="$stageCounts"
  :status-filter="$statusFilter"
  :status-labels="$statusLabels"
  :status-counts="$statusCounts"
  :status-groups="$statusGroups"
  :emergency-only="$emergencyOnly"
  :emergency-count="$emergencyCount"
  :is-my-cases="$isMyCases"
  :my-cases-count="$stageCounts->get('my_cases', 0)"
  :overdue-only="request('overdue') === '1'"
  :overdue-count="$stageCounts->get('overdue', 0)"
  :puroks="$puroks"
  :categories="$categories"
  :staff-users="$staffUsers"
  :fiscal-years="$fiscalYears"
  :ai-status-filter="$aiStatusFilter"
  :queue-route="$queueRoute"
/>

<div class="queue-toolbar">
  <span class="queue-count">{{ $reports->total() }} report{{ $reports->total() === 1 ? '' : 's' }}</span>
  <span class="queue-spacer"></span>
  <label class="queue-pin-toggle">
    <input type="checkbox" class="table-checkbox"
      onchange="window.location='{{ route($queueRoute, array_merge($baseParams, ['pin' => request('pin') === '1' ? '0' : '1'])) }}'"
      @checked(request('pin') === '1')>
    Pin emergencies to top
  </label>
  @if(auth()->user()->can('reports.log_phone'))
    <a class="btn btn-navy btn-small" href="{{ route('admin.reports.phone.create') }}" style="background:#2563eb;color:#fff;display:inline-flex;align-items:center;gap:0.35rem;">
      <i class="fa-solid fa-phone-volume" aria-hidden="true"></i> Record phone report
    </a>
  @endif
  <a class="btn btn-navy btn-small" href="{{ route('admin.reports.export', $exportParams) }}" data-turbo="false">
    <i class="fa-solid fa-file-csv" aria-hidden="true"></i> Export CSV
  </a>
</div>

{{-- Bulk bar: checkboxes reference this form so it lives outside the table. --}}
<form id="queue-bulk-form" method="POST" action="{{ route('admin.reports.bulk-action') }}" class="bulk-bar" style="display:none;">
  @csrf
  <span class="bulk-count"><span id="bulk-count">0</span> selected</span>
  <button type="submit" name="action" value="assign_me" class="bulk-btn">
    <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Assign to me
  </button>
  <select name="status" class="bulk-select" aria-label="Status for bulk change">
    <option value="">Change status to…</option>
    @foreach(\App\Models\Report::MANUAL_STATUSES as $manualStatus)
      <option value="{{ $manualStatus }}">{{ $statusLabels[$manualStatus] }}</option>
    @endforeach
  </select>
  <button type="submit" name="action" value="status" class="bulk-btn">Apply status</button>
  <span class="bulk-spacer"></span>
  <button type="button" id="bulk-clear-btn" class="bulk-btn">
    <i class="fa-solid fa-xmark mr-1" aria-hidden="true"></i> Clear
  </button>
  <button type="submit" formaction="{{ route('admin.reports.bulk-confirm') }}"
    class="bulk-btn" onclick="return confirm('Confirm and route selected reports?');">
    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Confirm &amp; route
  </button>
</form>

<div class="table-wrap">
  <table class="data-table data-table-queue is-comfortable table-fixed w-full">
    <thead>
      <tr>
        <th scope="col" style="width:2.5rem;" class="queue-th-select">
          <input type="checkbox" class="table-checkbox" id="select-all-checkbox" aria-label="Select all reports">
        </th>
        <th scope="col" class="queue-th-report" style="width:20rem;">
          <span>Report</span>
        </th>
        <th scope="col" class="sortable queue-th-priority hidden md:table-cell" style="width:6.5rem;">
          <a href="{{ $urgencySort['url'] }}" class="inline-flex items-center gap-1 text-xs font-normal text-slate-500 hover:text-blue-700" title="Sort by priority">
            <span>Priority</span>
            <span class="sort-indicator {{ $urgencySort['active'] ? '' : 'idle' }}" aria-hidden="true">
              {{ $urgencySort['active'] ? ($urgencySort['ascending'] ? '▲' : '▼') : '⇅' }}
            </span>
            @if($urgencySort['active'])<span class="sr-only">(sorted by priority {{ $urgencySort['ascending'] ? 'ascending' : 'descending' }})</span>@endif
          </a>
        </th>
        <th scope="col" class="sortable queue-th-status hidden md:table-cell" style="width:10rem;">
          <span class="th-status">
            <a href="{{ $statusSort['url'] }}">Status
              <span class="sort-indicator {{ $statusSort['active'] ? '' : 'idle' }}" aria-hidden="true">
                {{ $statusSort['active'] ? ($statusSort['ascending'] ? '▲' : '▼') : '⇅' }}
              </span>
              @if($statusSort['active'])<span class="sr-only">(sorted {{ $statusSort['ascending'] ? 'ascending' : 'descending' }})</span>@endif
            </a>
            <button type="button" class="status-legend-btn" data-legend-open
              aria-haspopup="dialog" title="What the statuses mean">
              <i class="fa-solid fa-circle-question" aria-hidden="true"></i>
              <span class="sr-only">What the statuses mean</span>
            </button>
          </span>
        </th>
        <th scope="col" class="queue-th-next" style="width:12.5rem;">Next Action</th>
        <th scope="col" class="sortable queue-th-assignee" style="width:8rem;">
          <a href="{{ $submittedSort['url'] }}">Assignee
            <span class="sort-indicator {{ $submittedSort['active'] ? '' : 'idle' }}" aria-hidden="true">
              {{ $submittedSort['active'] ? ($submittedSort['ascending'] ? '▲' : '▼') : '⇅' }}
            </span>
            @if($submittedSort['active'])<span class="sr-only">(sorted {{ $submittedSort['ascending'] ? 'ascending' : 'descending' }})</span>@endif
          </a>
        </th>
        <th scope="col" class="queue-th-actions" style="width:3.5rem;">
          <span class="sr-only">Actions</span>
        </th>
      </tr>
    </thead>
    <tbody>
      @forelse($reports as $report)
        @php
          $unreadCount = (int) ($unreadReplies->get($report->id, 0));
          $isClosed = in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true);
          $isEmergency = $report->is_emergency || $report->status === 'emergency_flagged';
          $next = $report->getNextAction();
          $urgency = strtolower($report->urgency ?? 'low');
          $priorityStripe = match($urgency) {
              'critical' => 'border-l-4 border-l-red-600',
              'high' => 'border-l-4 border-l-amber-500',
              default => 'border-l-4 border-l-transparent',
          };
          $isAssigneeActor = $report->assignee && strcasecmp(trim($report->assignee->name), trim($next['actor'])) === 0;
        @endphp
        <tr data-report-row="{{ $report->id }}" data-href="{{ route('admin.reports.show', $report) }}" class="queue-row relative group hover:bg-slate-50/90 transition-colors {{ $priorityStripe }} {{ $isEmergency ? 'is-emergency-row' : '' }}">
          <td class="queue-col-select relative z-20 text-center" data-label="Select">
            <input type="checkbox" name="report_ids[]" value="{{ $report->id }}"
              class="report-checkbox table-checkbox" form="queue-bulk-form"
              aria-label="Select report {{ $report->tracking_code }}">
          </td>
          <td class="queue-col-report" data-label="Report">
            <div class="flex items-center gap-1.5 min-w-0">
              @if($isEmergency)
                <i class="fa-solid fa-triangle-exclamation text-red-600 text-xs shrink-0" title="Emergency" aria-hidden="true"></i>
              @endif
              <a href="{{ route('admin.reports.show', $report) }}"
                 class="cell-report-code font-mono text-xs font-bold text-blue-700 hover:text-blue-900 hover:underline shrink-0 after:absolute after:inset-0 after:z-10 focus:outline-none focus:ring-2 focus:ring-blue-600 rounded">
                {{ $report->tracking_code }}
              </a>
              @if($report->source === 'voice')
                <span title="Voice report" class="inline-flex items-center text-indigo-600 text-[11px] shrink-0" aria-label="Voice report">
                  <i class="fa-solid fa-microphone"></i>
                </span>
              @elseif($report->source === 'phone_log')
                <span title="Phone intake" class="inline-flex items-center text-teal-600 text-[11px] shrink-0" aria-label="Phone intake">
                  <i class="fa-solid fa-phone"></i>
                </span>
              @endif
              @if($report->reopened_count > 0)
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-50 text-amber-700 border border-amber-200 shrink-0" title="Reopened">
                  <i class="fa-solid fa-arrow-rotate-left mr-0.5" aria-hidden="true"></i>Reopened
                </span>
              @endif
              @if($report->ai_status === 'failed')
                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-rose-50 text-rose-700 border border-rose-200 shrink-0" title="AI screening unavailable — manual review required.">
                  <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>Manual screening
                </span>
              @endif
              <span class="text-xs text-slate-700 truncate max-w-[200px] md:max-w-[220px] lg:max-w-[260px] inline-block" title="{{ $report->summary ?: $report->description }}">
                {{ $report->summary ?: $report->description }}
              </span>
              {{-- Mobile status badge --}}
              <span class="md:hidden ml-auto shrink-0 relative z-20">
                <x-status-badge :status="$report->status" :show-icon="false" class="text-xs" />
              </span>
            </div>
            <div class="text-[11px] text-slate-500 truncate mt-0.5">
              <span>{{ $report->category?->name ?? 'No category' }}</span>
              <span class="text-slate-300">·</span>
              <span>{{ $report->purok?->name ?? '—' }}</span>
              <span class="text-slate-300">·</span>
              <span title="{{ $report->created_at->format('M j, Y g:i A') }}">{{ $getShortAge($report->created_at) }}</span>
            </div>
          </td>
          <td class="queue-col-priority hidden md:table-cell" data-label="Priority">
            @if($urgency === 'critical')
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-red-100 text-red-700 uppercase tracking-wide shrink-0">Critical</span>
            @elseif($urgency === 'high')
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-amber-100 text-amber-800 uppercase tracking-wide shrink-0">High</span>
            @elseif($urgency === 'medium')
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium text-slate-600 bg-slate-100">Medium</span>
            @elseif($urgency === 'low')
              <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-normal text-slate-400 bg-slate-50">Low</span>
            @else
              <span class="text-xs text-slate-300">—</span>
            @endif
            <span class="sr-only">Priority: {{ ucfirst($urgency) }}</span>
          </td>
          <td class="queue-col-status hidden md:table-cell" data-label="Status">
            <x-status-badge :status="$report->status" :show-icon="false" class="text-xs" />
          </td>
          <td class="queue-col-next" data-label="Next Action">
            <div class="text-xs font-semibold text-slate-800 truncate" title="{{ $next['action'] }}">{{ $next['action'] }}</div>
            <div class="text-[11px] text-slate-500 truncate mt-0.5">
              @if(!$isAssigneeActor)
                <span>{{ $next['actor'] }}</span>
              @endif
              @if($next['due_text'])
                @if(!$isAssigneeActor)<span class="text-slate-300">·</span>@endif
                <span class="text-red-600 font-semibold" @if($next['due_tone'] === 'red') title="Overdue" @endif>{{ $next['due_text'] }}</span>
              @elseif($isAssigneeActor)
                <span class="text-slate-400">Assigned</span>
              @endif
            </div>
          </td>
          <td class="queue-col-assignee" data-label="Assignee">
            <div class="flex items-center gap-2 min-w-0">
              <span class="inline-flex items-center justify-center w-7 h-7 rounded-full text-[11px] font-bold shrink-0 {{ $report->assignee ? 'bg-blue-100 text-blue-700' : 'border border-dashed border-slate-300 text-slate-400 bg-slate-50' }}"
                    title="{{ $report->assignee?->name ?? 'Unassigned' }}"
                    aria-label="{{ $report->assignee?->name ?? 'Unassigned' }}">{{ $getInitials($report->assignee?->name) }}</span>
              @if($report->assignee)
                <span class="hidden xl:inline text-xs text-slate-700 font-medium truncate max-w-[110px]" title="{{ $report->assignee->name }}">
                  {{ $report->assignee->name }}
                </span>
              @else
                <span class="hidden xl:inline text-xs text-slate-400">Unassigned</span>
              @endif
            </div>
          </td>
          <td class="queue-col-actions relative z-20 text-center" data-label="Actions">
            <div class="kebab">
              <button type="button" class="kebab-toggle text-slate-400 hover:text-slate-700 p-1 rounded hover:bg-slate-100 transition-colors" data-kebab-toggle
                aria-haspopup="true" aria-expanded="false"
                aria-label="More actions for {{ $report->tracking_code }}">
                <i class="fa-solid fa-ellipsis-vertical" aria-hidden="true"></i>
              </button>
              <div class="kebab-menu" hidden>
                <form method="POST" action="{{ route('admin.reports.assign', $report) }}">
                  @csrf
                  @method('PATCH')
                  <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
                  <button type="submit" class="kebab-item" @disabled($report->assigned_to === auth()->id())>
                    <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Assign to me
                  </button>
                </form>
                <form method="POST" action="{{ route('admin.reports.status', $report) }}">
                  @csrf
                  @method('PATCH')
                  <select name="status" class="kebab-status-select" aria-label="New status for {{ $report->tracking_code }}">
                    <option value="">Change status…</option>
                    @foreach(\App\Models\Report::MANUAL_STATUSES as $manualStatus)
                      <option value="{{ $manualStatus }}" @disabled($manualStatus === $report->status)>{{ $statusLabels[$manualStatus] }}</option>
                    @endforeach
                  </select>
                  <select name="no_action_reason" class="kebab-reason-select" style="display:none;margin-top:0.35rem;width:100%;font-size:0.8rem;padding:0.35rem;border:1px solid var(--line);border-radius:6px;" aria-label="Reason code for no action">
                    <option value="">Reason for no action…</option>
                    @foreach(\App\Models\Report::NO_ACTION_REASONS as $reasonCode => $reasonLabel)
                      <option value="{{ $reasonCode }}">{{ $reasonLabel }}</option>
                    @endforeach
                  </select>
                  <input type="text" name="note" maxlength="500" placeholder="Note (required for no action)">
                  <button type="submit" class="kebab-item">
                    <i class="fa-solid fa-arrows-rotate" aria-hidden="true"></i> Apply status
                  </button>
                </form>
                <button type="button" class="kebab-item"
                  data-chat-open data-report-id="{{ $report->id }}"
                  data-chat-tracking="{{ $report->tracking_code }}"
                  data-chat-category="{{ $report->category?->name ?? 'Category not assigned' }}"
                  data-chat-status="{{ \App\Models\Report::STATUSES[$report->status] ?? $report->status }}"
                  data-chat-summary="{{ \Illuminate\Support\Str::limit($report->summary ?: $report->description, 160) }}">
                  <i class="fa-solid fa-comment-dots" aria-hidden="true"></i> Ask for info
                  @if($unreadCount > 0)
                    <span class="review-info-summary-unread">{{ $unreadCount }}</span>
                  @endif
                </button>
                @if(!$isEmergency && !$isClosed)
                  <form method="POST" action="{{ route('admin.reports.emergency', $report) }}"
                    data-confirm-submit="Flag this report as an emergency concern?">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="confirm" value="1">
                    <button type="submit" class="kebab-item">
                      <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Mark urgent
                    </button>
                  </form>
                @endif
              </div>
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="table-empty text-center py-8 text-slate-500" data-label="">
            <i class="fa-regular fa-folder-open text-3xl mb-2 text-slate-400 block" aria-hidden="true"></i>
            <p class="text-sm font-medium">No reports match your filters.</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="pagination-wrap">{{ $reports->links() }}</div>

{{-- Status icon legend: opened from the "?" button beside the Status header. --}}
<dialog id="status-legend-modal" class="legend-modal" role="dialog" aria-modal="true" aria-labelledby="status-legend-title">
  <div class="legend-frame">
    <header class="legend-header">
      <h2 id="status-legend-title">Status icons</h2>
      <button type="button" class="review-modal-close" data-legend-close aria-label="Close">&times;</button>
    </header>
    <ol class="legend-list">
      @foreach($statusIcons as $legendStatus => [$legendIcon, $legendTone])
        <li>
          <i class="status-icon fa-solid {{ $legendIcon }} si-{{ $legendTone }}" aria-hidden="true"></i>
          <div>
            <strong>{{ $statusLabels[$legendStatus] ?? $legendStatus }}</strong>
            <p>{{ $statusMeanings[$legendStatus] ?? '' }}</p>
          </div>
        </li>
      @endforeach
    </ol>
    <p class="legend-note">Flag icons: <i class="fa-solid fa-clock text-red-600" aria-hidden="true"></i> overdue ·
      <i class="fa-solid fa-fire text-red-600" aria-hidden="true"></i> escalated ·
      <i class="fa-solid fa-arrow-rotate-left text-amber-600" aria-hidden="true"></i> reopened.</p>
  </div>
</dialog>

{{-- Shared chat modal for "Ask for info" from both the kebab menus and the review page. --}}
@include('admin.reports.partials.info-thread-modal')

<script nonce>
(function () {
  'use strict';

  const bulkForm = document.getElementById('queue-bulk-form');
  const bulkCount = document.getElementById('bulk-count');
  const bulkClearBtn = document.getElementById('bulk-clear-btn');
  const selectAll = document.getElementById('select-all-checkbox');

  const checkedBoxes = () => document.querySelectorAll('.report-checkbox:checked');

  const updateBulkBar = () => {
    const count = checkedBoxes().length;
    if (bulkForm) bulkForm.style.display = count > 0 ? 'flex' : 'none';
    if (bulkCount) bulkCount.textContent = count;
    if (selectAll) {
      const allBoxes = document.querySelectorAll('.report-checkbox');
      selectAll.checked = allBoxes.length > 0 && count === allBoxes.length;
      selectAll.indeterminate = count > 0 && count < allBoxes.length;
    }
  };

  if (selectAll) {
    selectAll.addEventListener('change', () => {
      document.querySelectorAll('.report-checkbox').forEach((box) => {
        box.checked = selectAll.checked;
      });
      updateBulkBar();
    });
  }

  if (bulkClearBtn) {
    bulkClearBtn.addEventListener('click', () => {
      document.querySelectorAll('.report-checkbox').forEach((box) => {
        box.checked = false;
      });
      updateBulkBar();
    });
  }

  document.querySelectorAll('.report-checkbox').forEach((box) => {
    box.addEventListener('change', updateBulkBar);
  });

  // Kebab menus: open one at a time, close on outside click or Escape.
  const closeKebabs = (except) => {
    document.querySelectorAll('.kebab.open').forEach((kebab) => {
      if (kebab === except) return;
      kebab.classList.remove('open');
      kebab.querySelector('.kebab-menu').hidden = true;
      kebab.querySelector('[data-kebab-toggle]').setAttribute('aria-expanded', 'false');
    });
  };

  document.addEventListener('click', (event) => {
    const toggle = event.target.closest('[data-kebab-toggle]');
    if (toggle) {
      const kebab = toggle.closest('.kebab');
      const isOpen = kebab.classList.contains('open');
      closeKebabs();
      kebab.classList.toggle('open', !isOpen);
      kebab.querySelector('.kebab-menu').hidden = isOpen;
      toggle.setAttribute('aria-expanded', String(!isOpen));
      return;
    }
    if (!event.target.closest('.kebab')) closeKebabs();
  });

  document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') closeKebabs();
  });

  // Reveal reason select and toggle required validation when no_action is chosen.
  document.querySelectorAll('.kebab-status-select').forEach((select) => {
    select.addEventListener('change', () => {
      const form = select.closest('form');
      const reasonSelect = form?.querySelector('.kebab-reason-select');
      const noteInput = form?.querySelector('input[name="note"]');
      if (reasonSelect) {
        const isNoAction = select.value === 'no_action';
        reasonSelect.style.display = isNoAction ? 'block' : 'none';
        reasonSelect.required = isNoAction;
        if (noteInput) {
          noteInput.placeholder = isNoAction
            ? 'Note (required for no action)'
            : 'Note (optional)';
        }
      }
    });
  });

  // Whole-row clickability fallback for non-anchor clicks
  document.querySelectorAll('tr[data-href]').forEach((row) => {
    row.addEventListener('click', (event) => {
      if (event.target.closest('input, button, select, a, .kebab, label, [data-chat-open]')) {
        return;
      }
      window.location.href = row.dataset.href;
    });
  });

  // Confirm dialogs for kebab forms (mark urgent).
  document.querySelectorAll('form[data-confirm-submit]').forEach((form) => {
    form.addEventListener('submit', (event) => {
      if (!window.confirm(form.dataset.confirmSubmit)) event.preventDefault();
    });
  });

  // Status legend modal (centered <dialog>): open from the "?" button.
  const legendModal = document.getElementById('status-legend-modal');
  if (legendModal) {
    document.querySelectorAll('[data-legend-open]').forEach((button) => {
      button.addEventListener('click', () => legendModal.showModal());
    });
    legendModal.querySelectorAll('[data-legend-close]').forEach((button) => {
      button.addEventListener('click', () => legendModal.close());
    });
    legendModal.addEventListener('click', (event) => {
      if (event.target === legendModal) legendModal.close();
    });
  }
})();
</script>
@endsection
