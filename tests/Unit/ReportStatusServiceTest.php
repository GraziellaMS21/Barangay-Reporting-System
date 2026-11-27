<?php

namespace Tests\Unit;

use App\Services\ReportStatusService;
use App\Support\ReportStatus;
use PHPUnit\Framework\TestCase;

class ReportStatusServiceTest extends TestCase
{
    private ReportStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ReportStatusService;
    }

    public function test_can_transition_returns_true_for_valid_transitions(): void
    {
        // Submitted can move to under_review, routed, needs_info, referred, merged, no_action, emergency_flagged
        $this->assertTrue($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::UNDER_REVIEW));
        $this->assertTrue($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::ROUTED));
        $this->assertTrue($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::NEEDS_INFO));
        $this->assertTrue($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::REFERRED));
        $this->assertTrue($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::MERGED));
        $this->assertTrue($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::NO_ACTION));
        $this->assertTrue($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::EMERGENCY_FLAGGED));

        // In Progress can move to awaiting_confirmation, needs_info, referred, no_action, emergency_flagged
        $this->assertTrue($this->service->canTransition(ReportStatus::IN_PROGRESS, ReportStatus::AWAITING_CONFIRMATION));
        $this->assertTrue($this->service->canTransition(ReportStatus::IN_PROGRESS, ReportStatus::NEEDS_INFO));
        $this->assertTrue($this->service->canTransition(ReportStatus::IN_PROGRESS, ReportStatus::REFERRED));
        $this->assertTrue($this->service->canTransition(ReportStatus::IN_PROGRESS, ReportStatus::NO_ACTION));
        $this->assertTrue($this->service->canTransition(ReportStatus::IN_PROGRESS, ReportStatus::EMERGENCY_FLAGGED));

        // Awaiting confirmation can be confirmed (resolved) or disputed (reopened)
        $this->assertTrue($this->service->canTransition(ReportStatus::AWAITING_CONFIRMATION, ReportStatus::RESOLVED));
        $this->assertTrue($this->service->canTransition(ReportStatus::AWAITING_CONFIRMATION, ReportStatus::REOPENED));

        // Resolved can reopen or resume in_progress
        $this->assertTrue($this->service->canTransition(ReportStatus::RESOLVED, ReportStatus::IN_PROGRESS));
        $this->assertTrue($this->service->canTransition(ReportStatus::RESOLVED, ReportStatus::REOPENED));

        // No action can be resumed by staff
        $this->assertTrue($this->service->canTransition(ReportStatus::NO_ACTION, ReportStatus::IN_PROGRESS));
        $this->assertTrue($this->service->canTransition(ReportStatus::NO_ACTION, ReportStatus::REOPENED));

        // Merged can be recovered back to under_review
        $this->assertTrue($this->service->canTransition(ReportStatus::MERGED, ReportStatus::UNDER_REVIEW));

        // Emergency flagged can exit to no_action, under_review, or in_progress (locally handled)
        $this->assertTrue($this->service->canTransition(ReportStatus::EMERGENCY_FLAGGED, ReportStatus::NO_ACTION));
        $this->assertTrue($this->service->canTransition(ReportStatus::EMERGENCY_FLAGGED, ReportStatus::UNDER_REVIEW));
        $this->assertTrue($this->service->canTransition(ReportStatus::EMERGENCY_FLAGGED, ReportStatus::IN_PROGRESS));
    }

    public function test_can_transition_returns_true_for_allowed_self_transitions(): void
    {
        // in_progress -> in_progress (progress note without changing status)
        $this->assertTrue($this->service->canTransition(ReportStatus::IN_PROGRESS, ReportStatus::IN_PROGRESS));

        // needs_info -> needs_info (follow-up inquiry without changing status)
        $this->assertTrue($this->service->canTransition(ReportStatus::NEEDS_INFO, ReportStatus::NEEDS_INFO));
    }

    public function test_can_transition_returns_false_for_disallowed_self_transitions(): void
    {
        $this->assertFalse($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::SUBMITTED));
        $this->assertFalse($this->service->canTransition(ReportStatus::UNDER_REVIEW, ReportStatus::UNDER_REVIEW));
        $this->assertFalse($this->service->canTransition(ReportStatus::ROUTED, ReportStatus::ROUTED));
        $this->assertFalse($this->service->canTransition(ReportStatus::RESOLVED, ReportStatus::RESOLVED));
        $this->assertFalse($this->service->canTransition(ReportStatus::REFERRED, ReportStatus::REFERRED));
        $this->assertFalse($this->service->canTransition(ReportStatus::MERGED, ReportStatus::MERGED));
        $this->assertFalse($this->service->canTransition(ReportStatus::NO_ACTION, ReportStatus::NO_ACTION));
        $this->assertFalse($this->service->canTransition(ReportStatus::EMERGENCY_FLAGGED, ReportStatus::EMERGENCY_FLAGGED));
        $this->assertFalse($this->service->canTransition(ReportStatus::REOPENED, ReportStatus::REOPENED));
        $this->assertFalse($this->service->canTransition(ReportStatus::AWAITING_CONFIRMATION, ReportStatus::AWAITING_CONFIRMATION));
    }

    public function test_can_transition_returns_false_for_invalid_transitions(): void
    {
        // Submitted cannot jump directly to resolved or awaiting_confirmation
        $this->assertFalse($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::RESOLVED));
        $this->assertFalse($this->service->canTransition(ReportStatus::SUBMITTED, ReportStatus::AWAITING_CONFIRMATION));

        // In Progress cannot jump directly to resolved without awaiting_confirmation
        $this->assertFalse($this->service->canTransition(ReportStatus::IN_PROGRESS, ReportStatus::RESOLVED));

        // Resolved cannot jump directly to routed or submitted
        $this->assertFalse($this->service->canTransition(ReportStatus::RESOLVED, ReportStatus::ROUTED));
        $this->assertFalse($this->service->canTransition(ReportStatus::RESOLVED, ReportStatus::SUBMITTED));

        // Unknown statuses
        $this->assertFalse($this->service->canTransition('unknown_status', ReportStatus::IN_PROGRESS));
        $this->assertFalse($this->service->canTransition(ReportStatus::SUBMITTED, 'unknown_target'));
    }
}
