<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use Illuminate\Http\Request;

class FiscalYearController extends Controller
{
    public function index()
    {
        return view('stub', ['title' => 'Fiscal Years Management', 'owner' => 'WS1']);
    }

    public function store(Request $request)
    {
        return redirect()->route('admin.fiscal-years.index')->with('success', 'Fiscal year created.');
    }

    public function activate(FiscalYear $fiscal_year)
    {
        return redirect()->route('admin.fiscal-years.index')->with('success', 'Fiscal year activated.');
    }

    public function close(Request $request, FiscalYear $fiscal_year)
    {
        return redirect()->route('admin.fiscal-years.index')->with('success', 'Fiscal year closed.');
    }

    public function reopen(Request $request, FiscalYear $fiscal_year)
    {
        return redirect()->route('admin.fiscal-years.index')->with('success', 'Fiscal year reopened.');
    }
}
