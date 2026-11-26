<?php

namespace App\Listeners\Engine;

use App\Events\ReportSubmitted;
use App\Models\Report;
use App\Services\DuplicateService;
use App\Services\GeminiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProcessNewReport implements ShouldQueue
{
    use InteractsWithQueue;

    public bool $afterCommit = true;

    public bool $deleteWhenMissingModels = true;

    public int $tries = 2;

    /**
     * @var list<int>
     */
    public array $backoff = [10];

    public function handle(ReportSubmitted $event): void
    {
        $report = $event->report->fresh();

        if (! $report || ! $report->exists) {
            return;
        }

        // If staff already took official review action, skip AI screening to protect official decisions,
        // while still allowing advisory duplicate detection for non-merged reports.
        if ($report->reviewed_at !== null) {
            $this->detectDuplicates($report);

            return;
        }

        $report->update(['ai_status' => 'processing']);

        if (class_exists(GeminiService::class)) {
            // When executing on a real asynchronous queue worker (non-sync connection),
            // allow transient errors to be rethrown if retry attempts remain.
            // On a sync connection (e.g. testing or sync driver), do not rethrow so
            // the HTTP lifecycle completes gracefully and marks failed.
            $isSync = ! $this->job || $this->job->getConnectionName() === 'sync';
            $throwOnTransient = ! $isSync && $this->attempts() < $this->tries;

            app(GeminiService::class)->analyze($report, $throwOnTransient);
        }

        $freshReport = $report->fresh();
        if (! $freshReport || ! $freshReport->exists) {
            return;
        }

        $this->detectDuplicates($freshReport);
    }

    private function detectDuplicates(Report $report): void
    {
        if ($report->status === 'merged') {
            return;
        }

        try {
            app(DuplicateService::class)->recordSuggestions($report);
        } catch (Throwable $exception) {
            Log::warning('Duplicate detection failed during asynchronous report processing.', [
                'report_id' => $report->id,
                'tracking_code' => $report->tracking_code,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);
        }
    }
}
