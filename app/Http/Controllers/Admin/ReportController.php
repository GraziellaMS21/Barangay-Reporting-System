<?php

namespace App\Http\Controllers\Admin;

use App\Events\InfoRequested;
use App\Events\ResolutionProposed;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Models\ResolutionProof;
use App\Models\ResponseTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\PriorityService;
use App\Services\ReportReviewService;
use App\Services\ReportStatusService;
use App\Services\RoutingService;
use App\Support\ReportStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'stage' => ['nullable', Rule::in(array_keys(Report::STATUS_GROUPS))],
            'status' => ['nullable', Rule::in(['all', ...array_keys(Report::STATUSES)])],
            'emergency' => ['nullable', Rule::in(['0', '1'])],
            'purok_id' => ['nullable', 'integer', Rule::exists('puroks', 'id')],
            'assigned_to' => ['nullable', 'string'],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $statusFilter = $validated['status'] ?? null;
        $emergencyOnly = ($validated['emergency'] ?? null) === '1';
        $currentStage = $validated['stage'] ?? 'all';
        $assignedToFilter = $validated['assigned_to'] ?? null;

        if ($statusFilter === 'emergency_flagged') {
            $emergencyOnly = true;
            $statusFilter = null;
            $currentStage = 'all';
        } elseif ($statusFilter !== null && $statusFilter !== 'all') {
            $currentStage = $this->stageForStatus($statusFilter) ?? $currentStage;
        }

        $query = Report::query()
            ->select([
                'id',
                'tracking_code',
                'description',
                'purok_id',
                'status',
                'is_emergency',
                'created_at',
                'assigned_to',
                'due_at',
                'escalated_at',
                'reopened_count',
                'priority_score',
            ])
            ->with(['purok:id,name', 'assignee:id,name']);

        if ($currentStage !== 'all') {
            $query->whereIn('status', Report::STATUS_GROUPS[$currentStage]['statuses']);
        }

        if ($statusFilter !== null && $statusFilter !== 'all') {
            $query->where('status', $statusFilter);
        }

        if ($emergencyOnly) {
            $query->where(fn ($emergencyQuery) => $emergencyQuery
                ->where('is_emergency', true)
                ->orWhere('status', 'emergency_flagged'));
        }

        if (isset($validated['purok_id'])) {
            $query->where('purok_id', $validated['purok_id']);
        }

        if ($assignedToFilter !== null && $assignedToFilter !== 'all') {
            if ($assignedToFilter === 'unassigned') {
                $query->whereNull('assigned_to');
            } elseif (is_numeric($assignedToFilter)) {
                $query->where('assigned_to', (int) $assignedToFilter);
            }
        }

        if (isset($validated['q'])) {
            $term = addcslashes($validated['q'], '%_\\');
            $query->where(function ($sub) use ($term) {
                $sub->where('tracking_code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $closedStatusesList = "'".implode("','", Report::CLOSED_STATUSES)."'";
        $now = now()->toDateTimeString();
        $query->orderByDesc('is_emergency')
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', ['emergency_flagged'])
            ->orderByRaw("CASE WHEN due_at IS NOT NULL AND due_at < ? AND status NOT IN ({$closedStatusesList}) THEN 0 ELSE 1 END", [$now])
            ->orderByRaw("CASE WHEN status IN ({$closedStatusesList}) THEN 1 ELSE 0 END ASC")
            ->latest('created_at')
            ->latest('id');

        $reports = $query->paginate(15)->withQueryString();

        $statusCounts = Report::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status');

        $stageCounts = collect(Report::STATUS_GROUPS)
            ->mapWithKeys(fn (array $group, string $stage): array => [
                $stage => $group['statuses'] === null
                    ? $statusCounts->sum()
                    : array_sum(array_map(
                        fn (string $status): int => (int) $statusCounts->get($status, 0),
                        $group['statuses']
                    )),
            ]);

        $emergencyCount = Report::query()
            ->where(fn ($emergencyQuery) => $emergencyQuery
                ->where('is_emergency', true)
                ->orWhere('status', 'emergency_flagged'))
            ->count();

        $statusGroups = Report::STATUS_GROUPS;
        $statusLabels = Report::STATUSES;
        $puroks = Purok::orderBy('name')->get();
        $staffUsers = User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $isMyCases = $request->routeIs('admin.reports.my-cases');

        return view('admin.reports.index', compact(
            'reports',
            'statusCounts',
            'statusGroups',
            'statusLabels',
            'stageCounts',
            'emergencyCount',
            'puroks',
            'staffUsers',
            'currentStage',
            'statusFilter',
            'assignedToFilter',
            'emergencyOnly',
            'isMyCases'
        ));
    }

    public function myCases(Request $request): View
    {
        $request->merge(['assigned_to' => (string) $request->user()->id]);

        return $this->index($request);
    }

    private function stageForStatus(string $status): ?string
    {
        foreach (Report::STATUS_GROUPS as $stage => $group) {
            if ($group['statuses'] !== null && in_array($status, $group['statuses'], true)) {
                return $stage;
            }
        }

        return null;
    }

    public function show(Report $report, RoutingService $routingService): View
    {
        $report->load([
            'purok',
            'statusLogs.user',
            'aiResult',
            'category',
            'photos',
            'assignee',
            'internalNotes.user',
            'infoRequests.requester',
            'resolutionProofs.user',
        ]);

        $manualStatuses = $report->is_emergency
            ? ['under_review', 'out_of_scope']
            : Report::MANUAL_STATUSES;

        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $offices = Office::query()->orderBy('name')->get();
        $recommendedOffice = $report->category ? $routingService->recommendedOffice($report->category) : null;

        $staffUsers = User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $responseTemplates = ResponseTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $autoCloseDays = (int) Setting::value('auto_close_days', 5);

        return view('admin.reports.show', compact(
            'report',
            'manualStatuses',
            'categories',
            'offices',
            'recommendedOffice',
            'staffUsers',
            'responseTemplates',
            'autoCloseDays'
        ));
    }

    public function photo(Report $report): StreamedResponse
    {
        abort_if(blank($report->photo_path), 404);

        return Storage::disk('local')->response($report->photo_path);
    }

    public function showPhoto(Report $report, ReportPhoto $photo): StreamedResponse
    {
        abort_unless($photo->report_id === $report->id, 404);

        return Storage::disk('local')->response($photo->photo_path);
    }

    public function showProofPhoto(Report $report, ResolutionProof $proof): StreamedResponse
    {
        abort_unless($proof->report_id === $report->id && $proof->photo_path, 404);

        return Storage::disk('local')->response($proof->photo_path);
    }

    public function saveReview(
        Request $request,
        Report $report,
        ReportReviewService $reportReviewService
    ): RedirectResponse {
        $activeCategoryIds = Category::query()
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::in($activeCategoryIds)],
            'urgency' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'staff_description' => ['required', 'string', 'max:2000'],
            'photo_mismatch' => ['required', 'boolean'],
            'is_emergency' => ['required', 'boolean'],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ], [
            'category_id.required' => 'Select a category before saving the review.',
            'category_id.in' => 'The selected category is no longer available. Choose an active category.',
            'urgency.in' => 'Choose a valid urgency level.',
            'staff_description.required' => 'Enter a reviewed description before saving.',
            'staff_description.max' => 'The reviewed description cannot exceed 2,000 characters.',
            'review_note.max' => 'The confirmation note cannot exceed 1,000 characters.',
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $reportReviewService->save($report, [
            'category_id' => (int) $validated['category_id'],
            'urgency' => $validated['urgency'],
            'staff_description' => $validated['staff_description'],
            'photo_mismatch' => $request->boolean('photo_mismatch'),
            'is_emergency' => $request->boolean('is_emergency'),
            'review_note' => $validated['review_note'] ?? null,
        ], $reviewer);

        return back()->with('success', 'Review details saved.');
    }

    public function retryAiAnalysis(
        Report $report,
        GeminiService $geminiService
    ): RedirectResponse {
        abort_unless($report->ai_status === 'failed', 409);

        try {
            $geminiService->analyze($report);
        } catch (Throwable $exception) {
            Log::error('Retrying report AI analysis failed unexpectedly.', [
                'report_id' => $report->id,
                'exception' => $exception::class,
            ]);

            return back()->with('error', 'AI analysis could not be retried. You can continue manual review.');
        }

        return back()->with('success', $report->fresh()->ai_status === 'done'
            ? 'AI analysis completed.'
            : 'AI remains unavailable. You can continue manual review.');
    }

    public function updateStatus(
        Request $request,
        Report $report,
        RoutingService $routingService,
        PriorityService $priorityService,
        ReportStatusService $statusService
    ): RedirectResponse {
        $allowedStatuses = $report->is_emergency
            ? ['under_review', 'out_of_scope']
            : Report::MANUAL_STATUSES;

        $validated = $request->validate([
            'status' => ['required', Rule::in($allowedStatuses)],
            'note' => ['required', 'string', 'max:500'],
            'office_id' => ['nullable', 'exists:offices,id'],
        ], [
            'note.required' => 'A note is required for every status change.',
        ]);

        DB::transaction(function () use ($report, $validated, $request, $routingService, $priorityService, $statusService) {
            $toStatus = $validated['status'];
            $note = $validated['note'];
            $officeId = $validated['office_id'] ?? null;

            if ($report->is_emergency) {
                $report->office_id = null;
                $report->priority_score = 0;
            } else {
                if ($officeId) {
                    $report->office_id = $officeId;
                } elseif ($report->category) {
                    $recommendedOffice = $routingService->recommendedOffice($report->category);
                    $report->office_id = $recommendedOffice?->id ?? $report->office_id;
                }

                $report->priority_score = $priorityService->calculate($report);
            }

            $report->save();

            if (! $report->is_emergency && $toStatus === 'routed' && $report->category && $report->office_id) {
                $routingService->createReferral($report, Office::find($report->office_id));
            }

            $statusService->transition($report, $toStatus, $note, $request->user());
        });

        return back()->with('success', 'Report status updated successfully.');
    }

    public function assign(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['admin', 'staff'])),
            ],
        ]);

        $report->assigned_to = $validated['assigned_to'] ? (int) $validated['assigned_to'] : null;
        $report->save();

        $assigneeName = $report->fresh()->assignee?->name ?? 'Unassigned';

        return back()->with('success', "Report assigned to: {$assigneeName}.");
    }

    public function storeInfoRequest(
        Request $request,
        Report $report,
        ReportStatusService $statusService
    ): RedirectResponse {
        $validated = $request->validate([
            'question' => ['required', 'string', 'max:2000'],
        ]);

        $infoRequest = $report->infoRequests()->create([
            'requested_by' => $request->user()->id,
            'question' => $validated['question'],
        ]);

        $statusService->transition(
            $report,
            ReportStatus::NEEDS_INFO,
            'Information requested from resident: '.str($validated['question'])->limit(120),
            $request->user()
        );

        event(new InfoRequested($report, $infoRequest));

        return back()->with('success', 'Information requested from resident. Status set to Needs Info.');
    }

    public function storeInternalNote(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'max:3000'],
        ]);

        $report->internalNotes()->create([
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        return back()->with('success', 'Internal note saved.');
    }

    public function proposeResolution(
        Request $request,
        Report $report,
        ReportStatusService $statusService
    ): RedirectResponse {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
        ]);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('resolution_proofs', 'local');
        }

        $proof = $report->resolutionProofs()->create([
            'user_id' => $request->user()->id,
            'note' => $validated['note'],
            'photo_path' => $photoPath,
        ]);

        $statusService->transition(
            $report,
            ReportStatus::AWAITING_CONFIRMATION,
            'Resolution proposed with proof: '.str($validated['note'])->limit(120),
            $request->user()
        );

        event(new ResolutionProposed($report, $proof));

        return back()->with('success', 'Resolution proposed. Case status updated to Awaiting Confirmation.');
    }

    public function closeManually(
        Request $request,
        Report $report,
        ReportStatusService $statusService
    ): RedirectResponse {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $statusService->transition(
            $report,
            ReportStatus::RESOLVED,
            $validated['note'] ?: 'Case closed manually by staff after confirmation period.',
            $request->user()
        );

        return back()->with('success', 'Case marked as resolved.');
    }

    public function escalate(
        Request $request,
        Report $report,
        ReportStatusService $statusService
    ): RedirectResponse {
        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $report->escalated_at = now();
        $report->save();

        $statusService->transition(
            $report,
            $report->status,
            'Case escalated: '.($validated['reason'] ?: 'Escalated due to overdue SLA.'),
            $request->user()
        );

        return back()->with('success', 'Case has been escalated.');
    }
}
