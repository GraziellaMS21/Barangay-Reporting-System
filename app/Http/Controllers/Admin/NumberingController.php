<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FiscalYearService;
use App\Services\NumberingService;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NumberingController extends Controller
{
    public function __construct(
        protected NumberingService $numbering,
        protected SettingsService $settings,
        protected FiscalYearService $fiscalYear
    ) {}

    public function edit(): View
    {
        $fy = $this->fiscalYear->active();

        $formats = [
            'referral' => (string) setting('numbering.referral.format', 'REF-{YEAR}-{SEQ:4}'),
            'case' => (string) setting('numbering.case.format', 'CASE-{YEAR}-{SEQ:5}'),
            'blotter' => (string) setting('numbering.blotter.format', '{MM}-{SEQ:3}-{YEAR}'),
            'summons' => (string) setting('numbering.summons.format', 'SUM-{YEAR}-{SEQ:4}'),
        ];

        $previews = [
            'referral' => $fy ? $this->numbering->preview('referral', $fy) : 'REF-2026-0001',
            'case' => $fy ? $this->numbering->preview('case', $fy) : 'CASE-2026-00001',
            'blotter' => $fy ? $this->numbering->preview('blotter', $fy) : '11-001-2026',
            'summons' => $fy ? $this->numbering->preview('summons', $fy) : 'SUM-2026-0001',
        ];

        return view('admin.settings.numbering', compact('formats', 'previews', 'fy'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'referral_format' => ['required', 'string', 'max:50'],
            'case_format' => ['required', 'string', 'max:50'],
            'blotter_format' => ['required', 'string', 'max:50'],
            'summons_format' => ['required', 'string', 'max:50'],
        ]);

        foreach ($validated as $key => $format) {
            if (! $this->numbering->isValidFormat($format)) {
                return back()->withErrors([
                    $key => "The format mask '{$format}' is invalid. It must include a {SEQ:n} token with 1 to 8 digits.",
                ])->withInput();
            }
        }

        $this->settings->set('numbering.referral.format', $validated['referral_format'], $request->user());
        $this->settings->set('numbering.case.format', $validated['case_format'], $request->user());
        $this->settings->set('numbering.blotter.format', $validated['blotter_format'], $request->user());
        $this->settings->set('numbering.summons.format', $validated['summons_format'], $request->user());

        return redirect()->route('admin.numbering.edit')->with('success', 'Document numbering schemes saved successfully.');
    }
}
