@extends('layouts.admin')

@section('title', 'Referrals and routing')
@section('page-heading', 'Referrals and routing')

@section('content')
<div class="page-heading">
  <h2>Referrals and routing</h2>
  <p>Track reports routed to internal barangay offices (Mode A) and referrals sent to external agencies (Mode B) in one place.</p>
  @if(auth()->user()->isAdmin())
    <a class="btn btn-gold btn-small" href="{{ route('admin.settings.offices.index') }}">
      <i class="fa-solid fa-building" aria-hidden="true"></i> Manage offices and agencies
    </a>
  @endif
</div>

<div class="status-filters" role="navigation" aria-label="Destination type filter">
  @foreach([
    'all' => 'All Active',
    'internal' => 'Within barangay',
    'external' => 'External agencies',
  ] as $tabKey => $tabLabel)
    <a href="{{ route('admin.referrals.index', array_filter(['type' => $tabKey === 'all' ? null : $tabKey, 'status' => $statusFilter !== 'all' ? $statusFilter : null, 'office_id' => $officeFilter])) }}"
       @class(['status-filter', 'active' => $typeFilter === $tabKey])>
      <span>{{ $tabLabel }}</span>
    </a>
  @endforeach
</div>

<form method="GET" action="{{ route('admin.referrals.index') }}" class="card filter-form" style="margin-bottom:1.25rem;">
  @if($typeFilter !== 'all')
    <input type="hidden" name="type" value="{{ $typeFilter }}">
  @endif

  <div style="display:flex;align-items:flex-end;gap:0.75rem;flex-wrap:wrap;width:100%;">
    <div style="flex:1;min-width:200px;">
      <label for="status" style="font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:0.25rem;display:block;">Status</label>
      <select id="status" name="status" style="width:100%;">
        <option value="all" @selected($statusFilter === 'all')>All statuses</option>
        @foreach($statusOptions as $groupLabel => $groupItems)
          <optgroup label="{{ $groupLabel }}">
            @foreach($groupItems as $statusKey => $statusLabel)
              <option value="{{ $statusKey }}" @selected($statusFilter === $statusKey)>{{ $statusLabel }}</option>
            @endforeach
          </optgroup>
        @endforeach
      </select>
    </div>

    <div style="flex:1.5;min-width:240px;">
      <label for="office_id" style="font-size:0.78rem;font-weight:700;color:#475569;margin-bottom:0.25rem;display:block;">Office or agency</label>
      <select id="office_id" name="office_id" style="width:100%;">
        <option value="">All offices and agencies</option>
        @foreach($offices as $office)
          <option value="{{ $office->id }}" @selected((string) $officeFilter === (string) $office->id)>
            {{ $office->name }} ({{ $office->directoryType() === 'external' ? 'External' : 'Within barangay' }})
          </option>
        @endforeach
      </select>
    </div>

    <div style="display:flex;align-items:center;gap:0.5rem;">
      <button type="submit" class="btn btn-navy btn-small" style="padding:0.45rem 0.9rem;">
        <i class="fa-solid fa-filter" aria-hidden="true"></i> Apply filters
      </button>
      <a href="{{ route('admin.referrals.index') }}" class="btn btn-ghost btn-small" style="padding:0.45rem 0.75rem;">Clear</a>
    </div>
  </div>
</form>

