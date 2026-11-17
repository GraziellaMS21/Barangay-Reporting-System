<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Office;
use App\Models\Report;
use App\Models\ReportAiResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportReviewService
{
    public function __construct(
        private readonly PriorityService $priorityService,
        private readonly NotificationService $notificationService,
        private readonly RoutingService $routingService,
        private readonly ReportStatusService $statusService
    ) {}

    /**
     * Save staff-approved report values and optionally route or refer the report.
     *
     * @param  array{category_id: int, urgency: string, staff_description: string, purok_id: int|null, landmark: string|null, office_id: int|null, assigned_to: int|null, review_note: string|null}  $data
     */
    public function save(Report $report, array $data, User $reviewer, string $intent = 'save'): void
    {
        DB::transaction(function () use ($report, $data, $reviewer, $intent): void {
            $lockedReport = Report::query()
                ->whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();
            $category = Category::query()
                ->where('is_active', true)
                ->findOrFail($data['category_id']);
            $aiResult = $lockedReport->aiResult()->whereNull('error')->first();
            $routingDecision = $this->routingService->decisionForCategory($category);
            $selectedOffice = ($data['office_id'] ?? null)
                ? Office::query()->findOrFail($data['office_id'])
                : null;
            $oldValues = $this->reviewValues($lockedReport);

            if ($lockedReport->reviewed_at === null) {
                $lockedReport->resident_category_id ??= $lockedReport->category_id;
                $lockedReport->resident_purok_id ??= $lockedReport->purok_id;
                $lockedReport->resident_landmark ??= $lockedReport->landmark;
            }
            $lockedReport->category_id = $category->id;
            $lockedReport->setRelation('category', $category);
            $lockedReport->urgency = $data['urgency'];
            $lockedReport->staff_description = $data['staff_description'];
            $lockedReport->purok_id = array_key_exists('purok_id', $data)
                ? $data['purok_id']
                : $lockedReport->purok_id;
            $lockedReport->landmark = array_key_exists('landmark', $data)
                ? $data['landmark']
                : $lockedReport->landmark;
            $lockedReport->office_id = array_key_exists('office_id', $data)
                ? $selectedOffice?->id
                : $lockedReport->office_id;
            $lockedReport->assigned_to = array_key_exists('assigned_to', $data)
                ? $data['assigned_to']
                : $lockedReport->assigned_to;
            $lockedReport->reviewed_by = $reviewer->id;
            $lockedReport->reviewed_at = now();
            $lockedReport->review_note = array_key_exists('review_note', $data)
                ? $data['review_note']
                : $lockedReport->review_note;
            $lockedReport->priority_score = $this->priorityService->calculate($lockedReport);
            $lockedReport->save();

            $newValues = $this->reviewValues($lockedReport);
            if ($this->differsFromAi($data, $category, $aiResult, $routingDecision['office']?->id)) {
                AuditLog::query()->create([
                    'user_id' => $reviewer->id,
                    'action' => 'staff_review',
                    'model' => Report::class,
                    'model_id' => $lockedReport->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                ]);
            }

            $statusNote = $this->reviewSummary($data, $category, $aiResult, $routingDecision['office']?->id);
            if (($data['review_note'] ?? null) !== null && $data['review_note'] !== '') {
                $statusNote .= "\nMessage to resident: ".$data['review_note'];
            }

            if ($intent === 'route') {
                if ($lockedReport->is_emergency || $lockedReport->status === 'emergency_flagged') {
                    throw new \InvalidArgumentException('Emergency-flagged reports must be handled through the emergency banner.');
                }

                if (! in_array($lockedReport->status, ['submitted', 'under_review', 'reopened', 'needs_info', 'routed'], true)) {
                    throw new \InvalidArgumentException('This report is no longer in a review state that can be routed.');
                }

                if (! $selectedOffice) {
                    throw new \InvalidArgumentException('Choose an office before confirming the report.');
                }

                if (in_array($lockedReport->status, ['submitted', 'reopened', 'needs_info'], true)) {
                    $this->statusService->transition(
                        $lockedReport,
                        'under_review',
                        'Staff review completed.',
                        $reviewer
                    );
                }

                $targetStatus = $routingDecision['within_authority'] ? 'routed' : 'referred';
                if ($targetStatus === 'referred') {
                    $this->routingService->createReferral($lockedReport, $selectedOffice);
                }

                if ($lockedReport->status !== $targetStatus) {
                    $this->statusService->transition(
                        $lockedReport,
                        $targetStatus,
                        $statusNote,
                        $reviewer
                    );
                }
            } elseif (in_array($lockedReport->status, ['submitted', 'reopened', 'needs_info'], true)) {
                $this->statusService->transition(
                    $lockedReport,
                    'under_review',
                    $statusNote,
                    $reviewer
                );
            }
        });
    }

    public function resolveEmergency(Report $report, bool $isEmergency, User $reviewer): void
    {
        $shouldSendEmergencyAlert = DB::transaction(function () use ($report, $isEmergency, $reviewer): bool {
            $lockedReport = Report::query()
                ->whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();
            $aiResult = $lockedReport->aiResult()->whereNull('error')->first();
            $wasEmergency = $lockedReport->is_emergency || $lockedReport->status === 'emergency_flagged';

            $lockedReport->is_emergency = $isEmergency;
            if ($isEmergency) {
                $lockedReport->office_id = null;
                $lockedReport->priority_score = 0;
            } else {
                $lockedReport->loadMissing('category');
                $lockedReport->priority_score = $this->priorityService->calculate($lockedReport);
            }
            $lockedReport->save();

            if ($aiResult !== null && (bool) $aiResult->emergency_flag !== $isEmergency) {
                AuditLog::query()->create([
                    'user_id' => $reviewer->id,
                    'action' => 'emergency_flag_review',
                    'model' => Report::class,
                    'model_id' => $lockedReport->id,
                    'old_values' => ['emergency_flag' => (bool) $aiResult->emergency_flag],
                    'new_values' => ['is_emergency' => $isEmergency],
                ]);
            }

            $targetStatus = $isEmergency
                ? 'emergency_flagged'
                : ($lockedReport->status === 'emergency_flagged' ? 'under_review' : $lockedReport->status);
            if ($lockedReport->status !== $targetStatus) {
                $note = $isEmergency
                    ? 'Emergency concern confirmed by staff.'
                    : 'Staff determined the report is not an emergency.';
                $this->statusService->transition($lockedReport, $targetStatus, $note, $reviewer);
            }

            return $isEmergency && ! $wasEmergency;
        });

        if ($shouldSendEmergencyAlert) {
            $this->notificationService->sendEmergencyAlert($report->fresh());
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function reviewValues(Report $report): array
    {
        return [
            'category_id' => $report->category_id,
            'urgency' => $report->urgency,
            'staff_description' => $report->staff_description,
            'purok_id' => $report->purok_id,
            'landmark' => $report->landmark,
            'office_id' => $report->office_id,
            'assigned_to' => $report->assigned_to,
        ];
    }

    private function differsFromAi(
        array $data,
        Category $category,
        ?ReportAiResult $aiResult,
        ?int $recommendedOfficeId
    ): bool {
        if ($aiResult === null) {
            return true;
        }

        $aiCategoryId = $aiResult->suggested_category === 'unclassified'
            ? null
            : Category::query()
                ->where('is_active', true)
                ->where('slug', $aiResult->suggested_category)
                ->value('id');

        return $category->id !== $aiCategoryId
            || $data['urgency'] !== $aiResult->suggested_urgency
            || $data['staff_description'] !== ($aiResult->summary ?? '')
            || (array_key_exists('office_id', $data) && $data['office_id'] !== $recommendedOfficeId);
    }

    private function reviewSummary(
        array $data,
        Category $category,
        ?ReportAiResult $aiResult,
        ?int $recommendedOfficeId
    ): string {
        if ($aiResult === null) {
            return 'Review saved manually: Category, urgency, description, purok, and landmark reviewed.';
        }

        $aiCategoryId = $aiResult->suggested_category === 'unclassified'
            ? null
            : Category::query()
                ->where('is_active', true)
                ->where('slug', $aiResult->suggested_category)
                ->value('id');
        $decisions = [
            'Category '.($category->id === $aiCategoryId ? 'accepted' : 'edited'),
            'Urgency '.($data['urgency'] === $aiResult->suggested_urgency ? 'accepted' : 'edited'),
            'Description '.($data['staff_description'] === ($aiResult->summary ?? '') ? 'accepted' : 'edited'),
        ];

        if (array_key_exists('office_id', $data) && $data['office_id'] !== $recommendedOfficeId) {
            $decisions[] = 'Office changed from the routing recommendation';
        } else {
            $decisions[] = 'Office recommendation accepted';
        }

        return 'Review completed: '.implode('; ', $decisions).'.';
    }
}
