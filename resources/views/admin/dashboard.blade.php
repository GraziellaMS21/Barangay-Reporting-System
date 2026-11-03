@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<div class="page-heading">
  <h2>Barangay operations dashboard</h2>
  <p>Snapshot of queue volume, urgency, and hotspot activity.</p>
</div>

<section class="stat-grid" aria-label="Operational metrics">
  @foreach([
    'awaiting_review' => ['Awaiting review', 'fa-eye'],
    'high_or_critical' => ['High / critical', 'fa-triangle-exclamation'],
    'in_progress' => ['In progress', 'fa-gears'],
    'resolved' => ['Resolved', 'fa-circle-check'],
  ] as $key => [$label, $icon])
    <article class="card stat-card">
      <span class="stat-icon" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
      <div>
        <strong>{{ $counts[$key] ?? 0 }}</strong>
        <span>{{ $label }}</span>
      </div>
    </article>
  @endforeach
</section>

<div class="detail-grid">
  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> Reports by category</h2>
    <ul class="stacked-list">
      @forelse($categoryBreakdown as $item)
        <li><span>{{ $item['label'] }}</span><strong>{{ $item['total'] }}</strong></li>
      @empty
        <li>No category data available.</li>
      @endforelse
    </ul>
  </section>

  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Hotspots</h2>
    <ul class="stacked-list">
      @forelse($hotspots as $key => $count)
        <li><span>{{ $key }}</span><strong>{{ $count }}</strong></li>
      @empty
        <li>No hotspot activity recorded.</li>
      @endforelse
    </ul>
  </section>
</div>

<section class="card">
  <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> Recent reports</h2>
  <table class="data-table">
    <thead>
      <tr>
        <th>Code</th>
        <th>Category</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @foreach($recentReports as $report)
        <tr>
          <td><a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a></td>
          <td>{{ $report->category?->name ?? 'Unclassified' }}</td>
          <td><x-status-badge :status="$report->status" /></td>
        </tr>
      @endforeach
    </tbody>
  </table>
</section>
@endsection
