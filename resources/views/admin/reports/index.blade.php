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
@endphp

@php
  $lifecycleTabs = [
    'needs_review' => ['label' => 'Needs review', 'count' => $stageCounts->get('needs_review', 0)],
    'in_progress' => ['label' => 'In progress', 'count' => $stageCounts->get('in_progress', 0)],
    'waiting' => ['label' => 'Waiting on others', 'count' => $stageCounts->get('waiting', 0)],
    'done' => ['label' => 'Done', 'count' => $stageCounts->get('done', 0)],
    'my_cases' => ['label' => 'My cases', 'count' => $stageCounts->get('my_cases', 0)],
    'overdue' => ['label' => 'Overdue', 'count' => $stageCounts->get('overdue', 0)],
  ];
@endphp

<div class="lifecycle-filter-toolbar">
  <nav class="status-filters" aria-label="Filter reports by lifecycle stage">
    <a href="{{ route($queueRoute, array_merge($preservedFilters, ['stage' => 'all'])) }}"
      @class(['status-filter', 'active' => $currentStage === 'all'])>
      All
      @if($stageCounts->get('all', 0) > 0)
        <span>{{ $stageCounts->get('all') }}</span>
      @endif
    </a>
    @foreach($lifecycleTabs as $stage => $tab)
      <a href="{{ route($queueRoute, array_merge($preservedFilters, ['stage' => $stage])) }}"
        @class(['status-filter', 'active' => $currentStage === $stage])>
        {{ $tab['label'] }}
        @if($tab['count'] > 0)
          <span>{{ $tab['count'] }}</span>
        @endif
      </a>
    @endforeach
  </nav>

  <form method="GET" action="{{ route($queueRoute) }}" class="status-stage-mobile">
    @foreach($preservedFilters as $name => $value)
      <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <label class="sr-only" for="mobile-stage">Lifecycle stage</label>
    <select id="mobile-stage" name="stage">
      <option value="all" @selected($currentStage === 'all')>All ({{ $stageCounts->get('all', 0) }})</option>
      @foreach($lifecycleTabs as $stage => $tab)
        <option value="{{ $stage }}" @selected($currentStage === $stage)>{{ $tab['label'] }} ({{ $tab['count'] }})</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-navy btn-small">Apply</button>
  </form>

  <a href="{{ route($queueRoute, $emergencyFilterParams) }}"
    class="status-filter emergency-toggle {{ $emergencyOnly ? 'active' : '' }}"
    aria-pressed="{{ $emergencyOnly ? 'true' : 'false' }}">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    Emergency <span>{{ $emergencyCount }}</span>
  </a>
</div>

@if(isset($statusGroups[$currentStage]['statuses']) && count($statusGroups[$currentStage]['statuses']) > 1)
  <nav class="status-subfilters" aria-label="Filter by exact status">
    <span>Show:</span>
    <a href="{{ route($queueRoute, array_merge($preservedFilters, ['stage' => $currentStage])) }}"
      @class(['status-subfilter', 'active' => $statusFilter === null || $statusFilter === 'all'])>
      All
    </a>
    @foreach($statusGroups[$currentStage]['statuses'] as $status)
      <a href="{{ route($queueRoute, array_merge($preservedFilters, [
          'stage' => $currentStage,
          'status' => $status,
      ])) }}"
        @class(['status-subfilter', 'active' => $statusFilter === $status])>
        {{ $statusLabels[$status] }}
        @if($statusCounts->get($status, 0) > 0)
          <span>{{ $statusCounts->get($status) }}</span>
        @endif
      </a>
    @endforeach
  </nav>
@endif

@php
  $activeFilterCount = collect(['purok_id', 'category_id', 'assigned_to', 'year'])
      ->filter(fn($k) => request()->filled($k) && request($k) !== 'all')
      ->count();
  if ($statusFilter !== null && $statusFilter !== 'all' && !in_array($currentStage, ['active', 'closed-other'], true)) {
      $activeFilterCount++;
  }
@endphp

