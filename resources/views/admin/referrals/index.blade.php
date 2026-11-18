@extends('layouts.admin')

@section('title', 'Referrals')
@section('page-heading', 'Referrals')

@section('content')
<div class="page-heading">
  <h2>Referral tracking</h2>
  <p>Track reports referred beyond barangay authority. Agencies have no accounts; staff log every update.</p>
</div>

<form method="GET" action="{{ route('admin.referrals.index') }}" class="card filter-form">
  <label for="status">Status
    <select id="status" name="status">
      <option value="all" @selected($statusFilter === 'all')>All statuses</option>
      @foreach(\App\Models\Referral::STATUSES as $statusKey => $statusLabel)
        <option value="{{ $statusKey }}" @selected($statusFilter === $statusKey)>{{ $statusLabel }}</option>
      @endforeach
    </select>
  </label>

  <label for="office_id">Agency
    <select id="office_id" name="office_id">
      <option value="">All agencies</option>
      @foreach($offices as $office)
        <option value="{{ $office->id }}" @selected((string) $officeFilter === (string) $office->id)>{{ $office->name }}</option>
      @endforeach
    </select>
  </label>

  <button type="submit" class="btn btn-navy btn-small">Apply filters</button>
  <a href="{{ route('admin.referrals.index') }}" class="btn btn-ghost btn-small">Clear</a>
</form>

<section class="card">
  <table class="data-table data-table-cards">
    <thead>
      <tr>
        <th>REF no.</th>
        <th>Tracking code</th>
        <th>Agency</th>
        <th>Status</th>
        <th>Sent</th>
        <th>Follow-up</th>
      </tr>
    </thead>
    <tbody>
      @forelse($referrals as $referral)
        @php
          $referralBadge = match ($referral->status) {
            'prepared' => 'review-badge-amber',
            'sent', 'acknowledged' => 'review-badge-blue',
            'action_taken' => 'review-badge-green',
            default => 'review-badge-gray',
          };
        @endphp
        <tr>
          <td data-label="REF no.">
            <a href="{{ route('admin.reports.show', $referral->report_id) }}">{{ $referral->reference_no ?? 'Not assigned' }}</a>
          </td>
          <td data-label="Tracking code">{{ $referral->report?->tracking_code }}</td>
          <td data-label="Agency">{{ $referral->office?->name }}</td>
          <td data-label="Status">
            <span class="review-badge {{ $referralBadge }}">
              {{ \App\Models\Referral::STATUSES[$referral->status] ?? $referral->status }}
            </span>
          </td>
          <td data-label="Sent">
            @if($referral->sent_at)
              {{ $referral->sent_at->format('M d, Y') }}
            @else
              <span class="text-muted">Not sent</span>
            @endif
          </td>
          <td data-label="Follow-up">
            @if($referral->isOverdue())
              <span class="review-badge review-badge-red">Overdue</span>
              {{ $referral->follow_up_at->format('M d, Y') }}
            @elseif($referral->follow_up_at)
              {{ $referral->follow_up_at->format('M d, Y') }}
            @else
              <span class="text-muted">Not scheduled</span>
            @endif
          </td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="table-empty" data-label="">No referrals match the current filters.</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  {{ $referrals->links() }}
</section>
@endsection
