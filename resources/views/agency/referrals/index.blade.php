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
  <x-ui.table>
    <x-slot:header>
      <tr>
        <th scope="col" class="py-3 pl-4 pr-3 sm:pl-6 w-[28%] sm:w-[22%] md:w-[20%]">Referral No.</th>
        <th scope="col" class="px-3 py-3 w-[40%] sm:w-[34%] md:w-[30%]">Category / Summary</th>
        <th scope="col" class="hidden sm:table-cell px-3 py-3 w-[16%]">Purok / Location</th>
        <th scope="col" class="px-3 py-3 w-[20%] sm:w-[18%] md:w-[16%]">Status</th>
        <th scope="col" class="hidden lg:table-cell px-3 py-3 w-[14%]">Date Sent</th>
        <th scope="col" class="py-3 pl-3 pr-4 sm:pr-6 w-[12%] sm:w-[10%] text-right">Action</th>
      </tr>
    </x-slot:header>

    @forelse($referrals as $ref)
      <tr class="hover:bg-slate-50/75 transition-colors">
        <td class="py-3 pl-4 pr-3 sm:pl-6 text-xs font-bold text-blue-600" data-label="Referral No.">
          <a href="{{ route('agency.referrals.show', $ref) }}" class="hover:underline font-mono">
            {{ $ref->reference_no }}
          </a>
          <div class="text-[10px] text-slate-400 font-normal lg:hidden mt-0.5">
            {{ $ref->sent_at ? \Carbon\Carbon::parse($ref->sent_at)->format('M d, Y') : '' }}
          </div>
        </td>
        <td class="px-3 py-3 text-xs text-slate-900" data-label="Category / Summary">
          <div class="font-medium truncate max-w-[200px]" title="{{ $ref->report?->category?->name ?? 'General Barangay Matter' }}">
            {{ $ref->report?->category?->name ?? 'General Barangay Matter' }}
          </div>
          <div class="text-[11px] text-slate-500 line-clamp-1 truncate max-w-[220px]" title="{{ $ref->referral_text }}">
            {{ Str::limit($ref->referral_text, 65) }}
          </div>
        </td>
        <td class="hidden sm:table-cell px-3 py-3 text-xs text-slate-600 truncate max-w-[120px]" data-label="Location" title="{{ $ref->report?->purok?->name ?? 'Calarian' }}">
          {{ $ref->report?->purok?->name ?? 'Calarian' }}
        </td>
        <td class="px-3 py-3 text-xs" data-label="Status">
          @if($ref->status === 'sent')
            <x-ui.badge variant="warning">Awaiting Ack</x-ui.badge>
          @elseif($ref->status === 'acknowledged')
            <x-ui.badge variant="info">Acknowledged</x-ui.badge>
          @elseif($ref->status === 'action_taken')
            <x-ui.badge variant="success">In Progress</x-ui.badge>
          @elseif($ref->status === 'closed')
            <x-ui.badge variant="neutral">Closed ({{ ucfirst($ref->outcome ?? 'Resolved') }})</x-ui.badge>
          @else
            <x-ui.badge variant="neutral">{{ ucfirst($ref->status) }}</x-ui.badge>
          @endif
        </td>
        <td class="hidden lg:table-cell px-3 py-3 text-xs text-slate-500" data-label="Date Sent">
          {{ $ref->sent_at ? \Carbon\Carbon::parse($ref->sent_at)->format('M d, Y') : '—' }}
        </td>
        <td class="py-3 pl-3 pr-4 sm:pr-6 text-right text-xs" data-label="Action">
          <a href="{{ route('agency.referrals.show', $ref) }}"
             class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors"
             title="View &amp; Action" aria-label="View referral {{ $ref->reference_no }}">
            <span>View</span>
            <i class="fa-solid fa-arrow-right text-[10px]"></i>
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

    @if($referrals->hasPages())
      <x-slot:footer>
        <div class="p-3">
          {{ $referrals->links() }}
        </div>
      </x-slot:footer>
    @endif
  </x-ui.table>
</div>
@endsection
