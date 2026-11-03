<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\EmergencyContact;
use App\Models\Office;
use App\Models\Purok;
use App\Models\RoutingRule;
use App\Models\SensitivePlace;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index', [
            'categories' => Category::query()->orderBy('name')->get(),
            'offices' => Office::query()->orderBy('name')->get(),
            'routingRules' => RoutingRule::query()->with(['category', 'office'])->get(),
            'contacts' => EmergencyContact::query()->orderBy('name')->get(),
            'sensitivePlaces' => SensitivePlace::query()->orderBy('name')->get(),
            'puroks' => Purok::query()->orderBy('name')->get(),
            'staff' => User::query()->orderBy('name')->get(),
        ]);
    }

    public function updatePriority(Request $request): RedirectResponse
    {
        $request->validate([
            'priority.urgency' => 'nullable|integer|min:1',
            'priority.category' => 'nullable|integer|min:1',
        ]);

        foreach ($request->input('priority', []) as $key => $value) {
            if ($value !== null && $value !== '') {
                Setting::updateOrCreate(
                    ['key' => 'priority.'.$key],
                    ['value' => (int) $value]
                );
            }
        }

        return back()->with('success', 'Priority weights saved.');
    }
}
