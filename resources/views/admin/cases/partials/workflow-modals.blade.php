@php
    $respondents = $case->caseParties->where('role', 'respondent')->values();
    $activePersonnel = \App\Models\Personnel::query()->where('is_active', true)->with('position')->orderBy('full_name')->get();
    $kpService = app(\App\Services\KatarungangPambarangayService::class);
    $holidaysJson = json_encode($kpService->getHolidays());
    $clockInfo = $nextStep['mediation_clock'] ?? null;
    $endDateStr = $clockInfo['end_date'] ?? '';
    $nextAttemptNo = $nextStep['next_attempt_no'] ?? 1;
    $defaultDateTime = now()->addDays(2)->isWeekend()
        ? now()->next(\Carbon\Carbon::MONDAY)->setTime(9, 0)->format('Y-m-d\TH:i')
        : now()->addDays(2)->setTime(9, 0)->format('Y-m-d\TH:i');
@endphp

<!-- Modal: Mark Signed Complaint Received -->
<div id="modal-mark-signed-complaint" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-mark-signed-complaint')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-file-signature text-blue-600"></i>
                    <span>Mark Signed Complaint Received</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-mark-signed-complaint')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.mark-signed-complaint', $case) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-sans">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Date Signed in Person <span class="text-red-600">*</span></label>
                    <input type="date" name="signed_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Received & Administered By <span class="text-red-600">*</span></label>
                    <select name="received_by" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="Punong Barangay">Punong Barangay (Lupon Chairman)</option>
                        <option value="Barangay Secretary">Barangay Secretary</option>
                        <option value="Lupon Member">Lupon Member / Conciliator</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Upload Signed Paper Scan (Optional)</label>
                    <input type="file" name="scan_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    <p class="text-[11px] text-slate-500 mt-1">Official physical paper copy remains the primary legal record.</p>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Remarks / In-person Notes</label>
                    <textarea name="notes" rows="2" placeholder="e.g. Complainant appeared with valid ID..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-mark-signed-complaint')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Confirm Receipt</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Unified Modal: Schedule Hearing & Issue Summons (First, Next, or Reschedule) -->
