@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
<div class="page-heading">
  <h2>Barangay operations dashboard</h2>
  <p>What needs action right now.</p>
</div>

@if($counts['emergency'] > 0)
  <div class="emergency-dashboard-alert" role="alert">
    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
    <span><strong>{{ $counts['emergency'] }} emergency report{{ $counts['emergency'] === 1 ? '' : 's' }}</strong> need staff review. This system is not monitored in real time.</span>
    <a href="{{ route('admin.reports.index', ['emergency' => '1']) }}">Review emergency reports</a>
  </div>
@endif

<section class="stat-grid" aria-label="Operational metrics">
  <a class="card stat-card" style="border-left:4px solid #e0352b;" href="{{ route('admin.reports.index', ['overdue' => '1']) }}">
    <div><strong style="color:#e0352b; font-size:2.25rem;">{{ $counts['overdue'] ?: 'All clear' }}</strong><span>Overdue</span></div>
  </a>
  <a class="card stat-card" style="border-left:4px solid #c58a00;" href="{{ route('admin.reports.index', ['urgency' => 'High']) }}">
    <div><strong style="color:#c58a00; font-size:2.25rem;">{{ $counts['high_or_critical'] ?: 'All clear' }}</strong><span>High or critical</span></div>
  </a>
  <a class="card stat-card" href="{{ route('admin.reports.index', ['stage' => 'new']) }}">
    <div><strong style="font-size:2.25rem; font-weight:800;">{{ $counts['needs_review'] }}</strong><span>Needs review</span></div>
  </a>
  <a class="card stat-card" style="opacity:0.85;" href="{{ route('admin.reports.index', ['status' => 'needs_info']) }}">
    <div><strong style="font-size:1.5rem;">{{ $counts['awaiting_resident'] }}</strong><span>Awaiting resident</span></div>
  </a>
</section>

<div style="display:grid; grid-template-columns:2fr 1fr; gap:1rem;">
<section class="card">
  <h2 class="card-title">Needs attention</h2>
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead><tr><th>Report</th><th>Why</th><th>Age</th><th></th></tr></thead>
      <tbody>
        @forelse($needsAttentionReports as $report)
          <tr style="cursor:pointer" onclick="window.location='{{ route('admin.reports.show', $report) }}'">
            <td data-label="Report">
              <div class="font-mono font-bold">{{ $report->tracking_code }}</div>
              <div class="text-sm clamp-2">{{ \Illuminate\Support\Str::limit($report->summary ?? '', 80) }}</div>
              <div class="text-xs text-muted">{{ $report->purok?->name }}</div>
            </td>
            <td data-label="Why">
              @if($report->due_at && $report->due_at->isPast())
                <span class="status-badge b-red">Overdue</span>
              @elseif($report->urgency === 'Critical')
                <span class="status-badge b-red">Critical</span>
              @elseif($report->reopened_count > 0)
                <span class="status-badge b-gold">Reopened</span>
              @else
                <span class="status-badge b-blue">New</span>
              @endif
            </td>
            <td data-label="Age" title="{{ $report->created_at->format('M d, Y h:i A') }}">{{ $report->created_at->diffForHumans(null, true) }}</td>
            <td data-label=""><a href="{{ route('admin.reports.show', $report) }}"><i class="fa-solid fa-chevron-right"></i></a></td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="4">No reports need attention.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<section class="card dashboard-insights" aria-labelledby="dashboard-insights-heading">
  <h2 id="dashboard-insights-heading" class="card-title">Gemini dashboard insights</h2>
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

<section class="card" aria-labelledby="referrals-attention-heading" style="grid-column: 1 / -1;">
  <h2 id="referrals-attention-heading" class="card-title">Referrals needing attention</h2>
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead><tr><th>REF no.</th><th>Agency</th><th>Status</th><th>Follow-up</th><th></th></tr></thead>
      <tbody>
        @forelse($pendingReferrals as $referral)
          @php($referralOverdue = $referral->isOverdue())
          <tr style="cursor:pointer" onclick="window.location='{{ route('admin.reports.show', $referral->report_id) }}'">
            <td data-label="REF no.">
              <div class="font-mono font-bold">{{ $referral->reference_no ?? 'Not assigned' }}</div>
              <div class="text-xs text-muted">{{ $referral->report?->tracking_code }}</div>
            </td>
            <td data-label="Agency">{{ $referral->office?->name }}</td>
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
                {{ $referral->follow_up_at->format('M d, Y') }}
              @else
                <span class="text-muted">Not scheduled</span>
              @endif
            </td>
            <td data-label=""><a href="{{ route('admin.reports.show', $referral->report_id) }}"><i class="fa-solid fa-chevron-right"></i></a></td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="5">No referrals need attention.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
</div>

<section class="chart-grid" aria-label="Report analytics">
  @foreach(['category' => ['Reports by category', 'doughnut'], 'weekly' => ['Reports per week', 'line']] as $key => [$title, $type])
    @php($chart = $chartData[$key])
    <section class="card chart-card">
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

<details class="card">
  <summary class="card-title" style="cursor:pointer">More charts</summary>
  <section class="chart-grid" aria-label="More charts">
    @foreach(['status' => ['Reports by status', 'horizontal-bar'], 'purok' => ['Reports by purok', 'horizontal-bar'], 'urgency' => ['Urgency breakdown', 'bar']] as $key => [$title, $type])
      @php($chart = $chartData[$key])
      <section class="card chart-card">
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

<p class="field-hint">
  <a href="{{ route('admin.referrals.index') }}">Referrals needing attention: {{ $pendingReferralCount }}</a> ·
  <a href="{{ route('admin.reports.index') }}">Pending duplicates: {{ $pendingDuplicateCount }}</a>
</p>
@endsection