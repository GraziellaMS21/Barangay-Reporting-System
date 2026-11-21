<?php

namespace App\Events;

use App\Models\Report;
use App\Models\ResolutionProof;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResolutionProposed
{
    use Dispatchable, SerializesModels;

    public function __construct(public Report $report, public ResolutionProof $proof) {}
}