<div id="modal-schedule-and-issue-summons" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-schedule-and-issue-summons')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-plus text-blue-600"></i>
                    <span id="schedule_modal_title">Schedule Hearing & Issue Summons</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-schedule-and-issue-summons')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.schedule-and-issue-summons', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <input type="hidden" name="reschedule_hearing_id" id="schedule_reschedule_hearing_id" value="{{ old('reschedule_hearing_id') }}">

                @if($errors->any())
                    <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-900 text-xs">
                        <ul class="list-disc pl-4 space-y-0.5 text-red-800">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Hearing Date & Time <span class="text-red-600">*</span></label>
                    <input type="datetime-local"
                           id="schedule_hearing_datetime"
                           name="scheduled_at"
                           required
                           value="{{ old('scheduled_at', $defaultDateTime) }}"
                           onchange="checkScheduleWarnings(this.value)"
                           class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border @error('scheduled_at') border-red-500 @enderror">
                    <p class="text-[11px] text-slate-500 mt-1">Default time is 9:00 AM. Recommended: Mon–Fri, 8:00 AM – 5:00 PM.</p>
                </div>

                <!-- Non-blocking Warnings -->
                <div id="schedule_warnings_box" class="hidden p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-1">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                        <div id="schedule_warnings_list" class="space-y-0.5"></div>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Venue <span class="text-red-600">*</span></label>
                    <input type="text" name="venue" value="{{ old('venue', 'Barangay Calarian Hall (Lupon Mediation Room)') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Presiding Official</label>
                    <select name="presided_by" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="">Punong Barangay (Default)</option>
                        @foreach($activePersonnel as $personnel)
                            <option value="{{ $personnel->id }}" @selected(old('presided_by') == $personnel->id)>
                                {{ $personnel->full_name }} ({{ $personnel->position?->name ?? 'Lupon' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                @if($respondents->count() > 1)
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Respondent <span class="text-red-600">*</span></label>
                        <select name="respondent_case_party_id" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                            @foreach($respondents as $resp)
                                <option value="{{ $resp->id }}" @selected(old('respondent_case_party_id') == $resp->id)>
                                    {{ $resp->party?->full_name ?? "Respondent #{$resp->id}" }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @elseif($respondents->count() === 1)
                    <input type="hidden" name="respondent_case_party_id" value="{{ $respondents->first()->id }}">
                    <div class="text-[11px] text-slate-600 bg-slate-50 px-3 py-2 rounded-lg border border-slate-200">
                        Respondent: <strong>{{ $respondents->first()->party?->full_name ?? 'Respondent' }}</strong>
                    </div>
                @endif

                <div id="schedule_reason_container" class="hidden space-y-2">
                    <label class="block font-bold text-slate-700">Reason for Rescheduling / Next Hearing <span class="text-red-600">*</span></label>
                    <select name="reason_preset" id="schedule_reason_preset" onchange="handleScheduleReasonPreset(this.value)" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="">-- Choose Official Reason --</option>
                        <option value="Respondent did not appear at previous hearing">Respondent did not appear at previous hearing</option>
                        <option value="Previous summons could not be served">Previous summons could not be served</option>
                        <option value="Respondent requested postponement with justifiable cause">Respondent requested postponement with justifiable cause</option>
                        <option value="Complainant requested postponement with justifiable cause">Complainant requested postponement with justifiable cause</option>
                        <option value="Mutual agreement of both parties to reset date">Mutual agreement of both parties to reset date</option>
                        <option value="Presiding official schedule conflict">Presiding official schedule conflict</option>
                        <option value="other">Other (specify below)...</option>
                    </select>
                    <textarea name="reason" id="schedule_reason_detail" rows="2" placeholder="Enter or elaborate on the reason..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border font-sans">{{ old('reason') }}</textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Notes / Instructions (Optional)</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes for summons delivery..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">{{ old('notes') }}</textarea>
                </div>

                <!-- Notifications Checkbox -->
                <div class="space-y-1 bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" name="notify_channels" value="1" checked class="mt-0.5 rounded text-blue-600">
                        <span class="text-slate-800 font-medium">Also notify by email/SMS where contact details exist</span>
                    </label>
                    <p class="text-[11px] text-slate-500 pl-5">No SMS configured. Email only.</p>
                </div>

                <div id="schedule_helper_text" class="text-[11px] text-slate-600 font-medium pt-1">
                    Summons #{{ $nextAttemptNo }} will be generated from this date after you save.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-schedule-and-issue-summons')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save & Generate Summons</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Record Summons Service -->
<div id="modal-record-summons-service" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-record-summons-service')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-clipboard-check text-blue-600"></i>
                    <span>Record Service (Officer's Return)</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-record-summons-service')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.record-summons-service', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <input type="hidden" name="summons_id" id="service_modal_summons_id" value="">

                <div class="p-3 rounded-lg bg-blue-50/70 border border-blue-200 text-blue-900 text-xs">
                    Proof that the respondent received the summons. Without it, a no-show cannot be counted.
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Date Served <span class="text-red-600">*</span></label>
                        <input type="date" name="served_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Method <span class="text-red-600">*</span></label>
                        <select name="service_method" id="service_method_select" onchange="toggleSubstitutedRequirements(this.value)" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                            <option value="personal">Personal</option>
                            <option value="substituted">Substituted</option>
                            <option value="refused">Refused to receive</option>
                            <option value="not_served">Could not be served</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Served By (Staff / Tanod) <span class="text-red-600">*</span></label>
                    <select name="served_by" id="served_by_select" onchange="toggleCustomServedBy(this.value)" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="">-- Choose Staff / Tanod --</option>
                        @if($activePersonnel->isNotEmpty())
                            <optgroup label="Barangay Personnel / Staff">
                                @foreach($activePersonnel as $person)
                                    <option value="{{ $person->full_name }} ({{ $person->position?->name ?? 'Staff' }})">
                                        {{ $person->full_name }} — {{ $person->position?->name ?? 'Barangay Staff' }}
                                    </option>
                                @endforeach
                            </optgroup>
                        @endif
                        <optgroup label="Standard Designations">
                            <option value="Barangay Tanod on Duty">Barangay Tanod on Duty</option>
                            <option value="Chief Tanod (Executive Officer)">Chief Tanod (Executive Officer)</option>
                            <option value="Duty Process Server">Duty Process Server</option>
                            <option value="Barangay Secretary">Barangay Secretary</option>
                        </optgroup>
                        <option value="other">Other / Custom Name...</option>
                    </select>
                    <div id="custom_served_by_box" class="hidden mt-2">
                        <input type="text" name="custom_served_by" id="custom_served_by_input" placeholder="Enter serving officer name..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Received By (Name) <span id="recipient_name_req" class="hidden text-red-600">*</span>
                        </label>
                        <input type="text" name="recipient_name" id="service_recipient_name" placeholder="Name of recipient..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">
                            Relationship <span id="recipient_rel_req" class="hidden text-red-600">*</span>
                        </label>
                        <input type="text" name="recipient_relation" id="service_recipient_relation" placeholder="e.g. Self, Spouse, Sibling" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="2" placeholder="Describe delivery circumstances..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-record-summons-service')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Service</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Skip Service (Override with Mandatory Reason) -->
<div id="modal-skip-service" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-skip-service')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-forward text-amber-600"></i>
                    <span>Skip Service Step (Audit Override)</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-skip-service')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.skip-summons-service', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <input type="hidden" name="summons_id" id="skip_service_summons_id" value="">
                <p class="text-xs text-slate-600">
                    Skipping service unlocks the hearing outcome step and writes a permanent audit log entry.
                </p>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Reason for Skipping Service <span class="text-red-600">*</span></label>
                    <textarea name="reason" rows="3" required minlength="5" placeholder="Explain why service recording is being skipped..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-skip-service')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-amber-600 rounded-lg hover:bg-amber-700">Confirm Override</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Record Hearing Outcome -->
<div id="modal-record-hearing-outcome" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-record-hearing-outcome')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-gavel text-blue-600"></i>
                    <span>Record Hearing Outcome</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-record-hearing-outcome')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.record-hearing-outcome', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <input type="hidden" name="hearing_id" id="outcome_modal_hearing_id" value="">

                <div class="bg-slate-50 p-3 rounded-lg border border-slate-200 space-y-2">
                    <span class="block font-bold text-slate-700">Attendance</span>
                    <div class="flex items-center gap-6">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="complainant_present" value="1" checked class="rounded text-blue-600">
                            <span class="font-medium text-slate-800">Complainant present</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" name="respondent_present" value="1" checked class="rounded text-blue-600">
                            <span class="font-medium text-slate-800">Respondent present</span>
                        </label>
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Outcome <span class="text-red-600">*</span></label>
                    <select name="outcome" id="hearing_outcome_select" onchange="toggleOutcomeFields(this.value)" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="continued">Both appeared — mediation held (continue)</option>
                        <option value="settled">Settled (parties agreed on terms)</option>
                        <option value="respondent_absent">Respondent absent (summons served)</option>
                        <option value="complainant_absent">Complainant absent</option>
                        <option value="postponed">Postponed / rescheduled</option>
                        <option value="failed">Mediation failed (early endorsement, reason required)</option>
                    </select>
                </div>

                <div id="early_endorse_reason_box" class="hidden">
                    <label class="block font-bold text-slate-700 mb-1">Reason for Early Endorsement <span class="text-red-600">*</span></label>
                    <textarea name="early_endorse_reason" id="early_endorse_reason_input" rows="2" placeholder="Explain why mediation failed before 15 working days..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Notes</label>
                    <textarea name="notes" rows="3" placeholder="Summary of what transpired during the mediation session..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-record-hearing-outcome')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Outcome</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Mark Signed Settlement Received -->
<div id="modal-mark-signed-settlement" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-mark-signed-settlement')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-handshake text-emerald-600"></i>
                    <span>Mark Signed Settlement Received (KP Form 16)</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-mark-signed-settlement')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.mark-signed-settlement', $case) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-sans">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Date Signed by Both Parties <span class="text-red-600">*</span></label>
                    <input type="date" name="settled_on" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Agreed Settlement Terms <span class="text-red-600">*</span></label>
                    <textarea name="terms" rows="4" required placeholder="State exact settlement terms agreed by complainant and respondent..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border font-sans">{{ $case->settlement_terms }}</textarea>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Upload Signed Settlement Scan (KP Form 16)</label>
                    <input type="file" name="scan_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-mark-signed-settlement')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-emerald-600 rounded-lg hover:bg-emerald-700">Confirm Signed Settlement</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Record Repudiation -->
<div id="modal-record-repudiation" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-record-repudiation')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-shield-halved text-red-600"></i>
                    <span>Record Settlement Repudiation (Sec. 418)</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-record-repudiation')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.record-repudiation', $case) }}" method="POST" enctype="multipart/form-data" class="space-y-4 text-xs font-sans">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Repudiation Date <span class="text-red-600">*</span></label>
                        <input type="date" name="repudiation_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Ground for Repudiation <span class="text-red-600">*</span></label>
                        <select name="ground" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                            <option value="fraud">Fraud</option>
                            <option value="violence">Violence</option>
                            <option value="intimidation">Intimidation</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Repudiated By <span class="text-red-600">*</span></label>
                    <input type="text" name="repudiated_by" placeholder="Name of party..." required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Sworn Statement Scan (Optional)</label>
                    <input type="file" name="sworn_statement_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Explanation / Sworn Summary <span class="text-red-600">*</span></label>
                    <textarea name="explanation" rows="3" required placeholder="Detail the sworn allegations..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-record-repudiation')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700">Record Repudiation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Mark Court Transmitted -->
