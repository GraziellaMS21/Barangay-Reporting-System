<?php

namespace App\Events;

use App\Models\Feedback;
use App\Models\Report;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResolutionConfirmed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Report $report, public bool $confirmed, public ?Feedback $feedback = null) {}
}
