@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<div class="dashboard-header">
  <div class="page-heading">
    <div class="dashboard-eyebrow">
      <span class="dashboard-live-dot" aria-hidden="true"></span>
      <span>Live Operations Overview · {{ now()->format('l, F j, Y') }}</span>
    </div>
    <h2>Barangay operations dashboard</h2>
    <p>What needs action right now across incoming reports, active fieldwork, and agency referrals.</p>
  </div>
  <div class="dashboard-quick-actions">
    <a href="{{ route('admin.reports.index') }}" class="dashboard-action-btn dashboard-action-primary">
      <i class="fa-solid fa-inbox" aria-hidden="true"></i>
      <span>Report queue</span>
    </a>
    <a href="{{ route('admin.reports.my-cases') }}" class="dashboard-action-btn">
      <i class="fa-solid fa-user-check" aria-hidden="true"></i>
      <span>My cases</span>
    </a>
    <a href="{{ route('admin.map') }}" class="dashboard-action-btn">
      <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i>
      <span>Staff map</span>
    </a>
  </div>
</div>

@if($counts['emergency'] > 0)
  <div class="emergency-dashboard-alert" role="alert">
    <div class="emergency-alert-icon" aria-hidden="true">
      <i class="fa-solid fa-triangle-exclamation"></i>
    </div>
    <div class="emergency-alert-content">
      <strong>{{ $counts['emergency'] }} emergency report{{ $counts['emergency'] === 1 ? '' : 's' }} need immediate staff review</strong>
      <span>This system is not monitored in real time. Verify flagged emergency reports immediately.</span>
    </div>
    <a href="{{ route('admin.reports.index', ['emergency' => '1']) }}" class="emergency-alert-cta">
      Review emergency reports <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
    </a>
  </div>
@endif

<section class="stat-grid dashboard-kpi-grid" aria-label="Operational metrics">
  <a class="card stat-card kpi-card kpi-danger" href="{{ route('admin.reports.index', ['overdue' => '1']) }}">
    <div class="stat-icon kpi-icon-danger" aria-hidden="true">
      <i class="fa-solid fa-clock-rotate-left"></i>
    </div>
    <div class="kpi-body">
      <span class="kpi-label">Overdue</span>
      <strong class="kpi-value">{{ $counts['overdue'] ?: 'All clear' }}</strong>
      <small class="kpi-subtext">{{ $counts['overdue'] > 0 ? 'Past SLA target' : 'Within SLA target' }}</small>
    </div>
    <i class="fa-solid fa-arrow-up-right-from-square kpi-arrow" aria-hidden="true"></i>
  </a>

  <a class="card stat-card kpi-card kpi-warning" href="{{ route('admin.reports.index', ['urgency' => 'High']) }}">
    <div class="stat-icon kpi-icon-warning" aria-hidden="true">
      <i class="fa-solid fa-bolt"></i>
    </div>
    <div class="kpi-body">
      <span class="kpi-label">High or critical</span>
      <strong class="kpi-value">{{ $counts['high_or_critical'] ?: 'All clear' }}</strong>
      <small class="kpi-subtext">Priority active cases</small>
    </div>
    <i class="fa-solid fa-arrow-up-right-from-square kpi-arrow" aria-hidden="true"></i>
  </a>

  <a class="card stat-card kpi-card kpi-primary" href="{{ route('admin.reports.index', ['stage' => 'new']) }}">
    <div class="stat-icon kpi-icon-primary" aria-hidden="true">
      <i class="fa-solid fa-inbox"></i>
    </div>
    <div class="kpi-body">
      <span class="kpi-label">Needs review</span>
      <strong class="kpi-value">{{ $counts['needs_review'] }}</strong>
      <small class="kpi-subtext">Submitted &amp; under review</small>
    </div>
    <i class="fa-solid fa-arrow-up-right-from-square kpi-arrow" aria-hidden="true"></i>
  </a>

  <a class="card stat-card kpi-card kpi-info" href="{{ route('admin.reports.index', ['status' => 'in_progress']) }}">
    <div class="stat-icon kpi-icon-info" aria-hidden="true">
      <i class="fa-solid fa-screwdriver-wrench"></i>
    </div>
    <div class="kpi-body">
      <span class="kpi-label">In progress</span>
      <strong class="kpi-value">{{ $counts['in_progress'] }}</strong>
      <small class="kpi-subtext">Active field dispatch</small>
    </div>
    <i class="fa-solid fa-arrow-up-right-from-square kpi-arrow" aria-hidden="true"></i>
  </a>

  <a class="card stat-card kpi-card kpi-neutral" href="{{ route('admin.reports.index', ['status' => 'needs_info']) }}">
    <div class="stat-icon kpi-icon-neutral" aria-hidden="true">
      <i class="fa-solid fa-user-clock"></i>
    </div>
    <div class="kpi-body">
      <span class="kpi-label">Awaiting resident</span>
      <strong class="kpi-value">{{ $counts['awaiting_resident'] }}</strong>
      <small class="kpi-subtext">Needs info or confirmation</small>
    </div>
    <i class="fa-solid fa-arrow-up-right-from-square kpi-arrow" aria-hidden="true"></i>
  </a>

  <a class="card stat-card kpi-card kpi-success" href="{{ route('admin.reports.index', ['status' => 'resolved']) }}">
    <div class="stat-icon kpi-icon-success" aria-hidden="true">
      <i class="fa-solid fa-circle-check"></i>
    </div>
    <div class="kpi-body">
      <span class="kpi-label">Resolved</span>
      <strong class="kpi-value">{{ $counts['resolved'] }}</strong>
      <small class="kpi-subtext">{{ $averageResolutionHours !== null ? $averageResolutionHours.' h avg resolution' : 'Completed cases' }}</small>
    </div>
    <i class="fa-solid fa-arrow-up-right-from-square kpi-arrow" aria-hidden="true"></i>
  </a>
