<div class="space-y-5">
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-base font-semibold text-slate-900">Summons Archive</h3>
                @php
                    $nonVoidedCount = $case->summons->whereNull('voided_at')->count();
                @endphp
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-slate-200 text-slate-800">
                    {{ $nonVoidedCount }} {{ $nonVoidedCount === 1 ? 'summons' : 'summonses' }} issued (record only)
                </span>
            </div>
            <p class="mt-1 text-xs text-slate-600">
                Read-only archive of official KP Form No. 9 notices generated for this case.
            </p>
        </div>

        <button type="button"
                onclick="if(typeof switchCaseTab==='function') switchCaseTab('hearings')"
                class="inline-flex items-center gap-1.5 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 hover:bg-blue-100 transition-colors cursor-pointer">
            <i class="fa-solid fa-calendar-days"></i>
            <span>Summons are issued when a hearing is scheduled. Go to Hearings.</span>
        </button>
    </div>

    @if($case->summons->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center w-full bg-white space-y-2">
            <div class="w-11 h-11 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center mx-auto text-lg">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <p class="text-sm font-semibold text-slate-700">No summons yet. Schedule the first hearing.</p>
        </div>
    @else
        <div class="space-y-3">
            @foreach($case->summons->sortByDesc('sequence_no') as $s)
                @php
                    $isSuperseded = ! empty($s->superseded_at);
                    $isVoided = ! empty($s->voided_at);
                    $serviceLabel = match ($s->service_method) {
                        'personal' => 'Personal',
                        'substituted' => 'Substituted',
                        'refused' => 'Refused to receive',
                        'not_served' => 'Could not be served',
                        'appeared_voluntarily' => 'Appeared voluntarily',
                        default => $s->service_skipped_reason ? 'Skipped (Override)' : ucfirst(str_replace('_', ' ', $s->service_result ?? 'pending')),
                    };
                @endphp
                <div class="rounded-lg border {{ $isVoided ? 'border-red-200 bg-red-50/30' : ($isSuperseded ? 'border-slate-200 bg-slate-50/70 opacity-80' : 'border-slate-200 bg-white') }} p-4 shadow-xs">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $isSuperseded ? 'bg-slate-200 text-slate-600' : 'bg-blue-100 text-blue-700' }} font-bold text-xs">
                                #{{ $s->sequence_no }}
                            </span>
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <h4 class="text-sm font-bold text-slate-900">{{ $s->summons_number ?? "Summons #{$s->sequence_no}" }}</h4>
                                    @if($isSuperseded)
                                        <span class="rounded bg-slate-200 px-2 py-0.5 text-[10px] font-bold uppercase text-slate-700">Superseded</span>
                                    @elseif($isVoided)
                                        <span class="rounded bg-red-100 px-2 py-0.5 text-[10px] font-bold uppercase text-red-700">Voided</span>
                                    @endif
                                </div>
                                <p class="text-xs text-slate-500">
                                    Issued {{ $s->issued_on?->format('M d, Y') ?? 'N/A' }}
                                    @if($s->hearing)
                                        · Linked to Hearing #{{ $s->hearing->sequence_no }} ({{ $s->hearing->scheduled_at?->format('M d, Y g:i A') }})
                                    @elseif($s->hearing_at)
                                        · Hearing {{ $s->hearing_at->format('M d, Y g:i A') }}
                                    @endif
                                </p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $s->isServiceRecorded() ? 'bg-emerald-100 text-emerald-800' : ($s->service_method === 'not_served' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                Service: {{ $serviceLabel }}
                            </span>

                            <a href="{{ route('admin.cases.printables.summons', [$case, $s]) }}" target="_blank" class="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-print"></i> {{ $s->isServiceRecorded() || $isSuperseded ? 'Reprint' : 'Print' }}
                            </a>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                        <div>
                            <span class="text-slate-500">Respondent</span>
                            <p class="font-semibold text-slate-800">{{ $s->respondentCaseParty?->party?->full_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Venue</span>
                            <p class="font-semibold text-slate-800">{{ $s->venue ?? 'Barangay Calarian Hall' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Served On / Method</span>
                            <p class="font-semibold text-slate-800">
                                @if($s->served_on)
                                    {{ $s->served_on->format('M d, Y') }} · {{ $serviceLabel }}
                                    @if($s->served_by)
                                        <span class="text-slate-500 font-normal">({{ $s->served_by }})</span>
                                    @endif
                                @else
                                    {{ $s->service_skipped_reason ? 'Skipped' : 'Not recorded' }}
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
