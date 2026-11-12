@extends('layouts.admin')

@section('title', 'Insights')
@section('page-heading', 'Insights')

@section('content')
<div class="page-heading">
  <h2>System insights &amp; analytics</h2>
  <p>Real-time statistics, trends, and exportable report data.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.insights.export') }}">
    <i class="fa-solid fa-download" aria-hidden="true"></i> Export CSV
  </a>
</div>

{{-- Summary stat cards --}}
<section class="stat-grid" aria-label="Insight metrics">
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-file-lines"></i></span>
    <div><strong>{{ $stats['totalReports'] }}</strong><span>Total reports</span></div>
  </article>
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-circle-check"></i></span>
    <div><strong>{{ $stats['resolvedReports'] }}</strong><span>Resolved</span></div>
  </article>
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-hourglass-half"></i></span>
    <div><strong>{{ $stats['pendingReports'] }}</strong><span>Pending</span></div>
  </article>
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
    <div><strong>{{ $stats['emergencyReports'] }}</strong><span>Emergency</span></div>
  </article>
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-percent"></i></span>
    <div><strong>{{ $stats['resolutionRate'] }}%</strong><span>Resolution rate</span></div>
  </article>
</section>

{{-- Monthly trend --}}
<section class="card" aria-labelledby="monthly-heading">
  <h2 id="monthly-heading" class="card-title">
    <i class="fa-solid fa-chart-line" aria-hidden="true"></i> Reports per month (last 6 months)
  </h2>
  @if($stats['monthlyTrend']->isEmpty())
    <p class="chart-empty-state">No data available yet.</p>
  @else
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr>
            <th scope="col">Month</th>
            <th scope="col">Total reports</th>
          </tr>
        </thead>
        <tbody>
          @foreach($stats['monthlyTrend'] as $row)
            <tr>
              <td>{{ $row->month }}</td>
              <td>{{ $row->total }}</td>
            </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  @endif
</section>

{{-- Reports by category --}}
<div class="attention-grid">
  <section class="card" aria-labelledby="cat-heading">
    <h2 id="cat-heading" class="card-title">
      <i class="fa-solid fa-tags" aria-hidden="true"></i> Reports by category
    </h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th scope="col">Category</th><th scope="col">Total</th></tr>
        </thead>
        <tbody>
          @forelse($stats['byCategory'] as $row)
            <tr>
              <td>{{ $row['label'] }}</td>
              <td>{{ $row['total'] }}</td>
            </tr>
          @empty
            <tr><td class="table-empty" colspan="2">No data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  {{-- Reports by status --}}
  <section class="card" aria-labelledby="status-heading">
    <h2 id="status-heading" class="card-title">
      <i class="fa-solid fa-layer-group" aria-hidden="true"></i> Reports by status
    </h2>
    <div class="table-wrap">
      <table class="data-table">
        <thead>
          <tr><th scope="col">Status</th><th scope="col">Total</th></tr>
        </thead>
        <tbody>
          @forelse($stats['byStatus'] as $row)
            <tr>
              <td><x-status-badge :status="$row->status" /></td>
              <td>{{ $row->total }}</td>
            </tr>
          @empty
            <tr><td class="table-empty" colspan="2">No data.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>
@endsection
