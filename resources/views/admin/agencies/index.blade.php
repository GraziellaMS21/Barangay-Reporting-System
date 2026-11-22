@extends('layouts.admin')

@section('title', 'Partner Agencies')
@section('page-heading', 'Partner Agencies & Access Requests')

@section('content')
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900">Agency Access Requests & Partner Directory</h2>
      <p class="text-sm text-slate-500">Review, verify, and manage agency portal credentials for external departments.</p>
    </div>
  </div>

  {{-- Filters / Tabs --}}
  <div class="border-b border-slate-200">
    <nav class="-mb-px flex space-x-6" aria-label="Status Tabs">
      @php
        $currentStatus = request('status', 'pending');
      @endphp
      @foreach(['pending' => 'Pending Verification', 'approved' => 'Approved Partners', 'rejected' => 'Rejected', 'suspended' => 'Suspended', 'all' => 'All Requests'] as $statusKey => $label)
        <a href="{{ route('admin.agencies.index', ['status' => $statusKey]) }}"
           class="whitespace-nowrap pb-3 px-1 border-b-2 font-medium text-sm transition-colors {{ $currentStatus === $statusKey ? 'border-blue-600 text-blue-600 font-semibold' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
          {{ $label }}
          @if(isset($counts[$statusKey]) && $counts[$statusKey] > 0)
            <span class="ml-1.5 py-0.5 px-2 rounded-full text-xs font-semibold {{ $statusKey === 'pending' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700' }}">
              {{ $counts[$statusKey] }}
            </span>
          @endif
        </a>
      @endforeach
    </nav>
  </div>

  {{-- Table Card --}}
  <x-ui.card>
    <x-ui.table>
      <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
        <tr>
          <th class="px-6 py-3.5 text-left">Agency / Department</th>
          <th class="px-6 py-3.5 text-left">Representative</th>
          <th class="px-6 py-3.5 text-left">Linked Office</th>
          <th class="px-6 py-3.5 text-left">Role</th>
          <th class="px-6 py-3.5 text-left">Status</th>
          <th class="px-6 py-3.5 text-left">Submitted</th>
          <th class="px-6 py-3.5 text-right">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-200 text-sm">
        @forelse($registrations as $reg)
          <tr class="hover:bg-slate-50/75 transition-colors">
            <td class="px-6 py-4 font-semibold text-slate-900">
              <a href="{{ route('admin.agencies.show', $reg) }}" class="hover:text-blue-600">
                {{ $reg->agency_name_claimed }}
              </a>
            </td>
            <td class="px-6 py-4 text-slate-600">
              <div>{{ $reg->user?->name ?? 'N/A' }}</div>
              <div class="text-xs text-slate-400">{{ $reg->position_title }} · {{ $reg->contact_number }}</div>
            </td>
            <td class="px-6 py-4 text-slate-600">
              {{ $reg->office?->name ?? 'Unassigned' }}
            </td>
            <td class="px-6 py-4">
              @if($reg->is_office_admin)
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                  <i class="fa-solid fa-crown mr-1 text-[10px]"></i> Office Admin
                </span>
              @else
                <span class="text-xs text-slate-500">Representative</span>
              @endif
            </td>
            <td class="px-6 py-4">
              @if($reg->status === 'pending')
                <x-ui.badge variant="warning">Pending Review</x-ui.badge>
              @elseif($reg->status === 'approved')
                <x-ui.badge variant="success">Approved</x-ui.badge>
              @elseif($reg->status === 'rejected')
                <x-ui.badge variant="danger">Rejected</x-ui.badge>
              @else
                <x-ui.badge variant="neutral">{{ ucfirst($reg->status) }}</x-ui.badge>
              @endif
            </td>
            <td class="px-6 py-4 text-xs text-slate-500">
              {{ $reg->created_at->format('M d, Y') }}
            </td>
            <td class="px-6 py-4 text-right space-x-2">
              <a href="{{ route('admin.agencies.show', $reg) }}" class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium bg-slate-100 hover:bg-slate-200 text-slate-700 transition-colors">
                Review <i class="fa-solid fa-arrow-right ml-1"></i>
              </a>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="7" class="py-12">
              <x-ui.empty-state
                icon="fa-solid fa-building-shield"
                title="No agency registrations found"
                description="There are currently no partner agency requests matching this filter." />
            </td>
          </tr>
        @endforelse
      </tbody>
    </x-ui.table>

    @if($registrations->hasPages())
      <div class="px-6 py-4 border-t border-slate-200">
        {{ $registrations->links() }}
      </div>
    @endif
  </x-ui.card>
</div>
@endsection
