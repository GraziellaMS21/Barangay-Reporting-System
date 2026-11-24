<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ComplaintIntake;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CaseIntakeController extends Controller
{
    public function __construct(
        protected AuditLogger $audit
    ) {}

    /**
     * Display full details of a pending citizen complaint intake.
     */
    public function show(ComplaintIntake $intake): View
    {
        return view('admin.cases.intakes.show', compact('intake'));
    }

    /**
     * Reject or redirect a citizen complaint intake with a mandatory reason.
     */
    public function reject(Request $request, ComplaintIntake $intake): RedirectResponse
    {
        $validated = $request->validate([
            'outcome_reason' => ['required', 'string', 'max:50'],
            'explanation_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $fullReason = $validated['outcome_reason'].': '.$validated['explanation_note'];

        $oldStatus = $intake->status;
        $intake->update([
            'status' => 'rejected',
            'outcome_reason' => $fullReason,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
        ]);

        $this->audit->log(
            by: $request->user(),
            action: 'complaint_intake.rejected',
            subject: $intake,
            old: ['status' => $oldStatus],
            new: ['status' => 'rejected', 'reason' => $fullReason]
        );

        return redirect()->route('admin.cases.index', ['tab' => 'pending_intake'])
            ->with('status', 'Complaint intake request was successfully rejected.');
    }
}
