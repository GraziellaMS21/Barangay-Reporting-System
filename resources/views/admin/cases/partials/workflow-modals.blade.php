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

<!-- Merged Step 2 Modal: Schedule Hearing & Issue Summons -->
<div id="modal-schedule-and-issue-summons" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-schedule-and-issue-summons')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-calendar-plus text-blue-600"></i>
                    <span>Schedule Hearing & Issue Summons</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-schedule-and-issue-summons')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.schedule-and-issue-summons', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                @if(session('warning_message'))
                    <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs flex items-start gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                        <span>{{ session('warning_message') }}</span>
                    </div>
                @endif
                @if($errors->any())
                    <div class="p-3 rounded-lg bg-red-50 border border-red-200 text-red-900 text-xs">
                        <div class="font-bold mb-1 flex items-center gap-1.5 text-red-700">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span>Please resolve the following:</span>
                        </div>
                        <ul class="list-disc pl-4 space-y-0.5 text-red-800">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                <div class="p-3 rounded-lg bg-blue-50/70 border border-blue-200 text-blue-900 text-xs leading-relaxed">
                    <i class="fa-solid fa-circle-info mr-1 text-blue-600"></i>
                    <strong>Statutory Note:</strong> The 15-day mediation period starts at the first hearing when parties appear (Sec. 410b RA 7160).
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Hearing Date & Time <span class="text-red-600">*</span></label>
                    <input type="datetime-local" 
                           id="schedule_hearing_datetime" 
                           name="scheduled_at" 
                           required 
                           value="{{ old('scheduled_at', now()->addDays(2)->isWeekend() ? now()->next(\Carbon\Carbon::MONDAY)->setTime(9, 0)->format('Y-m-d\TH:i') : now()->addDays(2)->setTime(9, 0)->format('Y-m-d\TH:i')) }}" 
                           onchange="checkScheduleOffHours(this.value)" 
                           class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border @error('scheduled_at') border-red-500 @enderror">
                    @error('scheduled_at')
                        <p class="text-[11px] text-red-600 font-semibold mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-[11px] text-slate-500 mt-1">Recommended: Mon-Fri between 8:00 AM and 5:00 PM.</p>
                </div>

                <div id="schedule_off_hours_alert" class="hidden p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs space-y-2">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5"></i>
                        <span><strong>Notice:</strong> The selected time falls outside regular weekday office hours (Mon–Fri 8:00 AM – 5:00 PM).</span>
                    </div>
                    <label class="flex items-center gap-2 cursor-pointer font-semibold text-slate-800">
                        <input type="checkbox" name="warning_acknowledged" value="1" id="schedule_warning_acknowledged" checked class="rounded text-blue-600">
                        <span>Confirm and proceed with this schedule</span>
                    </label>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Venue <span class="text-red-600">*</span></label>
                    <input type="text" name="venue" value="Barangay Calarian Hall (Lupon Mediation Room)" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Presiding Officer</label>
                    <select name="presided_by" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="">-- Punong Barangay (Default) --</option>
                        @foreach(\App\Models\Personnel::query()->where('is_active', true)->orderBy('full_name')->get() as $personnel)
                            <option value="{{ $personnel->id }}">{{ $personnel->full_name }} ({{ $personnel->position?->name ?? 'Lupon' }})</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Notes / Instructions for Process Server</label>
                    <textarea name="notes" rows="2" placeholder="Optional notes for summons delivery..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>

                <!-- Notifications Checkbox -->
                <div class="space-y-1.5 bg-slate-50 p-3 rounded-lg border border-slate-200">
                    <label class="flex items-start gap-2 cursor-pointer">
                        <input type="checkbox" name="notify_channels" value="1" checked class="mt-0.5 rounded text-blue-600">
                        <span class="text-slate-800 font-medium">Also notify by email/SMS where contact details exist</span>
                    </label>
                    <p class="text-[11px] text-slate-500 pl-5">No SMS configured. Email only.</p>
                </div>

                <div class="text-[11px] text-slate-500 italic pt-1">
                    Summons will be generated after you save.
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-schedule-and-issue-summons')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save & Generate Summons</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Reschedule Hearing -->
<div id="modal-reschedule-hearing" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-reschedule-hearing')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-file-circle-plus text-blue-600"></i>
                    <span>Issue Next Summons & Reschedule Hearing</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-reschedule-hearing')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.reschedule-hearing', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 text-amber-900 text-xs">
                    <i class="fa-solid fa-triangle-exclamation mr-1 text-amber-600"></i>
                    This generates the <strong>next sequential summons (KP Form 9)</strong> with the new hearing appearance schedule and marks the previous summons as <strong>Superseded</strong>. Re-service by barangay tanods will be required.
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">New Hearing Date & Time <span class="text-red-600">*</span></label>
                    <input type="datetime-local" 
                           name="scheduled_at" 
                           required 
                           value="{{ old('scheduled_at', now()->addDays(2)->isWeekend() ? now()->next(\Carbon\Carbon::MONDAY)->setTime(9, 0)->format('Y-m-d\TH:i') : now()->addDays(2)->setTime(9, 0)->format('Y-m-d\TH:i')) }}" 
                           class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Venue <span class="text-red-600">*</span></label>
                    <input type="text" name="venue" value="Barangay Calarian Hall (Lupon Mediation Room)" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Reason for Rescheduling <span class="text-red-600">*</span></label>
                    <select name="reason_preset" id="reschedule_reason_preset" onchange="handleRescheduleReasonPreset(this.value)" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border mb-2">
                        <option value="">-- Choose Official Reason for Rescheduling --</option>
                        <option value="Respondent requested postponement with justifiable cause (medical/work emergency)">Respondent requested postponement with justifiable cause (medical/work emergency)</option>
                        <option value="Complainant requested postponement with justifiable cause">Complainant requested postponement with justifiable cause</option>
                        <option value="Mutual agreement of both parties to reset appearance date">Mutual agreement of both parties to reset appearance date</option>
                        <option value="Summons unserved / respondent could not be located in time">Summons unserved / respondent could not be located in time</option>
                        <option value="Punong Barangay / Conciliator official schedule conflict">Punong Barangay / Conciliator official schedule conflict</option>
                        <option value="Barangay emergency / official community event / work suspension">Barangay emergency / official community event / work suspension</option>
                        <option value="Parties requested additional time to discuss settlement terms">Parties requested additional time to discuss settlement terms</option>
                        <option value="other">Other Justifiable Reason (Specify Details Below)...</option>
                    </select>
                    <textarea name="reason" id="reschedule_reason_detail" rows="2" placeholder="State or elaborate on the official reason..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border font-sans"></textarea>
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-reschedule-hearing')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save & Generate Next Summons</button>
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
                    <span>Record Summons Service (Officer's Return)</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-record-summons-service')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.record-summons-service', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Service Date <span class="text-red-600">*</span></label>
                        <input type="date" name="served_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Service Method <span class="text-red-600">*</span></label>
                        <select name="service_method" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                            <option value="personal">Personal Service (Handed directly)</option>
                            <option value="substituted">Substituted Service (Household member)</option>
                            <option value="refused">Refused to Accept (Tendered copy)</option>
                            <option value="failed">Could Not Be Served (Unreachable/Moved)</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Served By (Tanod / Officer) <span class="text-red-600">*</span></label>
                    <select name="served_by" id="served_by_select" onchange="toggleCustomServedBy(this.value)" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="">-- Choose Barangay Tanod / Serving Officer --</option>
                        @php
                            $activePersonnel = \App\Models\Personnel::query()->where('is_active', true)->with('position')->orderBy('full_name')->get();
                        @endphp
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
                            <option value="Barangay Peacekeeping Action Team (BPAT)">Barangay Peacekeeping Action Team (BPAT)</option>
                            <option value="Duty Lupon Member">Duty Lupon Member</option>
                            <option value="Barangay Secretary">Barangay Secretary</option>
                        </optgroup>
                        <option value="other">Other / Custom Name...</option>
                    </select>
                    <div id="custom_served_by_box" class="hidden mt-2">
                        <input type="text" name="custom_served_by" id="custom_served_by_input" placeholder="Enter custom name of serving tanod or officer..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Received By (Name)</label>
                        <input type="text" name="recipient_name" placeholder="Name of recipient..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Relationship to Respondent</label>
                        <input type="text" name="recipient_relation" placeholder="e.g. Self, Spouse, Sibling" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Officer's Return Notes</label>
                    <textarea name="notes" rows="2" placeholder="Describe circumstances of delivery..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-record-summons-service')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Record Service</button>
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
                    <span>Record Mediation Hearing Outcome</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-record-hearing-outcome')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.record-hearing-outcome', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <div class="p-3 bg-blue-50/70 border border-blue-200 text-blue-900 text-xs leading-relaxed">
                    <strong>Sec. 410(b) Clock Trigger:</strong> The statutory 15-day mediation clock begins from the date of this actual first meeting with parties present.
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Date Hearing Held <span class="text-red-600">*</span></label>
                        <input type="date" name="hearing_held_on" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Appearance & Result <span class="text-red-600">*</span></label>
                        <select name="outcome" id="hearing_outcome_select" onchange="toggleRescheduleFields(this.value)" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                            <option value="settled">Settled (Parties agreed on terms)</option>
                            <option value="rescheduled">Reschedule Session (Subsequent mediation)</option>
                            <option value="respondent_absent">Respondent Absent without Cause (No-show)</option>
                            <option value="complainant_absent">Complainant Absent (May dismiss complaint)</option>
                            <option value="failed">Mediation Failed (Endorse to Pangkat / CFA)</option>
                        </select>
                    </div>
                </div>

                <div id="reschedule_box" class="hidden space-y-3 p-3 bg-slate-50 rounded-lg border border-slate-200">
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Next Session Date & Time</label>
                        <input type="datetime-local" name="reschedule_date" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-700 mb-1">Venue</label>
                        <input type="text" name="reschedule_venue" value="Barangay Calarian Hall (Lupon Mediation Room)" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                    </div>
                </div>

                <div>
                    <label class="block font-bold text-slate-700 mb-1">Minutes / Summary of Discussions</label>
                    <textarea name="notes" rows="3" placeholder="Summary of what transpired during the mediation session..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-record-hearing-outcome')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Save Hearing Outcome</button>
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
                <div class="p-3 bg-red-50 border border-red-200 text-red-900 rounded-lg text-xs leading-relaxed">
                    Under Sec. 418 of RA 7160, repudiation requires a <strong>sworn statement</strong> filed within 10 days on statutory grounds (Fraud, Violence, or Intimidation).
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Repudiation Filing Date <span class="text-red-600">*</span></label>
                    <input type="date" name="repudiation_date" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Statutory Ground <span class="text-red-600">*</span></label>
                    <select name="ground" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="fraud">Fraud (Misrepresentation of facts)</option>
                        <option value="violence">Violence (Force or coercion)</option>
                        <option value="intimidation">Intimidation (Threats of injury)</option>
                    </select>
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Repudiated By (Party Name) <span class="text-red-600">*</span></label>
                    <input type="text" name="repudiated_by" placeholder="Name of party repudiating..." required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Upload Sworn Statement (Affidavit of Repudiation)</label>
                    <input type="file" name="sworn_statement_file" accept=".pdf,.jpg,.jpeg,.png" class="w-full text-xs text-slate-500 file:mr-2 file:py-1 file:px-2.5 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-red-50 file:text-red-700 hover:file:bg-red-100">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Explanation & Circumstances <span class="text-red-600">*</span></label>
                    <textarea name="explanation" rows="3" required placeholder="Detail the grounds supporting this repudiation..." class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border font-sans"></textarea>
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-record-repudiation')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-red-600 rounded-lg hover:bg-red-700">Record Repudiation</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Mark Transmitted to Court -->
<div id="modal-mark-court-transmitted" class="hidden fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div onclick="closeWorkflowModal('modal-mark-court-transmitted')" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full border border-slate-200 p-6 space-y-4 relative z-10">
            <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                    <i class="fa-solid fa-landmark text-blue-600"></i>
                    <span>Mark Transmitted to Court (Sec. 419)</span>
                </h3>
                <button type="button" onclick="closeWorkflowModal('modal-mark-court-transmitted')" class="text-slate-400 hover:text-slate-600"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <form action="{{ route('admin.cases.workflow.mark-court-transmitted', $case) }}" method="POST" class="space-y-4 text-xs font-sans">
                @csrf
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Transmittal Date <span class="text-red-600">*</span></label>
                    <input type="date" name="transmitted_at" value="{{ date('Y-m-d') }}" max="{{ date('Y-m-d') }}" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div>
                    <label class="block font-bold text-slate-700 mb-1">Court Branch / Station</label>
                    <input type="text" name="court_branch" value="Municipal Trial Court in Cities (MTCC) - Branch 1, Zamboanga City" class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" onclick="closeWorkflowModal('modal-mark-court-transmitted')" class="px-3.5 py-2 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-lg hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 text-xs font-bold text-white bg-blue-600 rounded-lg hover:bg-blue-700">Confirm Transmittal</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Issue CFA (KP Form 20) -->
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
                    <select name="reason" required class="w-full text-xs rounded-lg border-slate-300 p-2.5 bg-white border">
                        <option value="Failed to reach amicable settlement despite personal confrontation">Failed to reach amicable settlement despite personal confrontation</option>
                        <option value="Respondent willfully refused to appear despite repeated summons">Respondent willfully refused to appear despite repeated summons</option>
                        <option value="Settlement repudiated within 10-day statutory period">Settlement repudiated within 10-day statutory period</option>
                    </select>
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
    function openWorkflowModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) el.classList.remove('hidden');
    }

    function closeWorkflowModal(modalId) {
        const el = document.getElementById(modalId);
        if (el) el.classList.add('hidden');
    }

    function toggleRescheduleFields(val) {
        const box = document.getElementById('reschedule_box');
        if (box) {
            if (val === 'rescheduled') {
                box.classList.remove('hidden');
            } else {
                box.classList.add('hidden');
            }
        }
    }

    function handleRescheduleReasonPreset(val) {
        const detail = document.getElementById('reschedule_reason_detail');
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
                if (input) input.focus();
            } else {
                box.classList.add('hidden');
                if (input) input.value = '';
            }
        }
    }

    function checkScheduleOffHours(val) {
        if (!val) return;
        const d = new Date(val);
        const day = d.getDay(); // 0 is Sun, 6 is Sat
        const h = d.getHours();
        const isWeekend = (day === 0 || day === 6);
        const isOffHours = (h < 8 || h >= 17);
        const box = document.getElementById('schedule_off_hours_alert');
        const chk = document.getElementById('schedule_warning_acknowledged');
        if (box) {
            if (isWeekend || isOffHours) {
                box.classList.remove('hidden');
                if (chk) chk.checked = true;
            } else {
                box.classList.add('hidden');
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function() {
        const input = document.getElementById('schedule_hearing_datetime');
        if (input && input.value) {
            checkScheduleOffHours(input.value);
        }
    });
</script>

@if(session('office_hours_warning') || $errors->has('scheduled_at') || $errors->has('venue'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            openWorkflowModal('modal-schedule-and-issue-summons');
        });
    </script>
@endif