{{-- Compact filter row: search bar with secondary filters in a popover drawer --}}
<form method="GET" action="{{ route($queueRoute) }}" class="queue-filter-form mb-4">
  <input type="hidden" name="stage" value="{{ $currentStage }}">
  @if($statusFilter !== null && $statusFilter !== 'all')
    <input type="hidden" name="status" value="{{ $statusFilter }}">
  @endif
  @if($emergencyOnly)
    <input type="hidden" name="emergency" value="1">
  @endif

  <div class="flex items-center gap-2.5 flex-wrap w-full">
    <div class="queue-filter-field grow min-w-[240px]" style="margin-bottom:0;">
      <label for="q" class="sr-only">Search</label>
      <div style="position:relative;width:100%;">
        <i class="fa-solid fa-magnifying-glass" style="position:absolute;left:0.75rem;top:50%;transform:translateY(-50%);color:#94a3b8;font-size:0.85rem;" aria-hidden="true"></i>
        <input id="q" type="search" name="q" value="{{ request('q') }}" placeholder="Tracking code or description" style="padding-left:2.2rem;width:100%;">
      </div>
    </div>

    <details class="queue-filters-drawer" style="position:relative;" @if($activeFilterCount > 0) open @endif>
      <summary class="btn btn-outline btn-small" style="display:inline-flex;align-items:center;gap:0.4rem;cursor:pointer;list-style:none;">
        <i class="fa-solid fa-sliders text-blue-600" aria-hidden="true"></i>
        <span>Filters</span>
        @if($activeFilterCount > 0)
          <span style="background:var(--blue);color:#fff;border-radius:999px;padding:0.1rem 0.45rem;font-size:0.72rem;font-weight:700;">{{ $activeFilterCount }}</span>
        @endif
        <i class="fa-solid fa-chevron-down" style="font-size:0.7rem;color:#94a3b8;" aria-hidden="true"></i>
      </summary>

      <div class="card queue-filter-popover" style="position:absolute;right:0;top:calc(100% + 0.5rem);z-index:40;width:320px;padding:1.15rem;box-shadow:0 10px 25px -5px rgba(0,0,0,0.1), 0 8px 10px -6px rgba(0,0,0,0.1);border:1px solid var(--line);border-radius:14px;background:#fff;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding-bottom:0.5rem;margin-bottom:0.75rem;border-bottom:1px solid #f1f5f9;">
          <strong style="font-size:0.88rem;color:var(--ink);">Secondary Filters</strong>
          @if($activeFilterCount > 0)
            <a href="{{ route($queueRoute, $clearFilterParams) }}" style="font-size:0.75rem;color:var(--blue);font-weight:600;">Clear all</a>
          @endif
        </div>

        <div style="display:flex;flex-direction:column;gap:0.75rem;">
          <div>
            <label for="purok_id" style="display:block;font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:0.25rem;">Purok</label>
            <select id="purok_id" name="purok_id" style="width:100%;font-size:0.85rem;">
              <option value="">All puroks</option>
              @foreach($puroks as $purok)
                <option value="{{ $purok->id }}" @selected((string) request('purok_id') === (string) $purok->id)>
                  {{ $purok->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label for="category_id" style="display:block;font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:0.25rem;">Category</label>
            <select id="category_id" name="category_id" style="width:100%;font-size:0.85rem;">
              <option value="">All categories</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) request('category_id') === (string) $category->id)>
                  {{ $category->name }}
                </option>
              @endforeach
            </select>
          </div>

          <div>
            <label for="queue-status-filter" style="display:block;font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:0.25rem;">Status</label>
            <select id="queue-status-filter" name="status" style="width:100%;font-size:0.85rem;">
              <option value="">All statuses</option>
              @foreach($statusLabels as $statusKey => $statusLabel)
                <option value="{{ $statusKey }}" @selected($statusFilter === $statusKey)>{{ $statusLabel }}</option>
              @endforeach
            </select>
          </div>

          @if(!$isMyCases)
            <div>
              <label for="assigned_to" style="display:block;font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:0.25rem;">Assignee</label>
              <select id="assigned_to" name="assigned_to" style="width:100%;font-size:0.85rem;">
                <option value="">All assignees</option>
                <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>Unassigned</option>
                @foreach($staffUsers as $user)
                  <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>
                    {{ $user->name }}
                  </option>
                @endforeach
              </select>
            </div>
          @else
            <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
          @endif

          @if(!empty($fiscalYears) && $fiscalYears->isNotEmpty())
            <div>
              <label for="queue-year-filter" style="display:block;font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:0.25rem;">Fiscal Year</label>
              <select id="queue-year-filter" name="year" style="width:100%;font-size:0.85rem;">
                <option value="">All years</option>
                @foreach($fiscalYears as $fy)
                  <option value="{{ $fy->year }}" @selected((string) request('year') === (string) $fy->year)>
                    {{ $fy->label ?? 'FY ' . $fy->year }}
                  </option>
                @endforeach
              </select>
            </div>
          @endif
        </div>

        <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;margin-top:1rem;padding-top:0.75rem;border-top:1px solid #f1f5f9;">
          <a href="{{ route($queueRoute, $clearFilterParams) }}" class="queue-filter-clear" style="font-size:0.8rem;">Reset</a>
          <button type="submit" class="btn btn-navy btn-small" style="padding:0.35rem 0.85rem;font-size:0.82rem;">
            Apply
          </button>
        </div>
      </div>
    </details>

    <div class="queue-filter-actions" style="margin-left:auto;">
      <button type="submit" class="btn btn-navy btn-small">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Filter
      </button>
      <a href="{{ route($queueRoute, $clearFilterParams) }}" class="queue-filter-clear">Clear</a>
    </div>
  </div>
