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

<form id="bulk-confirm-form" method="POST" action="{{ route('admin.reports.bulk-confirm') }}" onsubmit="return confirm('Confirm and route selected reports?');" style="display:none; position:sticky; bottom:0; background:#fff; padding:0.5rem; border-top:1px solid #e3e8f4;">
  @csrf
  <button type="submit" class="btn btn-gold btn-small">Confirm <span id="bulk-count">0</span> selected</button>
</form>
<script>
function updateBulkBar(){
  const checked = document.querySelectorAll('.report-checkbox:checked').length;
  const bar = document.getElementById('bulk-confirm-form');
  bar.style.display = checked > 0 ? 'block' : 'none';
  document.getElementById('bulk-count').textContent = checked;
}
</script>
<div class="table-wrap">
  <table class="data-table data-table-cards">
    <thead>
      <tr>
        <th scope="col" style="width:2.5rem;"><input type="checkbox" aria-label="Select all" onclick="document.querySelectorAll('.report-checkbox').forEach(c=>c.checked=this.checked);updateBulkBar();"></th>
        <th scope="col">Report</th>
        <th scope="col">Status</th>
        <th scope="col">Assignee</th>
        <th scope="col">Age</th>
      </tr>
    </thead>
    <tbody>
      @forelse($reports as $report)
        @php
          $ai = $report->aiResult;
          $threshold = (float) (\App\Models\Setting::value('ai_confidence_threshold', 0.8));
          $ready = $report->ai_status === 'done' && $ai && $ai->error === null
            && (float) $ai->confidence >= $threshold
            && $report->category_id && $report->urgency
            && $report->getRecommendedOffice() && !$report->is_emergency
            && $report->duplicateSuggestions->where('decision','pending')->isEmpty();
        @endphp
        <tr style="cursor:pointer" onclick="window.location='{{ route('admin.reports.show', $report) }}'">
          <td data-label="Select" onclick="event.stopPropagation()"><input type="checkbox" name="report_ids[]" value="{{ $report->id }}" class="report-checkbox" form="bulk-confirm-form" onchange="updateBulkBar()"></td>
          <td data-label="Report">
            <div class="font-mono font-bold">{{ $report->tracking_code }}</div>
            <div class="text-xs text-muted">{{ $report->purok?->name ?? '' }} @if($report->category) · {{ $report->category->name }} @endif</div>
            <div class="text-sm clamp-2">{{ \Illuminate\Support\Str::limit($report->summary ?: $report->description, 140) }}</div>
            @if($ready)
              <span class="status-badge b-green">Ready to confirm</span>
            @endif
            @if($report->priority_score !== null && $report->priority_score > 0)
              <span class="status-badge b-gray">Priority {{ $report->priority_score }}</span>
            @endif
          </td>
          <td data-label="Status">
            <x-status-badge :status="$report->status" />
            @if($report->due_at && $report->due_at->isPast() && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true))
              <i class="fa-solid fa-clock text-red-600" title="Overdue"></i>
            @endif
            @if($report->escalated_at)
              <i class="fa-solid fa-fire text-red-600" title="Escalated"></i>
            @endif
            @if($report->reopened_count > 0)
              <i class="fa-solid fa-arrow-rotate-left text-amber-600" title="Reopened"></i>
            @endif
          </td>
          <td data-label="Assignee">{{ $report->assignee ? \Illuminate\Support\Str::limit($report->assignee->name, 12, '') : '' }}</td>
          <td data-label="Age" title="{{ $report->created_at->format('M d, Y h:i A') }}">{{ $report->created_at->diffForHumans(null, true) }}</td>
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
