@extends('layouts.admin')

@section('title', 'Dashboard')
@section('page-heading', 'Dashboard')

@section('content')
@php
  // Combined KPI count for "Needs attention" (Overdue + High/Critical)
  $needsAttentionCount = ($counts['overdue'] ?? 0) + ($counts['high_or_critical'] ?? 0);
  $userName = auth()->user()?->name ?? 'Official';
@endphp

{{-- Slim Page Header: "Good day, {name}" on left, quick actions on right --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Good day, {{ $userName }}</h1>
    <p class="text-sm text-slate-500 mt-0.5">{{ now()->format('l, F j, Y') }}</p>
  </div>
  <div class="flex items-center gap-2.5 flex-wrap">
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

{{-- Emergency Alert Banner --}}
@if($counts['emergency'] > 0)
  <div class="emergency-dashboard-alert mb-6 rounded-2xl p-4 flex flex-col md:flex-row md:items-center justify-between gap-4 bg-red-50 border border-red-200" role="alert">
    <div class="flex items-center gap-3">
      <div class="w-10 h-10 rounded-full bg-red-100 text-red-600 flex items-center justify-center flex-shrink-0" aria-hidden="true">
        <i class="fa-solid fa-triangle-exclamation text-lg"></i>
      </div>
      <div>
        <strong class="text-red-900 font-semibold block">{{ $counts['emergency'] }} emergency report{{ $counts['emergency'] === 1 ? '' : 's' }} need immediate staff review</strong>
        <span class="text-xs text-red-700">This system is not monitored in real time. Verify flagged emergency reports immediately.</span>
      </div>
    </div>
    <a href="{{ route('admin.reports.index', ['emergency' => '1']) }}" class="emergency-alert-cta whitespace-nowrap px-4 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-xl transition-colors inline-flex items-center gap-2 self-start md:self-auto">
      Review emergency reports <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
    </a>
  </div>
@endif

{{-- 1. KPI Row (4 Cards, DASH Style, Responsive Grid) --}}
<section class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-6 mb-6" aria-label="Operational metrics">
  
  {{-- Card 1: Needs attention (Merged Overdue + High/Critical) --}}
  <a href="{{ route('admin.reports.index', ($counts['overdue'] > 0 ? ['overdue' => '1'] : ['urgency' => 'High'])) }}"
     class="card rounded-2xl bg-white p-6 shadow-sm hover:shadow-md transition-all duration-200 border border-slate-100 flex items-center justify-between group min-h-[110px] text-decoration-none">
    <div class="flex flex-col justify-center min-w-0 pr-3">
      <span class="text-xs font-medium text-slate-500 whitespace-nowrap">Needs attention</span>
      <div class="text-3xl font-bold tracking-tight text-red-600 mt-1">
        {{ $needsAttentionCount ?: 'All clear' }}
      </div>
      <span class="text-xs text-slate-400 mt-1 truncate block">
        {{ $counts['overdue'] }} overdue · {{ $counts['high_or_critical'] }} high priority
      </span>
    </div>
    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 bg-red-50 text-red-600 group-hover:scale-105 transition-transform" aria-hidden="true">
      <i class="fa-solid fa-triangle-exclamation text-lg"></i>
    </div>
  </a>

  {{-- Card 2: In progress --}}
  <a href="{{ route('admin.reports.index', ['status' => 'in_progress']) }}"
     class="card rounded-2xl bg-white p-6 shadow-sm hover:shadow-md transition-all duration-200 border border-slate-100 flex items-center justify-between group min-h-[110px] text-decoration-none">
    <div class="flex flex-col justify-center min-w-0 pr-3">
      <span class="text-xs font-medium text-slate-500 whitespace-nowrap">In progress</span>
      <div class="text-3xl font-bold tracking-tight text-blue-600 mt-1">
        {{ $counts['in_progress'] }}
      </div>
    </div>
    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 bg-blue-50 text-blue-600 group-hover:scale-105 transition-transform" aria-hidden="true">
      <i class="fa-solid fa-screwdriver-wrench text-lg"></i>
    </div>
  </a>

  {{-- Card 3: Awaiting resident --}}
  <a href="{{ route('admin.reports.index', ['status' => 'needs_info']) }}"
     class="card rounded-2xl bg-white p-6 shadow-sm hover:shadow-md transition-all duration-200 border border-slate-100 flex items-center justify-between group min-h-[110px] text-decoration-none">
    <div class="flex flex-col justify-center min-w-0 pr-3">
      <span class="text-xs font-medium text-slate-500 whitespace-nowrap">Awaiting resident</span>
      <div class="text-3xl font-bold tracking-tight text-slate-700 mt-1">
        {{ $counts['awaiting_resident'] }}
      </div>
    </div>
    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 bg-slate-100 text-slate-600 group-hover:scale-105 transition-transform" aria-hidden="true">
      <i class="fa-solid fa-user-clock text-lg"></i>
    </div>
  </a>

  {{-- Card 4: Resolved --}}
  <a href="{{ route('admin.reports.index', ['status' => 'resolved']) }}"
     class="card rounded-2xl bg-white p-6 shadow-sm hover:shadow-md transition-all duration-200 border border-slate-100 flex items-center justify-between group min-h-[110px] text-decoration-none">
    <div class="flex flex-col justify-center min-w-0 pr-3">
      <span class="text-xs font-medium text-slate-500 whitespace-nowrap">Resolved</span>
      <div class="text-3xl font-bold tracking-tight text-emerald-600 mt-1">
        {{ $counts['resolved'] }}
      </div>
      @if($averageResolutionHours !== null)
        <span class="text-xs text-slate-400 mt-1 truncate block">
          {{ $averageResolutionHours }} h avg resolution
        </span>
      @endif
    </div>
    <div class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 bg-emerald-50 text-emerald-600 group-hover:scale-105 transition-transform" aria-hidden="true">
      <i class="fa-solid fa-circle-check text-lg"></i>
    </div>
  </a>

</section>

{{-- 2. Main Operational Grid (xl:grid-cols-3) --}}
<div class="grid grid-cols-1 xl:grid-cols-3 gap-6 mb-6 items-start">

  {{-- Left (2 columns): "Needs attention" Table Card --}}
  <div class="xl:col-span-2">
    <section class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
      <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 flex-wrap gap-2">
        <div class="flex items-center gap-2.5">
          <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center" aria-hidden="true">
            <i class="fa-solid fa-bell text-sm"></i>
          </div>
          <div>
            <h2 class="text-base font-bold text-slate-900 m-0">Needs attention</h2>
            <p class="text-xs text-slate-500 m-0">Highest-priority open reports ordered by urgency and deadline.</p>
          </div>
        </div>
        <div class="flex items-center gap-2">
          <a href="{{ route('admin.reports.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center gap-1">
            View all <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
          </a>
          <button type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50 transition-colors" aria-label="Card options">
            <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
          </button>
        </div>
      </div>

      <div class="table-wrap overflow-x-auto">
        <table class="data-table data-table-cards w-full text-left border-collapse">
          <thead>
            <tr class="bg-slate-50/80 text-slate-500 text-xs font-semibold uppercase tracking-wider">
              <th class="py-3 px-3.5 rounded-l-lg">Report</th>
              <th class="py-3 px-3.5">Location</th>
              <th class="py-3 px-3.5">Status</th>
              <th class="py-3 px-3.5 text-right rounded-r-lg"><span class="sr-only">Actions</span></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            @forelse($needsAttentionReports as $report)
              @php
                $relativeAge = $report->created_at->diffForHumans(null, true);
                if ($report->is_emergency) {
                  $statusPillClass = 'bg-red-50 text-red-700 border-red-200';
                  $statusPillText = 'Emergency';
                } elseif ($report->due_at && $report->due_at->isPast()) {
                  $statusPillClass = 'bg-red-50 text-red-700 border-red-200';
                  $statusPillText = 'Overdue · ' . $relativeAge;
                } elseif ($report->urgency === 'Critical') {
                  $statusPillClass = 'bg-red-50 text-red-700 border-red-200';
                  $statusPillText = 'Critical · ' . $relativeAge;
                } elseif ($report->reopened_count > 0) {
                  $statusPillClass = 'bg-amber-50 text-amber-800 border-amber-200';
                  $statusPillText = 'Reopened · ' . $relativeAge;
                } else {
                  $statusPillClass = 'bg-blue-50 text-blue-700 border-blue-200';
                  $statusPillText = 'New · ' . $relativeAge;
                }
              @endphp
              <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer group" tabindex="0" role="link"
                  aria-label="Open report {{ $report->tracking_code }}"
                  onclick="window.location='{{ route('admin.reports.show', $report) }}'"
                  onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location='{{ route('admin.reports.show', $report) }}';}">
                <td data-label="Report" class="py-3 px-3.5">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono font-bold text-xs text-blue-700 bg-blue-50 px-2 py-0.5 rounded-md">{{ $report->tracking_code }}</span>
                    @if($report->category)
                      <span class="text-[11px] font-medium text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md">{{ $report->category->name }}</span>
                    @endif
                  </div>
                  @if($report->description)
                    <p class="text-xs text-slate-600 mt-1 line-clamp-2 m-0 max-w-md">{{ $report->description }}</p>
                  @endif
                </td>
                <td data-label="Location" class="py-3 px-3.5 text-xs text-slate-600 whitespace-nowrap">
                  @if($report->purok)
                    <div class="inline-flex items-center gap-1.5 font-medium">
                      <i class="fa-solid fa-location-dot text-slate-400 text-[11px]" aria-hidden="true"></i>
                      <span>{{ $report->purok->name }}</span>
                    </div>
                  @else
                    <span class="text-slate-400">Calarian</span>
                  @endif
                </td>
                <td data-label="Status" class="py-3 px-3.5 whitespace-nowrap">
                  <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold border {{ $statusPillClass }}">
                    {{ $statusPillText }}
                  </span>
                </td>
                <td data-label="" class="py-3 px-3.5 text-right whitespace-nowrap">
                  <span class="w-7 h-7 rounded-full inline-flex items-center justify-center text-slate-400 group-hover:text-blue-600 group-hover:bg-blue-50 transition-all">
                    <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
                  </span>
                </td>
              </tr>
            @empty
              <tr>
                <td class="table-empty py-8 text-center" colspan="4">
                  <div class="flex flex-col items-center justify-center gap-2 text-slate-400">
                    <i class="fa-solid fa-circle-check text-2xl text-emerald-500" aria-hidden="true"></i>
                    <span class="text-xs font-medium text-slate-600">No reports need attention right now.</span>
                  </div>
                </td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </section>
  </div>

  {{-- Right (1 column): Compact Gemini Insights Card --}}
  <div class="xl:col-span-1">
    <section class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 flex flex-col justify-between" aria-labelledby="dashboard-insights-heading">
      <div>
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100">
          <div class="flex items-center gap-2">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center" aria-hidden="true">
              <i class="fa-solid fa-wand-magic-sparkles text-sm"></i>
            </div>
            <div>
              <h2 id="dashboard-insights-heading" class="text-base font-bold text-slate-900 m-0">Gemini dashboard insights</h2>
              <p class="text-xs text-slate-500 m-0">Operational takeaways</p>
            </div>
          </div>
          <button type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50 transition-colors" aria-label="Insights options">
            <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
          </button>
        </div>

        @php
          $actionableInsights = array_slice($dashboardInsights, 0, 3);
        @endphp
        @if(!empty($actionableInsights))
          <ul class="space-y-3 m-0 p-0 list-none">
            @foreach($actionableInsights as $insight)
              <li class="flex items-start gap-2.5 text-xs text-slate-700 leading-relaxed">
                <i class="fa-solid fa-lightbulb text-amber-500 mt-0.5 flex-shrink-0" aria-hidden="true"></i>
                <span>{{ $insight }}</span>
              </li>
            @endforeach
          </ul>
        @else
          <div class="dashboard-insights-empty py-6 text-center">
            <i class="fa-solid fa-chart-line text-slate-400 text-xl mb-2" aria-hidden="true"></i>
            <p class="empty-state text-xs text-slate-500 leading-relaxed">Insights are temporarily unavailable. Review the dashboard totals and report trends above.</p>
          </div>
        @endif
      </div>

      {{-- Show Pending duplicates only if > 0 --}}
      @if($pendingDuplicateCount > 0)
        <div class="mt-4 pt-3 border-t border-slate-100">
          <a href="{{ route('admin.reports.index') }}" class="flex items-center justify-between p-2.5 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 text-xs font-medium hover:bg-amber-100 transition-colors">
            <span class="flex items-center gap-2">
              <i class="fa-solid fa-copy text-amber-600" aria-hidden="true"></i>
              <span>Pending duplicates</span>
            </span>
            <strong class="px-2 py-0.5 rounded-full bg-amber-200 text-amber-900 text-xs">{{ $pendingDuplicateCount }}</strong>
          </a>
        </div>
      @endif
    </section>
  </div>

</div>

{{-- 3. Referrals Needing Attention Table (Full Width) --}}
<section class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 mb-6" aria-labelledby="referrals-attention-heading">
  <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 flex-wrap gap-2">
    <div class="flex items-center gap-2.5">
      <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center" aria-hidden="true">
        <i class="fa-solid fa-building-shield text-sm"></i>
      </div>
      <div>
        <h2 id="referrals-attention-heading" class="text-base font-bold text-slate-900 m-0">Referrals needing attention</h2>
        <p class="text-xs text-slate-500 m-0">External agency referrals awaiting dispatch or follow-up.</p>
      </div>
    </div>
    <div class="flex items-center gap-2">
      <a href="{{ route('admin.referrals.index') }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center gap-1">
        All referrals ({{ $pendingReferralCount }}) <i class="fa-solid fa-arrow-right text-[10px]" aria-hidden="true"></i>
      </a>
      <button type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50 transition-colors" aria-label="Referrals options">
        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
      </button>
    </div>
  </div>

  <div class="table-wrap overflow-x-auto">
    <table class="data-table data-table-cards w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-50/80 text-slate-500 text-xs font-semibold uppercase tracking-wider">
          <th class="py-3 px-3.5 rounded-l-lg">REF no.</th>
          <th class="py-3 px-3.5">Agency</th>
          <th class="py-3 px-3.5">Status</th>
          <th class="py-3 px-3.5">Follow-up</th>
          <th class="py-3 px-3.5 text-right rounded-r-lg"><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($pendingReferrals as $referral)
          @php($referralOverdue = $referral->isOverdue())
          <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer group" tabindex="0" role="link"
              aria-label="Open referral {{ $referral->reference_no ?? $referral->report?->tracking_code }}"
              onclick="window.location='{{ route('admin.reports.show', $referral->report_id) }}'"
              onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location='{{ route('admin.reports.show', $referral->report_id) }}';}">
            <td data-label="REF no." class="py-3 px-3.5">
              <div class="font-mono font-bold text-xs text-blue-700">{{ $referral->reference_no ?? 'Not assigned' }}</div>
              @if($referral->report?->tracking_code)
                <div class="text-[11px] text-slate-400 flex items-center gap-1 mt-0.5">
                  <i class="fa-solid fa-ticket text-[10px]" aria-hidden="true"></i>
                  <span>{{ $referral->report->tracking_code }}</span>
                </div>
              @endif
            </td>
            <td data-label="Agency" class="py-3 px-3.5">
              <div class="font-semibold text-xs text-slate-800">{{ $referral->office?->name }}</div>
              @if($referral->office?->type)
                <span class="inline-block text-[10px] font-medium uppercase px-1.5 py-0.2 bg-slate-100 text-slate-600 rounded mt-0.5">{{ $referral->office->type }}</span>
              @endif
            </td>
            <td data-label="Status" class="py-3 px-3.5 whitespace-nowrap">
              @if($referral->status === 'prepared')
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                  <i class="fa-solid fa-paper-plane text-[10px]" aria-hidden="true"></i> Not sent
                </span>
              @elseif($referralOverdue)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">
                  <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i> Follow-up overdue
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                  <i class="fa-solid fa-clock text-[10px]" aria-hidden="true"></i> {{ \App\Models\Referral::STATUSES[$referral->status] ?? $referral->status }}
                </span>
              @endif
            </td>
            <td data-label="Follow-up" class="py-3 px-3.5 whitespace-nowrap text-xs">
              @if($referral->follow_up_at)
                <span class="{{ $referralOverdue ? 'text-red-700 font-bold' : 'text-slate-700 font-medium' }}">
                  @if($referralOverdue)
                    <i class="fa-solid fa-clock-rotate-left mr-1 text-[11px]" aria-hidden="true"></i>
                  @endif
                  {{ $referral->follow_up_at->format('M d, Y') }}
                </span>
              @else
                <span class="text-slate-400">Not scheduled</span>
              @endif
            </td>
            <td data-label="" class="py-3 px-3.5 text-right whitespace-nowrap">
              <span class="w-7 h-7 rounded-full inline-flex items-center justify-center text-slate-400 group-hover:text-blue-600 group-hover:bg-blue-50 transition-all">
                <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td class="table-empty py-8 text-center" colspan="5">
              <div class="flex flex-col items-center justify-center gap-2 text-slate-400">
                <i class="fa-solid fa-check text-2xl text-emerald-500" aria-hidden="true"></i>
                <span class="text-xs font-medium text-slate-600">No referrals need attention.</span>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

