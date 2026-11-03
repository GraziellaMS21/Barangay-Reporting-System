<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Office;
use App\Models\Purok;
use App\Models\Report;
use App\Models\ReportPhoto;
use App\Services\PriorityService;
use App\Services\RoutingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request): View
    {
        $validated = $request->validate([
            'stage' => ['nullable', Rule::in(array_keys(Report::STATUS_GROUPS))],
            'status' => ['nullable', Rule::in(['all', ...array_keys(Report::STATUSES)])],
            'emergency' => ['nullable', Rule::in(['0', '1'])],
            'purok_id' => ['nullable', 'integer', Rule::exists('puroks', 'id')],
            'q' => ['nullable', 'string', 'max:255'],
        ]);
        $statusFilter = $validated['status'] ?? null;
        $emergencyOnly = ($validated['emergency'] ?? null) === '1';
        $currentStage = $validated['stage'] ?? 'all';

        if ($statusFilter === 'emergency_flagged') {
            $emergencyOnly = true;
            $statusFilter = null;
            $currentStage = 'all';
        } elseif ($statusFilter !== null && $statusFilter !== 'all') {
            $currentStage = $this->stageForStatus($statusFilter) ?? $currentStage;
        }

        $query = Report::query()
            ->select(['id', 'tracking_code', 'description', 'purok_id', 'status', 'is_emergency', 'created_at'])
            ->with('purok:id,name');

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

        if (isset($validated['q'])) {
            $term = addcslashes($validated['q'], '%_\\');
            $query->where(function ($sub) use ($term) {
                $sub->where('tracking_code', 'like', "%{$term}%")
                    ->orWhere('description', 'like', "%{$term}%");
            });
        }

        $closedStatusesList = "'".implode("','", Report::CLOSED_STATUSES)."'";
        $query->orderByDesc('is_emergency')
            ->orderByRaw('CASE WHEN status = ? THEN 0 ELSE 1 END', ['emergency_flagged'])
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

        return view('admin.reports.index', compact(
            'reports',
            'statusCounts',
            'statusGroups',
            'statusLabels',
            'stageCounts',
            'emergencyCount',
            'puroks',
            'currentStage',
            'statusFilter',
            'emergencyOnly'
        ));
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
        $report->load(['purok', 'statusLogs.user', 'aiResult.confirmedBy', 'category', 'photos']);
        $manualStatuses = Report::MANUAL_STATUSES;
        $categories = Category::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);
        $offices = Office::query()->orderBy('name')->get();
        $recommendedOffice = $report->category ? $routingService->recommendedOffice($report->category) : null;

        return view('admin.reports.show', compact('report', 'manualStatuses', 'categories', 'offices', 'recommendedOffice'));
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

    public function confirmAiResult(Request $request, Report $report): RedirectResponse
    {
        $aiResult = $report->aiResult()->whereNull('error')->firstOrFail();
        $activeCategorySlugs = Category::query()
            ->where('is_active', true)
            ->pluck('slug')
            ->all();
        $validated = $request->validate([
            'suggested_category' => ['required', Rule::in([...$activeCategorySlugs, 'unclassified'])],
            'suggested_urgency' => ['required', Rule::in(['Low', 'Medium', 'High', 'Critical'])],
            'summary' => ['required', 'string', 'max:500'],
            'photo_description' => ['nullable', 'string', 'max:1000'],
            'photo_mismatch' => ['required', 'boolean'],
            'emergency_flag' => ['required', 'boolean'],
            'location_details' => ['nullable', 'string', 'max:255'],
            'key_facts_text' => ['nullable', 'string', 'max:2000'],
            'confidence' => ['required', 'numeric', 'between:0,1'],
            'confirmation_note' => ['required', 'string', 'max:500'],
        ]);
        $keyFacts = collect(preg_split('/\R/u', $validated['key_facts_text'] ?? ''))
            ->map(fn (string $fact): string => trim($fact))
            ->filter()
            ->values();

        if ($keyFacts->count() > 8 || $keyFacts->contains(fn (string $fact): bool => mb_strlen($fact) > 250)) {
            return back()
                ->withErrors(['key_facts_text' => 'Enter up to 8 key facts, with no more than 250 characters per fact.'])
                ->withInput();
        }

        $categoryId = $validated['suggested_category'] === 'unclassified'
            ? null
            : Category::query()
                ->where('slug', $validated['suggested_category'])
                ->where('is_active', true)
                ->value('id');

        DB::transaction(function () use ($aiResult, $categoryId, $keyFacts, $request, $report, $validated): void {
            $auditedAiFields = [
                'suggested_category',
                'suggested_urgency',
                'summary',
                'photo_description',
                'photo_mismatch',
                'emergency_flag',
                'location_details',
                'key_facts',
                'confidence',
                'confirmed_by',
                'confirmed_at',
                'confirmation_note',
            ];
            $oldValues = $aiResult->only($auditedAiFields) + [
                'report' => $report->only(['category_id', 'urgency', 'summary', 'is_emergency']),
            ];

            $aiResult->update([
                'suggested_category' => $validated['suggested_category'],
                'suggested_urgency' => $validated['suggested_urgency'],
                'summary' => $validated['summary'],
                'photo_description' => $validated['photo_description'] ?? null,
                'photo_mismatch' => $request->boolean('photo_mismatch'),
                'emergency_flag' => $request->boolean('emergency_flag'),
                'location_details' => $validated['location_details'] ?? null,
                'key_facts' => $keyFacts->all(),
                'confidence' => $validated['confidence'],
                'confirmed_by' => $request->user()->id,
                'confirmed_at' => now(),
                'confirmation_note' => $validated['confirmation_note'],
            ]);

            $report->update([
                'category_id' => $categoryId,
                'urgency' => $validated['suggested_urgency'],
                'summary' => $validated['summary'],
                'is_emergency' => $request->boolean('emergency_flag'),
            ]);

            AuditLog::query()->create([
                'user_id' => $request->user()->id,
                'action' => 'ai_override',
                'model' => $aiResult::class,
                'model_id' => $aiResult->id,
                'old_values' => $oldValues,
                'new_values' => $aiResult->only($auditedAiFields) + [
                    'report' => $report->only(['category_id', 'urgency', 'summary', 'is_emergency']),
                ],
            ]);
        });

        return back()->with('success', 'Staff-reviewed AI suggestions saved to the report.');
    }

    public function updateStatus(Request $request, Report $report, RoutingService $routingService, PriorityService $priorityService): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(Report::MANUAL_STATUSES)],
            'note' => ['required', 'string', 'max:500'],
            'office_id' => ['nullable', 'exists:offices,id'],
        ], [
            'note.required' => 'A note is required for every status change.',
        ]);

        DB::transaction(function () use ($report, $validated, $request, $routingService, $priorityService) {
            $fromStatus = $report->status;
            $toStatus = $validated['status'];
            $note = $validated['note'] ?? null;
            $officeId = $validated['office_id'] ?? null;

            if ($officeId) {
                $report->office_id = $officeId;
            } elseif ($report->category) {
                $recommendedOffice = $routingService->recommendedOffice($report->category);
                $report->office_id = $recommendedOffice?->id ?? $report->office_id;
            }

            $report->priority_score = $priorityService->calculate($report);
            $report->status = $toStatus;
            $report->save();

            if ($toStatus === 'routed' && $report->category && $report->office_id) {
                $routingService->createReferral($report, Office::find($report->office_id));
            }

            $report->statusLogs()->create([
                'user_id' => $request->user()->id,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'note' => $note,
            ]);
        });

        return back()->with('success', 'Report status updated successfully.');
    }
}