</form>

<div class="queue-toolbar">
  <span class="queue-count">{{ $reports->total() }} report{{ $reports->total() === 1 ? '' : 's' }}</span>
  <span class="queue-spacer"></span>
  <label class="queue-pin-toggle">
    <input type="checkbox" class="table-checkbox"
      onchange="window.location='{{ route($queueRoute, array_merge($baseParams, ['pin' => request('pin') === '1' ? '0' : '1'])) }}'"
      @checked(request('pin') === '1')>
    Pin emergencies to top
  </label>
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
  <button type="submit" formaction="{{ route('admin.reports.bulk-confirm') }}"
    class="bulk-btn" onclick="return confirm('Confirm and route selected reports?');">
    <i class="fa-solid fa-paper-plane" aria-hidden="true"></i> Confirm &amp; route
  </button>
</form>

<div class="table-wrap">
  <table class="data-table data-table-queue">
    <thead>
      <tr>
        <th scope="col" style="width:2.25rem;">
          <input type="checkbox" class="table-checkbox" id="select-all-checkbox" aria-label="Select all reports">
        </th>
        <th scope="col" style="width:26%;">Incident &amp; Category</th>
        <th scope="col" style="width:12%;">Location</th>
        <th scope="col" class="sortable" style="width:11%;">
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
        <th scope="col" class="sortable" style="width:10%;">
          <a href="{{ $urgencySort['url'] }}">Priority
            <span class="sort-indicator {{ $urgencySort['active'] ? '' : 'idle' }}" aria-hidden="true">
              {{ $urgencySort['active'] ? ($urgencySort['ascending'] ? '▲' : '▼') : '⇅' }}
            </span>
            @if($urgencySort['active'])<span class="sr-only">(sorted {{ $urgencySort['ascending'] ? 'ascending' : 'descending' }})</span>@endif
          </a>
        </th>
        <th scope="col" style="width:20%;">Next Action</th>
        <th scope="col" class="sortable" style="width:15%;">
          <a href="{{ $submittedSort['url'] }}">Assignee &amp; Action
            <span class="sort-indicator {{ $submittedSort['active'] ? '' : 'idle' }}" aria-hidden="true">
              {{ $submittedSort['active'] ? ($submittedSort['ascending'] ? '▲' : '▼') : '⇅' }}
            </span>
            @if($submittedSort['active'])<span class="sr-only">(sorted {{ $submittedSort['ascending'] ? 'ascending' : 'descending' }})</span>@endif
          </a>
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
        @endphp
        <tr data-report-row="{{ $report->id }}" data-href="{{ route('admin.reports.show', $report) }}" class="queue-row cursor-pointer hover:bg-slate-50 transition-colors {{ $isEmergency ? 'is-emergency-row' : '' }}">
          <td data-label="Select">
            <input type="checkbox" name="report_ids[]" value="{{ $report->id }}"
              class="report-checkbox table-checkbox" form="queue-bulk-form"
              aria-label="Select report {{ $report->tracking_code }}">
          </td>
          <td data-label="Incident">
            <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
              <a href="{{ route('admin.reports.show', $report) }}" class="cell-report-code font-mono font-bold text-blue-700">{{ $report->tracking_code }}</a>
              @if($isEmergency)
                <span class="status-badge b-red" style="font-size:0.7rem;padding:0.1rem 0.45rem;">
                  <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Emergency
                </span>
              @endif
              @if($report->reopened_count > 0)
                <span class="status-badge b-gold" title="Reopened" style="font-size:0.7rem;padding:0.1rem 0.45rem;">
                  <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i> Reopened
                </span>
              @endif
              @if($report->category)
                <span class="dashboard-category-tag" style="font-size:0.72rem;">{{ $report->category->name }}</span>
              @else
                <span class="cell-muted" style="font-size:0.72rem;">No category</span>
              @endif
            </div>
            <p class="cell-report-desc" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;word-break:break-word;margin-top:0.25rem;color:#334155;font-size:0.83rem;line-height:1.4;">{{ $report->summary ?: $report->description }}</p>
          </td>
          <td data-label="Location">
            <div style="font-weight:600;display:flex;align-items:center;gap:0.35rem;color:var(--ink);font-size:0.85rem;">
              <i class="fa-solid fa-location-dot" style="color:var(--blue);font-size:0.75rem;" aria-hidden="true"></i>
              <span>{{ $report->purok?->name ?? '—' }}</span>
            </div>
            @if($report->landmark)
              <div class="cell-muted" style="font-size:0.75rem;margin-top:0.2rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:180px;" title="{{ $report->landmark }}">
                {{ $report->landmark }}
              </div>
            @endif
          </td>
          <td data-label="Status">
            <x-status-badge :status="$report->status" />
          </td>
          <td data-label="Priority">
              @php
                $uClass = match(strtolower($report->urgency ?? '')) {
                  'critical' => 'priority-chip-critical',
                  'high' => 'priority-chip-high',
                  'medium' => 'priority-chip-medium',
                  'low' => 'priority-chip-low',
                  default => 'priority-chip-low'
                };
              @endphp
              <span class="priority-chip {{ $uClass }}" title="Priority Score: {{ $report->priority_score ?? 0 }}">
                P{{ $report->priority_score ?? 0 }} · {{ $report->urgency ?: 'Low' }}
              </span>
          </td>
          <td data-label="Next Action">
            <div class="next-action-cell" style="display:flex;flex-direction:column;gap:0.25rem;">
              <div style="display:inline-flex;align-items:center;gap:0.35rem;flex-wrap:wrap;">
                <span class="next-actor-pill tone-{{ $next['actor_tone'] }}">
                  {{ $next['actor'] }}
                </span>
                <span class="next-action-text" style="font-size:0.8rem;font-weight:600;color:var(--ink);">
                  {{ $next['action'] }}
                </span>
              </div>
              @if($next['due_text'])
                <div>
                  <span class="next-due-pill tone-{{ $next['due_tone'] }}" @if($next['due_tone'] === 'red') title="Overdue" @endif>
                    <i class="fa-solid fa-clock" aria-hidden="true" style="font-size:0.68rem;"></i>
                    <span>{{ $next['due_text'] }}</span>
                  </span>
                </div>
              @endif
            </div>
          </td>
          <td data-label="Assignee &amp; Action" class="cell-submitted">
            <div style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;">
              @if($report->assignee)
                <span class="cell-assignee" title="{{ $report->assignee->name }}">
                  <span class="assignee-initials" aria-hidden="true">{{ \Illuminate\Support\Str::initials($report->assignee->name) }}</span>
                  <span class="assignee-name" style="max-width:105px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">{{ $report->assignee->name }}</span>
                </span>
              @else
                <span class="cell-assignee" title="Unassigned">
                  <span class="assignee-initials unassigned" aria-hidden="true">—</span>
                  <span class="cell-muted assignee-name">Unassigned</span>
                </span>
              @endif
              <span class="cell-age" style="font-size:0.75rem;color:#64748b;font-weight:600;">{{ $report->created_at->diffForHumans(null, true) }} ago</span>
            </div>
            <div class="row-actions" style="display:flex;align-items:center;gap:0.35rem;margin-top:0.35rem;">
              <a class="btn btn-navy btn-small" style="padding:0.25rem 0.65rem;font-size:0.78rem;font-weight:700;" href="{{ route('admin.reports.show', $report) }}">
                Review
              </a>
              <div class="kebab">
                <button type="button" class="kebab-toggle" data-kebab-toggle
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
            </div>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="table-empty" data-label="">
            <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
            <p>No reports match your filters.</p>
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
  const selectAll = document.getElementById('select-all-checkbox');

  const checkedBoxes = () => document.querySelectorAll('.report-checkbox:checked');

  const updateBulkBar = () => {
    const count = checkedBoxes().length;
    if (bulkForm) bulkForm.style.display = count > 0 ? 'flex' : 'none';
    if (bulkCount) bulkCount.textContent = count;
    if (selectAll) {
      selectAll.checked = count > 0;
      selectAll.indeterminate = count > 0 && count < document.querySelectorAll('.report-checkbox').length;
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

  // Whole-row clickability: navigate to show page on click except interactive children
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
