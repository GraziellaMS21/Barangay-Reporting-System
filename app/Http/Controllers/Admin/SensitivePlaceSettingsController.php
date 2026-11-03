<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SensitivePlace;
use App\Services\SettingsManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SensitivePlaceSettingsController extends Controller
{
    private const AUDITED_FIELDS = ['name', 'type', 'lat', 'lng'];

    public function index(): View
    {
        return view('admin.settings.sensitive-places.index', [
            'sensitivePlaces' => SensitivePlace::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.settings.sensitive-places.form', [
            'sensitivePlace' => new SensitivePlace,
        ]);
    }

    public function edit(SensitivePlace $sensitivePlace): View
    {
        return view('admin.settings.sensitive-places.form', compact('sensitivePlace'));
    }

    public function store(Request $request, SettingsManagementService $settingsManagementService): RedirectResponse
    {
        $place = new SensitivePlace;
        $settingsManagementService->save(
            $place,
            $this->validatedAttributes($request),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.sensitive-places.index')->with('success', 'Sensitive place added.');
    }

    public function update(
        Request $request,
        SensitivePlace $sensitivePlace,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $settingsManagementService->save(
            $sensitivePlace,
            $this->validatedAttributes($request),
            $request->user(),
            self::AUDITED_FIELDS
        );

        return redirect()->route('admin.settings.sensitive-places.index')->with('success', 'Sensitive place updated.');
    }

    public function destroy(
        Request $request,
        SensitivePlace $sensitivePlace,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $settingsManagementService->delete($sensitivePlace, $request->user(), self::AUDITED_FIELDS);

        return redirect()->route('admin.settings.sensitive-places.index')->with('success', 'Sensitive place deleted.');
    }

    /**
     * @return array{name: string, type: string, lat: int|float, lng: int|float}
     */
    private function validatedAttributes(Request $request): array
    {
        $attributes = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'max:100'],
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
        ], [
            'lat.numeric' => 'Enter a valid latitude.',
            'lat.between' => 'Latitude must be between -90 and 90.',
            'lng.numeric' => 'Enter a valid longitude.',
            'lng.between' => 'Longitude must be between -180 and 180.',
        ]);
        $attributes['lat'] = (float) $attributes['lat'];
        $attributes['lng'] = (float) $attributes['lng'];

        return $attributes;
    }
}
