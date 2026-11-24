<div class="space-y-6">
    <div class="flex flex-col gap-3 rounded-lg border border-slate-200 bg-slate-50 p-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h3 class="text-base font-semibold text-slate-900">Conciliation Hearings</h3>
            <p class="mt-1 text-sm text-slate-600">Schedule mediation and conciliation hearings, assign presiding Lupon members, and log hearing outcomes.</p>
        </div>

        @if(auth()->user()?->hasPermission('cases.summons'))
            <button type="button" onclick="document.getElementById('schedule-hearing-panel').classList.toggle('hidden')" class="inline-flex items-center gap-1.5 rounded-lg bg-blue-600 px-3.5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-blue-700">
                <i class="fa-solid fa-calendar-plus"></i> Schedule Hearing
            </button>
        @endif
    </div>

    <!-- Inline Schedule Hearing Form -->
    <div id="schedule-hearing-panel" class="hidden rounded-lg border border-blue-200 bg-blue-50/40 p-4">
        <h4 class="text-sm font-bold text-slate-900 mb-3 flex items-center gap-2">
            <i class="fa-solid fa-calendar-days text-blue-600"></i> New Hearing Schedule
        </h4>
        <form method="POST" action="{{ route('admin.cases.hearings.store', $case) }}" class="space-y-3 text-xs">
            @csrf
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3">
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Date & Time <span class="text-red-500">*</span></label>
                    <input type="datetime-local" name="scheduled_at" required class="w-full rounded border border-slate-300 p-2 text-xs bg-white">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Venue</label>
                    <input type="text" name="venue" value="Barangay Hall Mediation Room" class="w-full rounded border border-slate-300 p-2 text-xs bg-white">
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Presided By</label>
                    <select name="presided_by_personnel_id" class="w-full rounded border border-slate-300 p-2 text-xs bg-white">
                        <option value="">Select Official / Lupon Member...</option>
                        @foreach(\App\Models\Personnel::query()->where('is_active', true)->orderBy('full_name')->get() as $p)
                            <option value="{{ $p->id }}">{{ $p->full_name }} ({{ $p->position?->name ?? 'Lupon' }})</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block font-semibold text-slate-700 mb-1">Linked Summons (Optional)</label>
                    <select name="summons_id" class="w-full rounded border border-slate-300 p-2 text-xs bg-white">
                        <option value="">None</option>
                        @foreach($case->activeSummons as $as)
                            <option value="{{ $as->id }}">{{ $as->summons_number ?? "Summons #{$as->sequence_no}" }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="document.getElementById('schedule-hearing-panel').classList.add('hidden')" class="px-3 py-1.5 font-medium text-slate-600 hover:text-slate-800">Cancel</button>
                <button type="submit" class="rounded bg-blue-600 px-4 py-1.5 font-semibold text-white shadow-sm hover:bg-blue-700">Save Schedule</button>
            </div>
        </form>
    </div>

    @if($case->hearings->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-8 text-center w-full bg-white space-y-3">
            <div class="w-12 h-12 rounded-full bg-blue-50 text-blue-600 flex items-center justify-center mx-auto text-xl">
                <i class="fa-solid fa-calendar-xmark"></i>
            </div>
            <div>
                <p class="text-sm font-bold text-slate-800">No hearings scheduled yet</p>
                <p class="mt-1 text-xs text-slate-500 max-w-md mx-auto">Scheduled mediation and conciliation hearings will be listed here with attendance and proceedings notes.</p>
            </div>
            @if(isset($nextStep) && $nextStep['code'] === 'STEP_B_SCHEDULE_HEARING')
                <div class="pt-2">
                    <button type="button" onclick="openWorkflowModal('modal-schedule-first-hearing')" class="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors cursor-pointer">
                        <i class="fa-solid fa-calendar-plus"></i>
                        <span>Proceed to Next Step: Schedule First Hearing</span>
                    </button>
                </div>
            @endif
        </div>
    @else
        <div class="space-y-4">
            @foreach($case->hearings->sortByDesc('scheduled_at') as $h)
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between border-b border-slate-100 pb-3">
                        <div class="flex items-center gap-3">
                            <span class="inline-flex h-8 w-8 items-center justify-center rounded-full bg-blue-50 text-blue-700 font-bold text-xs">
                                <i class="fa-solid fa-gavel"></i>
                            </span>
                            <div>
                                <h4 class="text-sm font-bold text-slate-900">{{ $h->scheduled_at?->format('F d, Y · h:i A') }}</h4>
                                <p class="text-xs text-slate-500">Venue: {{ $h->venue ?? 'Barangay Hall' }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="rounded px-2 py-0.5 text-xs font-semibold {{ $h->status === 'held' ? 'bg-emerald-100 text-emerald-800' : ($h->status === 'cancelled' ? 'bg-rose-100 text-rose-800' : 'bg-blue-100 text-blue-800') }}">
                                {{ ucfirst($h->status) }}
                            </span>
                        </div>
                    </div>

                    <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-3 text-xs">
                        <div>
                            <span class="text-slate-500">Presided By</span>
                            <p class="font-semibold text-slate-800">{{ $h->presidedBy?->full_name ?? 'Unassigned' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Linked Summons</span>
                            <p class="font-semibold text-slate-800">{{ $h->summons?->summons_number ?? 'None' }}</p>
                        </div>
                        <div>
                            <span class="text-slate-500">Complainant Attendance</span>
                            <p class="font-semibold {{ $h->complainant_attendance === 'failed_to_appear' ? 'text-rose-600' : ($h->complainant_attendance === 'appeared' ? 'text-emerald-700' : 'text-slate-800') }}">
                                {{ ucfirst(str_replace('_', ' ', $h->complainant_attendance ?? 'pending')) }}
                            </p>
                        </div>
                        <div>
                            <span class="text-slate-500">Respondent Attendance</span>
                            <p class="font-semibold {{ $h->respondent_attendance === 'failed_to_appear' ? 'text-rose-600' : ($h->respondent_attendance === 'appeared' ? 'text-emerald-700' : 'text-slate-800') }}">
                                {{ ucfirst(str_replace('_', ' ', $h->respondent_attendance ?? 'pending')) }}
                            </p>
                        </div>
                    </div>

                    @if($h->respondent_barred_counterclaim)
                        <div class="mt-2.5 flex items-center gap-2 p-2 rounded bg-rose-50 border border-rose-200 text-xs text-rose-800 font-medium">
                            <i class="fa-solid fa-ban text-rose-600"></i>
                            <span>Respondent Counterclaim Barred (Sec. 412(b)): Respondent willfully failed to appear. Counterclaim barred from court assertion per RA 7160. Pangkat conciliation required.</span>
                        </div>
                    @endif

                    @if($h->outcome_note)
                        <div class="mt-3 text-xs bg-slate-50 p-2.5 rounded border border-slate-200 text-slate-700">
                            <span class="font-bold text-slate-900 block mb-0.5">Proceedings / Outcome:</span>
                            <p class="whitespace-pre-wrap">{{ $h->outcome_note }}</p>
                        </div>
                    @endif

                    @if(auth()->user()?->hasPermission('cases.summons'))
                        <div class="mt-4 pt-3 border-t border-slate-100 flex flex-wrap items-center gap-3">
                            <!-- Record Outcome Dropdown -->
                            <details class="relative">
                                <summary class="cursor-pointer inline-flex items-center gap-1 rounded bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-200">
                                    <i class="fa-solid fa-pen-nib"></i> {{ $h->outcome_note ? 'Update Outcome' : 'Record Outcome' }}
                                </summary>
                                <div class="absolute left-0 mt-2 z-20 w-80 rounded-lg border border-slate-200 bg-white p-3 shadow-lg">
                                    <form method="POST" action="{{ route('admin.cases.hearings.outcome', [$case, $h]) }}" class="space-y-2 text-xs">
                                        @csrf
                                        <div>
                                            <label class="block font-semibold text-slate-700">Status</label>
                                            <select name="status" class="w-full rounded border border-slate-300 p-1.5 text-xs">
                                                <option value="held" {{ $h->status === 'held' ? 'selected' : '' }}>Held</option>
                                                <option value="reset" {{ $h->status === 'reset' ? 'selected' : '' }}>Reset / Rescheduled</option>
                                                <option value="cancelled" {{ $h->status === 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                                            </select>
                                        </div>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div>
                                                <label class="block font-semibold text-slate-700">Complainant</label>
                                                <select name="complainant_attendance" class="w-full rounded border border-slate-300 p-1.5 text-xs">
                                                    <option value="pending" {{ ($h->complainant_attendance ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="appeared" {{ ($h->complainant_attendance ?? '') === 'appeared' ? 'selected' : '' }}>Appeared</option>
                                                    <option value="failed_to_appear" {{ ($h->complainant_attendance ?? '') === 'failed_to_appear' ? 'selected' : '' }}>Failed to Appear</option>
                                                    <option value="excused" {{ ($h->complainant_attendance ?? '') === 'excused' ? 'selected' : '' }}>Excused</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block font-semibold text-slate-700">Respondent</label>
                                                <select name="respondent_attendance" class="w-full rounded border border-slate-300 p-1.5 text-xs">
                                                    <option value="pending" {{ ($h->respondent_attendance ?? 'pending') === 'pending' ? 'selected' : '' }}>Pending</option>
                                                    <option value="appeared" {{ ($h->respondent_attendance ?? '') === 'appeared' ? 'selected' : '' }}>Appeared</option>
                                                    <option value="failed_to_appear" {{ ($h->respondent_attendance ?? '') === 'failed_to_appear' ? 'selected' : '' }}>Failed to Appear</option>
                                                    <option value="excused" {{ ($h->respondent_attendance ?? '') === 'excused' ? 'selected' : '' }}>Excused</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div>
                                            <label class="block font-semibold text-slate-700">Proceedings & Outcome Notes <span class="text-red-500">*</span></label>
                                            <textarea name="outcome_note" rows="3" required class="w-full rounded border border-slate-300 p-1.5 text-xs" placeholder="Summary of proceedings, agreement points, or next steps...">{{ $h->outcome_note }}</textarea>
                                        </div>
                                        <div class="flex justify-end pt-1">
                                            <button type="submit" class="rounded bg-blue-600 px-3 py-1 font-semibold text-white hover:bg-blue-700">Save Proceedings</button>
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
