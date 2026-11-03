<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Office;
use App\Services\SettingsManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OfficeSettingsController extends Controller
{
    private const AUDITED_FIELDS = ['name', 'type', 'is_internal', 'contact'];

    public function index(): View
    {
        return view('admin.settings.offices.index', [
            'offices' => Office::query()
                ->withCount(['categories', 'routingRules', 'reports', 'referrals'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.settings.offices.form', [
            'office' => new Office,
        ]);
    }

    public function edit(Office $office): View
    {
        return view('admin.settings.offices.form', compact('office'));
    }

    public function store(Request $request, SettingsManagementService $settingsManagementService): RedirectResponse
    {
        $office = new Office;
        $settingsManagementService->save(
            $office,
            $this->validatedAttributes($request),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.offices.index')->with('success', 'Office added.');
    }

    public function update(
        Request $request,
        Office $office,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $settingsManagementService->save(
            $office,
            $this->validatedAttributes($request, $office),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.offices.index')->with('success', 'Office updated.');
    }

    public function destroy(
        Request $request,
        Office $office,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        if (
            $office->categories()->exists()
            || $office->routingRules()->exists()
            || $office->reports()->exists()
            || $office->referrals()->exists()
        ) {
            throw ValidationException::withMessages([
                'office' => 'This office is linked to categories, routing rules, reports, or referrals and cannot be deleted.',
            ]);
        }

        $settingsManagementService->delete($office, $request->user(), self::AUDITED_FIELDS);

        return redirect()->route('admin.settings.offices.index')->with('success', 'Office deleted.');
    }

    /**
     * @return array{name: string, type: string, is_internal: bool, contact: string|null}
     */
    private function validatedAttributes(Request $request, ?Office $office = null): array
    {
        $nameRule = Rule::unique('offices', 'name');
        if ($office !== null) {
            $nameRule->ignore($office->id);
        }

        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255', $nameRule],
            'type' => ['required', 'string', 'max:100'],
            'is_internal' => ['required', 'boolean'],
            'contact' => ['nullable', 'string', 'max:255'],
        ], [
            'name.unique' => 'An office with this name already exists.',
            'type.required' => 'Choose an office type.',
        ]);
        $attributes['is_internal'] = $request->boolean('is_internal');

        return $attributes;
    }
}
