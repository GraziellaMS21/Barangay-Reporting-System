@extends('layouts.admin')
@section('title', $isMyCases ? 'My Cases' : 'Report Queue')
@section('page-heading', $isMyCases ? 'My assigned cases' : 'Report queue')

@section('content')
<div class="page-heading">
  <h2>{{ $isMyCases ? 'My assigned cases' : 'Resident reports' }}</h2>
  <p>{{ $isMyCases ? 'Reports currently assigned to you for inspection, response, and resolution.' : 'Review incoming reports, narrow the queue, and open a report to manage its status.' }}</p>
</div>

@php
  $preservedFilters = request()->only(['q', 'purok_id', 'emergency', 'assigned_to']);
  $emergencyFilterParams = array_merge($preservedFilters, ['stage' => $currentStage]);
  $clearFilterParams = ['stage' => $currentStage];

  if ($statusFilter !== null && $statusFilter !== 'all') {
      $emergencyFilterParams['status'] = $statusFilter;
      $clearFilterParams['status'] = $statusFilter;
  }

  if ($emergencyOnly) {
      $clearFilterParams['emergency'] = '1';
      unset($emergencyFilterParams['emergency']);
  } else {
      $emergencyFilterParams['emergency'] = '1';
  }
@endphp

<div class="lifecycle-filter-toolbar">
  <nav class="status-filters" aria-label="Filter reports by lifecycle stage">
    @foreach($statusGroups as $stage => $group)
      <a href="{{ route($isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index', array_merge($preservedFilters, ['stage' => $stage])) }}"
        @class(['status-filter', 'active' => $currentStage === $stage])>
        {{ $group['label'] }}
        @if($stageCounts->get($stage, 0) > 0)
          <span>{{ $stageCounts->get($stage) }}</span>
        @endif
      </a>
    @endforeach
  </nav>

  <form method="GET" action="{{ route($isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index') }}" class="status-stage-mobile">
    @foreach(request()->only(['q', 'purok_id', 'emergency', 'assigned_to']) as $name => $value)
      <input type="hidden" name="{{ $name }}" value="{{ $value }}">
    @endforeach
    <label class="sr-only" for="mobile-stage">Lifecycle stage</label>
    <select id="mobile-stage" name="stage">
      @foreach($statusGroups as $stage => $group)
        <option value="{{ $stage }}" @selected($currentStage === $stage)>{{ $group['label'] }}</option>
      @endforeach
    </select>
    <button type="submit" class="btn btn-navy btn-small">Apply</button>
  </form>

  <a href="{{ route($isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index', $emergencyFilterParams) }}"
    class="status-filter emergency-toggle {{ $emergencyOnly ? 'active' : '' }}"
    aria-pressed="{{ $emergencyOnly ? 'true' : 'false' }}">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    Emergency <span>{{ $emergencyCount }}</span>
  </a>
</div>

@if(in_array($currentStage, ['active', 'closed-other'], true))
  <nav class="status-subfilters" aria-label="Filter by exact status">
    <span>Show:</span>
    <a href="{{ route($isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index', array_merge($preservedFilters, ['stage' => $currentStage])) }}"
      @class(['status-subfilter', 'active' => $statusFilter === null || $statusFilter === 'all'])>
      All
    </a>
    @foreach($statusGroups[$currentStage]['statuses'] as $status)
      <a href="{{ route($isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index', array_merge($preservedFilters, [
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

