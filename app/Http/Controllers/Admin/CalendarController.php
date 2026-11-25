<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Support\WorkingDays;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function __construct(
        protected SettingsService $settings
    ) {}

    public function index(): View
    {
        $rawHolidays = (array) setting('calendar.holidays', []);
        $holidays = collect($rawHolidays)->map(function ($item, $key) {
            if (is_array($item)) {
                return [
                    'date' => $item['date'] ?? '',
                    'label' => $item['label'] ?? 'Official Holiday',
                ];
            }

            return [
                'date' => (string) $item,
                'label' => 'Official Holiday',
            ];
        })->filter(fn ($item) => ! empty($item['date']))
            ->sortBy('date')
            ->values()
            ->all();

        $workingDays = WorkingDays::configuredWorkingDays();

        return view('admin.settings.calendar', compact('holidays', 'workingDays'));
    }

    public function updateWorkingDays(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'working_days' => ['required', 'array', 'min:1'],
            'working_days.*' => ['integer', 'between:1,7'],
        ], [
            'working_days.required' => 'Select at least one working day per week.',
        ]);

        $days = array_values(array_unique(array_map('intval', $validated['working_days'])));
        sort($days);

        $this->settings->set('cases.working_days', implode(',', $days), $request->user());

        return redirect()->route('admin.calendar.index')->with('success', 'Working days schedule updated.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'label' => ['required', 'string', 'max:255'],
        ]);

        $rawHolidays = (array) setting('calendar.holidays', []);
        $existing = collect($rawHolidays)->map(fn ($item) => is_array($item) ? ($item['date'] ?? '') : (string) $item)->all();

        if (in_array($validated['date'], $existing, true)) {
            return back()->withErrors(['date' => 'This holiday date is already recorded.'])->withInput();
        }

        $rawHolidays[] = [
            'date' => $validated['date'],
            'label' => $validated['label'],
        ];

        $this->settings->set('calendar.holidays', $rawHolidays, $request->user());

        return redirect()->route('admin.calendar.index')->with('success', 'Official holiday added to calendar.');
    }

    public function destroy(Request $request, string $date): RedirectResponse
    {
        $rawHolidays = (array) setting('calendar.holidays', []);
        $filtered = collect($rawHolidays)->filter(function ($item) use ($date) {
            $itemDate = is_array($item) ? ($item['date'] ?? '') : (string) $item;

            return $itemDate !== $date;
        })->values()->all();

        $this->settings->set('calendar.holidays', $filtered, $request->user());

        return redirect()->route('admin.calendar.index')->with('success', 'Holiday removed from calendar.');
    }
}
