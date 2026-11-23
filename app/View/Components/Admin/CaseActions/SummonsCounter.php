<?php

namespace App\View\Components\Admin\CaseActions;

use App\Models\BarangayCase;
use App\Services\SummonsCounterService;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\View\Component;

class SummonsCounter extends Component
{
    public int $issued;

    public function __construct(
        BarangayCase $case,
        SummonsCounterService $summonsCounterService
    ) {
        $this->issued = $summonsCounterService->countForCase($case);
    }

    public function render(): ViewContract
    {
        return view('components.admin.case-actions.summons-counter');
    }
}
