<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Models\Referral;
use App\Models\ReferralUpdate;
use App\Models\Report;
use App\Models\User;
use App\Services\ReferralService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Unified Referrals and routing tracking for both internal barangay offices
 * (Mode A) and external agencies (Mode B).
 */
class ReferralController extends Controller
{
    public function __construct(private readonly ReferralService $referralService) {}

    public function index(Request $request): View
    {
        $allowedStatuses = array_unique(array_merge(
            ['all'],
            array_keys(Referral::STATUSES),
            array_keys(Report::STATUSES)
        ));

        $validated = $request->validate([
            'type' => ['nullable', Rule::in(['all', 'internal', 'external'])],
            'status' => ['nullable', Rule::in($allowedStatuses)],
            'office_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
        ]);

        $typeFilter = $validated['type'] ?? 'all';
        $statusFilter = $validated['status'] ?? 'all';
        $officeFilter = $validated['office_id'] ?? null;

        $items = collect();

        // 1. External agency referrals (Mode B)
        if ($typeFilter === 'all' || $typeFilter === 'external') {
            $referralQuery = Referral::query()
                ->with([
                    'report:id,tracking_code,status',
                    'office:id,name,type,is_internal,is_external',
                    'followUpOwner:id,name',
                    'preparedBy:id,name',
                ])
                ->latest('id');

            if ($statusFilter !== 'all') {
                $referralQuery->where('status', $statusFilter);
            }

            if ($officeFilter !== null) {
                $referralQuery->where('office_id', (int) $officeFilter);
            }

            foreach ($referralQuery->get() as $referral) {
                $ownerName = $referral->followUpOwner?->name
                    ?? $referral->preparedBy?->name
                    ?? 'Unassigned';

                $items->push([
                    'kind' => 'external',
                    'id' => $referral->id,
                    'report_id' => $referral->report_id,
                    'reference_no' => $referral->reference_no ?? 'Not assigned',
                    'tracking_code' => $referral->report?->tracking_code,
                    'type_label' => 'External',
                    'office_name' => $referral->office?->name ?? 'Unassigned',
                    'status' => $referral->status,
                    'status_label' => Referral::STATUSES[$referral->status] ?? $referral->status,
                    'routed_or_sent_at' => $referral->sent_at,
                    'routed_or_sent_empty' => 'Not sent',
                    'follow_up_at' => $referral->follow_up_at,
                    'is_overdue' => $referral->isOverdue(),
                    'owner_name' => $ownerName,
                    'url' => route('admin.reports.show', $referral->report_id),
                    'sort_at' => $referral->sent_at ?? $referral->created_at,
                    'model' => $referral,
                ]);
            }
        }

        // 2. Internal barangay routed reports (Mode A — derived directly from Report)
        if ($typeFilter === 'all' || $typeFilter === 'internal') {
            $internalQuery = Report::query()
                ->with([
                    'office:id,name,type,is_internal,is_external',
                    'assignee:id,name',
                    'statusLogs' => fn ($q) => $q->where('to_status', 'routed')->latest('id'),
                ])
                ->whereNotNull('office_id')
                ->whereHas('office', fn ($q) => $q->where('is_internal', true)->where('is_external', false))
                ->where('status', '!=', 'referred')
                ->latest('id');

            if ($statusFilter !== 'all') {
                $internalQuery->where('status', $statusFilter);
            }

            if ($officeFilter !== null) {
                $internalQuery->where('office_id', (int) $officeFilter);
            }

            foreach ($internalQuery->get() as $report) {
                $routedLog = $report->statusLogs->first();
                $routedAt = $routedLog?->created_at ?? $report->reviewed_at ?? $report->created_at;
                $isOverdue = $report->due_at !== null
                    && $report->due_at->isPast()
                    && ! in_array($report->status, Report::CLOSED_STATUSES, true);

                $items->push([
                    'kind' => 'internal',
                    'id' => $report->id,
                    'report_id' => $report->id,
                    'reference_no' => $report->tracking_code,
                    'tracking_code' => $report->tracking_code,
                    'type_label' => 'Within barangay',
                    'office_name' => $report->office?->name ?? 'Unassigned',
                    'status' => $report->status,
                    'status_label' => Report::STATUSES[$report->status] ?? $report->status,
                    'routed_or_sent_at' => $routedAt,
                    'routed_or_sent_empty' => 'Not routed',
                    'follow_up_at' => $report->due_at,
                    'is_overdue' => $isOverdue,
                    'owner_name' => $report->assignee?->name ?? 'Unassigned',
                    'url' => route('admin.reports.show', $report),
                    'sort_at' => $routedAt ?? $report->created_at,
                    'model' => $report,
                ]);
            }
        }

        $sorted = $items->sortByDesc(fn (array $row) => $row['sort_at']?->getTimestamp() ?? 0)->values();
        $perPage = 15;
        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $referrals = new LengthAwarePaginator(
            $sorted->forPage($currentPage, $perPage)->values(),
            $sorted->count(),
            $perPage,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $offices = Office::query()
            ->orderByDesc('is_internal')
            ->orderBy('name')
            ->get(['id', 'name', 'type', 'is_internal', 'is_external', 'is_active']);

        $statusOptions = [
            'External referrals' => Referral::STATUSES,
            'Within barangay reports' => [
                'routed' => Report::STATUSES['routed'],
                'in_progress' => Report::STATUSES['in_progress'],
                'needs_info' => Report::STATUSES['needs_info'],
                'awaiting_confirmation' => Report::STATUSES['awaiting_confirmation'],
                'resolved' => Report::STATUSES['resolved'],
                'closed' => Report::STATUSES['closed'] ?? 'Closed',
            ],
        ];

        return view('admin.referrals.index', compact(
            'referrals',
            'offices',
            'typeFilter',
            'statusFilter',
            'officeFilter',
            'statusOptions'
        ));
    }

    /** Printable referral letter (Print / Save as PDF). */
    public function letter(Referral $referral): View
    {
        $referral->load(['report.purok', 'report.category', 'office']);

        return view('admin.referrals.letter', [
            'referral' => $referral,
            'report' => $referral->report,
            'office' => $referral->office,
        ]);
    }

    public function markSent(Request $request, Referral $referral): RedirectResponse
    {
        abort_unless($referral->isOpen(), 409, 'This referral is already closed.');

        $validated = $request->validate([
            'sent_at' => ['nullable', 'date'],
            'sent_via' => ['nullable', Rule::in(array_keys(Referral::SENT_VIA))],
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $referral = $this->referralService->markSent(
            $referral,
            $reviewer,
            $validated['sent_at'] ?? null,
            $validated['sent_via'] ?? null
        );

        return back()->with('success', 'Referral '.$referral->reference_no.' marked as sent. Follow-up scheduled for '
            .$referral->follow_up_at?->format('M d, Y').'.');
    }

    public function storeUpdate(Request $request, Referral $referral): RedirectResponse
    {
        abort_unless($referral->isOpen(), 409, 'This referral is already closed.');

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'channel' => ['required', Rule::in(array_keys(Referral::CHANNELS))],
            'occurred_at' => ['nullable', 'date'],
            'status' => ['nullable', Rule::in(Referral::UPDATE_TARGET_STATUSES)],
            'agency_reference_no' => ['nullable', 'string', 'max:100'],
            'visible_to_resident' => ['sometimes', 'boolean'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:5120'],
        ], [
            'note.required' => 'Describe what the agency reported.',
            'channel.required' => 'Choose how the update was received.',
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('referral_updates', 'public');
        }

        $update = $this->referralService->logUpdate(
            $referral,
            $reviewer,
            $validated['note'],
            $validated['channel'],
            $validated['occurred_at'] ?? null,
            $photoPath,
            $validated['agency_reference_no'] ?? null,
            $validated['status'] ?? null,
            (bool) ($validated['visible_to_resident'] ?? true)
        );

        return back()->with('success', 'Referral update logged for '.$referral->reference_no
            .($update->visible_to_resident ? ' and shared with the resident.' : ' (internal only).'));
    }

    public function close(Request $request, Referral $referral): RedirectResponse
    {
        abort_unless($referral->isOpen(), 409, 'This referral is already closed.');

        $validated = $request->validate([
            'outcome' => ['required', Rule::in(array_keys(Referral::OUTCOMES))],
            'note' => ['required', 'string', 'max:2000'],
        ], [
            'outcome.required' => 'Choose the referral outcome.',
            'note.required' => 'Add a short note explaining the outcome.',
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $referral = $this->referralService->close($referral, $reviewer, $validated['outcome'], $validated['note']);

        return $referral->report->status === 'resolved'
            ? back()->with('success', 'Referral closed as resolved. The report is now resolved.')
            : back()->with('success', 'Referral closed ('.$validated['outcome']
                .'). The report stays open; choose the next action in Route and assign.');
    }

    public function updatePhoto(Referral $referral, ReferralUpdate $update): StreamedResponse
    {
        abort_unless($update->referral_id === $referral->id && $update->photo_path, 404);

        if (Storage::disk('public')->exists($update->photo_path)) {
            return Storage::disk('public')->response($update->photo_path);
        }

        abort_unless(Storage::disk('local')->exists($update->photo_path), 404);

        return Storage::disk('local')->response($update->photo_path);
    }

    public function destroy(Referral $referral): RedirectResponse
    {
        abort_unless($referral->isOpen(), 409);

        $referral->delete();

        return back()->with('success', 'Referral deleted.');
    }
}
