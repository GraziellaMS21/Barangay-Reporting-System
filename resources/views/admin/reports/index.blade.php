@extends('layouts.admin')
@section('title', 'Report Queue')
@section('page-heading', 'Report queue')

@section('content')
<div class="page-heading">
  <h2>Resident reports</h2>
  <p>Review incoming reports, narrow the queue, and open a report to manage its status.</p>
</div>

<section class="stat-grid" aria-label="Report totals">
  @foreach([
      'total' => ['All reports', 'fa-inbox', ''],
      'new' => ['Awaiting review', 'fa-eye', ''],
      'under_review' => ['Under review', 'fa-magnifying-glass', ''],
      'in_progress' => ['In progress', 'fa-gears', ''],
      'resolved' => ['Resolved', 'fa-circle-check', ''],
  ] as $key => [$label, $icon])
    <article class="card stat-card">
      <span class="stat-icon" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
      <div>
        <strong>{{ $statTotals[$key] }}</strong>
        <span>{{ $label }}</span>
      </div>
    </article>
  @endforeach
</section>

<nav class="status-filters" aria-label="Filter reports by status">
  <a href="{{ route('admin.reports.index', request()->except(['status', 'page'])) }}"
    @class(['status-filter', 'active' => !request('status')])>
    All <span>{{ $statTotals['total'] }}</span>
  </a>
  @foreach(\App\Models\Report::STATUSES as $status => $label)
    <a href="{{ route('admin.reports.index', array_merge(request()->except('page'), ['status' => $status])) }}"
      @class(['status-filter', 'active' => request('status') === $status])>
      {{ $label }} <span>{{ $statusCounts->get($status, 0) }}</span>
    </a>
  @endforeach
</nav>

<form method="GET" action="{{ route('admin.reports.index') }}" class="card filter-form">
  <label for="q">Search reports
    <input id="q" type="search" name="q" value="{{ request('q') }}"
      placeholder="Tracking code or description">
  </label>
  <label for="status">Status
    <select id="status" name="status">
      <option value="">All statuses</option>
      @foreach(\App\Models\Report::STATUSES as $value => $label)
        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
      @endforeach
    </select>
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
    <a href="{{ route('admin.reports.index') }}" class="filter-clear">Clear</a>
  </div>
</form>

<div class="table-wrap">
  <table class="data-table">
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
          <td class="whitespace-nowrap font-mono">
            <a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a>
          </td>
          <td class="description-cell">{{ \Illuminate\Support\Str::limit($report->description, 110) }}</td>
          <td class="whitespace-nowrap">{{ $report->purok?->name ?? '—' }}</td>
          <td><x-status-badge :status="$report->status" /></td>
          <td class="whitespace-nowrap">{{ $report->created_at->format('M d, Y') }}</td>
          <td>
            <a href="{{ route('admin.reports.show', $report) }}" class="btn btn-gold btn-small">
              <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Review
            </a>
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="table-empty">
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
