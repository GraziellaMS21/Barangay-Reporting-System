<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\Referral;
use App\Models\ReferralAttachment;
use App\Models\ReferralUpdate;
use App\Services\AuditLogger;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReferralController extends Controller
{
    public function index(Request $request): View
    {
        $officeId = $request->user()->office_id;
        $status = $request->query('status', 'active');

        $query = Referral::where('office_id', $officeId)->with(['report.category', 'report.purok'])->latest();

        if ($status === 'active') {
            $query->whereIn('status', ['sent', 'acknowledged', 'action_taken']);
        } elseif ($status !== 'all') {
            $query->where('status', $status);
        }

        $referrals = $query->paginate(20)->withQueryString();

        $counts = [
            'active' => Referral::where('office_id', $officeId)->whereIn('status', ['sent', 'acknowledged', 'action_taken'])->count(),
            'sent' => Referral::where('office_id', $officeId)->where('status', 'sent')->count(),
            'acknowledged' => Referral::where('office_id', $officeId)->where('status', 'acknowledged')->count(),
            'action_taken' => Referral::where('office_id', $officeId)->where('status', 'action_taken')->count(),
            'closed' => Referral::where('office_id', $officeId)->where('status', 'closed')->count(),
            'all' => Referral::where('office_id', $officeId)->count(),
        ];

        return view('agency.referrals.index', compact('referrals', 'counts', 'status'));
    }

    public function show(Request $request, Referral $referral, AuditLogger $auditLogger): View
    {
        // Enforce agency scoping
        if ($referral->office_id !== $request->user()->office_id) {
            abort(403, 'Unauthorized access to this referral.');
        }

        $referral->load(['report.category', 'report.purok', 'updates.user', 'attachments.uploadedBy', 'case']);

        $auditLogger->log(
            $request->user(),
            'agency.viewed_referral',
            $referral,
            [],
            ['reference_no' => $referral->reference_no]
        );

        return view('agency.referrals.show', compact('referral'));
    }

    public function update(Request $request, Referral $referral, AuditLogger $auditLogger, ReferralService $referralService): RedirectResponse
    {
        if ($referral->office_id !== $request->user()->office_id) {
            abort(403, 'Unauthorized access to this referral.');
        }

        $actionType = $request->input('action_type');

        if ($actionType === 'acknowledge') {
            $validated = $request->validate([
                'agency_reference_no' => ['nullable', 'string', 'max:100'],
            ]);

            DB::transaction(function () use ($referral, $validated, $request, $auditLogger) {
                $oldStatus = $referral->status;
                $referral->update([
                    'status' => 'acknowledged',
                    'agency_reference_no' => $validated['agency_reference_no'] ?? $referral->agency_reference_no,
                ]);

                ReferralUpdate::create([
                    'referral_id' => $referral->id,
                    'user_id' => $request->user()->id,
                    'note' => 'Receipt acknowledged by '.$request->user()->name.($validated['agency_reference_no'] ? " (Agency Ref: {$validated['agency_reference_no']})" : ''),
                    'channel' => 'portal',
                    'occurred_at' => now(),
                    'visible_to_resident' => true,
                ]);

                $auditLogger->log(
                    $request->user(),
                    'agency.acknowledged_referral',
                    $referral,
                    ['status' => $oldStatus],
                    ['status' => 'acknowledged']
                );
            });

            return redirect()->route('agency.referrals.show', $referral)->with('success', 'Referral receipt successfully acknowledged.');
        }

        if ($actionType === 'action_taken') {
            $validated = $request->validate([
                'note' => ['required', 'string', 'min:5', 'max:1000'],
            ]);

            DB::transaction(function () use ($referral, $validated, $request, $auditLogger) {
                $oldStatus = $referral->status;
                $referral->update([
                    'status' => 'action_taken',
                ]);

                ReferralUpdate::create([
                    'referral_id' => $referral->id,
                    'user_id' => $request->user()->id,
                    'note' => $validated['note'],
                    'channel' => 'portal',
                    'occurred_at' => now(),
                    'visible_to_resident' => true,
                ]);

                $auditLogger->log(
                    $request->user(),
                    'agency.action_taken',
                    $referral,
                    ['status' => $oldStatus],
                    ['status' => 'action_taken', 'note' => $validated['note']]
                );
            });

            return redirect()->route('agency.referrals.show', $referral)->with('success', 'Action taken successfully recorded.');
        }

        if ($actionType === 'close') {
            $validated = $request->validate([
                'outcome' => ['required', 'in:resolved,declined'],
                'note' => ['required', 'string', 'min:5', 'max:1000'],
            ]);

            $referralService->close($referral, $request->user(), $validated['outcome'], $validated['note']);

            $auditLogger->log(
                $request->user(),
                'agency.closed_referral',
                $referral->fresh(),
                [],
                ['status' => 'closed', 'outcome' => $validated['outcome']]
            );

            return redirect()->route('agency.referrals.show', $referral)->with('success', 'Referral closed.');
        }

        return redirect()->route('agency.referrals.show', $referral);
    }

    public function message(Request $request, Referral $referral, AuditLogger $auditLogger): RedirectResponse
    {
        if ($referral->office_id !== $request->user()->office_id) {
            abort(403, 'Unauthorized access to this referral.');
        }

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($referral, $validated, $request, $auditLogger) {
            ReferralUpdate::create([
                'referral_id' => $referral->id,
                'user_id' => $request->user()->id,
                'note' => $validated['body'],
                'channel' => 'portal',
                'occurred_at' => now(),
                'visible_to_resident' => false,
            ]);

            $auditLogger->log(
                $request->user(),
                'agency.message_sent',
                $referral,
                [],
                ['message_length' => strlen($validated['body'])]
            );
        });

        return redirect()->route('agency.referrals.show', $referral)->with('success', 'Note sent to barangay staff.');
    }

    public function downloadAttachment(Request $request, Referral $referral, ReferralAttachment $attachment): StreamedResponse
    {
        if ($referral->office_id !== $request->user()->office_id || $attachment->referral_id !== $referral->id) {
            abort(403, 'Unauthorized access to this attachment.');
        }

        if (! Storage::disk('private')->exists($attachment->path)) {
            abort(404, 'Attachment file not found.');
        }

        return Storage::disk('private')->response($attachment->path, basename($attachment->path));
    }

    public function uploadAttachment(Request $request, Referral $referral, AuditLogger $auditLogger): RedirectResponse
    {
        if ($referral->office_id !== $request->user()->office_id) {
            abort(403, 'Unauthorized access to this referral.');
        }

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx,txt', 'max:10240'],
        ]);

        $path = $request->file('file')->store("referral-attachments/{$referral->id}", 'private');

        $attachment = ReferralAttachment::create([
            'referral_id' => $referral->id,
            'label' => $validated['label'],
            'path' => $path,
            'uploaded_by' => $request->user()->id,
        ]);

        ReferralUpdate::create([
            'referral_id' => $referral->id,
            'user_id' => $request->user()->id,
            'note' => "Attached document: {$validated['label']}",
            'channel' => 'portal',
            'occurred_at' => now(),
            'visible_to_resident' => true,
        ]);

        $auditLogger->log(
            $request->user(),
            'agency.uploaded_attachment',
            $referral,
            [],
            ['attachment_id' => $attachment->id, 'label' => $validated['label']]
        );

        return back()->with('success', 'Document attachment uploaded successfully.');
    }
}
