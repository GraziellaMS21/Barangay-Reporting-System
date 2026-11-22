<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralUpdate;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralActionController extends Controller
{
    /**
     * Dispatch referral to external agency portal.
     */
    public function send(Request $request, Referral $referral, AuditLogger $auditLogger): RedirectResponse
    {
        $oldStatus = $referral->status;

        DB::transaction(function () use ($referral, $oldStatus, $request, $auditLogger) {
            $referral->update([
                'status' => 'sent',
                'sent_at' => now(),
                'follow_up_at' => now()->addDays((int) setting('referral.follow_up_days', 3)),
            ]);

            ReferralUpdate::create([
                'referral_id' => $referral->id,
                'user_id' => $request->user()->id,
                'note' => 'Referral officially transmitted to agency portal by '.$request->user()->name,
                'channel' => 'portal',
                'occurred_at' => now(),
                'visible_to_resident' => true,
            ]);

            $auditLogger->log(
                $request->user(),
                'referral.sent_to_portal',
                $referral,
                ['status' => $oldStatus],
                ['status' => 'sent', 'sent_at' => now()->toDateTimeString()]
            );
        });

        return back()->with('success', "Referral {$referral->reference_no} sent to agency portal.");
    }

    /**
     * Store staff message/reply in referral updates thread.
     */
    public function storeMessage(Request $request, Referral $referral, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
            'visible_to_resident' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($referral, $validated, $request, $auditLogger) {
            ReferralUpdate::create([
                'referral_id' => $referral->id,
                'user_id' => $request->user()->id,
                'note' => $validated['body'],
                'channel' => 'portal',
                'occurred_at' => now(),
                'visible_to_resident' => $request->boolean('visible_to_resident', false),
            ]);

            $auditLogger->log(
                $request->user(),
                'referral.staff_message',
                $referral,
                [],
                ['length' => strlen($validated['body'])]
            );
        });

        return back()->with('success', 'Message posted to referral thread.');
    }
}
