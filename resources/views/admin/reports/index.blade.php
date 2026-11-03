@extends('layouts.admin')
@section('title', 'Report Queue')
@section('page-heading', 'Report queue')

@section('content')
<div class="page-heading">
  <h2>Resident reports</h2>
  <p>Review incoming reports, narrow the queue, and open a report to manage its status.</p>
</div>

@php
  $preservedFilters = request()->only(['q', 'purok_id', 'emergency']);
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
      <a href="{{ route('admin.reports.index', array_merge($preservedFilters, ['stage' => $stage])) }}"
        @class(['status-filter', 'active' => $currentStage === $stage])>
        {{ $group['label'] }}
        @if($stageCounts->get($stage, 0) > 0)
          <span>{{ $stageCounts->get($stage) }}</span>
        @endif
      </a>
    @endforeach
  </nav>

  <form method="GET" action="{{ route('admin.reports.index') }}" class="status-stage-mobile">
    @foreach(request()->only(['q', 'purok_id', 'emergency']) as $name => $value)
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

  <a href="{{ route('admin.reports.index', $emergencyFilterParams) }}"
    class="status-filter emergency-toggle {{ $emergencyOnly ? 'active' : '' }}"
    aria-pressed="{{ $emergencyOnly ? 'true' : 'false' }}">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    Emergency <span>{{ $emergencyCount }}</span>
  </a>
</div>

@if(in_array($currentStage, ['active', 'closed-other'], true))
  <nav class="status-subfilters" aria-label="Filter by exact status">
    <span>Show:</span>
    <a href="{{ route('admin.reports.index', array_merge($preservedFilters, ['stage' => $currentStage])) }}"
      @class(['status-subfilter', 'active' => $statusFilter === null || $statusFilter === 'all'])>
      All
    </a>
    @foreach($statusGroups[$currentStage]['statuses'] as $status)
      <a href="{{ route('admin.reports.index', array_merge($preservedFilters, [
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

<form method="GET" action="{{ route('admin.reports.index') }}" class="card filter-form">
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
  <div class="filter-actions">
    <button type="submit" class="btn btn-navy btn-small">
      <i class="fa-solid fa-filter" aria-hidden="true"></i> Filter
    </button>
    <a href="{{ route('admin.reports.index', $clearFilterParams) }}" class="filter-clear">Clear</a>
  </div>
</form>

<div class="table-wrap">
  <table class="data-table data-table-cards">
    <thead>
      <tr>
        <th scope="col">Tracking code</th>
        <th scope="col">Report summary</th>
        <th scope="col">Purok</th>
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
          <td class="description-cell" data-label="Report summary">{{ \Illuminate\Support\Str::limit($report->description, 110) }}</td>
          <td class="whitespace-nowrap" data-label="Purok">{{ $report->purok?->name ?? '—' }}</td>
          <td data-label="Status">
            <span class="report-status-cell">
              <x-status-badge :status="$report->status" />
              @if($report->is_emergency && $report->status !== 'emergency_flagged')
                <span class="status-badge b-red emergency-row-chip">
                  <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                  <span>Emergency</span>
                </span>
              @endif
            </span>
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
@endsection
