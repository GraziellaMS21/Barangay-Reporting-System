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
    'all' => 'All',
    'internal' => 'Within barangay',
    'external' => 'External agencies',
  ] as $tabKey => $tabLabel)
    <a href="{{ route('admin.referrals.index', array_filter(['type' => $tabKey === 'all' ? null : $tabKey, 'status' => $statusFilter !== 'all' ? $statusFilter : null, 'office_id' => $officeFilter])) }}"
       @class(['status-filter', 'active' => $typeFilter === $tabKey])>
      {{ $tabLabel }}
    </a>
  @endforeach
</div>

<form method="GET" action="{{ route('admin.referrals.index') }}" class="card filter-form">
  @if($typeFilter !== 'all')
    <input type="hidden" name="type" value="{{ $typeFilter }}">
  @endif

  <label for="status">Status
    <select id="status" name="status">
      <option value="all" @selected($statusFilter === 'all')>All statuses</option>
      @foreach($statusOptions as $groupLabel => $groupItems)
        <optgroup label="{{ $groupLabel }}">
          @foreach($groupItems as $statusKey => $statusLabel)
            <option value="{{ $statusKey }}" @selected($statusFilter === $statusKey)>{{ $statusLabel }}</option>
          @endforeach
        </optgroup>
      @endforeach
    </select>
  </label>

  <label for="office_id">Office or agency
    <select id="office_id" name="office_id">
      <option value="">All offices and agencies</option>
      @foreach($offices as $office)
        <option value="{{ $office->id }}" @selected((string) $officeFilter === (string) $office->id)>
          {{ $office->name }} ({{ $office->directoryType() === 'external' ? 'External' : 'Within barangay' }})
        </option>
      @endforeach
    </select>
  </label>

  <button type="submit" class="btn btn-navy btn-small">Apply filters</button>
  <a href="{{ route('admin.referrals.index') }}" class="btn btn-ghost btn-small">Clear</a>
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
            $statusBadgeClass = match ($row['status']) {
              'prepared', 'needs_info', 'on_hold', 'awaiting_confirmation' => 'review-badge-amber',
              'sent', 'acknowledged', 'routed', 'in_progress' => 'review-badge-blue',
              'action_taken', 'resolved' => 'review-badge-green',
              default => 'review-badge-gray',
            };
          @endphp
          <tr>
            <td data-label="Ref/Tracking code">
              <a href="{{ $row['url'] }}" class="font-mono font-bold">{{ $row['reference_no'] }}</a>
              @if($row['kind'] === 'external' && $row['tracking_code'])
                <div class="text-xs text-muted">{{ $row['tracking_code'] }}</div>
              @endif
            </td>
            <td data-label="Type">
              @if($row['kind'] === 'external')
                <span class="review-badge review-badge-amber">External</span>
              @else
                <span class="review-badge review-badge-blue">Within barangay</span>
              @endif
            </td>
            <td data-label="Office or agency">{{ $row['office_name'] }}</td>
            <td data-label="Status">
              <span class="review-badge {{ $statusBadgeClass }}">
                {{ $row['status_label'] }}
              </span>
            </td>
            <td data-label="Date routed or sent">
              @if($row['routed_or_sent_at'])
                {{ $row['routed_or_sent_at']->format('M d, Y') }}
              @else
                <span class="text-muted">{{ $row['routed_or_sent_empty'] }}</span>
              @endif
            </td>
            <td data-label="Follow-up">
              @if($row['is_overdue'] && $row['follow_up_at'])
                <span class="review-badge review-badge-red">Overdue</span>
                {{ $row['follow_up_at']->format('M d, Y') }}
              @elseif($row['follow_up_at'])
                {{ $row['follow_up_at']->format('M d, Y') }}
              @else
                <span class="text-muted">Not scheduled</span>
              @endif
            </td>
            <td data-label="Owner">{{ $row['owner_name'] }}</td>
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
