@extends('layouts.admin')

@section('title', 'Referrals')
@section('page-heading', 'Referrals')

@section('content')
<div class="page-heading">
  <h2>Office referrals</h2>
  <p>Track reports routed beyond barangay responsibility.</p>
</div>

<section class="card">
  <table class="data-table">
    <thead>
      <tr>
        <th>Tracking code</th>
        <th>Office</th>
        <th>Referral</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      @forelse($referrals as $referral)
        <tr>
          <td><a href="{{ route('admin.reports.show', $referral->report) }}">{{ $referral->report->tracking_code }}</a></td>
          <td>{{ $referral->office->name }}</td>
          <td>{{ $referral->referral_text }}</td>
          <td>{{ $referral->status }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="4">No referrals have been created yet.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
  {{ $referrals->links() }}
</section>
@endsection
