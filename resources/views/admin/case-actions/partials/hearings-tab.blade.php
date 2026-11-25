@php
    $kpService = app(\App\Services\KatarungangPambarangayService::class);
    $activeHearings = $case->hearings->whereNull('superseded_at')->sortBy('sequence_no');
    $supersededHearings = $case->hearings->whereNotNull('superseded_at')->sortByDesc('sequence_no');
    $firstActiveHearing = $activeHearings->first();
    $nextAttemptNo = ((int) $case->hearings->max('sequence_no')) + 1;
    $hasAnyHearing = $case->hearings->isNotEmpty();
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Conciliation Hearings</h3>
            <p class="mt-1 text-xs text-slate-600">Each hearing attempt links Hearing, Summons (KP Form 9), Service Proof, and Outcome in one chain.</p>
        </div>

        @if(auth()->user()?->hasPermission('cases.summons') || auth()->user()?->hasPermission('cases.edit'))
            <button type="button"
                    onclick="openScheduleModal({ attemptNo: {{ $nextAttemptNo }}, rescheduleHearingId: {{ $activeHearings->last()?->id ?? 'null' }} })"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700 cursor-pointer">
                <i class="fa-solid fa-calendar-plus"></i>
                <span>{{ $hasAnyHearing ? "Schedule Next Hearing (Summons #{$nextAttemptNo})" : 'Schedule Hearing & Issue Summons' }}</span>
            </button>
        @endif
    </div>

    @if($activeHearings->isEmpty() && $supersededHearings->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center w-full bg-white space-y-3">
            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-calendar-xmark"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-800">No hearings scheduled yet</p>
                <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto">Schedule the first hearing to generate Hearing #1 and Summons #1 together.</p>
            </div>
        </div>
    @else
        <!-- Active Hearings -->
        <div class="space-y-4">
            @foreach($activeHearings->sortByDesc('sequence_no') as $h)
                @php
                    $summons = $h->summons;
                    $seq = $h->sequence_no ?: 1;
                    $dayInfo = $h->scheduled_at ? $kpService->workingDayForDate($case, $h->scheduled_at, $firstActiveHearing) : null;
                    $serviceRecorded = $summons?->isServiceRecorded() ?? false;
                    $serviceSkipped = $summons?->isServiceSkipped() ?? false;
                    $canRecordOutcome = $summons ? $summons->canRecordOutcome() : true;
                    $outcomeRecorded = in_array($h->status, ['held', 'settled', 'unsettled', 'reset'], true);
                    $serviceMethodLabel = match ($summons?->service_method) {
                        'personal' => 'Personal',
                        'substituted' => 'Substituted',
                        'refused' => 'Refused to receive',
                        'not_served' => 'Could not be served',
                        'appeared_voluntarily' => 'Appeared voluntarily',
                        default => ucfirst(str_replace('_', ' ', $summons?->service_result ?? 'pending')),
                    };
                @endphp

                <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-xs space-y-4">
                    <!-- Card Header -->
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between border-b border-slate-100 pb-3">
                        <div>
                            <div class="flex items-center gap-2 flex-wrap">
                                <h4 class="text-sm font-bold text-slate-900">
                                    Hearing #{{ $seq }} — {{ $h->scheduled_at?->format('M d, Y · g:i A') }} — {{ $h->venue ?? 'Barangay Calarian Hall' }}
                                </h4>
                                <span class="rounded px-2 py-0.5 text-[11px] font-semibold {{ $h->status === 'settled' ? 'bg-emerald-100 text-emerald-800' : ($h->status === 'unsettled' ? 'bg-rose-100 text-rose-800' : ($h->status === 'held' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800')) }}">
                                    {{ ucfirst($h->status) }}
                                </span>
                            </div>
                            <div class="mt-1 flex items-center gap-3 text-xs text-slate-600 flex-wrap">
                                <span>Presiding: <strong>{{ $h->presidedBy?->full_name ?? 'Punong Barangay' }}</strong></span>
                                @if($dayInfo)
                                    <span>·</span>
                                    <span class="font-medium {{ $dayInfo['beyond_end'] ? 'text-amber-700' : 'text-slate-700' }}">
                                        Working day {{ $dayInfo['day'] }} of {{ $dayInfo['period'] }}
                                        @if($dayInfo['beyond_end'])
                                            <span class="ml-1 rounded bg-amber-100 px-1.5 py-0.5 text-[10px] font-bold text-amber-800">Beyond 15-day period</span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </div>

                        <details class="relative shrink-0">
                            <summary class="cursor-pointer list-none px-2.5 py-1.5 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-xs font-semibold text-slate-700 inline-flex items-center gap-1">
                                <span>More</span>
                                <i class="fa-solid fa-chevron-down text-[10px]"></i>
                            </summary>
                            <div class="absolute right-0 mt-1 w-48 rounded-lg border border-slate-200 bg-white py-1 shadow-lg z-20">
                                <button type="button"
                                        onclick="this.closest('details').open = false; openScheduleModal({ attemptNo: {{ $nextAttemptNo }}, rescheduleHearingId: {{ $h->id }} })"
                                        class="w-full text-left px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2 cursor-pointer">
                                    <i class="fa-solid fa-arrows-rotate text-slate-500 w-3.5"></i>
                                    <span>Reschedule</span>
                                </button>
                                @if($summons)
                                    <a href="{{ route('admin.cases.printables.summons', [$case, $summons]) }}" target="_blank" class="px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                        <i class="fa-solid fa-print text-slate-500 w-3.5"></i>
                                        <span>Reprint Summons</span>
                                    </a>
                                @endif
                                <a href="{{ route('admin.cases.printables.conciliation-notice', [$case, $h]) }}" target="_blank" class="px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-50 flex items-center gap-2">
                                    <i class="fa-solid fa-calendar-check text-slate-500 w-3.5"></i>
                                    <span>Reprint Notice</span>
                                </a>
                            </div>
                        </details>
                    </div>

                    <!-- 3-Step Chain Inside Card -->
                    <div class="space-y-3 text-xs">
                        <!-- Line 1: Summons -->
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 p-2.5 rounded-lg bg-slate-50 border border-slate-200/70">
                            <div class="flex items-center gap-2">
                                <span class="w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-[10px] font-bold">1</span>
                                @if($summons)
                                    <span class="text-slate-800">
                                        <strong>Summons #{{ $summons->sequence_no }}</strong> issued {{ $summons->issued_on?->format('M d, Y') ?? 'N/A' }}
                                    </span>
                                @elseif($h->no_summons_flag)
                                    <span class="text-amber-800 font-medium">No summons on record</span>
                                @else
                                    <span class="text-slate-500">Summons pending</span>
                                @endif
                            </div>

                            @if($summons)
                                <div class="flex items-center gap-2 flex-wrap">
                                    <a href="{{ route('admin.cases.printables.summons', [$case, $summons]) }}" target="_blank" class="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        <i class="fa-solid fa-print"></i> Print Summons (Form 9)
                                    </a>
                                    <a href="{{ route('admin.cases.printables.conciliation-notice', [$case, $h]) }}" target="_blank" class="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                        <i class="fa-solid fa-calendar-check"></i> Print Notice - Complainant
                                    </a>
                                </div>
                            @endif
                        </div>

                        <!-- Line 2: Service -->
                        <div class="p-2.5 rounded-lg border {{ $serviceRecorded ? 'bg-emerald-50/40 border-emerald-200/70' : ($serviceSkipped ? 'bg-amber-50/40 border-amber-200' : 'bg-slate-50 border-slate-200/70') }}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="w-5 h-5 rounded-full {{ $canRecordOutcome ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }} flex items-center justify-center text-[10px] font-bold">2</span>
                                    @if($serviceRecorded)
                                        <span class="text-slate-800">
                                            <strong>Service:</strong>
                                            Served {{ $summons->served_on?->format('M d, Y') }} - {{ $serviceMethodLabel }}
                                            @if($summons->received_by_name)
                                                - received by {{ $summons->received_by_name }}@if($summons->received_by_relationship), {{ $summons->received_by_relationship }}@endif
                                            @endif
                                        </span>
                                    @elseif($serviceSkipped)
                                        <span class="text-amber-900">
                                            <strong>Service skipped:</strong> {{ $summons->service_skipped_reason }}
                                        </span>
                                    @elseif($summons?->service_method === 'not_served')
                                        <span class="text-rose-700 font-semibold">
                                            Service: Could not be served ({{ $summons->served_on?->format('M d, Y') }})
                                        </span>
                                    @else
                                        <span class="text-slate-700 font-medium">Service: not recorded yet</span>
                                    @endif
                                </div>

                                @if($summons)
                                    <div class="flex items-center gap-2 flex-wrap">
                                        @if($serviceRecorded)
                                            <button type="button"
                                                    onclick="openRecordServiceModal({{ $summons->id }})"
                                                    class="text-xs font-semibold text-blue-700 hover:underline cursor-pointer">
                                                Edit
                                            </button>
                                        @else
                                            <button type="button"
                                                    onclick="openRecordServiceModal({{ $summons->id }})"
                                                    class="inline-flex items-center gap-1 rounded bg-blue-600 px-2.5 py-1 text-xs font-semibold text-white hover:bg-blue-700 cursor-pointer">
                                                <i class="fa-solid fa-clipboard-check"></i> Record Service
                                            </button>
                                            <form method="POST" action="{{ route('admin.cases.workflow.voluntary-appearance', $case) }}" class="inline">
                                                @csrf
                                                <input type="hidden" name="summons_id" value="{{ $summons->id }}">
                                                <button type="submit" class="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50 cursor-pointer">
                                                    Respondent appeared voluntarily
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                            @if(! $serviceRecorded && ! $serviceSkipped)
                                <p class="mt-1.5 pl-7 text-[11px] text-slate-500">
                                    "Records who delivered the summons, when, and how. A no-show only counts if the summons was served."
                                </p>
                            @endif
                        </div>

                        <!-- Line 3: Hearing Outcome -->
                        <div class="p-2.5 rounded-lg border {{ $outcomeRecorded ? 'bg-emerald-50/40 border-emerald-200/70' : 'bg-slate-50 border-slate-200/70' }}">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="w-5 h-5 rounded-full {{ $outcomeRecorded ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' }} flex items-center justify-center text-[10px] font-bold">3</span>
                                    @if($outcomeRecorded)
                                        <span class="text-slate-800">
                                            <strong>Hearing outcome:</strong> {{ ucfirst(str_replace('_', ' ', $h->status)) }}
                                            · Complainant: {{ ucfirst(str_replace('_', ' ', $h->complainant_attendance ?? 'pending')) }}
                                            · Respondent: {{ ucfirst(str_replace('_', ' ', $h->respondent_attendance ?? 'pending')) }}
                                        </span>
                                    @else
                                        <span class="text-slate-700 font-medium">Hearing outcome: pending</span>
                                    @endif
                                </div>

                                <div class="flex items-center gap-2 flex-wrap">
                                    @if($canRecordOutcome)
                                        <button type="button"
                                                onclick="openRecordOutcomeModal({{ $h->id }})"
                                                class="inline-flex items-center gap-1 rounded {{ $outcomeRecorded ? 'border border-slate-300 bg-white text-slate-700 hover:bg-slate-50' : 'bg-blue-600 text-white hover:bg-blue-700' }} px-2.5 py-1 text-xs font-semibold cursor-pointer">
                                            <i class="fa-solid fa-gavel"></i> {{ $outcomeRecorded ? 'Update Outcome' : 'Record Outcome' }}
                                        </button>
                                    @else
                                        <button type="button"
                                                disabled
                                                title="Record service first"
                                                class="inline-flex items-center gap-1 rounded bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-500 cursor-not-allowed">
                                            <i class="fa-solid fa-lock text-[10px]"></i> Record Outcome
                                        </button>
                                        @if($summons)
                                            <button type="button"
                                                    onclick="openSkipServiceModal({{ $summons->id }})"
                                                    class="text-[11px] font-semibold text-amber-700 hover:underline cursor-pointer">
                                                Skip service (reason required)
                                            </button>
                                        @endif
                                    @endif
                                </div>
                            </div>

                            @if($h->outcome_note)
                                <p class="mt-1.5 pl-7 text-xs text-slate-600 whitespace-pre-wrap">{{ $h->outcome_note }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Superseded Hearings (Collapsed) -->
        @if($supersededHearings->isNotEmpty())
            <details class="rounded-lg border border-slate-200 bg-slate-50 p-3">
                <summary class="cursor-pointer text-xs font-semibold text-slate-600 hover:text-slate-900">
                    Superseded hearings ({{ $supersededHearings->count() }})
                </summary>
                <div class="mt-3 space-y-2">
                    @foreach($supersededHearings as $sh)
                        <div class="rounded-lg border border-slate-200 bg-white p-3 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-2 opacity-80">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-bold uppercase text-slate-700">Superseded</span>
                                    <span class="font-bold text-slate-800">Hearing #{{ $sh->sequence_no }} — {{ $sh->scheduled_at?->format('M d, Y · g:i A') }}</span>
                                </div>
                                @if($sh->superseded_reason)
                                    <p class="text-slate-600 mt-1">Reason: {{ $sh->superseded_reason }}</p>
                                @endif
                            </div>
                            <span class="text-[11px] text-slate-400">Superseded {{ $sh->superseded_at?->format('M d, Y') }}</span>
                        </div>
                    @endforeach
                </div>
            </details>
        @endif
    @endif
</div>
