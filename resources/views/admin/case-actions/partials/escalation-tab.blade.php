<div class="space-y-6">
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Escalation & Certification to File Action (CFA)</h3>
            <p class="mt-1 text-sm text-slate-600">Complainant-initiated escalation to external authorities (Court, PNP, Prosecutor) when mediation or conciliation fails.</p>
        </div>

        @if(auth()->user()?->hasPermission('cases.escalate') && $case->status !== \App\Support\CaseStatus::ESCALATED && $case->status !== \App\Support\CaseStatus::SETTLED)
            <x-ui.button href="{{ route('admin.cases.escalations.create', $case) }}" variant="danger">
                <i class="fa-solid fa-arrow-up-right-from-square mr-1.5"></i> Escalate Case / Issue CFA
            </x-ui.button>
        @endif
    </div>

    <!-- Informational Note -->
    <div class="rounded-lg border border-blue-100 bg-blue-50/50 p-4 text-xs text-blue-900 flex items-start gap-3">
        <i class="fa-solid fa-circle-info text-blue-600 text-sm mt-0.5"></i>
        <div class="space-y-1">
            <p class="font-semibold">Katarungang Pambarangay Legal Reminder</p>
            <p class="text-blue-800/90 leading-relaxed">
                Summons counts are for administrative record-keeping only and do not determine escalation timing. Escalation requires a formal complainant request and confirmation by an authorized official (Punong Barangay / Pangkat Chairman). The system never files court charges or dispatches police automatically.
            </p>
        </div>
    </div>

    @if($case->escalations->isEmpty())
        <div class="rounded-lg border border-dashed border-slate-300 p-8 text-center">
            <i class="fa-solid fa-file-shield text-3xl text-slate-400 mb-2"></i>
            <p class="text-sm font-semibold text-slate-700">No escalation record filed</p>
            <p class="mt-1 text-xs text-slate-500">When mediation reaches an impasse and the complainant requests escalation, the CFA record and printable letters will be stored here.</p>
        </div>
    @else
        <div class="space-y-4">
            @foreach($case->escalations->sortByDesc('created_at') as $esc)
                <div class="rounded-lg border border-red-200 bg-white p-5 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-full bg-red-100 text-red-700 font-bold text-sm">
                                <i class="fa-solid fa-certificate"></i>
                            </span>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">
                                    CFA #{{ $esc->cfa_number ?? 'Pending Number' }}
                                    <span class="ml-2 text-xs font-normal text-slate-500">to {{ $esc->destination?->name ?? 'External Agency' }}</span>
                                </h4>
                                <p class="text-xs text-slate-500">Escalated on {{ $esc->escalated_at?->format('F d, Y · h:i A') ?? $esc->created_at?->format('F d, Y') ?? 'N/A' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            <a href="{{ route('admin.cases.escalations.cfa', $case) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                                <i class="fa-solid fa-print text-blue-600"></i> Print CFA
                            </a>
                            <a href="{{ route('admin.cases.escalations.letter', $case) }}" target="_blank" class="inline-flex items-center gap-1.5 rounded-lg border border-slate-300 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                                <i class="fa-solid fa-envelope-open-text text-emerald-600"></i> Print Endorsement Letter
                            </a>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-4 text-xs">
                        <div>
                            <span class="text-slate-500 font-medium">Requesting Complainant</span>
                            <p class="font-bold text-slate-800 mt-0.5">{{ $esc->requestedByParty?->party?->full_name ?? 'Complainant' }}</p>
                            <p class="text-slate-400 text-[11px]">Requested: {{ $esc->requested_on?->format('M d, Y') ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium">Escalation Destination</span>
                            <p class="font-bold text-slate-800 mt-0.5">{{ $esc->destination?->name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium">Summons Count at Time</span>
                            <p class="font-bold text-slate-800 mt-0.5">{{ $esc->summons_count_at_time }} summons issued</p>
                        </div>
                        <div>
                            <span class="text-slate-500 font-medium">Authorized Officials</span>
                            <p class="font-bold text-slate-800 mt-0.5">{{ $esc->signedByPersonnel?->full_name ?? $case->pangkatChairman?->full_name ?? 'Pangkat Chairman' }}</p>
                            <p class="text-slate-400 text-[11px]">Encoded by: {{ $esc->escalatedBy?->name ?? 'Staff' }}</p>
                        </div>
                    </div>

                    <div class="mt-3 text-xs bg-slate-50 p-3 rounded border border-slate-200">
                        <span class="font-bold text-slate-900 block mb-1">Stated Reason for Escalation:</span>
                        <p class="text-slate-700 whitespace-pre-wrap leading-relaxed">{{ $esc->reason }}</p>
                        @if($esc->override_note)
                            <div class="mt-2 pt-2 border-t border-slate-200/80 text-amber-800">
                                <span class="font-bold">Official Note:</span> {{ $esc->override_note }}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
