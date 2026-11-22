<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class GeneralSettingsController extends Controller
{
    public function __construct(
        protected SettingsService $settings
    ) {}

    public function edit(): View
    {
        $settings = [
            'barangay_name' => setting('barangay.name', 'Barangay Calarian'),
            'city' => setting('barangay.city', 'Zamboanga City'),
            'province' => setting('barangay.province', 'Zamboanga del Sur'),
            'region' => setting('barangay.region', 'Region IX (Zamboanga Peninsula)'),
            'hall_address' => setting('barangay.hall_address', 'Calarian, Zamboanga City, 7000'),
            'contact_number' => setting('barangay.contact_number', '0917-123-4567'),
            'email' => setting('barangay.email', 'info@calarian.gov.ph'),
            'cases_enabled' => (bool) setting('cases.enabled', true),
            'agencies_registration_open' => (bool) setting('agencies.registration_open', true),
            'default_format' => (string) setting('reports.default_format', 'print'),
            'paper_size' => (string) setting('reports.paper_size', 'letter'),
        ];

        return view('admin.settings.general', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'barangay_name' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'province' => ['nullable', 'string', 'max:255'],
            'region' => ['nullable', 'string', 'max:255'],
            'hall_address' => ['required', 'string', 'max:500'],
            'contact_number' => ['required', 'string', 'max:50'],
            'email' => ['required', 'email', 'max:255'],
            'cases_enabled' => ['nullable', 'boolean'],
            'agencies_registration_open' => ['nullable', 'boolean'],
            'default_format' => ['required', 'in:print,pdf,csv'],
            'paper_size' => ['required', 'in:letter,a4,legal'],
        ]);

        $this->settings->set('barangay.name', $validated['barangay_name'], $request->user());
        $this->settings->set('barangay.city', $validated['city'], $request->user());
        $this->settings->set('barangay.province', $validated['province'] ?? '', $request->user());
        $this->settings->set('barangay.region', $validated['region'] ?? '', $request->user());
        $this->settings->set('barangay.hall_address', $validated['hall_address'], $request->user());
        $this->settings->set('barangay.contact_number', $validated['contact_number'], $request->user());
        $this->settings->set('barangay.email', $validated['email'], $request->user());
        $this->settings->set('cases.enabled', ! empty($validated['cases_enabled']), $request->user());
        $this->settings->set('agencies.registration_open', ! empty($validated['agencies_registration_open']), $request->user());
        $this->settings->set('reports.default_format', $validated['default_format'], $request->user());
        $this->settings->set('reports.paper_size', $validated['paper_size'], $request->user());

        return redirect()->route('admin.settings.general.edit')->with('success', 'General settings saved successfully.');
    }
}
