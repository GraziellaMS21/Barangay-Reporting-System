@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-7xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Conciliation Hearings</h1>
            <p class="mt-1 text-sm text-slate-600">Mediation and conciliation hearing schedule across all barangay cases.</p>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Hearings</span>
            <p class="mt-2 text-3xl font-extrabold text-blue-600">{{ $todayCount }}</p>
        </div>
        <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Upcoming Schedule</span>
            <p class="mt-2 text-3xl font-extrabold text-emerald-600">{{ $upcomingCount }}</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="flex items-center gap-2 border-b border-slate-200 pb-2">
        <a href="{{ route('admin.hearings.index', ['filter' => 'upcoming']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-md {{ $filter === 'upcoming' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Upcoming
        </a>
        <a href="{{ route('admin.hearings.index', ['filter' => 'today']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-md {{ $filter === 'today' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Today ({{ $todayCount }})
        </a>
        <a href="{{ route('admin.hearings.index', ['filter' => 'past']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-md {{ $filter === 'past' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            Past Proceedings
        </a>
        <a href="{{ route('admin.hearings.index', ['filter' => 'all']) }}" class="px-3 py-1.5 text-xs font-semibold rounded-md {{ $filter === 'all' ? 'bg-blue-600 text-white' : 'text-slate-600 hover:bg-slate-100' }}">
            All
        </a>
    </div>

    <!-- Hearings Table -->
    @if($hearings->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-12 text-center bg-white">
            <i class="fa-solid fa-calendar-xmark text-4xl text-slate-400 mb-3"></i>
            <h3 class="text-base font-bold text-slate-800">No hearings found</h3>
            <p class="mt-1 text-xs text-slate-500">There are no hearings matching the selected filter.</p>
        </div>
    @else
        <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
            <table class="w-full text-left text-xs table-fixed responsive-card-table">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4 w-[16%]">Date & Time</th>
                        <th class="py-3 px-4 w-[22%]">Case Docket</th>
                        <th class="py-3 px-4 w-[22%]">Parties</th>
                        <th class="py-3 px-4 w-[12%]">Venue</th>
                        <th class="py-3 px-4 w-[12%]">Presided By</th>
                        <th class="py-3 px-4 w-[8%]">Status</th>
                        <th class="py-3 px-4 w-[8%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($hearings as $h)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td data-label="Date & Time" class="py-3 px-4 font-bold text-slate-900">
                                {{ $h->scheduled_at?->format('M d, Y · h:i A') }}
                            </td>
                            <td data-label="Case Docket" class="py-3 px-4">
                                <a href="{{ route('admin.cases.show', ['case' => $h->case, 'tab' => 'hearings']) }}" class="font-bold text-blue-600 hover:underline">
                                    {{ $h->case?->case_number }}
                                </a>
                                <p class="text-[11px] text-slate-500 truncate max-w-xs">{{ $h->case?->title }}</p>
                            </td>
                            <td data-label="Parties" class="py-3 px-4">
                                <span class="font-medium text-slate-800">C: {{ $h->case?->complainants?->pluck('full_name')->first() ?? 'N/A' }}</span>
                                <span class="block text-slate-500 text-[11px]">R: {{ $h->case?->respondents?->pluck('full_name')->first() ?? 'N/A' }}</span>
                            </td>
                            <td data-label="Venue" class="py-3 px-4">
                                {{ $h->venue ?? 'Barangay Hall' }}
                            </td>
                            <td data-label="Presided By" class="py-3 px-4">
                                {{ $h->presidedBy?->full_name ?? 'Unassigned' }}
                            </td>
                            <td data-label="Status" class="py-3 px-4">
                                <span class="rounded px-2 py-0.5 text-[11px] font-semibold {{ $h->status === 'held' ? 'bg-emerald-100 text-emerald-800' : ($h->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800') }}">
                                    {{ ucfirst($h->status) }}
                                </span>
                            </td>
                            <td data-label="Actions" class="py-3 px-4 text-right">
                                <a href="{{ route('admin.cases.show', ['case' => $h->case, 'tab' => 'hearings']) }}" class="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2.5 py-1 font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                                    View Hearing
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $hearings->links() }}
        </div>
    @endif
</div>
@endsection
