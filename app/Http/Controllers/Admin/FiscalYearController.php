<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FiscalYear;
use App\Services\FiscalYearService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiscalYearController extends Controller
{
    public function __construct(
        protected FiscalYearService $fiscalYearService
    ) {}

    public function index(): View
    {
        $years = FiscalYear::with(['closedBy'])
            ->withCount(['reports', 'referrals', 'cases'])
            ->orderByDesc('year')
            ->get();

        return view('admin.settings.fiscal-years.index', [
            'years' => $years,
            'activeYear' => $this->fiscalYearService->active(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'year' => ['required', 'integer', 'min:2020', 'max:2050', 'unique:fiscal_years,year'],
            'activate' => ['nullable', 'boolean'],
        ]);

        $fy = $this->fiscalYearService->create((int) $validated['year']);

        if (! empty($validated['activate'])) {
            $this->fiscalYearService->activate($fy);
        }

        return redirect()->route('admin.fiscal-years.index')->with('success', "Fiscal year {$fy->year} created.");
    }

    public function activate(FiscalYear $fiscal_year): RedirectResponse
    {
        $this->fiscalYearService->activate($fiscal_year);

        return redirect()->route('admin.fiscal-years.index')->with('success', "Fiscal year {$fiscal_year->year} activated.");
    }

    public function close(Request $request, FiscalYear $fiscal_year): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->fiscalYearService->close(
            $fiscal_year,
            $request->user(),
            $validated['note'] ?? 'Annual operational close'
        );

        return redirect()->route('admin.fiscal-years.index')->with('success', "Fiscal year {$fiscal_year->year} closed and locked.");
    }

    public function reopen(Request $request, FiscalYear $fiscal_year): RedirectResponse
    {
        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $this->fiscalYearService->reopen(
            $fiscal_year,
            $request->user(),
            $validated['note']
        );

        return redirect()->route('admin.fiscal-years.index')->with('success', "Fiscal year {$fiscal_year->year} reopened.");
    }
}
