@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<div class="page-heading">
  <h2>Barangay operations dashboard</h2>
  <p>Review key report metrics, trends, and items that need attention.</p>
</div>

@if($counts['emergency'] > 0)
  <div class="emergency-dashboard-alert" role="alert">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    <span><strong>{{ $counts['emergency'] }} emergency report{{ $counts['emergency'] === 1 ? '' : 's' }}</strong> need staff review. This system is not monitored in real time.</span>
    <a href="{{ route('admin.reports.index', ['stage' => 'all', 'emergency' => '1']) }}">Review emergency reports</a>
  </div>
@endif

<section class="stat-grid" aria-label="Operational metrics">
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-eye"></i></span>
    <div><strong>{{ $counts['awaiting_review'] }}</strong><span>Awaiting review</span></div>
  </article>
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-triangle-exclamation"></i></span>
    <div><strong>{{ $counts['high_or_critical'] }}</strong><span>High or critical</span></div>
  </article>
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-gears"></i></span>
    <div><strong>{{ $counts['in_progress'] }}</strong><span>In progress</span></div>
  </article>
  <article class="card stat-card">
    <span class="stat-icon" aria-hidden="true"><i class="fa-solid fa-clock"></i></span>
    <div>
      <strong>{{ $averageResolutionHours === null ? '—' : number_format($averageResolutionHours, 1).' h' }}</strong>
      <span>Average time to resolve</span>
    </div>
  </article>
</section>

<section class="chart-grid" aria-label="Report analytics">
  @foreach([
    'category' => ['Reports by category', 'doughnut'],
    'status' => ['Reports by status', 'horizontal-bar'],
    'weekly' => ['Reports per week · last 8 weeks', 'line'],
    'purok' => ['Reports by purok · top 8', 'horizontal-bar'],
    'urgency' => ['Urgency breakdown', 'bar'],
  ] as $key => [$title, $type])
    @php($chart = $chartData[$key])
    <section class="card chart-card">
      <h2 class="card-title">{{ $title }}</h2>
      @if(array_sum($chart['counts']) > 0)
        <div class="chart-canvas-wrap"
          data-report-chart="{{ json_encode($chart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
          data-chart-type="{{ $type }}">
          <canvas role="img" aria-label="{{ $title }}"></canvas>
        </div>
      @else
        <p class="chart-empty-state">No reports yet.</p>
      @endif
    </section>
  @endforeach
</section>

<section class="card">
  <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> 5 most recent reports</h2>
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">Tracking code</th>
          <th scope="col">Category</th>
          <th scope="col">Status</th>
          <th scope="col">Submitted</th>
        </tr>
      </thead>
      <tbody>
        @forelse($recentReports as $report)
          <tr>
            <td data-label="Tracking code"><a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a></td>
            <td data-label="Category">{{ $report->category?->name ?? 'Unclassified' }}</td>
            <td data-label="Status"><x-status-badge :status="$report->status" /></td>
            <td data-label="Submitted">{{ $report->created_at->format('M d, Y') }}</td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="4" data-label="">No reports have been submitted yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<div class="attention-grid">
  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-arrow-up-wide-short" aria-hidden="true"></i> Top 5 priority reports</h2>
    <div class="table-wrap">
      <table class="data-table data-table-cards">
        <thead>
          <tr><th scope="col">Report</th><th scope="col">Priority</th><th scope="col">Urgency</th></tr>
        </thead>
        <tbody>
          @forelse($topPriorityReports as $report)
            <tr>
              <td data-label="Report"><a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a></td>
              <td data-label="Priority">{{ $report->priority_score }}</td>
              <td data-label="Urgency">{{ $report->urgency ?? '—' }}</td>
            </tr>
          @empty
            <tr><td class="table-empty" colspan="3" data-label="">No scored reports need attention.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-eye" aria-hidden="true"></i> Still unreviewed</h2>
    <div class="table-wrap">
      <table class="data-table data-table-cards">
        <thead>
          <tr><th scope="col">Report</th><th scope="col">Submitted</th></tr>
        </thead>
        <tbody>
          @forelse($unreviewedReports as $report)
            <tr>
              <td data-label="Report"><a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a></td>
              <td data-label="Submitted">{{ $report->created_at->format('M d, Y') }}</td>
            </tr>
          @empty
            <tr><td class="table-empty" colspan="2" data-label="">No unreviewed reports.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-copy" aria-hidden="true"></i> Pending duplicate suggestions</h2>
    <div class="table-wrap">
      <table class="data-table data-table-cards">
        <thead>
          <tr><th scope="col">Report pair</th><th scope="col">Similarity</th></tr>
        </thead>
        <tbody>
          @forelse($pendingDuplicateSuggestions as $suggestion)
            <tr>
              <td data-label="Report pair">
                <a href="{{ route('admin.reports.show', $suggestion->report) }}">{{ $suggestion->report->tracking_code }}</a>
                and
                <a href="{{ route('admin.reports.show', $suggestion->candidate) }}">{{ $suggestion->candidate->tracking_code }}</a>
              </td>
              <td data-label="Similarity">{{ number_format($suggestion->score * 100, 0) }}%</td>
            </tr>
          @empty
            <tr><td class="table-empty" colspan="2" data-label="">No pending duplicate suggestions.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-share-from-square" aria-hidden="true"></i> Pending referrals</h2>
    <div class="table-wrap">
      <table class="data-table data-table-cards">
        <thead>
          <tr><th scope="col">Report</th><th scope="col">Office</th></tr>
        </thead>
        <tbody>
          @forelse($pendingReferrals as $referral)
            <tr>
              <td data-label="Report"><a href="{{ route('admin.reports.show', $referral->report) }}">{{ $referral->report->tracking_code }}</a></td>
              <td data-label="Office">{{ $referral->office->name }}</td>
            </tr>
          @empty
            <tr><td class="table-empty" colspan="2" data-label="">No pending referrals.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>

<section class="card dashboard-insights" aria-labelledby="dashboard-insights-heading">
  <h2 id="dashboard-insights-heading" class="card-title">
    <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i> Gemini dashboard insights
  </h2>
  @if($dashboardInsights !== [])
    <ul class="stacked-list">
      @foreach($dashboardInsights as $insight)
        <li>{{ $insight }}</li>
      @endforeach
    </ul>
  @else
    <p class="empty-state">Insights are temporarily unavailable. Review the dashboard totals and report trends above.</p>
  @endif
</section>
@endsection