{{-- 4. Analytics Section (2-Column Row) --}}
<section class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6" aria-label="Report analytics">
  
  {{-- Chart 1: Reports by category (horizontal-bar chart) --}}
  @php($categoryChart = $chartData['category'])
  <section class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
      <div>
        <h2 class="text-base font-bold text-slate-900 m-0">Reports by category</h2>
        <p class="text-xs text-slate-500 m-0">Distribution of non-emergency reports across barangay categories.</p>
      </div>
      <button type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50 transition-colors" aria-label="Category chart options">
        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
      </button>
    </div>
    @if(array_sum($categoryChart['counts']) > 0)
      <div class="chart-canvas-wrap h-64 w-full relative" data-report-chart="{{ json_encode($categoryChart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-chart-type="horizontal-bar">
        <canvas role="img" aria-label="Reports by category"></canvas>
      </div>
    @else
      <div class="py-12 text-center text-slate-400">
        <p class="chart-empty-state text-xs">No reports yet.</p>
      </div>
    @endif
  </section>

  {{-- Chart 2: Reports per week (line trend) --}}
  @php($weeklyChart = $chartData['weekly'])
  <section class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 flex flex-col justify-between">
    <div class="flex items-center justify-between pb-3 mb-3 border-b border-slate-100">
      <div>
        <h2 class="text-base font-bold text-slate-900 m-0">Reports per week</h2>
        <p class="text-xs text-slate-500 m-0">8-week submission trend across the barangay.</p>
      </div>
      <button type="button" class="text-slate-400 hover:text-slate-600 p-1.5 rounded-lg hover:bg-slate-50 transition-colors" aria-label="Weekly chart options">
        <i class="fa-solid fa-ellipsis" aria-hidden="true"></i>
      </button>
    </div>
    @if(array_sum($weeklyChart['counts']) > 0)
      <div class="chart-canvas-wrap h-64 w-full relative" data-report-chart="{{ json_encode($weeklyChart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-chart-type="line">
        <canvas role="img" aria-label="Reports per week"></canvas>
      </div>
    @else
      <div class="py-12 text-center text-slate-400">
        <p class="chart-empty-state text-xs">No reports yet.</p>
      </div>
    @endif
  </section>

