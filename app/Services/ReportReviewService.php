<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Report;
use App\Models\ReportAiResult;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ReportReviewService
{
    public function __construct(
        private readonly PriorityService $priorityService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * Save staff-approved report values while preserving the resident text and AI suggestion.
     *
     * @param  array{category_id: int, urgency: string, staff_description: string, photo_mismatch: bool, is_emergency: bool, review_note?: string|null}  $data
     */
    public function save(Report $report, array $data, User $reviewer): void
    {
        $shouldSendEmergencyAlert = DB::transaction(function () use ($report, $data, $reviewer): bool {
            $lockedReport = Report::query()
                ->whereKey($report->id)
                ->lockForUpdate()
                ->firstOrFail();
            $category = Category::query()
                ->where('is_active', true)
                ->findOrFail($data['category_id']);
            $aiResult = $lockedReport->aiResult()->whereNull('error')->first();
            $oldValues = $this->reviewValues($lockedReport);
            $wasEmergency = $lockedReport->is_emergency || $lockedReport->status === 'emergency_flagged';
            $isEmergency = $data['is_emergency'];

            $lockedReport->category_id = $category->id;
            $lockedReport->setRelation('category', $category);
            $lockedReport->urgency = $data['urgency'];
            $lockedReport->staff_description = $data['staff_description'];
            $lockedReport->photo_mismatch = $data['photo_mismatch'];
            $lockedReport->is_emergency = $isEmergency;
            $lockedReport->reviewed_by = $reviewer->id;
            $lockedReport->reviewed_at = now();
            $lockedReport->review_note = $data['review_note'] ?? null;

            if ($isEmergency) {
                $lockedReport->status = 'emergency_flagged';
                $lockedReport->office_id = null;
                $lockedReport->priority_score = 0;
            } else {
                if ($lockedReport->status === 'emergency_flagged') {
                    $lockedReport->status = 'under_review';
                }

                $lockedReport->priority_score = $this->priorityService->calculate($lockedReport);
            }

            $lockedReport->save();
            $newValues = $this->reviewValues($lockedReport);

            if ($this->differsFromAi($data, $category, $aiResult)) {
                AuditLog::query()->create([
                    'user_id' => $reviewer->id,
                    'action' => 'staff_review',
                    'model' => Report::class,
                    'model_id' => $lockedReport->id,
                    'old_values' => $oldValues,
                    'new_values' => $newValues,
                ]);
            }

            if ($oldValues['status'] !== $newValues['status']) {
                $lockedReport->statusLogs()->create([
                    'user_id' => $reviewer->id,
                    'from_status' => $oldValues['status'],
                    'to_status' => $newValues['status'],
                    'note' => $data['review_note'] ?? 'Status updated during staff review.',
                ]);
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
            'photo_mismatch' => $report->photo_mismatch,
            'is_emergency' => $report->is_emergency,
            'review_note' => $report->review_note,
            'status' => $report->status,
            'office_id' => $report->office_id,
            'priority_score' => $report->priority_score,
        ];
    }

    private function differsFromAi(array $data, Category $category, ?ReportAiResult $aiResult): bool
    {
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
            || $data['photo_mismatch'] !== (bool) $aiResult->photo_mismatch
            || $data['is_emergency'] !== (bool) $aiResult->emergency_flag;
    }
}
