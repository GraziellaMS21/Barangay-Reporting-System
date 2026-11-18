<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveOfficeRequest;
use App\Models\Office;
use App\Models\User;
use App\Services\SettingsManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OfficeSettingsController extends Controller
{
    private const AUDITED_FIELDS = [
        'name',
        'type',
        'description',
        'is_internal',
        'is_external',
        'contact',
        'contact_person',
        'contact_phone',
        'contact_email',
        'address',
        'default_assignee_id',
        'is_active',
    ];

    public function index(): View
    {
        return view('admin.settings.offices.index', [
            'offices' => Office::query()
                ->with('defaultAssignee:id,name')
                ->withCount(['categories', 'routingRules', 'reports', 'referrals'])
                ->orderByDesc('is_active')
                ->orderByDesc('is_internal')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.settings.offices.form', [
            'office' => new Office(['type' => 'internal', 'is_internal' => true, 'is_active' => true]),
            'staffUsers' => $this->activeStaffUsers(),
        ]);
    }

    public function edit(Office $office): View
    {
        return view('admin.settings.offices.form', [
            'office' => $office,
            'staffUsers' => $this->activeStaffUsers(),
        ]);
    }

    public function store(SaveOfficeRequest $request, SettingsManagementService $settingsManagementService): RedirectResponse
    {
        $office = new Office;
        $settingsManagementService->save(
            $office,
            $request->validatedAttributes(),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.offices.index')->with('success', 'Office added.');
    }

    public function update(
        SaveOfficeRequest $request,
        Office $office,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $settingsManagementService->save(
            $office,
            $request->validatedAttributes($office),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.offices.index')->with('success', 'Office updated.');
    }

    public function toggleActive(
        Request $request,
        Office $office,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $newState = $request->has('is_active')
            ? $request->boolean('is_active')
            : ! $office->is_active;

        $settingsManagementService->save(
            $office,
            ['is_active' => $newState],
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()
            ->route('admin.settings.offices.index')
            ->with('success', $office->name.($newState ? ' activated.' : ' deactivated.'));
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
                'office' => 'This office is linked to categories, routing rules, reports, or referrals and cannot be deleted. Deactivate it instead.',
            ]);
        }

        $settingsManagementService->delete($office, $request->user(), self::AUDITED_FIELDS);

        return redirect()->route('admin.settings.offices.index')->with('success', 'Office deleted.');
    }

    private function activeStaffUsers()
    {
        return User::query()
            ->whereIn('role', ['admin', 'staff'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'role']);
    }
}
