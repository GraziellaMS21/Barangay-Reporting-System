@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-7xl space-y-8 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-2 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Secretary Dashboard</h1>
            <p class="mt-1 text-sm text-slate-600">Operational reminders, conciliation calendars, and statutory deadline monitoring.</p>
        </div>
        <div class="text-xs text-slate-500 font-medium">
            <i class="fa-regular fa-clock mr-1"></i> As of {{ now()->format('F d, Y · h:i A') }}
        </div>
    </div>

    <!-- 7 Operational Metrics Cards -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
        <!-- Today's Hearings -->
        <div class="rounded-xl border border-blue-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Today's Hearings</span>
            <p class="mt-2 text-2xl font-black text-blue-700">{{ $todayHearings->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Scheduled for today</p>
        </div>

        <!-- Next 7 Days Hearings -->
        <div class="rounded-xl border border-indigo-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Next 7 Days</span>
            <p class="mt-2 text-2xl font-black text-indigo-700">{{ $upcomingHearings->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Upcoming hearings</p>
        </div>

        <!-- Summons Awaiting Service -->
        <div class="rounded-xl border border-amber-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Awaiting Service</span>
            <p class="mt-2 text-2xl font-black text-amber-700">{{ $pendingSummons->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Pending summons notices</p>
        </div>

        <!-- Cases Near Deadline -->
        <div class="rounded-xl border border-yellow-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Near Deadline</span>
            <p class="mt-2 text-2xl font-black text-yellow-600">{{ $casesNearDeadline->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Day 12–14 of 15</p>
        </div>

        <!-- Overdue Cases -->
        <div class="rounded-xl border border-red-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Overdue Cases</span>
            <p class="mt-2 text-2xl font-black text-red-600">{{ $casesOverdue->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Exceeded 15 working days</p>
        </div>

        <!-- Ready for Next Step -->
        <div class="rounded-xl border border-slate-300 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Ready for Next Step</span>
            <p class="mt-2 text-2xl font-black text-slate-700">{{ $casesReadyForNextStep->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Mediation finished / unsettled</p>
        </div>

        <!-- Referrals Awaiting Follow-up -->
        <div class="rounded-xl border border-emerald-200 bg-white p-4 shadow-sm">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Referral Follow-ups</span>
            <p class="mt-2 text-2xl font-black text-emerald-700">{{ $referralsDue->count() }}</p>
            <p class="mt-1 text-[11px] text-slate-500">Awaiting agency follow-up</p>
        </div>
    </div>

    <!-- Section 1: Today's Hearings & Summons Awaiting Service -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Today's Hearings -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-day text-blue-600"></i> Today's Hearings ({{ $todayHearings->count() }})
                </h2>
                <a href="{{ route('admin.hearings.index', ['filter' => 'today']) }}" class="text-xs font-semibold text-blue-600 hover:underline">View all</a>
            </div>

            @if($todayHearings->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-xs text-slate-500 shadow-sm">
                    No hearings scheduled for today.
                </div>
            @else
                <div class="space-y-2">
                    @foreach($todayHearings as $th)
                        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm hover:border-blue-300 transition-colors">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-blue-700">{{ $th->scheduled_at?->format('h:i A') }}</span>
                                <span class="rounded px-2 py-0.5 text-[11px] font-semibold bg-blue-50 text-blue-800">{{ ucfirst($th->status) }}</span>
                            </div>
                            <h3 class="mt-1 text-xs font-semibold text-slate-900">
                                <a href="{{ route('admin.cases.show', ['case' => $th->case, 'tab' => 'hearings']) }}" class="hover:underline">
                                    {{ $th->case?->case_number }}: {{ $th->case?->title }}
                                </a>
                            </h3>
                            <div class="mt-1 text-[11px] text-slate-500 flex justify-between">
                                <span>Venue: {{ $th->venue ?? 'Barangay Hall' }}</span>
                                <span>Presided by: {{ $th->presidedBy?->full_name ?? 'Unassigned' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>

        <!-- Summons Awaiting Service -->
        <section class="space-y-3">
            <div class="flex items-center justify-between">
                <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-truck-fast text-amber-600"></i> Summons Awaiting Service ({{ $pendingSummons->count() }})
                </h2>
            </div>

            @if($pendingSummons->isEmpty())
                <div class="rounded-xl border border-slate-200 bg-white p-6 text-center text-xs text-slate-500 shadow-sm">
                    All issued summonses have recorded service results.
                </div>
            @else
                <div class="space-y-2">
                    @foreach($pendingSummons->take(5) as $ps)
                        <div class="rounded-lg border border-slate-200 bg-white p-3 shadow-sm hover:border-amber-300 transition-colors">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-bold text-slate-900">{{ $ps->summons_number ?? "Summons #{$ps->sequence_no}" }}</span>
                                <span class="rounded px-2 py-0.5 text-[11px] font-semibold bg-amber-100 text-amber-800">Pending Service</span>
                            </div>
                            <h3 class="mt-1 text-xs font-semibold text-slate-800">
                                <a href="{{ route('admin.cases.show', ['case' => $ps->case, 'tab' => 'summons']) }}" class="hover:underline text-blue-600">
                                    Case {{ $ps->case?->case_number }}
                                </a>
                                · Respondent: {{ $ps->respondentCaseParty?->party?->full_name ?? 'N/A' }}
                            </h3>
                            <div class="mt-1 text-[11px] text-slate-500 flex justify-between">
                                <span>Issued: {{ $ps->issued_on?->format('M d, Y') }}</span>
                                <span>Hearing: {{ $ps->hearing_at?->format('M d, Y h:i A') ?? 'N/A' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </section>
    </div>

    <!-- Section 2: Overdue & Deadline Monitoring -->
    <section class="space-y-4">
        <h2 class="text-base font-bold text-slate-900 flex items-center gap-2">
            <i class="fa-solid fa-hourglass-half text-red-600"></i> Statutory Case Deadline Monitoring
        </h2>

        @if($casesOverdue->isEmpty() && $casesNearDeadline->isEmpty())
            <div class="rounded-xl border border-slate-200 bg-white p-8 text-center text-xs text-slate-500 shadow-sm">
                <i class="fa-solid fa-shield-halved text-3xl text-emerald-500 mb-2"></i>
                <p class="font-semibold text-slate-700">No overdue cases or cases nearing statutory deadline</p>
                <p class="mt-0.5">All active mediation and conciliation cases are well within their 15 working days timeline.</p>
            </div>
        @else
            <div class="rounded-xl border border-slate-200 bg-white shadow-sm overflow-hidden">
                <table class="w-full text-left text-xs table-fixed responsive-card-table">
                    <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                        <tr>
                            <th class="py-3 px-4 w-[24%]">Case Docket</th>
                            <th class="py-3 px-4 w-[24%]">Parties</th>
                            <th class="py-3 px-4 w-[14%]">Current Status</th>
                            <th class="py-3 px-4 w-[16%]">Deadline Status</th>
                            <th class="py-3 px-4 w-[12%]">Deadline Date</th>
                            <th class="py-3 px-4 w-[10%] text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-700">
                        @foreach($casesOverdue->merge($casesNearDeadline) as $c)
                            @php $od = $c->overdue_info; @endphp
                            <tr class="hover:bg-slate-50/70 transition-colors">
                                <td data-label="Case Docket" class="py-3 px-4">
                                    <a href="{{ route('admin.cases.show', $c) }}" class="font-bold text-blue-600 hover:underline">
                                        {{ $c->case_number }}
                                    </a>
                                    <p class="text-[11px] text-slate-500 truncate max-w-xs">{{ $c->title }}</p>
                                </td>
                                <td data-label="Parties" class="py-3 px-4">
                                    <span class="font-medium text-slate-800">C: {{ $c->complainants->pluck('full_name')->first() ?? 'N/A' }}</span>
                                    <span class="block text-slate-500 text-[11px]">R: {{ $c->respondents->pluck('full_name')->first() ?? 'N/A' }}</span>
                                </td>
                                <td data-label="Current Status" class="py-3 px-4">
                                    <span class="rounded px-2 py-0.5 text-[11px] font-semibold bg-slate-100 text-slate-700">
                                        {{ \App\Support\CaseStatus::label($c->status) }}
                                    </span>
                                </td>
                                <td data-label="Deadline Status" class="py-3 px-4">
                                    @if($od['is_overdue'])
                                        <span class="rounded bg-red-100 px-2 py-0.5 text-[11px] font-bold text-red-800">
                                            Overdue {{ $od['days_overdue'] }} d
                                        </span>
                                    @elseif($od['day'] >= 12)
                                        <span class="rounded bg-amber-100 px-2 py-0.5 text-[11px] font-bold text-amber-800">
                                            Day {{ $od['day'] }} of 15 (Due soon)
                                        </span>
                                    @else
                                        <span class="rounded bg-slate-100 px-2 py-0.5 text-[11px] font-medium text-slate-700">
                                            Day {{ $od['day'] }} of 15
                                        </span>
                                    @endif
                                </td>
                                <td data-label="Deadline Date" class="py-3 px-4 text-slate-600 font-medium">
                                    {{ $od['deadline_on'] ? \Carbon\Carbon::parse($od['deadline_on'])->format('M d, Y') : 'N/A' }}
                                </td>
                                <td data-label="Actions" class="py-3 px-4 text-right">
                                    <a href="{{ route('admin.cases.show', $c) }}" class="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2.5 py-1 font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                                        Open Case
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
</div>
@endsection
