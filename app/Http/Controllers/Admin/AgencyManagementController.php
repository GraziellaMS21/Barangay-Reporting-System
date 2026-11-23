<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AgencyRegistration;
use App\Models\Office;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AgencyManagementController extends Controller
{
    public function index(Request $request): View
    {
        $status = $request->query('status', 'pending');

        $query = AgencyRegistration::with(['user', 'office'])->latest();

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $registrations = $query->paginate(20)->withQueryString();

        $counts = [
            'pending' => AgencyRegistration::where('status', 'pending')->count(),
            'approved' => AgencyRegistration::where('status', 'approved')->count(),
            'rejected' => AgencyRegistration::where('status', 'rejected')->count(),
            'suspended' => AgencyRegistration::where('status', 'suspended')->count(),
            'all' => AgencyRegistration::count(),
        ];

        return view('admin.agencies.index', compact('registrations', 'counts', 'status'));
    }

    public function show(AgencyRegistration $registration): View
    {
        $registration->load(['user', 'office', 'reviewer']);

        $offices = Office::where('is_active', true)
            ->where(function ($query) {
                $query->where('is_external', true)
                    ->orWhere('type', 'external');
            })
            ->orderBy('name')
            ->get();

        return view('admin.agencies.show', compact('registration', 'offices'));
    }

    public function approve(Request $request, AgencyRegistration $registration, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'office_id' => ['required', 'exists:offices,id'],
            'is_office_admin' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($registration, $validated, $request, $auditLogger) {
            $user = $registration->user;
            if ($user) {
                $user->update([
                    'office_id' => $validated['office_id'],
                    'is_active' => true,
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ]);
            }

            $registration->update([
                'office_id' => $validated['office_id'],
                'is_office_admin' => $request->boolean('is_office_admin'),
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            $auditLogger->log(
                $request->user(),
                'agency.approved',
                $registration,
                ['status' => 'pending'],
                ['status' => 'approved', 'office_id' => $validated['office_id']]
            );
        });

        return redirect()->route('admin.agencies.show', $registration)->with('success', 'Agency registration successfully approved.');
    }

    public function reject(Request $request, AgencyRegistration $registration, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'decision_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($registration, $validated, $request, $auditLogger) {
            $oldStatus = $registration->status;
            $registration->update([
                'status' => 'rejected',
                'decision_note' => $validated['decision_note'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($registration->user) {
                $registration->user->update(['is_active' => false]);
            }

            $auditLogger->log(
                $request->user(),
                'agency.rejected',
                $registration,
                ['status' => $oldStatus],
                ['status' => 'rejected', 'note' => $validated['decision_note']]
            );
        });

        return redirect()->route('admin.agencies.show', $registration)->with('success', 'Agency registration rejected.');
    }

    public function suspend(Request $request, AgencyRegistration $registration, AuditLogger $auditLogger): RedirectResponse
    {
        $validated = $request->validate([
            'decision_note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        DB::transaction(function () use ($registration, $validated, $request, $auditLogger) {
            $oldStatus = $registration->status;
            $registration->update([
                'status' => 'suspended',
                'decision_note' => $validated['decision_note'],
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($registration->user) {
                $registration->user->update(['is_active' => false]);
            }

            $auditLogger->log(
                $request->user(),
                'agency.suspended',
                $registration,
                ['status' => $oldStatus],
                ['status' => 'suspended', 'note' => $validated['decision_note']]
            );
        });

        return redirect()->route('admin.agencies.show', $registration)->with('success', 'Agency account suspended.');
    }

    public function reinstate(Request $request, AgencyRegistration $registration, AuditLogger $auditLogger): RedirectResponse
    {
        DB::transaction(function () use ($registration, $request, $auditLogger) {
            $oldStatus = $registration->status;
            $registration->update([
                'status' => 'approved',
                'reviewed_by' => $request->user()->id,
                'reviewed_at' => now(),
            ]);

            if ($registration->user) {
                $registration->user->update(['is_active' => true]);
            }

            $auditLogger->log(
                $request->user(),
                'agency.reinstated',
                $registration,
                ['status' => $oldStatus],
                ['status' => 'approved']
            );
        });

        return redirect()->route('admin.agencies.show', $registration)->with('success', 'Agency account reinstated.');
    }

    public function document(AgencyRegistration $registration): StreamedResponse
    {
        if (! $registration->id_document_path || ! Storage::disk('private')->exists($registration->id_document_path)) {
            abort(404, 'Verification document not found.');
        }

        return Storage::disk('private')->response($registration->id_document_path);
    }
}
