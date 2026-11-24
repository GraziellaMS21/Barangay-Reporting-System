<div class="space-y-6">
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h3 class="text-base font-semibold text-slate-900">Summons Management</h3>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                    {{ $case->summons->count() }} issued
                </span>
            </div>
            <p class="mt-1 text-xs text-slate-600">
                Track official summons notices, service returns, and party attendance.
                <span class="text-slate-400 cursor-help ml-1" title="Summonses are numbered sequentially for record-keeping only and do not establish statutory mediation limits.">
                    <i class="fa-solid fa-circle-info text-[11px]" aria-hidden="true"></i>
                    <span class="sr-only">Numbered sequentially for administrative tracking only</span>
                </span>
            </p>
        </div>

        @if(auth()->user()?->hasPermission('cases.summons'))
            <x-ui.button href="{{ route('admin.cases.summons.create', $case) }}" variant="primary">
                <i class="fa-solid fa-plus mr-1.5"></i> Issue Summons
            </x-ui.button>
        @endif
    </div>

    @if($case->summons->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center w-full bg-white space-y-3">
            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-envelope-open-text"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-800">No summons issued yet</p>
                <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto">Official summons notices issued for this case will appear here with service and attendance tracking.</p>
            </div>
            @if(isset($nextStep) && $nextStep['code'] === 'STEP_C_ISSUE_NOTICES')
                <div class="pt-2">
                    <button type="button" onclick="openWorkflowModal('modal-mark-notices-issued')" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-check-double"></i>
                        <span>Proceed to Next Step: Issue Summons & Notices</span>
                    </button>
                </div>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach($case->summons as $s)
                <div class="rounded-lg border {{ $s->voided_at ? 'border-red-200 bg-red-50/30' : 'border-slate-200 bg-white' }} p-4 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full {{ $s->voided_at ? 'bg-red-100 text-red-700' : 'bg-blue-100 text-blue-700' }} font-bold text-xs">
                                #{{ $s->sequence_no }}
                            </span>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">{{ $s->summons_number ?? "Summons #{$s->sequence_no}" }}</h4>
                                <p class="text-xs text-slate-500">Issued on {{ $s->issued_on?->format('M d, Y') ?? 'N/A' }} by {{ $s->issuedBy?->name ?? 'Staff' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 flex-wrap">
                            @if($s->voided_at)
                                <span class="rounded bg-red-100 px-2 py-0.5 text-xs font-semibold text-red-800">Voided</span>
                            @else
                                <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $s->service_result === 'received' ? 'bg-emerald-100 text-emerald-800' : ($s->service_result === 'refused' ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">
                                    Service: {{ ucfirst(str_replace('_', ' ', $s->service_result)) }}
                                </span>
                                <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $s->attendance === 'appeared' ? 'bg-emerald-100 text-emerald-800' : ($s->attendance === 'failed_to_appear' ? 'bg-rose-100 text-rose-800' : 'bg-slate-100 text-slate-700') }}">
                                    Attendance: {{ ucfirst(str_replace('_', ' ', $s->attendance)) }}
                                </span>
                            @endif

                            <a href="{{ route('admin.cases.summons.print', [$case, $s]) }}" target="_blank" class="inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <i class="fa-solid fa-print"></i> Print
                            </a>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-slate-500">Respondent</span>
                            <p class="font-semibold text-slate-800">{{ $s->respondentCaseParty?->party?->full_name ?? 'N/A' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Scheduled Hearing</span>
                            <p class="font-semibold text-slate-800">{{ $s->hearing_at?->format('M d, Y g:i A') ?? 'Not specified' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Venue</span>
                            <p class="font-semibold text-slate-800">{{ $s->venue ?? 'Barangay Hall' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Served On / By</span>
                            <p class="font-semibold text-slate-800">
                                {{ $s->served_on?->format('M d, Y') ?? 'Pending' }}
                                @if($s->servedBy)
                                    <span class="text-slate-500">({{ $s->servedBy->full_name }})</span>
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($s->remarks)
                        <div class="mt-2 text-xs bg-slate-50 p-2 rounded text-slate-600">
                            <span class="font-semibold text-slate-700">Remarks:</span> {{ $s->remarks }}
                        </div>
                    @endif

                    @if($s->voided_at)
                        <div class="mt-2 text-xs bg-red-100/60 p-2 rounded text-red-800">
                            <span class="font-semibold">Voided on {{ $s->voided_at->format('M d, Y g:i A') }}:</span> {{ $s->void_note }}
                        </div>
                    @elseif(auth()->user()?->hasPermission('cases.summons'))
                        <!-- Action Forms Bar -->
                        <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-3">
                            <!-- Service Result Trigger/Form -->
                            <details class="relative">
                                <summary class="cursor-pointer inline-flex items-center gap-1 rounded bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                                    <i class="fa-solid fa-truck"></i> Record Service
                                </summary>
                                <div class="absolute left-0 mt-2 z-20 w-72 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                    <form method="POST" action="{{ route('admin.cases.summons.service', [$case, $s]) }}" class="space-y-2 text-xs">
                                        @csrf
                                        <div>
                                            <label class="block font-semibold text-slate-700">Service Result</label>
                                            <select name="service_result" required class="w-full rounded border border-slate-300 p-1.5 text-xs">
                                                <option value="received" {{ $s->service_result === 'received' ? 'selected' : '' }}>Received</option>
                                                <option value="refused" {{ $s->service_result === 'refused' ? 'selected' : '' }}>Refused</option>
                                                <option value="not_found" {{ $s->service_result === 'not_found' ? 'selected' : '' }}>Not Found</option>
                                                <option value="other" {{ $s->service_result === 'other' ? 'selected' : '' }}>Other</option>
                                            </select>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700">Date Served</label>
                                            <input type="date" name="served_on" value="{{ $s->served_on?->format('Y-m-d') ?? date('Y-m-d') }}" required class="w-full rounded border border-slate-300 p-1.5 text-xs">
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700">Remarks</label>
                                            <textarea name="remarks" rows="2" class="w-full rounded border border-slate-300 p-1.5 text-xs" placeholder="e.g. Received by spouse...">{{ $s->remarks }}</textarea>
                                        </div>
                                        <div class="flex justify-end pt-1">
                                            <button type="submit" class="rounded bg-blue-600 px-3 py-1 font-semibold text-white hover:bg-blue-700">Save Service</button>
                                        </div>
                                    </form>
                                </div>
                            </details>

                            <!-- Attendance Form -->
                            <form method="POST" action="{{ route('admin.cases.summons.attendance', [$case, $s]) }}" class="inline-flex items-center gap-1.5 text-xs">
                                @csrf
                                <label class="font-medium text-slate-600">Attendance:</label>
                                <select name="attendance" onchange="this.form.submit()" class="rounded border border-slate-300 py-0.5 px-2 text-xs bg-white text-slate-700">
                                    <option value="pending" {{ $s->attendance === 'pending' ? 'selected' : '' }}>Pending</option>
                                    <option value="appeared" {{ $s->attendance === 'appeared' ? 'selected' : '' }}>Appeared</option>
                                    <option value="failed_to_appear" {{ $s->attendance === 'failed_to_appear' ? 'selected' : '' }}>Failed to Appear</option>
                                </select>
                            </form>

                            <!-- Void Action -->
                            <details class="relative ml-auto">
                                <summary class="cursor-pointer text-xs text-red-600 hover:text-red-800 font-medium">
                                    Void Summons
                                </summary>
                                <div class="absolute right-0 mt-2 z-20 w-64 rounded-lg border border-red-200 bg-white p-3 shadow-lg">
                                    <form method="POST" action="{{ route('admin.cases.summons.void', [$case, $s]) }}" class="space-y-2 text-xs">
                                        @csrf
                                        <label class="block font-semibold text-red-800">Reason for Voiding <span class="text-red-500">*</span></label>
                                        <textarea name="void_note" rows="2" required class="w-full rounded border border-slate-300 p-1.5 text-xs" placeholder="Reason why this summons was voided..."></textarea>
                                        <div class="flex justify-end pt-1">
                                            <button type="submit" class="rounded bg-red-600 px-3 py-1 font-semibold text-white hover:bg-red-700">Confirm Void</button>
                                        </div>
                                    </form>
                                </div>
                            </details>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    @endif
</div>