<form method="GET" action="{{ route($isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index') }}" class="card filter-form">
  <input type="hidden" name="stage" value="{{ $currentStage }}">
  @if($statusFilter !== null && $statusFilter !== 'all')
    <input type="hidden" name="status" value="{{ $statusFilter }}">
  @endif
  @if($emergencyOnly)
    <input type="hidden" name="emergency" value="1">
  @endif

  <label for="q">Search reports
    <input id="q" type="search" name="q" value="{{ request('q') }}"
      placeholder="Tracking code or description">
  </label>

  <label for="purok_id">Purok
    <select id="purok_id" name="purok_id">
      <option value="">All puroks</option>
      @foreach($puroks as $purok)
        <option value="{{ $purok->id }}" @selected((string) request('purok_id') === (string) $purok->id)>
          {{ $purok->name }}
        </option>
      @endforeach
    </select>
  </label>

  @if(!$isMyCases)
    <label for="assigned_to">Assignee
      <select id="assigned_to" name="assigned_to">
        <option value="">All assignees</option>
        <option value="unassigned" @selected(request('assigned_to') === 'unassigned')>Unassigned</option>
        @foreach($staffUsers as $user)
          <option value="{{ $user->id }}" @selected((string) request('assigned_to') === (string) $user->id)>
            {{ $user->name }}
          </option>
        @endforeach
      </select>
    </label>
  @else
    <input type="hidden" name="assigned_to" value="{{ auth()->id() }}">
  @endif

  <div class="filter-actions">
    <button type="submit" class="btn btn-navy btn-small">
      <i class="fa-solid fa-filter" aria-hidden="true"></i> Filter
    </button>
    <a href="{{ route($isMyCases ? 'admin.reports.my-cases' : 'admin.reports.index', $clearFilterParams) }}" class="filter-clear">Clear</a>
  </div>
</form>

<div class="table-wrap">
  <table class="data-table data-table-cards">
    <thead>
      <tr>
        <th scope="col">Tracking code</th>
        <th scope="col">Report summary</th>
        <th scope="col">Purok</th>
        <th scope="col">Assignee</th>
        <th scope="col">Status</th>
        <th scope="col">Submitted</th>
        <th scope="col"><span class="sr-only">Actions</span></th>
      </tr>
    </thead>
    <tbody>
      @forelse($reports as $report)
        <tr>
          <td class="whitespace-nowrap font-mono" data-label="Tracking code">
            <a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a>
          </td>
          <td class="description-cell" data-label="Report summary">
            {{ \Illuminate\Support\Str::limit($report->description, 110) }}
          </td>
          <td class="whitespace-nowrap" data-label="Purok">{{ $report->purok?->name ?? '—' }}</td>
          <td class="whitespace-nowrap" data-label="Assignee">
            @if($report->assignee)
              <span class="assignee-badge">
                <i class="fa-solid fa-user-check" aria-hidden="true"></i>
                {{ $report->assignee->name }}
              </span>
            @else
              <span class="text-muted"><i class="fa-regular fa-user" aria-hidden="true"></i> Unassigned</span>
            @endif
          </td>
          <td data-label="Status">
            <div class="report-status-cell">
              <x-status-badge :status="$report->status" />
              @if($report->is_emergency && $report->status !== 'emergency_flagged')
                <span class="status-badge b-red emergency-row-chip">
                  <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                  <span>Emergency</span>
                </span>
              @endif
              @if($report->due_at && $report->due_at->isPast() && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true))
                <span class="status-badge b-red overdue-chip" title="Due on {{ $report->due_at->format('M d, Y h:i A') }}">
                  <i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i>
                  <span>Overdue</span>
                </span>
              @endif
              @if($report->reopened_count > 0)
                <span class="status-badge b-gold reopened-chip" title="Reopened {{ $report->reopened_count }} time(s)">
                  <i class="fa-solid fa-arrow-rotate-left" aria-hidden="true"></i>
                  <span>Reopened ({{ $report->reopened_count }})</span>
                </span>
              @endif
              @if($report->escalated_at)
                <span class="status-badge b-red escalated-chip" title="Escalated on {{ $report->escalated_at->format('M d, Y h:i A') }}">
                  <i class="fa-solid fa-fire" aria-hidden="true"></i>
                  <span>Escalated</span>
                </span>
              @endif
            </div>
          </td>
          <td class="whitespace-nowrap" data-label="Submitted">{{ $report->created_at->format('M d, Y') }}</td>
          <td data-label="Action">
            <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-gold btn-small">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Review
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="7" class="table-empty" data-label="">
            <i class="fa-regular fa-folder-open" aria-hidden="true"></i>
            <p>No reports match your filters.</p>
          </td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="pagination-wrap">{{ $reports->links() }}</div>
@endsection
