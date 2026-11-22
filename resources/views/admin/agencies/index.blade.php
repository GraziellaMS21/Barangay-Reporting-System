@extends('layouts.admin')

@section('title', 'Partner Agencies')
@section('page-heading', 'Partner Agencies')

@section('content')

{{-- Page Header --}}
<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
  <div>
    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Partner Agencies</h1>
    <p class="text-sm text-slate-500 mt-0.5">Review, verify, and manage agency portal credentials for external departments.</p>
  </div>
</div>

{{-- KPI Summary Cards --}}
<section class="grid grid-cols-2 sm:grid-cols-4 gap-4 mb-6" aria-label="Agency request metrics">
  @php
    $cards = [
      ['label' => 'Pending Review', 'count' => $counts['pending'] ?? 0, 'bg' => 'bg-amber-50', 'text' => 'text-amber-700', 'border' => 'border-amber-200', 'icon' => 'fa-solid fa-clock', 'key' => 'pending'],
      ['label' => 'Approved Partners', 'count' => $counts['approved'] ?? 0, 'bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'icon' => 'fa-solid fa-circle-check', 'key' => 'approved'],
      ['label' => 'Rejected', 'count' => $counts['rejected'] ?? 0, 'bg' => 'bg-red-50', 'text' => 'text-red-700', 'border' => 'border-red-200', 'icon' => 'fa-solid fa-circle-xmark', 'key' => 'rejected'],
      ['label' => 'Suspended', 'count' => $counts['suspended'] ?? 0, 'bg' => 'bg-slate-50', 'text' => 'text-slate-700', 'border' => 'border-slate-200', 'icon' => 'fa-solid fa-ban', 'key' => 'suspended'],
    ];
  @endphp

  @foreach($cards as $c)
    <a href="{{ route('admin.agencies.index', ['status' => $c['key']]) }}"
       class="card rounded-2xl bg-white p-5 shadow-sm hover:shadow-md transition-all duration-200 border border-slate-100 flex items-center justify-between group text-decoration-none">
      <div class="flex flex-col justify-center min-w-0 pr-2">
        <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wider">{{ $c['label'] }}</span>
        <div class="text-2xl font-bold tracking-tight {{ $c['text'] }} mt-1">{{ $c['count'] }}</div>
      </div>
      <div class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0 {{ $c['bg'] }} {{ $c['text'] }} group-hover:scale-105 transition-transform" aria-hidden="true">
        <i class="{{ $c['icon'] }} text-base"></i>
      </div>
    </a>
  @endforeach
</section>

{{-- Status Tabs + List Table --}}
<section class="card rounded-2xl bg-white shadow-sm border border-slate-100">
  <div class="px-6 pt-5 pb-0">
    <nav class="-mb-px flex space-x-6 border-b border-slate-100" aria-label="Status Tabs">
      @php $currentStatus = request('status', 'pending'); @endphp
      @foreach(['pending' => 'Pending Verification', 'approved' => 'Approved Partners', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'all' => 'All Requests'] as $statusKey => $label)
        <a href="{{ route('admin.agencies.index', ['status' => $statusKey]) }}"
           class="whitespace-nowrap pb-3.5 px-1 border-b-2 font-semibold text-xs uppercase tracking-wider transition-colors {{ $currentStatus === $statusKey ? 'border-blue-600 text-blue-600' : 'border-transparent text-slate-400 hover:text-slate-700 hover:border-slate-300' }}">
          {{ $label }}
          @if(isset($counts[$statusKey]) && $counts[$statusKey] > 0)
            <span class="ml-1.5 py-0.5 px-2 rounded-full text-[11px] font-bold {{ $statusKey === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
              {{ $counts[$statusKey] }}
            </span>
          @endif
        </a>
      @endforeach
    </nav>
  </div>

  <div class="table-wrap overflow-x-auto">
    <table class="data-table data-table-cards w-full text-left border-collapse">
      <thead>
        <tr class="bg-slate-50/80 text-slate-500 text-xs font-semibold uppercase tracking-wider">
          <th class="py-3 px-5 rounded-l-lg">Agency / Department</th>
          <th class="py-3 px-5">Representative</th>
          <th class="py-3 px-5">Office</th>
          <th class="py-3 px-5">Role</th>
          <th class="py-3 px-5">Status</th>
          <th class="py-3 px-5">Submitted</th>
          <th class="py-3 px-5 text-right rounded-r-lg"><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100">
        @forelse($registrations as $reg)
          <tr class="hover:bg-slate-50/80 transition-colors cursor-pointer group" tabindex="0" role="link"
              aria-label="Review {{ $reg->agency_name_claimed }}"
              onclick="window.location='{{ route('admin.agencies.show', $reg) }}'"
              onkeydown="if(event.key==='Enter'||event.key===' '){event.preventDefault();window.location='{{ route('admin.agencies.show', $reg) }}';}">
            <td data-label="Agency" class="py-3.5 px-5">
              <div class="font-bold text-sm text-slate-900 group-hover:text-blue-600 transition-colors">{{ $reg->agency_name_claimed }}</div>
            </td>
            <td data-label="Representative" class="py-3.5 px-5">
              <div class="text-sm font-semibold text-slate-800">{{ $reg->user?->name ?? 'N/A' }}</div>
              <div class="text-[11px] text-slate-400 mt-0.5">{{ $reg->position_title }} · {{ $reg->contact_number }}</div>
            </td>
            <td data-label="Office" class="py-3.5 px-5 text-xs text-slate-600">
              {{ $reg->office?->name ?? 'Unassigned' }}
            </td>
            <td data-label="Role" class="py-3.5 px-5">
              @if($reg->is_office_admin)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                  <i class="fa-solid fa-crown text-[10px]" aria-hidden="true"></i> Admin
                </span>
              @else
                <span class="text-[11px] text-slate-400 font-medium">Representative</span>
              @endif
            </td>
            <td data-label="Status" class="py-3.5 px-5 whitespace-nowrap">
              @if($reg->status === 'pending')
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-amber-50 text-amber-800 border border-amber-200">
                  <i class="fa-solid fa-clock text-[10px]" aria-hidden="true"></i> Pending
                </span>
              @elseif($reg->status === 'approved')
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                  <i class="fa-solid fa-circle-check text-[10px]" aria-hidden="true"></i> Approved
                </span>
              @elseif($reg->status === 'rejected')
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-red-50 text-red-700 border border-red-200">
                  <i class="fa-solid fa-xmark text-[10px]" aria-hidden="true"></i> Rejected
                </span>
              @else
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600 border border-slate-200">
                  <i class="fa-solid fa-ban text-[10px]" aria-hidden="true"></i> {{ ucfirst($reg->status) }}
                </span>
              @endif
            </td>
            <td data-label="Submitted" class="py-3.5 px-5 text-xs text-slate-500 whitespace-nowrap">
              {{ $reg->created_at->format('M d, Y') }}
            </td>
            <td data-label="" class="py-3.5 px-5 text-right whitespace-nowrap">
              <span class="w-7 h-7 rounded-full inline-flex items-center justify-center text-slate-400 group-hover:text-blue-600 group-hover:bg-blue-50 transition-all">
                <i class="fa-solid fa-chevron-right text-xs" aria-hidden="true"></i>
              </span>
            </td>
          </tr>
        @empty
          <tr>
            <td class="table-empty py-12 text-center" colspan="7">
              <div class="flex flex-col items-center justify-center gap-2 text-slate-400">
                <i class="fa-solid fa-building-shield text-2xl text-slate-300" aria-hidden="true"></i>
                <span class="text-xs font-medium text-slate-500">No partner agency requests found for this filter.</span>
              </div>
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  @if($registrations->hasPages())
    <div class="px-6 py-4 border-t border-slate-100">
      {{ $registrations->links() }}
    </div>
  @endif
</section>
@endsection
