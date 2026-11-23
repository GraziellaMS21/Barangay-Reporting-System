<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\DeactivatePersonnelRequest;
use App\Http\Requests\Admin\People\SavePersonnelRequest;
use App\Models\Personnel;
use App\Models\Position;
use App\Models\User;
use App\Services\PersonnelService;
use App\Services\SignatoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;

class PersonnelController extends Controller
{
    public function __construct(
        protected PersonnelService $personnelService,
        protected SignatoryResolver $signatoryResolver,
    ) {}

    public function index(Request $request): View
    {
        $query = Personnel::query()
            ->with(['position', 'user'])
            ->orderByDesc('is_active')
            ->orderBy('full_name');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search): void {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($request->filled('position_id')) {
            $query->where('position_id', (int) $request->input('position_id'));
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === 'active');
        }

        $personnelList = $query->paginate(25)->withQueryString();
        $positions = Position::query()->orderBy('sort_order')->orderBy('name')->get();

        return view('admin.personnel.index', compact('personnelList', 'positions'));
    }

    public function create(): View
    {
        return view('admin.personnel.form', [
            'personnel' => new Personnel([
                'term_start' => now(),
                'is_active' => true,
            ]),
            'positions' => Position::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(),
            'availableUsers' => User::query()->whereIn('role', ['admin', 'staff'])->orderBy('name')->get(),
            'presets' => config('permission_presets.presets', []),
            'signatureDataUri' => null,
        ]);
    }

    public function store(SavePersonnelRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $tempPassword = null;

        if ($request->boolean('create_login') && empty($validated['user_id'])) {
            $tempPassword = Str::password(12);
            $validated['password'] = $tempPassword;
            $validated['create_login'] = true;
        }

        if ($request->hasFile('signature')) {
            $validated['signature'] = $request->file('signature');
        }

        $validated['is_signatory'] = $request->boolean('is_signatory');
        $validated['is_active'] = $request->has('is_active') ? $request->boolean('is_active') : true;

        $personnel = $this->personnelService->create($validated);

        $redirect = redirect()->route('admin.personnel.index')->with('success', "Personnel {$personnel->full_name} created.");
        if ($tempPassword !== null) {
            $redirect->with('generated_password', $tempPassword)
                ->with('generated_email', $personnel->user?->email);
        }

        return $redirect;
    }

    public function show(Personnel $personnel): View
    {
        $personnel->load(['position', 'user.permissions']);
        $signatureDataUri = $this->signatoryResolver->encodeSignatureBase64($personnel->signature_path);

        return view('admin.personnel.show', compact('personnel', 'signatureDataUri'));
    }

    public function edit(Personnel $personnel): View
    {
        $personnel->load(['position', 'user']);
        $positions = Position::query()->orderBy('sort_order')->orderBy('name')->get();
        $availableUsers = User::query()->whereIn('role', ['admin', 'staff'])->orderBy('name')->get();
        $presets = config('permission_presets.presets', []);
        $signatureDataUri = $this->signatoryResolver->encodeSignatureBase64($personnel->signature_path);

        return view('admin.personnel.form', compact(
            'personnel',
            'positions',
            'availableUsers',
            'presets',
            'signatureDataUri'
        ));
    }

    public function update(SavePersonnelRequest $request, Personnel $personnel): RedirectResponse
    {
        $validated = $request->validated();
        $tempPassword = null;

        if ($request->boolean('create_login') && empty($personnel->user_id) && empty($validated['user_id'])) {
            $tempPassword = Str::password(12);
            $validated['password'] = $tempPassword;
            $validated['create_login'] = true;
        }

        if ($request->hasFile('signature')) {
            $validated['signature'] = $request->file('signature');
        }

        $validated['remove_signature'] = $request->boolean('remove_signature');
        $validated['is_signatory'] = $request->boolean('is_signatory');
        if ($request->has('is_active')) {
            $validated['is_active'] = $request->boolean('is_active');
        }

        $this->personnelService->update($personnel, $validated, $request->user());

        $redirect = redirect()->route('admin.personnel.index')->with('success', 'Personnel updated.');
        if ($tempPassword !== null) {
            $redirect->with('generated_password', $tempPassword)
                ->with('generated_email', $personnel->fresh()->user?->email);
        }

        return $redirect;
    }

    public function deactivate(DeactivatePersonnelRequest $request, Personnel $personnel): RedirectResponse
    {
        $validated = $request->validated();
        $termEnd = ! empty($validated['term_end'])
            ? Carbon::parse($validated['term_end'])
            : now();

        $this->personnelService->deactivate($personnel, $termEnd, $validated['note'], $request->user());

        return redirect()->route('admin.personnel.index')
            ->with('success', "{$personnel->full_name} has been deactivated.");
    }

    public function destroy(DeactivatePersonnelRequest $request, Personnel $personnel): RedirectResponse
    {
        $validated = $request->validated();
        $termEnd = ! empty($validated['term_end'])
            ? Carbon::parse($validated['term_end'])
            : now();

        $this->personnelService->deactivate($personnel, $termEnd, $validated['note'], $request->user());

        return redirect()->route('admin.personnel.index')->with('success', 'Personnel deactivated.');
    }
}
