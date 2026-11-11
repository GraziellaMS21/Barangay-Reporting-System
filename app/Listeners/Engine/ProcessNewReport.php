<?php

namespace App\Listeners\Engine;

use App\Events\ReportSubmitted;
use App\Services\GeminiService;

class ProcessNewReport
{
    public function handle(ReportSubmitted $event): void
    {
        if (class_exists(GeminiService::class)) {
            app(GeminiService::class)->analyze($event->report);
        }
    }
}
