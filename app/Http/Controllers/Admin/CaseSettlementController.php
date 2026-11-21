<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use Illuminate\Http\Request;

class CaseSettlementController extends Controller
{
    public function store(Request $request, BarangayCase $case)
    {
        return redirect()->route('admin.cases.show', $case)->with('success', 'Amicable settlement recorded.');
    }
}