</section>

<div class="dashboard-main-grid">
  <section class="card dashboard-panel">
    <div class="dashboard-card-header">
      <div>
        <h2 class="card-title">
          <i class="fa-solid fa-bell" aria-hidden="true"></i> Needs attention
        </h2>
        <p class="dashboard-card-subtitle">Highest-priority open reports ordered by emergency, SLA deadline, and priority score.</p>
      </div>
      <a href="{{ route('admin.reports.index') }}" class="dashboard-card-link">
        View all <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
      </a>
    </div>
    <div class="table-wrap">
      <table class="data-table data-table-cards dashboard-table">
        <thead>
          <tr>
            <th>Report</th>
            <th>Why</th>
            <th>Age</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($needsAttentionReports as $report)
            <tr class="dashboard-table-row" tabindex="0" role="link"
              aria-label="Open report {{ $report->tracking_code }}"
              onclick="window.location='{{ route('admin.reports.show', $report) }}'"
              onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location='{{ route('admin.reports.show', $report) }}';}">
              <td data-label="Report">
                <div class="dashboard-report-cell">
                  <span class="dashboard-tracking-pill">{{ $report->tracking_code }}</span>
                  @if($report->category)
                    <span class="dashboard-category-tag">{{ $report->category->name }}</span>
                  @endif
                </div>
                @if($report->description)
                  <div class="dashboard-report-desc">{{ \Illuminate\Support\Str::limit($report->description, 85) }}</div>
                @endif
                @if($report->purok)
                  <div class="dashboard-report-meta">
                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i> {{ $report->purok->name }}
                  </div>
                @endif
              </td>
              <td data-label="Why">
                @if($report->is_emergency)
                  <span class="status-badge b-red"><i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Emergency</span>
                @elseif($report->due_at && $report->due_at->isPast())
                  <span class="status-badge b-red"><i class="fa-solid fa-clock" aria-hidden="true"></i> Overdue</span>
                @elseif($report->urgency === 'Critical')
                  <span class="status-badge b-red">Critical</span>
                @elseif($report->reopened_count > 0)
                  <span class="status-badge b-gold">Reopened</span>
                @else
                  <span class="status-badge b-blue">New</span>
                @endif
              </td>
              <td data-label="Age" title="{{ $report->created_at->format('M d, Y h:i A') }}">
                <span class="dashboard-age-text">{{ $report->created_at->diffForHumans(null, true) }}</span>
              </td>
              <td data-label="" class="dashboard-row-action">
                <a href="{{ route('admin.reports.show', $report) }}" class="dashboard-chevron-btn" aria-label="Open report {{ $report->tracking_code }}">
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td class="table-empty" colspan="4">
                <div class="dashboard-empty-box">
                  <i class="fa-solid fa-circle-check" aria-hidden="true"></i>
                  <span>No reports need attention right now.</span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>

  <section class="card dashboard-insights dashboard-ai-panel" aria-labelledby="dashboard-insights-heading">
    <div class="dashboard-card-header">
      <div>
        <h2 id="dashboard-insights-heading" class="card-title">
          <i class="fa-solid fa-wand-magic-sparkles dashboard-ai-icon" aria-hidden="true"></i> Gemini dashboard insights
        </h2>
        <p class="dashboard-card-subtitle">Automated operational takeaways from current queue metrics.</p>
      </div>
      <span class="dashboard-ai-badge">AI Summary</span>
    </div>
    @if($dashboardInsights !== [])
      <ul class="stacked-list dashboard-insight-list">
        @foreach($dashboardInsights as $insight)
          <li>
            <i class="fa-solid fa-lightbulb" aria-hidden="true"></i>
            <span>{{ $insight }}</span>
          </li>
        @endforeach
      </ul>
    @else
      <div class="dashboard-insights-empty">
        <i class="fa-solid fa-chart-line" aria-hidden="true"></i>
        <p class="empty-state">Insights are temporarily unavailable. Review the dashboard totals and report trends above.</p>
      </div>
    @endif

    <div class="dashboard-Quick-summary-footer">
      <a href="{{ route('admin.referrals.index') }}" class="dashboard-summary-chip">
        <span><i class="fa-solid fa-share-nodes" aria-hidden="true"></i> Referrals needing attention</span>
        <strong>{{ $pendingReferralCount }}</strong>
      </a>
      <a href="{{ route('admin.reports.index') }}" class="dashboard-summary-chip">
        <span><i class="fa-solid fa-copy" aria-hidden="true"></i> Pending duplicates</span>
        <strong>{{ $pendingDuplicateCount }}</strong>
      </a>
    </div>
  </section>

  <section class="card dashboard-panel dashboard-span-full" aria-labelledby="referrals-attention-heading">
    <div class="dashboard-card-header">
      <div>
        <h2 id="referrals-attention-heading" class="card-title">
          <i class="fa-solid fa-building-shield" aria-hidden="true"></i> Referrals needing attention
        </h2>
        <p class="dashboard-card-subtitle">External agency referrals awaiting dispatch or follow-up.</p>
      </div>
      <a href="{{ route('admin.referrals.index') }}" class="dashboard-card-link">
        All referrals ({{ $pendingReferralCount }}) <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
      </a>
    </div>
    <div class="table-wrap">
      <table class="data-table data-table-cards dashboard-table">
        <thead>
          <tr>
            <th>REF no.</th>
            <th>Agency</th>
            <th>Status</th>
            <th>Follow-up</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          @forelse($pendingReferrals as $referral)
            @php($referralOverdue = $referral->isOverdue())
            <tr class="dashboard-table-row" tabindex="0" role="link"
              aria-label="Open referral {{ $referral->reference_no ?? $referral->report?->tracking_code }}"
              onclick="window.location='{{ route('admin.reports.show', $referral->report_id) }}'"
              onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location='{{ route('admin.reports.show', $referral->report_id) }}';}">
              <td data-label="REF no.">
                <div class="font-mono font-bold">{{ $referral->reference_no ?? 'Not assigned' }}</div>
                <div class="text-xs text-muted">{{ $referral->report?->tracking_code }}</div>
              </td>
              <td data-label="Agency">
                <span class="font-semibold">{{ $referral->office?->name }}</span>
              </td>
              <td data-label="Status">
                @if($referral->status === 'prepared')
                  <span class="status-badge b-gold">Not sent</span>
                @elseif($referralOverdue)
                  <span class="status-badge b-red">Follow-up overdue</span>
                @else
                  <span class="status-badge b-blue">{{ \App\Models\Referral::STATUSES[$referral->status] ?? $referral->status }}</span>
                @endif
              </td>
              <td data-label="Follow-up">
                @if($referral->follow_up_at)
                  <span class="{{ $referralOverdue ? 'text-red font-semibold' : '' }}">{{ $referral->follow_up_at->format('M d, Y') }}</span>
                @else
                  <span class="text-muted">Not scheduled</span>
                @endif
              </td>
              <td data-label="" class="dashboard-row-action">
                <a href="{{ route('admin.reports.show', $referral->report_id) }}" class="dashboard-chevron-btn" aria-label="Open referred report">
                  <i class="fa-solid fa-chevron-right" aria-hidden="true"></i>
                </a>
              </td>
            </tr>
          @empty
            <tr>
              <td class="table-empty" colspan="5">
                <div class="dashboard-empty-box">
                  <i class="fa-solid fa-check" aria-hidden="true"></i>
                  <span>No referrals need attention.</span>
                </div>
              </td>
            </tr>
          @endforelse
        </tbody>
      </table>
    </div>
  </section>
