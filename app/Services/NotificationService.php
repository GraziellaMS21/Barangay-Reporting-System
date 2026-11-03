<?php

namespace App\Services;

use App\Models\NotificationLog;
use App\Models\Report;

class NotificationService
{
    public function log(Report $report, string $type, string $recipient, string $status = 'sent', ?string $error = null): NotificationLog
    {
        return NotificationLog::create([
            'report_id' => $report->id,
            'type' => $type,
            'recipient' => $recipient,
            'status' => $status,
            'error' => $error,
        ]);
    }
}
