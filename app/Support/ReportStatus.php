<?php

namespace App\Support;

class ReportStatus
{
    public const SUBMITTED = 'submitted';

    public const UNDER_REVIEW = 'under_review';

    public const ROUTED = 'routed';

    public const IN_PROGRESS = 'in_progress';

    public const RESOLVED = 'resolved';

    public const REFERRED = 'referred';

    public const MERGED = 'merged';

    public const NO_ACTION = 'no_action';

    public const EMERGENCY_FLAGGED = 'emergency_flagged';

    public const NEEDS_INFO = 'needs_info';

    public const AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public const REOPENED = 'reopened';

    public const REASON_NOT_BARANGAY = 'not_barangay_matter';

    public const REASON_EMERGENCY_REDIRECTED = 'emergency_redirected';

    public const REASON_CANNOT_RESOLVE = 'cannot_resolve';

    public const REASON_INVALID_REPORT = 'invalid_report';

    public const REASON_RESIDENT_UNREACHABLE = 'resident_unreachable';

    public const REASON_CONVERTED_TO_CASE = 'converted_to_case';

    public static function noActionReasons(): array
    {
        return [
            self::REASON_NOT_BARANGAY,
            self::REASON_EMERGENCY_REDIRECTED,
            self::REASON_CANNOT_RESOLVE,
            self::REASON_INVALID_REPORT,
            self::REASON_RESIDENT_UNREACHABLE,
            self::REASON_CONVERTED_TO_CASE,
        ];
    }

    public static function all(): array
    {
        return [
            self::SUBMITTED,
            self::UNDER_REVIEW,
            self::ROUTED,
            self::IN_PROGRESS,
            self::RESOLVED,
            self::REFERRED,
            self::MERGED,
            self::NO_ACTION,
            self::EMERGENCY_FLAGGED,
            self::NEEDS_INFO,
            self::AWAITING_CONFIRMATION,
            self::REOPENED,
        ];
    }
}
