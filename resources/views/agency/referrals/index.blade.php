@extends('layouts.agency')

@section('title', 'Referrals Queue')
@section('page-heading', 'Assigned Referrals')

@section('content')
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900">Official Referrals for {{ auth()->user()->office?->name }}</h2>
      <p class="text-sm text-slate-500">Track and respond to matters forwarded by Barangay Calarian.</p>
    </div>
  </div>

  {{-- Status Tabs --}}
  <div class="border-b border-slate-200">
    <nav class="-mb-px flex space-x-6">
      @php
        $selectedStatus = request('status', 'active');
      @endphp
      <a href="{{ route('agency.referrals.index', ['status' => 'active']) }}"
         class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm {{ $selectedStatus === 'active' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
        Active Referrals
        @if($counts['active'] > 0)
          <span class="ml-1.5 py-0.5 px-2 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
            {{ $counts['active'] }}
          </span>
        @endif
      </a>
      <a href="{{ route('agency.referrals.index', ['status' => 'sent']) }}"
         class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm {{ $selectedStatus === 'sent' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
        Awaiting Acknowledgment
        @if($counts['sent'] > 0)
          <span class="ml-1.5 py-0.5 px-2 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
            {{ $counts['sent'] }}
          </span>
        @endif
      </a>
      <a href="{{ route('agency.referrals.index', ['status' => 'action_taken']) }}"
         class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm {{ $selectedStatus === 'action_taken' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
        Action In Progress
      </a>
      <a href="{{ route('agency.referrals.index', ['status' => 'closed']) }}"
         class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm {{ $selectedStatus === 'closed' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
        Closed / Completed
      </a>
      <a href="{{ route('agency.referrals.index', ['status' => 'all']) }}"
         class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm {{ $selectedStatus === 'all' ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700' }}">
        All Referrals
      </a>
    </nav>
  </div>

  {{-- Referrals Table --}}
  <x-ui.card>
    <x-ui.table>
      <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
        <tr>
          <th class="px-6 py-3.5 text-left">Referral No.</th>
          <th class="px-6 py-3.5 text-left">Category / Summary</th>
          <th class="px-6 py-3.5 text-left">Purok / Location</th>
          <th class="px-6 py-3.5 text-left">Status</th>
          <th class="px-6 py-3.5 text-left">Date Sent</th>
          <th class="px-6 py-3.5 text-right">Action</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 text-sm">
        @forelse($referrals as $ref)
          <tr class="hover:bg-slate-50/75 transition-colors">
            <td class="px-6 py-4 font-bold text-blue-600">
              <a href="{{ route('agency.referrals.show', $ref) }}" class="hover:underline">
                {{ $ref->reference_no }}
              </a>
            </td>
            <td class="px-6 py-4 text-slate-900">
              <div class="font-medium">{{ $ref->report?->category?->name ?? 'General Barangay Matter' }}</div>
              <div class="text-xs text-slate-500 line-clamp-1">{{ Str::limit($ref->referral_text, 65) }}</div>
            </td>
            <td class="px-6 py-4 text-xs text-slate-600">
              {{ $ref->report?->purok?->name ?? 'Calarian' }}
            </td>
            <td class="px-6 py-4">
              @if($ref->status === 'sent')
                <x-ui.badge variant="warning">Awaiting Acknowledgment</x-ui.badge>
              @elseif($ref->status === 'acknowledged')
                <x-ui.badge variant="info">Acknowledged</x-ui.badge>
              @elseif($ref->status === 'action_taken')
                <x-ui.badge variant="success">Action In Progress</x-ui.badge>
              @elseif($ref->status === 'closed')
                <x-ui.badge variant="neutral">Closed ({{ ucfirst($ref->outcome ?? 'Resolved') }})</x-ui.badge>
              @else
                <x-ui.badge variant="neutral">{{ ucfirst($ref->status) }}</x-ui.badge>
              @endif
            </td>
            <td class="px-6 py-4 text-xs text-slate-500">
              {{ $ref->sent_at ? \Carbon\Carbon::parse($ref->sent_at)->format('M d, Y') : '—' }}
            </td>
            <td class="px-6 py-4 text-right">
              <a href="{{ route('agency.referrals.show', $ref) }}"
                 class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
                View &amp; Action <i class="fa-solid fa-arrow-right ml-1"></i>
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="6" class="py-12">
              <x-ui.empty-state
                icon="fa-solid fa-inbox"
                title="No referrals found"
                description="There are currently no referrals assigned to your agency for this status." />
            </td>
          </tr>
        @endforelse
      </tbody>
    </x-ui.table>

    @if($referrals->hasPages())
      <div class="px-6 py-4 border-t border-slate-200">
        {{ $referrals->links() }}
      </div>
    @endif
  </x-ui.card>
</div>
@endsection
