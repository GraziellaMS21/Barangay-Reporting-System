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

    public const OUT_OF_SCOPE = 'out_of_scope';

    public const EMERGENCY_FLAGGED = 'emergency_flagged';

    public const NEEDS_INFO = 'needs_info';

    public const AWAITING_CONFIRMATION = 'awaiting_confirmation';

    public const REOPENED = 'reopened';

    public const CLOSED = 'closed';

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
            self::OUT_OF_SCOPE,
            self::EMERGENCY_FLAGGED,
            self::NEEDS_INFO,
            self::AWAITING_CONFIRMATION,
            self::REOPENED,
            self::CLOSED,
        ];
    }
}
