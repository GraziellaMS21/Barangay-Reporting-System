<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\PriorityService;
use App\Services\SettingsManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function index(): View
    {
        return view('admin.settings.index');
    }

    public function priorityWeights(PriorityService $priorityService): View
    {
        return view('admin.settings.priority-weights', [
            'weights' => $priorityService->weights(),
        ]);
    }

    public function updatePriority(
        Request $request,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $validated = $request->validate([
            'priority' => ['required', 'array:urgency,category,sensitive_place,supporting_reports,age_days'],
            'priority.urgency' => ['required', 'integer', 'min:0', 'max:1000'],
            'priority.category' => ['required', 'integer', 'min:0', 'max:1000'],
            'priority.sensitive_place' => ['required', 'integer', 'min:0', 'max:1000'],
            'priority.supporting_reports' => ['required', 'integer', 'min:0', 'max:1000'],
            'priority.age_days' => ['required', 'integer', 'min:0', 'max:1000'],
        ], [
            'priority.*.integer' => 'Each priority weight must be a whole number.',
            'priority.*.min' => 'Priority weights cannot be negative.',
            'priority.*.max' => 'Priority weights cannot exceed 1000.',
        ]);

        $settingsManagementService->updateSettings(
            $this->prioritySettings(array_map('intval', $validated['priority'])),
            $request->user()
        );

        return back()->with('success', 'Priority weights saved.');
    }

    public function resetPriority(
        Request $request,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $settingsManagementService->updateSettings(
            $this->prioritySettings(PriorityService::DEFAULT_WEIGHTS),
            $request->user()
        );

        return back()->with('success', 'Priority weights reset to their defaults.');
    }

    public function emergencyAlertEmails(): View
    {
        return view('admin.settings.emergency-alert-emails', [
            'emailAddresses' => Setting::value('emergency_alert_emails', ''),
        ]);
    }

    public function updateEmergencyAlertEmails(
        Request $request,
        SettingsManagementService $settingsManagementService
    ): RedirectResponse {
        $validated = $request->validate([
            'email_addresses' => ['nullable', 'string', 'max:5000'],
        ]);
        $emailAddresses = collect(preg_split('/[\r\n,]+/', $validated['email_addresses'] ?? '') ?: [])
            ->map(fn (string $email): string => trim($email))
            ->filter()
            ->unique(fn (string $email): string => Str::lower($email))
            ->values();

        $emailValidator = Validator::make(
            ['emails' => $emailAddresses->all()],
            [
                'emails' => ['array', 'max:20'],
                'emails.*' => ['required', 'email', 'max:255'],
            ],
            [
                'emails.max' => 'Enter no more than 20 alert email addresses.',
                'emails.*.email' => 'Each alert address must be a valid email address.',
            ]
        );

        if ($emailValidator->fails()) {
            throw ValidationException::withMessages([
                'email_addresses' => $emailValidator->errors()->all(),
            ]);
        }

        $settingsManagementService->updateSettings([
            'emergency_alert_emails' => $emailAddresses->implode(PHP_EOL),
        ], $request->user());

        return back()->with('success', 'Emergency alert email recipients saved.');
    }

    /**
     * @param  array<string, int>  $weights
     * @return array<string, int>
     */
    private function prioritySettings(array $weights): array
    {
        return collect($weights)
            ->mapWithKeys(fn (int $value, string $key): array => ['priority.'.$key => $value])
            ->all();
    }
}