<section class="card">
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th>Ref/Tracking code</th>
          <th>Type</th>
          <th>Office or agency</th>
          <th>Status</th>
          <th>Date routed or sent</th>
          <th>Follow-up</th>
          <th>Owner</th>
        </tr>
      </thead>
      <tbody>
        @forelse($referrals as $row)
          @php
            $isOverdue = $row['is_overdue'] && $row['follow_up_at'];
            $isDueSoon = !$isOverdue && $row['follow_up_at'] && $row['follow_up_at']->isFuture() && $row['follow_up_at']->diffInDays(now()) <= 7;
            $statusBadgeClass = match ($row['status']) {
              'prepared', 'needs_info', 'awaiting_confirmation' => 'review-badge-amber',
              'sent', 'acknowledged', 'routed', 'in_progress' => 'review-badge-blue',
              'action_taken', 'resolved' => 'review-badge-green',
              default => 'review-badge-gray',
            };
          @endphp
          <tr @class(['is-emergency-row' => $isOverdue])>
            <td data-label="Ref/Tracking code">
              <a href="{{ $row['url'] }}" class="font-mono font-bold text-blue-700 hover:underline">{{ $row['reference_no'] }}</a>
              @if($row['kind'] === 'external' && $row['tracking_code'])
                <div class="text-xs text-muted" style="margin-top:0.2rem;display:flex;align-items:center;gap:0.25rem;">
                  <i class="fa-solid fa-ticket" style="font-size:0.68rem;" aria-hidden="true"></i>
                  <span>{{ $row['tracking_code'] }}</span>
                </div>
              @endif
            </td>
            <td data-label="Type">
              @if($row['kind'] === 'external')
                <span class="status-badge b-gold" style="font-size:0.74rem;">
                  <i class="fa-solid fa-building-flag" aria-hidden="true"></i> External
                </span>
              @else
                <span class="status-badge b-blue" style="font-size:0.74rem;">
                  <i class="fa-solid fa-building-user" aria-hidden="true"></i> Within barangay
                </span>
              @endif
            </td>
            <td data-label="Office or agency">
              <strong style="color:var(--ink);display:block;">{{ $row['office_name'] }}</strong>
            </td>
            <td data-label="Status">
              <span class="review-badge {{ $statusBadgeClass }}">
                {{ $row['status_label'] }}
              </span>
            </td>
            <td data-label="Date routed or sent">
              @if($row['routed_or_sent_at'])
                <span style="font-weight:500;">{{ $row['routed_or_sent_at']->format('M d, Y') }}</span>
              @else
                <span class="text-muted">{{ $row['routed_or_sent_empty'] }}</span>
              @endif
            </td>
            <td data-label="Follow-up">
              @if($isOverdue)
                <div style="display:flex;flex-direction:column;gap:0.2rem;">
                  <span class="status-badge b-red" style="font-size:0.72rem;padding:0.1rem 0.45rem;display:inline-flex;align-items:center;gap:0.25rem;width:fit-content;">
                    <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i> Overdue
                  </span>
                  <span style="color:#b91c1c;font-weight:700;font-size:0.85rem;">{{ $row['follow_up_at']->format('M d, Y') }}</span>
                </div>
              @elseif($isDueSoon)
                <div style="display:flex;flex-direction:column;gap:0.2rem;">
                  <span class="status-badge b-gold" style="font-size:0.72rem;padding:0.1rem 0.45rem;display:inline-flex;align-items:center;gap:0.25rem;width:fit-content;">
                    <i class="fa-solid fa-clock" aria-hidden="true"></i> Due soon
                  </span>
                  <span style="color:#b45309;font-weight:600;font-size:0.85rem;">{{ $row['follow_up_at']->format('M d, Y') }}</span>
                </div>
              @elseif($row['follow_up_at'])
                <span style="font-weight:500;">{{ $row['follow_up_at']->format('M d, Y') }}</span>
              @else
                <span class="text-muted">Not scheduled</span>
              @endif
            </td>
            <td data-label="Owner">
              <div style="display:flex;align-items:center;gap:0.4rem;">
                <span class="assignee-initials" style="width:24px;height:24px;font-size:0.72rem;" aria-hidden="true">{{ \Illuminate\Support\Str::initials($row['owner_name']) }}</span>
                <span style="font-size:0.84rem;font-weight:500;">{{ $row['owner_name'] }}</span>
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="table-empty" data-label="">No routed reports or referrals match the current filters.</td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{ $referrals->links() }}
</section>
@endsection
