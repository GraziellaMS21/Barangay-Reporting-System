<?php

namespace App\Http\Controllers;

use App\Services\FiscalYearContext;
use Illuminate\Http\Request;

class FiscalYearSwitchController extends Controller
{
    public function switch(Request $request, FiscalYearContext $context)
    {
        $context->setViewing($request->input('fiscal_year_id'));

        return back();
    }
}
