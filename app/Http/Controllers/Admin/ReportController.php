<?php

namespace App\Http\Controllers\Admin;

use App\Events\InfoRequested;
use App\Events\ResolutionProposed;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreReportUpdateRequest;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Models\ReportProofPhoto;
use App\Models\ResolutionProof;
use App\Models\ResponseTemplate;
use App\Models\Setting;
use App\Models\User;
use App\Services\GeminiService;
use App\Services\PriorityService;
use App\Services\ProofPhotoService;
use App\Services\ReferralService;
use App\Services\ReportReviewService;
use App\Services\ReportStatusService;
use App\Services\RoutingService;
use App\Support\ReportStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
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
        $validated = $this->validateQueueFilters($request);

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

        $query = $this->queueQuery($validated, $currentStage, $statusFilter, $emergencyOnly, $assignedToFilter);
        $this->applyQueueOrder($query, $validated);

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

        $unreadReplies = $this->unreadReplyCounts($reports->getCollection());

        $statusGroups = Report::STATUS_GROUPS;
        $statusLabels = Report::STATUSES;
        $puroks = Purok::orderBy('name')->get();
        $categories = Category::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']);
        $staffUsers = User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $isMyCases = $request->routeIs('admin.reports.my-cases');

        return view('admin.reports.index', compact(
            'reports',
            'statusCounts',
            'statusGroups',
            'statusLabels',
            'stageCounts',
            'emergencyCount',
            'unreadReplies',
            'puroks',
            'categories',
            'staffUsers',
            'currentStage',
            'statusFilter',
            'assignedToFilter',
            'emergencyOnly',
            'isMyCases'
        ));
    }

    /** Shared validation for the queue list, export, and bulk actions. */
    private function validateQueueFilters(Request $request): array
    {
        return $request->validate([
            'stage' => ['nullable', Rule::in(array_keys(Report::STATUS_GROUPS))],
            'status' => ['nullable', Rule::in(['all', ...array_keys(Report::STATUSES)])],
            'emergency' => ['nullable', Rule::in(['0', '1'])],
            'purok_id' => ['nullable', 'integer', Rule::exists('puroks', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('categories', 'id')],
            'assigned_to' => ['nullable', 'string'],
            'overdue' => ['nullable', Rule::in(['0', '1'])],
            'q' => ['nullable', 'string', 'max:255'],
            // Whitelisted sorting; unknown values silently fall back to the
            // default order inside applyQueueOrder instead of erroring.
            'sort' => ['nullable', 'string', 'max:32'],
            'direction' => ['nullable', 'string', 'max:16'],
            'pin' => ['nullable', 'string', 'max:1'],
        ]);
    }

    /**
     * The report queue query shared by the table and the CSV export. Callers
     * apply ordering themselves (applyQueueOrder).
     */
    private function queueQuery(
        array $validated,
        string $currentStage,
        ?string $statusFilter,
        bool $emergencyOnly,
        ?string $assignedToFilter
    ): Builder {
        $query = Report::query()
            ->select([
                'id',
                'tracking_code',
                'description',
                'purok_id',
                'category_id',
                'status',
                'urgency',
                'is_emergency',
                'is_anonymous',
                'created_at',
                'assigned_to',
                'due_at',
                'escalated_at',
                'reopened_count',
                'priority_score',
                'summary',
                'ai_status',
            ])
            ->with(['purok:id,name', 'category:id,name', 'assignee:id,name', 'aiResult', 'duplicateSuggestions']);

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

        if (($validated['overdue'] ?? null) === '1') {
            $query->whereNotNull('due_at')->where('due_at', '<', now())->whereNotIn('status', Report::CLOSED_STATUSES);
        }

        if (isset($validated['purok_id'])) {
            $query->where('purok_id', $validated['purok_id']);
        }

        if (isset($validated['category_id'])) {
            $query->where(function ($categoryQuery) use ($validated): void {
                $categoryQuery->where('category_id', $validated['category_id'])
                    ->orWhere('resident_category_id', $validated['category_id']);
            });
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

        return $query;
    }

    /**
     * Newest first (created_at desc, id desc tiebreaker) by default. The
     * "Pin emergencies to top" toggle restores the operations-first ordering.
     * Column-header sorting is whitelisted to submitted/status/urgency.
     */
    private function applyQueueOrder($query, array $validated): void
    {
        $direction = ($validated['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

        if (($validated['pin'] ?? null) === '1') {
            $closedStatusesList = "'".implode("','", Report::CLOSED_STATUSES)."'";
            $now = now()->toDateTimeString();
            $query->orderByDesc('is_emergency')
                ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', ['emergency_flagged'])
                ->orderByRaw("CASE WHEN due_at IS NOT NULL AND due_at < ? AND status NOT IN ({$closedStatusesList}) THEN 0 ELSE 1 END", [$now])
                ->orderByDesc('priority_score')
                ->orderByRaw("CASE WHEN status IN ({$closedStatusesList}) THEN 1 ELSE 0 END ASC")
                ->orderByDesc('created_at')
                ->orderByDesc('id');

            return;
        }

        switch ($validated['sort'] ?? null) {
            case 'status':
                $query->orderBy('status', $direction)
                    ->orderByRaw('CASE WHEN is_emergency = 1 OR status = ? THEN 0 ELSE 1 END', ['emergency_flagged'])
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');

                return;
            case 'urgency':
                // Highest urgency first when descending.
                $query->orderByRaw('CASE urgency '
                    ."WHEN 'Critical' THEN 4 WHEN 'High' THEN 3 WHEN 'Medium' THEN 2 WHEN 'Low' THEN 1 ELSE 0 END ".($direction === 'asc' ? 'ASC' : 'DESC'))
                    ->orderByDesc('priority_score', $direction === 'asc' ? 'asc' : 'desc')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id');

                return;
            case 'submitted':
                $query->orderBy('created_at', $direction)->orderBy('id', $direction);

                return;
            default:
                $query->orderByDesc('created_at')->orderByDesc('id');
        }
    }

    /** Unread resident-reply counts per report for the queue badge. */
    private function unreadReplyCounts(iterable $reports): Collection
    {
        return collect($reports)
            ->mapWithKeys(fn (Report $report): array => [
                $report->id => $report->infoRequests()
                    ->whereNotNull('responded_at')
                    ->get()
                    ->filter(fn ($request) => $request->response)
                    ->filter(function ($request) use ($report): bool {
                        $seenAt = session('info_thread_seen.'.$report->id);

                        return $seenAt === null || $request->responded_at->gt($seenAt);
                    })
                    ->count(),
            ]);
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

    public function show(Report $report, RoutingService $routingService, ReferralService $referralService): View
    {
        $report->load([
            'purok',
            'residentPurok',
            'residentCategory',
            'statusLogs.user',
            'aiResult',
            'category',
            'photos',
            'assignee',
            'internalNotes.user',
            'infoRequests.requester',
            'resolutionProofs.user',
            'proofPhotos',
            'activeReferral.office',
            'activeReferral.updates.user',
            'duplicateSuggestions.candidate',
        ]);

        $manualStatuses = $report->is_emergency
            ? ['under_review', 'out_of_scope']
            : Report::MANUAL_STATUSES;

        $categories = Category::query()
            ->with('defaultOffice')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'default_office_id', 'question_schema']);

        $offices = Office::query()->orderBy('name')->get();
        $externalOffices = Office::query()
            ->where('is_external', true)
            ->orderBy('name')
            ->get();
        $puroks = Purok::query()->orderBy('name')->get(['id', 'name']);
        $routingDecisions = $routingService->decisionsForCategories($categories);

        // Jurisdiction decision for the report's final category (Mode A / Mode B).
        $jurisdiction = $report->category
            ? $routingService->decisionForCategory($report->category)
            : ['office' => null, 'within_authority' => true, 'jurisdiction_note' => 'Category not yet assigned.'];

        // Per-agency letter and resident message templates for the Mode B form.
        $letterTemplates = [];
        foreach ($externalOffices as $externalOffice) {
            $letterTemplates[$externalOffice->id] = [
                'letter' => $referralService->buildLetter($report, $externalOffice),
                'resident_message' => 'We referred this to '.$externalOffice->name
                    .' because '.$jurisdiction['jurisdiction_note']
                    .' We will keep you updated here.',
                'contact_person' => $externalOffice->contact_person,
                'contact_email' => $externalOffice->contact_email,
                'contact_phone' => $externalOffice->contact_phone,
                'address' => $externalOffice->address,
            ];
        }

        $staffUsers = User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);

        $responseTemplates = ResponseTemplate::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $autoCloseDays = (int) Setting::value('auto_close_days', 5);
        $aiConfidenceThreshold = (float) Setting::value('ai_confidence_threshold', 0.8);
        $aiConfidenceLowThreshold = (float) Setting::value('ai_confidence_low_threshold', 0.6);
        $duplicateCandidates = Report::query()
            ->where('id', '!=', $report->id)
            ->where('status', '!=', 'merged')
            ->latest('created_at')
            ->limit(50)
            ->get(['id', 'tracking_code', 'description', 'created_at']);

        $activityEntries = collect();
        foreach ($report->statusLogs as $statusLog) {
            if (($statusLog->to_status === 'needs_info' || $statusLog->from_status === 'needs_info') && ! $statusLog->update_type) {
                continue;
            }

            $activityEntries->push([
                'at' => $statusLog->created_at,
                'title' => $statusLog->update_title.($statusLog->visible_to_resident === false ? ' (Internal)' : ''),
                'body' => $statusLog->note,
                'user' => $statusLog->user?->name,
            ]);
        }

        foreach ($report->infoRequests as $infoRequest) {
            $requester = $infoRequest->requester?->name ?? 'Staff';
            $body = 'Needs info: requested by '.$requester.': '.$infoRequest->question.'.';
            if ($infoRequest->response !== null && $infoRequest->response !== '') {
                $body .= ' Resident replied: '.$infoRequest->response;
            }

            $activityEntries->push([
                'at' => $infoRequest->responded_at ?? $infoRequest->created_at,
                'title' => 'Information request',
                'body' => $body,
                'user' => $requester,
            ]);
        }

        foreach ($report->internalNotes as $internalNote) {
            $activityEntries->push([
                'at' => $internalNote->created_at,
                'title' => 'Internal note',
                'body' => $internalNote->body,
                'user' => $internalNote->user?->name,
            ]);
        }

        foreach ($report->activeReferral?->updates ?? [] as $referralUpdate) {
            $activityEntries->push([
                'at' => $referralUpdate->occurred_at ?? $referralUpdate->created_at,
                'title' => 'Referral update ('.$referralUpdate->channel.')',
                'body' => $referralUpdate->note,
                'user' => $referralUpdate->user?->name,
            ]);
        }

        $activityEntries = $activityEntries->sortByDesc('at')->values();
        $photoMismatchReviewed = AuditLog::query()
            ->where('model', Report::class)
            ->where('model_id', $report->id)
            ->where('action', 'photo_mismatch_review')
            ->exists()
            || ($report->photo_mismatch && (bool) $report->aiResult?->photo_mismatch);
        $emergencyFlagReviewed = AuditLog::query()
            ->where('model', Report::class)
            ->where('model_id', $report->id)
            ->where('action', 'emergency_flag_review')
            ->exists();
        $currentUserId = auth()->id();
        $proofConfig = config('reports.proof_photos');
        $referralConfig = config('reports.referrals');
        $infoThreadSummary = $this->infoThreadSummary($report);

        return view('admin.reports.show', compact(
            'report',
            'infoThreadSummary',
            'manualStatuses',
            'categories',
            'offices',
            'externalOffices',
            'letterTemplates',
            'jurisdiction',
            'puroks',
            'routingDecisions',
            'staffUsers',
            'responseTemplates',
            'autoCloseDays',
            'aiConfidenceThreshold',
            'aiConfidenceLowThreshold',
            'duplicateCandidates',
            'activityEntries',
            'photoMismatchReviewed',
            'emergencyFlagReviewed',
            'currentUserId',
            'proofConfig',
            'referralConfig'
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

    public function showProofPhotoImage(Report $report, ReportProofPhoto $proofPhoto, ProofPhotoService $proofPhotoService): StreamedResponse
    {
        abort_unless($proofPhoto->report_id === $report->id, 404);

        return $proofPhotoService->response($proofPhoto);
    }

    public function storeProofPhotos(
        Request $request,
        Report $report,
        ProofPhotoService $proofPhotoService
    ): RedirectResponse {
        $config = config('reports.proof_photos');

        $validated = $request->validate([
            'proof_photos' => ['required', 'array', 'min:1', 'max:'.$config['max_files']],
            'proof_photos.*' => [
                'file',
                'image',
                'mimes:'.$config['mimes'],
                'max:'.$config['max_size_kb'],
            ],
        ], [
            'proof_photos.required' => 'Choose at least one photo to upload.',
            'proof_photos.max' => 'Upload at most '.$config['max_files'].' photos at a time.',
            'proof_photos.*.image' => 'Each proof photo must be an image.',
            'proof_photos.*.mimes' => 'Proof photos must be JPG, PNG, or WebP images.',
            'proof_photos.*.max' => 'Each proof photo must be smaller than '.round($config['max_size_kb'] / 1024).' MB.',
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $result = $proofPhotoService->storeMany($report, $validated['proof_photos'], $reviewer);

        $message = 'Proof photo'.($result['stored'] === 1 ? '' : 's').' uploaded.';
        if ($result['skipped'] !== []) {
            $message .= ' Skipped: '.implode(' ', $result['skipped']);
        }

        return back()->with($result['stored'] > 0 ? 'success' : 'error', $message);
    }

    public function deleteProofPhoto(
        Request $request,
        Report $report,
        ReportProofPhoto $proofPhoto,
        ProofPhotoService $proofPhotoService
    ): RedirectResponse {
        abort_unless($proofPhoto->report_id === $report->id, 404);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $proofPhotoService->delete($report, $proofPhoto, $reviewer);

        return back()->with('success', 'Proof photo deleted.');
    }

    public function saveReview(
        Request $request,
        Report $report,
        ReportReviewService $reportReviewService,
        RoutingService $routingService
    ): RedirectResponse {
        $activeCategoryIds = Category::query()
            ->where('is_active', true)
            ->pluck('id')
            ->all();

        $validated = $request->validate([
            'intent' => ['sometimes', Rule::in(['save', 'route', 'referral'])],
            'category_id' => ['required', 'integer', Rule::in($activeCategoryIds)],
            'urgency' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'staff_description' => ['required', 'string', 'max:2000'],
            'purok_id' => ['nullable', 'integer', Rule::exists('puroks', 'id')],
            'landmark' => ['nullable', 'string', 'max:255'],
            'office_id' => ['nullable', 'integer', Rule::exists('offices', 'id')],
            'authority_mode' => ['nullable', Rule::in(['within', 'beyond'])],
            'referral_letter' => ['nullable', 'string', 'max:20000'],
            'resident_message' => ['nullable', 'string', 'max:1000'],
            'assigned_to' => [
                'nullable',
                'integer',
                Rule::exists('users', 'id')->where(fn ($query) => $query->whereIn('role', ['admin', 'staff'])),
            ],
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
        $intent = $validated['intent'] ?? 'save';
        $category = Category::query()->findOrFail((int) $validated['category_id']);
        $officeId = $request->has('office_id')
            ? ($validated['office_id'] ?? null)
            : $report->office_id;
        $assignedTo = $request->has('assigned_to')
            ? ($validated['assigned_to'] ?? null)
            : $report->assigned_to;
        $reviewNote = $request->has('review_note')
            ? ($validated['review_note'] ?? null)
            : $report->review_note;
        if ($intent === 'route' && $officeId === null) {
            $officeId = $routingService->decisionForCategory($category)['office']?->id;
            if ($officeId === null) {
                return back()->withInput()->withErrors([
                    'office_id' => 'Choose an office before confirming and routing this report.',
                ]);
            }
        }
        if ($intent === 'referral' && $officeId === null) {
            return back()->withInput()->withErrors([
                'office_id' => 'Choose an agency before preparing the referral.',
            ]);
        }

        try {
            $reportReviewService->save($report, [
                'category_id' => (int) $validated['category_id'],
                'urgency' => $validated['urgency'],
                'staff_description' => $validated['staff_description'],
                'purok_id' => isset($validated['purok_id']) ? (int) $validated['purok_id'] : null,
                'landmark' => $validated['landmark'] ?? null,
                'office_id' => $officeId === null ? null : (int) $officeId,
                'assigned_to' => $assignedTo === null ? null : (int) $assignedTo,
                'review_note' => $reviewNote,
                'authority_mode' => $validated['authority_mode'] ?? null,
                'referral_letter' => $validated['referral_letter'] ?? null,
                'resident_message' => $validated['resident_message'] ?? null,
            ], $reviewer, $intent);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['review' => $exception->getMessage()]);
        }

        $report->refresh();
        $message = match ([$report->status, $intent]) {
            ['referred', 'referral'] => 'Referral prepared. Track it in Route and assign and on the referrals page.',
            ['referred', 'route'] => 'Review saved and report referred to the selected office.',
            'referred' => 'Review saved and report referred to the selected office.',
            ['routed', 'route'] => 'Review saved and report routed.',
            default => 'Review saved without routing.',
        };

        return back()->with('success', $message);
    }

    public function reviewAiStatus(Report $report): JsonResponse
    {
        $report->load('aiResult');
        $aiResult = $report->aiResult;

        if (in_array($report->ai_status, ['pending', 'processing'], true)) {
            return response()->json(['status' => 'pending']);
        }

        if ($report->ai_status !== 'done' || $aiResult === null || $aiResult->error !== null) {
            return response()->json(['status' => 'unavailable']);
        }

        $category = Category::query()
            ->where('is_active', true)
            ->where('slug', $aiResult->suggested_category)
            ->first(['id', 'name']);

        return response()->json([
            'status' => 'ready',
            'confidence' => $aiResult->confidence,
            'values' => [
                'category' => ['value' => $category?->id, 'label' => $category?->name],
                'urgency' => ['value' => $aiResult->suggested_urgency, 'label' => $aiResult->suggested_urgency],
                'description' => ['value' => $aiResult->summary, 'label' => $aiResult->summary],
                'purok' => ['value' => null, 'label' => null],
                'landmark' => ['value' => null, 'label' => null],
            ],
        ]);
    }

    public function resolveEmergencyFlag(
        Request $request,
        Report $report,
        ReportReviewService $reportReviewService
    ): RedirectResponse {
        abort_if(in_array($report->status, Report::CLOSED_STATUSES, true), 409);

        $validated = $request->validate([
            'confirm' => ['required', 'boolean'],
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        try {
            $reportReviewService->resolveEmergency($report, (bool) $validated['confirm'], $reviewer);
        } catch (\InvalidArgumentException $exception) {
            return back()->withInput()->withErrors(['emergency' => $exception->getMessage()]);
        }

        return back()->with('success', $validated['confirm']
            ? 'Emergency concern confirmed.'
            : 'Emergency concern cleared; report returned to review.');
    }

    public function resolvePhotoMismatch(Request $request, Report $report): RedirectResponse
    {
        $validated = $request->validate([
            'confirm' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($report, $validated, $request): void {
            $lockedReport = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $aiMismatch = (bool) $lockedReport->aiResult?->photo_mismatch;
            $confirmedMismatch = (bool) $validated['confirm'];
            $lockedReport->photo_mismatch = $confirmedMismatch;
            $lockedReport->save();

            if ($confirmedMismatch !== $aiMismatch) {
                AuditLog::query()->create([
                    'user_id' => $request->user()->id,
                    'action' => 'photo_mismatch_review',
                    'model' => Report::class,
                    'model_id' => $lockedReport->id,
                    'old_values' => ['photo_mismatch' => $aiMismatch],
                    'new_values' => ['photo_mismatch' => $confirmedMismatch],
                ]);
            }
        });

        return back()->with('success', $validated['confirm']
            ? 'Photo mismatch confirmed.'
            : 'Photo mismatch cleared.');
    }

    public function referReport(
        Request $request,
        Report $report,
        RoutingService $routingService,
        ReportStatusService $statusService
    ): RedirectResponse {
        abort_if($report->is_emergency || $report->status === 'emergency_flagged', 409);

        $validated = $request->validate([
            'office_id' => ['required', 'integer', Rule::exists('offices', 'id')],
            'note' => ['required', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($report, $validated, $request, $routingService, $statusService): void {
            $lockedReport = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $office = Office::query()->findOrFail($validated['office_id']);
            if ($lockedReport->status === 'needs_info') {
                $statusService->transition($lockedReport, 'under_review', 'Review resumed before referral.', $request->user());
            }

            $lockedReport->office_id = $office->id;
            $lockedReport->save();
            $routingService->createReferral($lockedReport, $office, $request->user());
            $statusService->transition($lockedReport, 'referred', $validated['note'], $request->user());
        });

        return back()->with('success', 'Report referred to the selected office.');
    }

    public function markDuplicate(Request $request, Report $report, ReportStatusService $statusService): RedirectResponse
    {
        abort_if($report->is_emergency || $report->status === 'emergency_flagged', 409);

        $validated = $request->validate([
            'candidate_id' => [
                'required',
                'integer',
                Rule::exists('reports', 'id')->where(fn ($query) => $query->where('id', '!=', $report->id)),
            ],
            'note' => ['required', 'string', 'max:500'],
        ]);
        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        DB::transaction(function () use ($report, $validated, $reviewer, $statusService): void {
            $lockedReport = Report::query()->whereKey($report->id)->lockForUpdate()->firstOrFail();
            $candidate = Report::query()->whereKey($validated['candidate_id'])->firstOrFail();
            abort_if($candidate->status === 'merged', 422, 'The selected report has already been merged.');

            DB::table('duplicate_suggestions')->updateOrInsert(
                ['report_id' => $lockedReport->id, 'candidate_id' => $candidate->id],
                ['decision' => 'merged', 'decided_by' => $reviewer->id, 'updated_at' => now(), 'created_at' => now()]
            );
            $lockedReport->merged_into_id = $candidate->id;
            $lockedReport->save();
            $statusService->transition($lockedReport, 'merged', $validated['note'], $reviewer);
        });

        return back()->with('success', 'Report marked as a duplicate.');
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
            'note' => [
                Rule::requiredIf(in_array($request->input('status'), ['out_of_scope', 'referred', 'merged'], true)),
                'nullable',
                'string',
                'max:500',
            ],
            'office_id' => ['nullable', 'exists:offices,id'],
        ], [
            'note.required' => 'A note is required for this status change.',
        ]);

        DB::transaction(function () use ($report, $validated, $request, $routingService, $priorityService, $statusService) {
            $toStatus = $validated['status'];
            $note = $validated['note'] ?? null;
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

            $office = Office::find($report->office_id);
            if (! $report->is_emergency && $toStatus === 'routed' && $report->category && $office && $office->is_external) {
                $routingService->createReferral($report, $office, $request->user());
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
    ): RedirectResponse|JsonResponse {
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

        // Chat-modal clients send with Accept: application/json and append the
        // message without reloading; normal form posts keep the redirect flow.
        if ($request->expectsJson()) {
            return response()->json([
                'report_status' => $report->fresh()->status,
                'summary' => $this->infoThreadSummary($report),
                'messages' => $this->infoThreadMessages($report),
            ], 201);
        }

        return back()->with('success', 'Information requested from resident. Status set to Needs Info.');
    }

    /**
     * JSON thread for the "Ask Reporter for More Info" chat modal. Also marks
     * resident replies as seen so the queue badge can show unread counts.
     */
    public function infoThread(Request $request, Report $report): JsonResponse
    {
        if ($request->boolean('seen')) {
            $this->markInfoThreadSeen($report);
        }

        return response()->json([
            'report' => [
                'id' => $report->id,
                'tracking_code' => $report->tracking_code,
                'category' => $report->category?->name,
                'status' => $report->status,
                'status_label' => Report::STATUSES[$report->status] ?? $report->status,
                'description' => $report->description,
                'purok' => $report->purok?->name,
            ],
            'summary' => $this->infoThreadSummary($report),
            'messages' => $this->infoThreadMessages($report),
        ]);
    }

    /**
     * @return array{count: int, awaiting_reply: bool, unread: int}
     */
    public function infoThreadSummary(Report $report): array
    {
        $requests = $report->infoRequests()->orderBy('created_at')->with('requester:id,name')->get();
        $last = $requests->last();
        $awaitingReply = $last !== null && $last->responded_at === null;

        return [
            'count' => $requests->count(),
            'awaiting_reply' => $awaitingReply,
            'unread' => $this->unreadInfoReplyCount($report, $requests),
        ];
    }

    /**
     * Staff questions and resident replies, oldest first, shaped for the chat log.
     *
     * @return array<int, array{id: int, sender: string, sender_name: string, text: string, at: string}>
     */
    private function infoThreadMessages(Report $report): array
    {
        $messages = [];
        $requests = $report->infoRequests()->orderBy('created_at')->orderBy('id')->with('requester:id,name')->get();

        foreach ($requests as $request) {
            $messages[] = [
                'id' => 'q'.$request->id,
                'sender' => 'staff',
                'sender_name' => $request->requester?->name ?? 'Staff',
                'text' => (string) $request->question,
                'at' => optional($request->created_at)->toIso8601String() ?? now()->toIso8601String(),
            ];

            if ($request->responded_at && $request->response) {
                $messages[] = [
                    'id' => 'r'.$request->id,
                    'sender' => 'resident',
                    'sender_name' => 'Resident',
                    'text' => (string) $request->response,
                    'at' => optional($request->responded_at)->toIso8601String() ?? now()->toIso8601String(),
                ];
            }
        }

        return $messages;
    }

    /** Resident replies newer than the staff member's last seen marker. */
    private function unreadInfoReplyCount(Report $report, ?iterable $requests = null): int
    {
        $seenAt = session('info_thread_seen.'.$report->id);
        $requests ??= $report->infoRequests()->whereNotNull('responded_at')->get();

        return collect($requests)
            ->filter(fn ($request) => $request->responded_at !== null && $request->response)
            ->filter(fn ($request) => $seenAt === null || $request->responded_at->gt($seenAt))
            ->count();
    }

    private function markInfoThreadSeen(Report $report): void
    {
        $latestReply = $report->infoRequests()
            ->whereNotNull('responded_at')
            ->orderByDesc('responded_at')
            ->value('responded_at');

        if ($latestReply !== null) {
            session(['info_thread_seen.'.$report->id => $latestReply]);
        }
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
        ReportStatusService $statusService,
        ProofPhotoService $proofPhotoService
    ): RedirectResponse {
        $proofConfig = config('reports.proof_photos');

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:2000'],
            // Legacy single-photo input; kept until the new multi-upload is verified.
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:10240'],
            'proof_photos' => ['nullable', 'array', 'max:'.$proofConfig['max_files']],
            'proof_photos.*' => [
                'file',
                'image',
                'mimes:'.$proofConfig['mimes'],
                'max:'.$proofConfig['max_size_kb'],
            ],
        ], [
            'proof_photos.max' => 'Upload at most '.$proofConfig['max_files'].' photos at a time.',
            'proof_photos.*.image' => 'Each proof photo must be an image.',
            'proof_photos.*.mimes' => 'Proof photos must be JPG, PNG, or WebP images.',
            'proof_photos.*.max' => 'Each proof photo must be smaller than '.round($proofConfig['max_size_kb'] / 1024).' MB.',
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $photoPath = null;
        if ($request->hasFile('photo')) {
            $photoPath = $request->file('photo')->store('resolution_proofs', 'local');
        }

        $proof = DB::transaction(function () use ($report, $validated, $reviewer, $photoPath, $proofPhotoService): ResolutionProof {
            $proof = $report->resolutionProofs()->create([
                'user_id' => $reviewer->id,
                'note' => $validated['note'],
                'photo_path' => $photoPath,
            ]);

            $files = array_values($validated['proof_photos'] ?? []);
            if ($files !== []) {
                $proofPhotoService->storeMany($report, $files, $reviewer);
            }

            return $proof;
        });

        $statusService->transition(
            $report,
            ReportStatus::AWAITING_CONFIRMATION,
            'Resolution proposed with proof: '.str($validated['note'])->limit(120),
            $reviewer
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

    public function storeUpdate(
        StoreReportUpdateRequest $request,
        Report $report,
        ReportStatusService $statusService
    ): RedirectResponse {
        $validated = $request->validated();
        $user = $request->user();

        $updateTypeConfig = Report::UPDATE_TYPES[$validated['update_type']] ?? null;
        abort_unless($updateTypeConfig !== null, 422);

        $targetStatus = $updateTypeConfig['status'];
        $message = $validated['message'] ?: ($updateTypeConfig['template'] ?? null);
        $expectedDate = $validated['expected_completion_date'] ?? null;
        $visibleToResident = $request->boolean('visible_to_resident', true);

        DB::transaction(function () use (
            $request,
            $report,
            $targetStatus,
            $message,
            $user,
            $validated,
            $expectedDate,
            $visibleToResident,
            $statusService
        ): void {
            $storedAttachments = [];
            if ($request->hasFile('photos')) {
                foreach ($request->file('photos') as $photoFile) {
                    if ($photoFile && $photoFile->isValid()) {
                        $storedAttachments[] = $photoFile->store('progress_photos', 'public');
                    }
                }
            }

            $statusService->transition(
                $report,
                $targetStatus,
                $message,
                $user,
                [
                    'update_type' => $validated['update_type'],
                    'expected_completion_date' => $expectedDate,
                    'visible_to_resident' => $visibleToResident,
                    'office_id' => $report->office_id,
                    'attachments' => $storedAttachments,
                ]
            );
        });

        return back()->with('success', 'Progress update posted successfully.');
    }

    public function reopenReport(
        Request $request,
        Report $report,
        ReportStatusService $statusService
    ): RedirectResponse {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['admin', 'staff'], true), 403);

        $allowedReopen = [ReportStatus::RESOLVED, ReportStatus::CLOSED, 'out_of_scope'];
        abort_unless(in_array($report->status, $allowedReopen, true), 422);

        $validated = $request->validate([
            'reason' => ['required', 'string', 'min:5', 'max:1000'],
        ], [
            'reason.required' => 'A reason is required to reopen this report.',
            'reason.min' => 'The reopen reason must be at least 5 characters.',
        ]);

        $statusService->transition(
            $report,
            ReportStatus::IN_PROGRESS,
            'Report reopened by staff: '.$validated['reason'],
            $user,
            [
                'visible_to_resident' => true,
                'office_id' => $report->office_id,
            ]
        );

        return back()->with('success', 'Report reopened back to In Progress.');
    }

    public function bulkConfirm(
        Request $request,
        RoutingService $routingService,
        PriorityService $priorityService,
        ReportStatusService $statusService
    ): RedirectResponse {
        $validated = $request->validate([
            'report_ids' => ['required', 'array'],
            'report_ids.*' => ['integer', 'exists:reports,id'],
        ]);

        $threshold = (float) Setting::value('ai_confidence_threshold', 0.8);
        $confirmed = 0;

        foreach ($validated['report_ids'] as $id) {
            $report = Report::query()->with(['category', 'aiResult', 'duplicateSuggestions'])->find($id);
            if (! $report) {
                continue;
            }
            $ai = $report->aiResult;
            $ready = $report->ai_status === 'done' && $ai && $ai->error === null
                && (float) $ai->confidence >= $threshold
                && $report->category_id && $report->urgency
                && $report->getRecommendedOffice() && ! $report->is_emergency
                && $report->duplicateSuggestions->where('decision', 'pending')->isEmpty();
            if (! $ready) {
                continue;
            }

            DB::transaction(function () use ($report, $routingService, $priorityService, $statusService, $request) {
                if (! $report->office_id && $report->category) {
                    $recommendedOffice = $routingService->recommendedOffice($report->category);
                    $report->office_id = $recommendedOffice?->id;
                }
                $report->priority_score = $priorityService->calculate($report);
                $report->save();
                $statusService->transition($report, 'routed', 'Confirmed and routed via bulk review.', $request->user());
            });
            $confirmed++;
        }

        return back()->with('success', "Confirmed and routed {$confirmed} report(s).");
    }

    /**
     * Bulk quick actions from the queue's selection bar: assign to me or a
     * status change. Every row runs through the same services (audit logs and
     * status logs stay intact); disallowed transitions are skipped per row.
     */
    public function bulkAction(
        Request $request,
        RoutingService $routingService,
        PriorityService $priorityService,
        ReportStatusService $statusService
    ): RedirectResponse {
        $validated = $request->validate([
            'action' => ['required', Rule::in(['assign_me', 'status'])],
            'report_ids' => ['required', 'array', 'min:1'],
            'report_ids.*' => ['integer', 'exists:reports,id'],
            'status' => ['nullable', Rule::in(Report::MANUAL_STATUSES)],
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $user = $request->user();
        abort_unless($user instanceof User, 403);

        if ($validated['action'] === 'status' && ($validated['status'] ?? null) === null) {
            return back()->withErrors(['status' => 'Choose a status for the selected reports.']);
        }

        $note = $validated['note'] ?? null;
        $changed = 0;
        $skipped = 0;

        foreach (Report::query()->whereIn('id', $validated['report_ids'])->get() as $report) {
            try {
                if ($validated['action'] === 'assign_me') {
                    $report->assigned_to = $user->id;
                    $report->save();
                } else {
                    $toStatus = $validated['status'];
                    $report->office_id = $report->office_id
                        ?? ($report->category ? $routingService->recommendedOffice($report->category)?->id : null);
                    $report->priority_score = $priorityService->calculate($report);
                    $report->save();

                    $office = Office::find($report->office_id);
                    if ($toStatus === 'routed' && $report->category && $office && $office->is_external) {
                        $this->createLegacyReferral($report, $office, $user, $routingService);
                    }

                    $statusService->transition($report, $toStatus, $note, $user);
                }
                $changed++;
            } catch (\InvalidArgumentException) {
                $skipped++;
            }
        }

        $message = $validated['action'] === 'assign_me'
            ? "Assigned {$changed} report(s) to you."
            : "Updated status for {$changed} report(s).";

        if ($skipped > 0) {
            $message .= " {$skipped} skipped (invalid transition).";
        }

        return back()->with('success', $message);
    }

    /** Legacy referral creation kept only for the routed-to-external case. */
    private function createLegacyReferral(Report $report, Office $office, User $user, RoutingService $routingService): void
    {
        $routingService->createReferral($report, $office, $user);
    }

    /** CSV export of the current queue (same filters, no pagination). */
    public function export(Request $request): StreamedResponse
    {
        $validated = $this->validateQueueFilters($request);

        $statusFilter = $validated['status'] ?? null;
        $emergencyOnly = ($validated['emergency'] ?? null) === '1';
        $currentStage = $validated['stage'] ?? 'all';
        $assignedToFilter = $validated['assigned_to'] ?? null;

        if ($statusFilter !== null && $statusFilter !== 'all' && $statusFilter !== 'emergency_flagged') {
            $currentStage = $this->stageForStatus($statusFilter) ?? $currentStage;
        }

        $query = $this->queueQuery($validated, $currentStage, $statusFilter, $emergencyOnly, $assignedToFilter);
        // The CSV export is for staff use, so reporter details are allowed here
        // (they stay out of the queue table per the privacy policy).
        $query->addSelect('reporter_name');
        $this->applyQueueOrder($query, $validated);

        $filename = 'calarian-reports-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Tracking code', 'Description', 'Category', 'Reporter', 'Purok', 'Status',
                'Urgency', 'Priority', 'Assignee', 'Submitted', 'Due',
            ]);

            $query->chunk(500, function ($reports) use ($out): void {
                foreach ($reports as $report) {
                    fputcsv($out, [
                        $report->tracking_code,
                        $report->summary ?: $report->description,
                        $report->category?->name ?? '',
                        $report->is_anonymous ? 'Anonymous' : ($report->reporter_name ?? ''),
                        $report->purok?->name ?? '',
                        Report::STATUSES[$report->status] ?? $report->status,
                        $report->urgency ?? '',
                        $report->priority_score ?? '',
                        $report->assignee?->name ?? '',
                        optional($report->created_at)->format('Y-m-d H:i:s'),
                        optional($report->due_at)->format('Y-m-d H:i:s'),
                    ]);
                }
            });

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function acceptReview(
        Request $request,
        Report $report,
        ReportReviewService $reportReviewService,
        ReportStatusService $statusService
    ): RedirectResponse {
        $activeCategoryIds = Category::query()->where('is_active', true)->pluck('id')->all();

        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::in($activeCategoryIds)],
            'urgency' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'staff_description' => ['required', 'string', 'max:2000'],
            'purok_id' => ['nullable', 'integer', Rule::exists('puroks', 'id')],
            'landmark' => ['nullable', 'string', 'max:255'],
        ], [
            'category_id.required' => 'Select a category before saving the review.',
            'urgency.required' => 'Select an urgency before saving the review.',
            'staff_description.required' => 'Enter a reviewed description before saving.',
        ]);

        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        $aiResult = $report->aiResult;
        $photoMismatch = $aiResult?->photo_mismatch ?? (bool) $report->photo_mismatch;
        $isEmergency = $aiResult?->emergency_flag ?? (bool) $report->is_emergency;

        $reportReviewService->save($report, [
            'category_id' => (int) $validated['category_id'],
            'urgency' => $validated['urgency'],
            'staff_description' => $validated['staff_description'],
            'photo_mismatch' => $photoMismatch,
            'is_emergency' => $isEmergency,
            'review_note' => null,
            'purok_id' => $validated['purok_id'] ?? $report->purok_id,
            'landmark' => $validated['landmark'] ?? $report->landmark,
        ], $reviewer);

        $report->refresh();
        if (in_array($report->status, ['submitted', 'reopened'], true)) {
            try {
                $statusService->transition($report, 'under_review', 'Review accepted by '.$reviewer->name, $reviewer);
            } catch (Throwable $e) {
                // ignore if not allowed
            }
        }

        return back()->with('success', 'Review accepted.');
    }

    public function routeReport(
        Request $request,
        Report $report,
        ReportStatusService $statusService
    ): RedirectResponse {
        $reviewer = $request->user();
        abort_unless($reviewer instanceof User, 403);

        if (! $report->category_id || ! $report->urgency) {
            return back()->withErrors(['category_id' => 'Category and urgency are required before routing.'])->withInput();
        }

        $validated = $request->validate([
            'office_id' => ['nullable', 'exists:offices,id'],
            'assigned_to' => ['nullable', Rule::exists('users', 'id')->where(fn ($q) => $q->whereIn('role', ['admin', 'staff']))],
            'review_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $recommended = $report->category ? app(RoutingService::class)->recommendedOffice($report->category) : null;
        $oldOfficeId = $report->office_id;

        $report->office_id = $validated['office_id'] ?: ($recommended?->id ?? $report->office_id);
        $report->assigned_to = array_key_exists('assigned_to', $validated) && $validated['assigned_to'] !== null
            ? (int) $validated['assigned_to']
            : $report->assigned_to;
        if ($report->assigned_to === null && $recommended) {
            // default assignee: current user fallback
            $report->assigned_to = $reviewer->id;
        }
        $report->review_note = $validated['review_note'] ?? $report->review_note;
        $report->save();

        if ($oldOfficeId !== $report->office_id) {
            AuditLog::query()->create([
                'user_id' => $reviewer->id,
                'action' => 'office_override',
                'model' => Report::class,
                'model_id' => $report->id,
                'old_values' => ['office_id' => $oldOfficeId],
                'new_values' => ['office_id' => $report->office_id],
            ]);
        }

        try {
            $statusService->transition($report, 'routed', $validated['review_note'] ?: 'Confirmed and routed by staff.', $reviewer);
        } catch (Throwable $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Confirmed and routed.');
    }

    public function acceptAndRoute(
        Request $request,
        Report $report,
        ReportReviewService $reportReviewService,
        ReportStatusService $statusService,
        RoutingService $routingService
    ): RedirectResponse {
        // perform accept then route in one request
        $request->merge([]);
        $this->acceptReview($request, $report, $reportReviewService, $statusService);
        $report->refresh();
        $this->routeReport($request, $report, $statusService);

        return back()->with('success', 'Accepted and routed.');
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
