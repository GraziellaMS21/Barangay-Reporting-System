<?php

namespace App\Services;

use App\Models\Office;
use App\Models\Report;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;

/**
 * Centralises personnel/office responsibility changes for concerns.
 *
 * Assignment establishes responsibility only. It never changes a report's
 * status and therefore never implies acceptance for handling. Acceptance is
 * represented by the existing workflow statuses (`routed` for internal work
 * and `referred` for external agency work).
 */
class ReportAssignmentService
{
    public const PERMISSION = 'reports.assign';

    public function __construct(private readonly AuditLogger $auditLogger) {}

    /**
     * Validate and persist an assignment change.
     *
     * `$changes` may contain `assigned_to` and/or `office_id`. A key that is
     * absent means "leave unchanged"; an explicit null clears the value.
     *
     * @param  array{assigned_to?: int|null, office_id?: int|null}  $changes
     * @return bool True when at least one responsibility field changed.
     *
     * @throws AuthorizationException
     * @throws \InvalidArgumentException
     */
    public function update(
        Report $report,
        User $actor,
        array $changes,
        ?string $reason = null,
        ?string $officeMode = null,
        bool $enforcePermission = true
    ): bool {
        $report->loadMissing(['assignee:id,name', 'office:id,name']);

        $oldAssignedTo = $report->assigned_to;
        $oldOfficeId = $report->office_id;
        $oldAssigneeName = $report->assignee?->name;
        $oldOfficeName = $report->office?->name;

        $assigneeProvided = array_key_exists('assigned_to', $changes);
        $officeProvided = array_key_exists('office_id', $changes);

        $newAssignedTo = $assigneeProvided ? $this->normaliseId($changes['assigned_to']) : $oldAssignedTo;
        $newOfficeId = $officeProvided ? $this->normaliseId($changes['office_id']) : $oldOfficeId;

        $assigneeChanged = $assigneeProvided && $newAssignedTo !== $oldAssignedTo;
        $officeChanged = $officeProvided && $newOfficeId !== $oldOfficeId;

        if (! $assigneeChanged && ! $officeChanged) {
            return false;
        }

        if ($assigneeChanged && $enforcePermission) {
            $this->authorize($actor);
        }

        $newAssignee = $assigneeChanged && $newAssignedTo !== null
            ? $this->assertAssigneeEligible($newAssignedTo)
            : null;
        $newOffice = $officeChanged && $newOfficeId !== null
            ? $this->assertOfficeEligible($newOfficeId, $officeMode)
            : null;

        // Replacing (or clearing) an existing assignee must be explained.
        if ($assigneeChanged && $oldAssignedTo !== null && trim((string) $reason) === '') {
            throw new \InvalidArgumentException('Provide a reason for changing the assigned staff member.');
        }

        DB::transaction(function () use (
            $report,
            $newAssignedTo,
            $newOfficeId,
            $oldAssignedTo,
            $oldOfficeId,
            $oldAssigneeName,
            $oldOfficeName,
            $newAssignee,
            $newOffice,
            $assigneeChanged,
            $actor,
            $reason
        ): void {
            $report->assigned_to = $newAssignedTo;
            $report->office_id = $newOfficeId;
            $report->save();

            // Personnel responsibility is the assignment audit's subject. Office
            // changes are already captured by staff_review / office_override and
            // status logs, so only log when the assignee actually changes.
            if ($assigneeChanged) {
                $this->record(
                    $report,
                    $oldAssignedTo,
                    $oldOfficeId,
                    $actor,
                    $reason,
                    $oldAssigneeName,
                    $oldOfficeName,
                    $newAssignee?->name,
                    $newOffice?->name
                );
            }
        });

        return true;
    }

    /**
     * Write the canonical assignment audit entry when the report's assignee
     * differs from the supplied previous state. The entry also records the
     * old/new office context so responsibility changes are self-contained.
     */
    public function record(
        Report $report,
        ?int $oldAssignedTo,
        ?int $oldOfficeId,
        User $actor,
        ?string $reason = null,
        ?string $oldAssigneeName = null,
        ?string $oldOfficeName = null,
        ?string $newAssigneeName = null,
        ?string $newOfficeName = null
    ): void {
        $newAssignedTo = $report->assigned_to;
        $newOfficeId = $report->office_id;

        if ($newAssignedTo === $oldAssignedTo) {
            return;
        }

        $newAssigneeName ??= $newAssignedTo !== null
            ? User::query()->whereKey($newAssignedTo)->value('name')
            : null;
        $newOfficeName ??= $newOfficeId !== null
            ? Office::query()->whereKey($newOfficeId)->value('name')
            : null;

        $action = $oldAssignedTo !== null
            ? 'reassign_report'
            : 'assign_report';

        $oldValues = [
            'assigned_to' => $oldAssignedTo,
            'assignee_name' => $oldAssigneeName,
            'office_id' => $oldOfficeId,
            'office_name' => $oldOfficeName,
        ];
        $newValues = [
            'assigned_to' => $newAssignedTo,
            'assignee_name' => $newAssigneeName,
            'office_id' => $newOfficeId,
            'office_name' => $newOfficeName,
        ];

        $reason = trim((string) $reason);
        if ($reason !== '') {
            $newValues['reason'] = $reason;
        }

        $this->auditLogger->log($actor, $action, $report, $oldValues, $newValues);
    }

    /**
     * @throws AuthorizationException
     */
    public function authorize(User $actor): void
    {
        if (! $actor->hasPermission(self::PERMISSION)) {
            throw new AuthorizationException('You do not have permission to assign or reassign concerns.');
        }
    }

    /**
     * @throws \InvalidArgumentException
     */
    public function assertAssigneeEligible(int $assigneeId): User
    {
        $assignee = User::query()->find($assigneeId);

        if ($assignee === null || ! $assignee->is_active) {
            throw new \InvalidArgumentException('The selected staff member is inactive or unavailable.');
        }

        if (! in_array($assignee->role, ['admin', 'staff'], true) || $assignee->isAgency()) {
            throw new \InvalidArgumentException('Concerns can only be assigned to barangay staff or administrators.');
        }

        return $assignee;
    }

    /**
     * @param  'internal'|'external'|null  $mode
     *
     * @throws \InvalidArgumentException
     */
    public function assertOfficeEligible(int $officeId, ?string $mode = null): Office
    {
        $office = Office::query()->find($officeId);

        if ($office === null || ! $office->is_active) {
            throw new \InvalidArgumentException('The selected office is inactive or unavailable.');
        }

        if ($mode === 'internal' && ! ($office->is_internal && ! $office->is_external)) {
            throw new \InvalidArgumentException('Internal assignment requires an internal barangay office.');
        }

        if ($mode === 'external' && ! $office->is_external) {
            throw new \InvalidArgumentException('Referrals require an external agency.');
        }

        return $office;
    }

    private function normaliseId(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }
}
