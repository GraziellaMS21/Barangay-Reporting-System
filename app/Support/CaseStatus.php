<?php

namespace App\Support;

class CaseStatus
{
    public const FILED = 'filed';

    public const IN_MEDIATION = 'in_mediation';

    public const SETTLED = 'settled';

    public const UNSETTLED = 'unsettled';

    public const ESCALATED = 'escalated';

    public const DISMISSED = 'dismissed';

    public const WITHDRAWN = 'withdrawn';

    /**
     * @return array<int, string>
     */
    public static function all(): array
    {
        return [
            self::FILED,
            self::IN_MEDIATION,
            self::SETTLED,
            self::UNSETTLED,
            self::ESCALATED,
            self::DISMISSED,
            self::WITHDRAWN,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function labels(): array
    {
        return [
            self::FILED => 'Filed',
            self::IN_MEDIATION => 'In Mediation',
            self::SETTLED => 'Settled',
            self::UNSETTLED => 'Unsettled',
            self::ESCALATED => 'Escalated',
            self::DISMISSED => 'Dismissed',
            self::WITHDRAWN => 'Withdrawn',
        ];
    }
}
