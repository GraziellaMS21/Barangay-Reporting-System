@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<div class="page-heading">
  <h2>Barangay operations dashboard</h2>
  <p>Review the queue, analyze trends, and follow up on items that need attention.</p>
</div>

<div class="dashboard-tabs" role="tablist" aria-label="Dashboard sections" data-dashboard-tabs>
  <button id="dashboard-tab-overview" type="button" role="tab" aria-selected="true"
    aria-controls="dashboard-panel-overview" tabindex="0" data-dashboard-tab="overview">Overview</button>
  <button id="dashboard-tab-analytics" type="button" role="tab" aria-selected="false"
    aria-controls="dashboard-panel-analytics" tabindex="-1" data-dashboard-tab="analytics">Analytics</button>
  <button id="dashboard-tab-needs-attention" type="button" role="tab" aria-selected="false"
    aria-controls="dashboard-panel-needs-attention" tabindex="-1" data-dashboard-tab="needs-attention">Needs Attention</button>
</div>

<section id="dashboard-panel-overview" class="dashboard-panel" role="tabpanel"
  aria-labelledby="dashboard-tab-overview" tabindex="0" data-dashboard-panel="overview">
  @if($counts['emergency'] > 0)
    <div class="emergency-dashboard-alert" role="alert">
      <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
      <span><strong>{{ $counts['emergency'] }} emergency report{{ $counts['emergency'] === 1 ? '' : 's' }}</strong> need staff review. This system is not monitored in real time.</span>
      <a href="{{ route('admin.reports.index', ['status' => 'emergency_flagged']) }}">Review emergency reports</a>
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
      <p class="empty-state">Insights are temporarily unavailable. Review the dashboard totals and report trends below.</p>
    @endif
  </section>

  <section class="card">
    <h2 class="card-title"><i class="fa-solid fa-clock-rotate-left" aria-hidden="true"></i> 5 most recent reports</h2>
    <div class="table-wrap">
      <table class="data-table data-table-cards">
        <thead>
          <tr><th scope="col">Code</th><th scope="col">Category</th><th scope="col">Status</th></tr>
        </thead>
        <tbody>
          @forelse($recentReports as $report)
            <tr>
              <td data-label="Tracking code"><a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a></td>
              <td data-label="Category">{{ $report->category?->name ?? 'Unclassified' }}</td>
              <td data-label="Status"><x-status-badge :status="$report->status" /></td>
            </tr>
          @empty
            <tr><td class="table-empty" colspan="3" data-label="">No reports have been submitted yet.</td></tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</section>

<section id="dashboard-panel-analytics" class="dashboard-panel" role="tabpanel"
  aria-labelledby="dashboard-tab-analytics" tabindex="0" data-dashboard-panel="analytics" hidden>
  <div class="chart-grid">
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
  </div>
</section>

<section id="dashboard-panel-needs-attention" class="dashboard-panel" role="tabpanel"
  aria-labelledby="dashboard-tab-needs-attention" tabindex="0" data-dashboard-panel="needs-attention" hidden>
  <div class="attention-grid">
    <section class="card">
      <h2 class="card-title"><i class="fa-solid fa-arrow-up-wide-short" aria-hidden="true"></i> Top 5 priority reports</h2>
      <ul class="stacked-list">
        @forelse($topPriorityReports as $report)
          <li>
            <a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a>
            <strong>{{ $report->priority_score }}</strong>
          </li>
        @empty
          <li>No scored reports need attention.</li>
        @endforelse
      </ul>
    </section>

    <section class="card">
      <h2 class="card-title"><i class="fa-solid fa-eye" aria-hidden="true"></i> Still unreviewed</h2>
      <ul class="stacked-list">
        @forelse($unreviewedReports as $report)
          <li><a href="{{ route('admin.reports.show', $report) }}">{{ $report->tracking_code }}</a><span>{{ $report->created_at->format('M d, Y') }}</span></li>
        @empty
          <li>No unreviewed reports.</li>
        @endforelse
      </ul>
    </section>

    <section class="card">
      <h2 class="card-title"><i class="fa-solid fa-copy" aria-hidden="true"></i> Pending duplicate suggestions</h2>
      <ul class="stacked-list">
        @forelse($pendingDuplicateSuggestions as $suggestion)
          <li>
            <span>
              <a href="{{ route('admin.reports.show', $suggestion->report) }}">{{ $suggestion->report->tracking_code }}</a>
              and
              <a href="{{ route('admin.reports.show', $suggestion->candidate) }}">{{ $suggestion->candidate->tracking_code }}</a>
            </span>
            <strong>{{ number_format($suggestion->score * 100, 0) }}%</strong>
          </li>
        @empty
          <li>No pending duplicate suggestions.</li>
        @endforelse
      </ul>
    </section>

    <section class="card">
      <h2 class="card-title"><i class="fa-solid fa-share-from-square" aria-hidden="true"></i> Pending referrals</h2>
      <ul class="stacked-list">
        @forelse($pendingReferrals as $referral)
          <li>
            <a href="{{ route('admin.reports.show', $referral->report) }}">{{ $referral->report->tracking_code }}</a>
            <span>{{ $referral->office->name }}</span>
          </li>
        @empty
          <li>No pending referrals.</li>
        @endforelse
      </ul>
    </section>
  </div>
</section>
@endsection