</section>

{{-- 5. Collapsible "More charts & breakdowns" (Collapsed by default) --}}
<details class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100 mb-6 group" id="dashboard-more-charts-details">
  <summary class="flex items-center justify-between cursor-pointer list-none select-none">
    <div class="flex items-center gap-3">
      <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center" aria-hidden="true">
        <i class="fa-solid fa-chart-pie text-sm"></i>
      </div>
      <div>
        <span class="text-sm font-bold text-slate-900 block">More charts &amp; breakdowns</span>
        <span class="text-xs text-slate-500">View status pipeline, purok distribution, and urgency breakdown</span>
      </div>
    </div>
    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-slate-100 text-slate-600 group-open:bg-blue-50 group-open:text-blue-700 transition-colors">
      <span id="more-charts-toggle-text">Expand</span>
      <i class="fa-solid fa-chevron-down text-[10px] group-open:rotate-180 transition-transform" aria-hidden="true"></i>
    </span>
  </summary>

  <section class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6 pt-6 border-t border-slate-100" aria-label="More charts">
    @foreach(['status' => ['Reports by status', 'horizontal-bar'], 'purok' => ['Reports by purok', 'horizontal-bar'], 'urgency' => ['Urgency breakdown', 'bar']] as $key => [$title, $type])
      @php($chart = $chartData[$key])
      <div class="p-4 rounded-xl bg-slate-50/60 border border-slate-100 flex flex-col justify-between">
        <h3 class="text-xs font-bold text-slate-800 uppercase tracking-wider mb-3">{{ $title }}</h3>
        @if(array_sum($chart['counts']) > 0)
          <div class="chart-canvas-wrap h-52 w-full relative" data-report-chart="{{ json_encode($chart, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}" data-chart-type="{{ $type }}">
            <canvas role="img" aria-label="{{ $title }}"></canvas>
          </div>
        @else
          <div class="py-10 text-center text-slate-400">
            <p class="chart-empty-state text-xs">No reports yet.</p>
          </div>
        @endif
      </div>
    @endforeach
  </section>
</details>

@endsection