<div id="modal-mark-court-transmitted" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-mark-court-transmitted')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-landmark text-blue-600"></i>
                    <span>Record Transmittal to Court (Sec. 419)</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-mark-court-transmitted')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.mark-court-transmitted', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Date Transmitted <span class="text-red-600">*</span></label>
                    <input type="date" name="transmitted_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Receiving Court / Branch</label>
                    <input type="text" name="court_branch" value="Municipal Trial Court in Cities (MTCC) - Zamboanga City" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-mark-court-transmitted')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Confirm Transmittal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Issue Certificate to File Action (Form 20) -->
<div id="modal-issue-cfa" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-issue-cfa')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-certificate text-amber-600"></i>
                    <span>Issue Certificate to File Action (KP Form 20)</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-issue-cfa')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.issue-cfa', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Date Issued <span class="text-red-600">*</span></label>
                    <input type="date" name="issued_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Reason for Certification <span class="text-red-600">*</span></label>
                    <textarea name="reason" rows="3" required minlength="5" placeholder="State the official reason for issuing KP Form 20..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-issue-cfa')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-amber-600 rounded-lg hover:bg-amber-700">Issue CFA</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const KP_HOLIDAYS = {!! $holidaysJson !!};
    const KP_END_DATE = @json($endDateStr);

    function openWorkflowModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) el.classList.remove('hidden');
    }

    function closeWorkflowModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) el.classList.add('hidden');
    }

    function openScheduleModal(opts = {}) {
        const attemptNo = opts.attemptNo || 1;
        const rescheduleId = opts.rescheduleHearingId || null;

        const titleEl = document.getElementById('schedule_modal_title');
        const helperEl = document.getElementById('schedule_helper_text');
        const rescheduleInput = document.getElementById('schedule_reschedule_hearing_id');
        const reasonContainer = document.getElementById('schedule_reason_container');
        const reasonDetail = document.getElementById('schedule_reason_detail');

        if (rescheduleInput) rescheduleInput.value = rescheduleId || '';
        if (titleEl) {
            titleEl.textContent = rescheduleId
                ? `Schedule Next Hearing (Summons #${attemptNo})`
                : 'Schedule Hearing & Issue Summons';
        }
        if (helperEl) {
            helperEl.textContent = `Summons #${attemptNo} will be generated from this date after you save.`;
        }
        if (reasonContainer) {
            if (rescheduleId) {
                reasonContainer.classList.remove('hidden');
                if (reasonDetail) reasonDetail.required = true;
            } else {
                reasonContainer.classList.add('hidden');
                if (reasonDetail) reasonDetail.required = false;
            }
        }

        const dtInput = document.getElementById('schedule_hearing_datetime');
        if (dtInput) checkScheduleWarnings(dtInput.value);

        openWorkflowModal('modal-schedule-and-issue-summons');
    }

    function openRecordServiceModal(summonsId) {
        const input = document.getElementById('service_modal_summons_id');
        if (input) input.value = summonsId || '';
        openWorkflowModal('modal-record-summons-service');
    }

    function openSkipServiceModal(summonsId) {
        const input = document.getElementById('skip_service_summons_id');
        if (input) input.value = summonsId || '';
        openWorkflowModal('modal-skip-service');
    }

    function openRecordOutcomeModal(hearingId) {
        const input = document.getElementById('outcome_modal_hearing_id');
        if (input) input.value = hearingId || '';
        openWorkflowModal('modal-record-hearing-outcome');
    }

    function handleScheduleReasonPreset(val) {
        const detail = document.getElementById('schedule_reason_detail');
        if (!detail) return;
        if (val && val !== 'other') {
            detail.value = val;
        } else if (val === 'other') {
            detail.value = '';
            detail.focus();
        }
    }

    function toggleCustomServedBy(val) {
        const box = document.getElementById('custom_served_by_box');
        const input = document.getElementById('custom_served_by_input');
        if (box) {
            if (val === 'other') {
                box.classList.remove('hidden');
                if (input) {
                    input.required = true;
                    input.focus();
                }
            } else {
                box.classList.add('hidden');
                if (input) {
                    input.required = false;
                    input.value = '';
                }
            }
        }
    }

    function toggleSubstitutedRequirements(method) {
        const nameInput = document.getElementById('service_recipient_name');
        const relInput = document.getElementById('service_recipient_relation');
        const nameReq = document.getElementById('recipient_name_req');
        const relReq = document.getElementById('recipient_rel_req');
        const isSub = (method === 'substituted');
        if (nameInput) nameInput.required = isSub;
        if (relInput) relInput.required = isSub;
        if (nameReq) nameReq.classList.toggle('hidden', !isSub);
        if (relReq) relReq.classList.toggle('hidden', !isSub);
    }

    function toggleOutcomeFields(val) {
        const box = document.getElementById('early_endorse_reason_box');
        const input = document.getElementById('early_endorse_reason_input');
        const isFailed = (val === 'failed');
        if (box) box.classList.toggle('hidden', !isFailed);
        if (input) input.required = isFailed;
    }

    function checkScheduleWarnings(val) {
        const box = document.getElementById('schedule_warnings_box');
        const list = document.getElementById('schedule_warnings_list');
        if (!box || !list || !val) return;

        const warnings = [];
        const d = new Date(val);
        const dateStr = val.slice(0, 10);
        const day = d.getDay();
        const h = d.getHours();

        if (day === 0 || day === 6) {
            warnings.push('Selected date falls on a weekend.');
        }
        if (Array.isArray(KP_HOLIDAYS) && KP_HOLIDAYS.includes(dateStr)) {
            warnings.push('Selected date is a configured holiday.');
        }
        if (h < 8 || h >= 17) {
            warnings.push('Selected time is outside 8:00 AM – 5:00 PM.');
        }
        if (KP_END_DATE && dateStr > KP_END_DATE) {
            warnings.push('This date is beyond the 15-working-day mediation period.');
        }

        if (warnings.length > 0) {
            list.innerHTML = warnings.map(w => `<p>${w}</p>`).join('');
            box.classList.remove('hidden');
        } else {
            list.innerHTML = '';
            box.classList.add('hidden');
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('schedule_hearing_datetime');
        if (input && input.value) {
            checkScheduleWarnings(input.value);
        }
    });
</script>

@if($errors->has('scheduled_at') || $errors->has('venue') || $errors->has('reason'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            openScheduleModal({
                attemptNo: {{ $nextAttemptNo }},
                rescheduleHearingId: @json(old('reschedule_hearing_id'))
            });
        });
    </script>
@elseif($errors->has('recipient_name') || $errors->has('served_by') || $errors->has('served_at') || $errors->has('service_method'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            openRecordServiceModal(@json(old('summons_id')));
        });
    </script>
@endif
