<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Services\AuditLogger;
use App\Services\CaseStatusService;
use App\Support\CaseStatus;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseSettlementController extends Controller
{
    public function __construct(
        protected CaseStatusService $statusService,
        protected AuditLogger $auditLogger
    ) {}

    public function store(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'settled_on' => ['required', 'date'],
            'terms' => ['required', 'string', 'min:5'],
            'settlement_amount' => ['nullable', 'numeric', 'min:0'],
            'agreement_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $filePath = null;
        if ($request->hasFile('agreement_file')) {
            $filePath = $request->file('agreement_file')->store("case-files/{$case->id}", 'private');
        }

        if ($case->status !== CaseStatus::SETTLED) {
            $this->statusService->transition(
                $case,
                CaseStatus::SETTLED,
                'Amicable settlement reached: '.Str::limit($validated['terms'], 120),
                $request->user()
            );
        }

        $repudiationDays = (int) config('katarungang_pambarangay.repudiation_period_days', 10);
        $repudiationEndsAt = Carbon::parse($validated['settled_on'])->addDays($repudiationDays);

        $case->update([
            'settled_on' => $validated['settled_on'],
            'settlement_terms' => $validated['terms'],
            'settlement_amount' => $validated['settlement_amount'] ?? null,
            'settlement_agreement_path' => $filePath ?? $case->settlement_agreement_path,
            'repudiation_ends_at' => $repudiationEndsAt,
            'repudiated_at' => null,
            'repudiation_ground' => null,
            'repudiated_by' => null,
        ]);

        $this->auditLogger->log(
            $request->user(),
            'case.settlement_recorded',
            $case,
            [],
            [
                'settled_on' => $validated['settled_on'],
                'has_amount' => ! empty($validated['settlement_amount']),
                'has_file' => ! empty($filePath),
            ]
        );

        return redirect()->route('admin.cases.show', $case)
            ->with('success', 'Amicable settlement agreement recorded successfully.');
    }

    public function agreement(Request $request, BarangayCase $case): StreamedResponse
    {
        $user = $request->user();
        if ($user && ! $case->is_confidential === false) {
            abort_unless(
                $user->isAdmin() ||
                $user->hasPermission('cases.view_confidential') ||
                $case->encoded_by === $user->id ||
                $case->assigned_to === $user->id,
                403
            );
        }

        if (empty($case->settlement_agreement_path) || ! Storage::disk('private')->exists($case->settlement_agreement_path)) {
            abort(404, 'Settlement agreement document not found.');
        }

        return Storage::disk('private')->response($case->settlement_agreement_path);
    }
}
