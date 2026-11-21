<?php

namespace App\Events;

use App\Models\InfoRequest;
use App\Models\Report;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class InfoRequested
{
    use Dispatchable, SerializesModels;

    public function __construct(public Report $report, public InfoRequest $infoRequest) {}
}