</div>

<section class="chart-grid dashboard-chart-grid" aria-label="Report analytics">
  @foreach(['category' => ['Reports by category', 'doughnut', 'Distribution of non-emergency reports across barangay categories.'], 'weekly' => ['Reports per week', 'line', '8-week submission trend across the barangay.']] as $key => [$title, $type, $subtitle])
    @php($chart = $chartData[$key])
    <section class="card chart-card dashboard-chart-card">
      <div class="dashboard-card-header">
        <div>
          <h2 class="card-title">{{ $title }}</h2>
          <p class="dashboard-card-subtitle">{{ $subtitle }}</p>
        </div>
      </div>
      @if(array_sum($chart['counts']) > 0)
        <div class="chart-canvas-wrap" data-report-chart="{{ json_encode($chart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-chart-type="{{ $type }}">
          <canvas role="img" aria-label="{{ $title }}"></canvas>
        </div>
      @else
        <p class="chart-empty-state">No reports yet.</p>
      @endif
    </section>
  @endforeach
</section>

<details class="card dashboard-more-charts">
  <summary class="dashboard-more-summary">
    <div>
      <span class="card-title"><i class="fa-solid fa-chart-pie" aria-hidden="true"></i> More charts &amp; breakdowns</span>
      <span class="dashboard-card-subtitle">View status pipeline, purok distribution, and urgency breakdown</span>
    </div>
    <span class="dashboard-more-toggle">
      <span>Expand</span>
      <i class="fa-solid fa-chevron-down" aria-hidden="true"></i>
    </span>
  </summary>
  <section class="chart-grid dashboard-secondary-charts" aria-label="More charts">
    @foreach(['status' => ['Reports by status', 'horizontal-bar'], 'purok' => ['Reports by purok', 'horizontal-bar'], 'urgency' => ['Urgency breakdown', 'bar']] as $key => [$title, $type])
      @php($chart = $chartData[$key])
      <section class="card chart-card dashboard-chart-card">
        <h2 class="card-title">{{ $title }}</h2>
        @if(array_sum($chart['counts']) > 0)
          <div class="chart-canvas-wrap" data-report-chart="{{ json_encode($chart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-chart-type="{{ $type }}">
            <canvas role="img" aria-label="{{ $title }}"></canvas>
          </div>
        @else
          <p class="chart-empty-state">No reports yet.</p>
        @endif
      </section>
    @endforeach
  </section>
</details>
@endsection
