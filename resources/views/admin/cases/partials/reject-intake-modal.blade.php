<div id="reject-intake-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900 flex items-center gap-2">
                <i class="fa-solid fa-circle-exclamation text-red-600"></i>
                <span>Redirect / Reject Complaint Intake</span>
            </h3>
            <button type="button" onclick="closeRejectModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="reject-intake-form" method="POST" action="">
            @csrf
            <div class="space-y-4">
                <p class="text-xs text-slate-600">
                    Rejecting intake for <strong id="reject-complainant-name"></strong> vs. <strong id="reject-respondent-name"></strong>. This action will archive the intake with an audit trail without deleting the citizen record.
                </p>

                <div>
                    <label for="outcome_reason" class="block text-xs font-semibold text-slate-700 mb-1">
                        Reason for Rejection / Redirection <span class="text-red-500">*</span>
                    </label>
                    <select name="outcome_reason" id="outcome_reason" required class="w-full text-xs border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">-- Choose reason --</option>
                        <option value="out_of_jurisdiction">Outside Territorial Jurisdiction (Parties not in Calarian)</option>
                        <option value="not_barangay_matter">Not Covered by Katarungang Pambarangay Law</option>
                        <option value="confidential_vawc">Confidential Matter (Redirected to VAW Desk / PNP)</option>
                        <option value="insufficient_info">Insufficient or Incomplete Incident Information</option>
                        <option value="duplicate_intake">Duplicate of Existing Filed Case / Blotter</option>
                        <option value="settled_informally">Parties Resolved Dispute Informally</option>
                        <option value="other">Other Official Reason</option>
                    </select>
                </div>

                <div>
                    <label for="explanation_note" class="block text-xs font-semibold text-slate-700 mb-1">
                        Written Explanation & Staff Notes <span class="text-red-500">*</span>
                    </label>
                    <textarea name="explanation_note" id="explanation_note" rows="3" required minlength="5" placeholder="Detail the official reason why this complaint request cannot be docketed..." class="w-full text-xs border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"></textarea>
                </div>
            </div>

            <div class="mt-5 flex items-center justify-end gap-2 pt-3 border-t border-slate-100">
                <button type="button" onclick="closeRejectModal()" class="px-3 py-1.5 text-xs font-semibold text-slate-600 hover:text-slate-800">
                    Cancel
                </button>
                <button type="submit" class="px-4 py-1.5 text-xs font-semibold text-white bg-red-600 hover:bg-red-700 rounded-md shadow-sm transition-colors">
                    Confirm Rejection
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRejectModal(intakeId, complainant, respondent) {
        document.getElementById('reject-complainant-name').innerText = complainant;
        document.getElementById('reject-respondent-name').innerText = respondent;
        document.getElementById('reject-intake-form').action = `/admin/cases/intakes/${intakeId}/reject`;
        document.getElementById('reject-intake-modal').classList.remove('hidden');
    }

    function closeRejectModal() {
        document.getElementById('reject-intake-modal').classList.add('hidden');
    }
</script>
