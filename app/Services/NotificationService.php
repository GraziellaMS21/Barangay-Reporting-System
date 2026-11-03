<?php

namespace App\Services;

use App\Mail\EmergencyAlert;
use App\Models\NotificationLog;
use App\Models\Report;
use App\Models\Setting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class NotificationService
{
    public function sendEmergencyAlert(Report $report): void
    {
        $recipients = collect(preg_split('/[\r\n,;]+/', (string) Setting::value('emergency_alert_emails', '')) ?: [])
            ->map(fn (string $recipient): string => trim($recipient))
            ->filter(fn (string $recipient): bool => filter_var($recipient, FILTER_VALIDATE_EMAIL) !== false)
            ->unique()
            ->values();

        if ($recipients->isEmpty()) {
            Log::warning('Emergency alert was not sent because no valid recipients are configured.', [
                'report_id' => $report->id,
            ]);
            $this->log($report, 'emergency_alert', '', 'failed', 'No valid alert recipients are configured.');

            return;
        }

        $report->loadMissing('purok');

        foreach ($recipients as $recipient) {
            try {
                Mail::to($recipient)->send(new EmergencyAlert($report));
                $this->log($report, 'emergency_alert', $recipient);
            } catch (Throwable $exception) {
                Log::error('Emergency alert delivery failed.', [
                    'report_id' => $report->id,
                    'exception' => $exception::class,
                ]);
                $this->log($report, 'emergency_alert', $recipient, 'failed', $exception::class);
            }
        }
    }

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
