<?php

namespace App\Http\Controllers;

use App\Models\EmergencyContact;
use App\Models\FiscalYear;
use App\Models\Permission;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\Setting;
use App\Models\User;
use App\Services\FiscalYearService;
use App\Services\SettingsService;
use Database\Seeders\RequiredDataSeeder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService,
        protected FiscalYearService $fiscalYearService
    ) {}

    public function show(): RedirectResponse
    {
        if ($this->isCompleted()) {
            return redirect()->route('login');
        }

        $step = (int) session('setup_step', 1);

        return redirect()->route('setup.step', ['step' => $step]);
    }

    public function step(string $step): View|RedirectResponse
    {
        if ($this->isCompleted()) {
            return redirect()->route('login');
        }

        if ($step === '5b') {
            return view('setup.step5b', [
                'currentStep' => '5b',
                'steps' => [
                    ['label' => 'Verification', 'step' => 1],
                    ['label' => 'Admin Account', 'step' => 2],
                    ['label' => 'Barangay Profile', 'step' => 3],
                    ['label' => 'Fiscal Year', 'step' => 4],
                    ['label' => 'Personnel & Review', 'step' => 5],
                    ['label' => 'Staff Onboarding', 'step' => '5b'],
                ],
                'positions' => Position::orderBy('sort_order')->get(),
                'presets' => config('permission_presets.presets', []),
                'staffMembers' => User::where('role', 'staff')->get(),
            ]);
        }

        $stepNum = (int) $step;
        if ($stepNum < 1 || $stepNum > 5) {
            return redirect()->route('setup.step', ['step' => 1]);
        }

        if ($stepNum > 1 && $this->requiresSetupToken() && ! session('setup_token_verified')) {
            return redirect()->route('setup.step', ['step' => 1])
                ->withErrors(['setup_token' => 'Please enter the single-use installation token first.']);
        }

        $data = [
            'currentStep' => $stepNum,
            'steps' => [
                ['label' => 'Verification', 'step' => 1],
                ['label' => 'Admin Account', 'step' => 2],
                ['label' => 'Barangay Profile', 'step' => 3],
                ['label' => 'Fiscal Year', 'step' => 4],
                ['label' => 'Personnel & Review', 'step' => 5],
            ],
        ];

        if ($stepNum === 1) {
            $data['checks'] = $this->runEnvironmentChecks();
            $data['requiresSetupToken'] = $this->requiresSetupToken();
        } elseif ($stepNum === 2) {
            $data['admin'] = User::where('role', 'admin')->first();
        } elseif ($stepNum === 3) {
            $policeNum = EmergencyContact::where('type', 'police')->value('number');
            $fireNum = EmergencyContact::where('type', 'fire')->value('number');

            $data['profile'] = [
                'name' => setting('barangay.name', 'Barangay Calarian'),
                'city' => setting('barangay.city', 'Zamboanga City'),
                'province' => setting('barangay.province', 'Zamboanga del Sur'),
                'region' => setting('barangay.region', 'Region IX (Zamboanga Peninsula)'),
                'hall_address' => setting('barangay.hall_address', 'Calarian, Zamboanga City, 7000'),
                'contact_number' => setting('barangay.contact_number', '0917-123-4567'),
                'email' => setting('barangay.email', 'info@calarian.gov.ph'),
                'police_hotline' => ($policeNum && stripos($policeNum, 'N/A') === false) ? $policeNum : '',
                'fire_hotline' => ($fireNum && stripos($fireNum, 'N/A') === false) ? $fireNum : '',
            ];
        } elseif ($stepNum === 4) {
            $data['currentYear'] = (int) now()->year;
            $data['fiscalYear'] = $this->fiscalYearService->active() ?? FiscalYear::first();
        } elseif ($stepNum === 5) {
            $data['positions'] = Position::orderBy('sort_order')->get();
            $data['personnel'] = Personnel::with('position')->get();
            $data['admin'] = User::where('role', 'admin')->first();
            $data['activeYear'] = $this->fiscalYearService->active();
        }

        return view("setup.step{$stepNum}", $data);
    }

    public function store(Request $request, string $step): RedirectResponse
    {
        if ($this->isCompleted()) {
            return redirect()->route('login');
        }

        $stepNum = (int) $step;

        if ($stepNum === 1) {
            if ($this->requiresSetupToken()) {
                $expectedToken = (string) setting('system.setup_token');
                $validated = $request->validate([
                    'setup_token' => ['required', 'string'],
                ]);

                if (! hash_equals(strtoupper(trim($expectedToken)), strtoupper(trim($validated['setup_token'])))) {
                    return back()->withErrors(['setup_token' => 'Invalid installation token. Check the server console output from php artisan app:empty.'])->withInput();
                }

                session(['setup_token_verified' => true]);
            }

            session(['setup_step' => 2]);

            return redirect()->route('setup.step', ['step' => 2]);
        }

        if ($this->requiresSetupToken() && ! session('setup_token_verified')) {
            return redirect()->route('setup.step', ['step' => 1])
                ->withErrors(['setup_token' => 'Please enter the single-use installation token first.']);
        }

        if ($stepNum === 2) {
            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'confirmed'],
            ]);

            $admin = User::where('email', $validated['email'])->first();
            if ($admin) {
                $admin->update([
                    'name' => $validated['name'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'admin',
                    'is_active' => true,
                    'must_change_password' => false,
                ]);
            } else {
                User::create([
                    'name' => $validated['name'],
                    'email' => $validated['email'],
                    'password' => Hash::make($validated['password']),
                    'role' => 'admin',
                    'is_active' => true,
                    'must_change_password' => false,
                ]);
            }

            session(['setup_step' => 3]);

            return redirect()->route('setup.step', ['step' => 3])->with('success', 'Administrator account saved.');
        }

        if ($stepNum === 3) {
            $validated = $request->validate([
                'barangay_name' => ['required', 'string', 'max:255'],
                'city' => ['required', 'string', 'max:255'],
                'province' => ['nullable', 'string', 'max:255'],
                'region' => ['nullable', 'string', 'max:255'],
                'hall_address' => ['required', 'string', 'max:500'],
                'contact_number' => ['required', 'string', 'max:50'],
                'email' => ['required', 'email', 'max:255'],
                'police_hotline' => ['nullable', 'string', 'max:50'],
                'fire_hotline' => ['nullable', 'string', 'max:50'],
            ]);

            $this->settingsService->set('barangay.name', $validated['barangay_name']);
            $this->settingsService->set('barangay.city', $validated['city']);
            $this->settingsService->set('barangay.province', $validated['province'] ?? '');
            $this->settingsService->set('barangay.region', $validated['region'] ?? '');
            $this->settingsService->set('barangay.hall_address', $validated['hall_address']);
            $this->settingsService->set('barangay.contact_number', $validated['contact_number']);
            $this->settingsService->set('barangay.email', $validated['email']);

            if (! empty($validated['police_hotline'])) {
                EmergencyContact::where('type', 'police')->update(['number' => trim($validated['police_hotline'])]);
            }
            if (! empty($validated['fire_hotline'])) {
                EmergencyContact::where('type', 'fire')->update(['number' => trim($validated['fire_hotline'])]);
            }

            session(['setup_step' => 4]);

            return redirect()->route('setup.step', ['step' => 4])->with('success', 'Barangay profile saved.');
        }

        if ($stepNum === 4) {
            $validated = $request->validate([
                'year' => ['required', 'integer', 'min:2020', 'max:2050'],
            ]);

            $year = (int) $validated['year'];
            $fy = FiscalYear::firstOrCreate(
                ['year' => $year],
                [
                    'label' => "FY {$year}",
                    'starts_on' => "{$year}-01-01",
                    'ends_on' => "{$year}-12-31",
                    'status' => 'active',
                    'is_locked' => false,
                ]
            );

            $this->fiscalYearService->activate($fy);

            session(['setup_step' => 5]);

            return redirect()->route('setup.step', ['step' => 5])->with('success', "Fiscal year {$year} configured and activated.");
        }

        if ($step === '5b') {
            if ($request->has('skip') || $request->input('action') === 'skip') {
                return $this->finish($request);
            }

            if (Permission::count() === 0) {
                app(RequiredDataSeeder::class)->run();
            }

            $validated = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', 'string', 'min:8'],
                'position_id' => ['nullable', 'exists:positions,id'],
                'preset' => ['nullable', 'string', 'in:'.implode(',', array_keys(config('permission_presets.presets', [])))],
            ]);

            $staff = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'staff',
                'is_active' => true,
                'must_change_password' => true,
            ]);

            if (! empty($validated['preset'])) {
                $presetConfig = config("permission_presets.presets.{$validated['preset']}");
                if (! empty($presetConfig['permissions'])) {
                    $permissions = Permission::whereIn('slug', $presetConfig['permissions'])->get();
                    $staff->permissions()->sync($permissions);
                }
            }

            if ($request->has('add_another')) {
                return redirect()->route('setup.step', ['step' => '5b'])
                    ->with('success', "Staff account {$staff->name} provisioned. You may add another staff member or complete setup.");
            }

            return $this->finish($request);
        }

        if ($stepNum === 5) {
            return $this->finish($request);
        }

        return redirect()->route('setup.step', ['step' => 1]);
    }

    public function finish(Request $request): RedirectResponse
    {
        if ($this->isCompleted()) {
            return redirect()->route('login');
        }

        if ($this->requiresSetupToken() && ! session('setup_token_verified')) {
            return redirect()->route('setup.step', ['step' => 1])
                ->withErrors(['setup_token' => 'Please enter the single-use installation token first.']);
        }

        // Run required seed data if not yet seeded
        if (Position::count() === 0) {
            app(RequiredDataSeeder::class)->run();
        }

        // Auto-enroll primary admin as Punong Barangay personnel for signatory resolution
        $admin = User::where('role', 'admin')->first();
        $pbPosition = Position::where('code', 'punong_barangay')->first();
        if ($admin && $pbPosition && ! Personnel::where('user_id', $admin->id)->exists()) {
            Personnel::create([
                'user_id' => $admin->id,
                'position_id' => $pbPosition->id,
                'full_name' => $admin->name,
                'email' => $admin->email,
                'term_start' => now()->startOfYear(),
                'is_signatory' => true,
                'is_active' => true,
            ]);
        }

        // Ensure active fiscal year
        if (! $this->fiscalYearService->active()) {
            $currentYear = (int) now()->year;
            $fy = FiscalYear::firstOrCreate(
                ['year' => $currentYear],
                [
                    'label' => "FY {$currentYear}",
                    'starts_on' => "{$currentYear}-01-01",
                    'ends_on' => "{$currentYear}-12-31",
                    'status' => 'active',
                    'is_locked' => false,
                ]
            );
            $this->fiscalYearService->activate($fy);
        }

        // Mark setup completed in database and consume single-use setup token
        Setting::updateOrCreate(
            ['key' => 'system.setup_completed'],
            [
                'value' => 'true',
                'type' => 'boolean',
                'group' => 'system',
                'label' => 'System Setup Completed',
                'description' => 'Flag indicating initial setup wizard has been concluded.',
                'is_confirmed' => true,
            ]
        );
        Setting::where('key', 'system.setup_token')->delete();

        Cache::forget('setting.system.setup_completed');
        Cache::forget('setting.system.setup_token');
        session()->forget(['setup_step', 'setup_token_verified']);

        return redirect()->route('login')->with('success', 'System setup completed successfully! Please sign in with your administrator account.');
    }

    protected function requiresSetupToken(): bool
    {
        $token = Setting::where('key', 'system.setup_token')->value('value');

        return is_string($token) && trim($token) !== '';
    }

    protected function isCompleted(): bool
    {
        $setting = Setting::where('key', 'system.setup_completed')->first();

        return $setting ? filter_var($setting->value, FILTER_VALIDATE_BOOLEAN) : false;
    }

    /**
     * @return array<string, array{label: string, passed: bool, message: string}>
     */
    protected function runEnvironmentChecks(): array
    {
        $checks = [];

        // 1. Database
        try {
            DB::connection()->getPdo();
            $checks['database'] = [
                'label' => 'Database Connected',
                'passed' => true,
                'message' => 'Connection verified successfully.',
            ];
        } catch (\Throwable $e) {
            $checks['database'] = [
                'label' => 'Database Connected',
                'passed' => false,
                'message' => $e->getMessage(),
            ];
        }

        // 2. Storage
        $storagePath = storage_path('app');
        $checks['storage'] = [
            'label' => 'Storage Directory Writable',
            'passed' => is_writable($storagePath),
            'message' => is_writable($storagePath) ? 'Storage directory is writable.' : 'Storage directory is not writable.',
        ];

        // 3. Mail Driver
        $driver = config('mail.default', 'log');
        $checks['mail'] = [
            'label' => 'Mail Transport Configured',
            'passed' => ! empty($driver),
            'message' => "Configured driver: {$driver}",
        ];

        return $checks;
    }
}
