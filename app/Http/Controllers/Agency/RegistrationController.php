<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyRegistration;
use App\Models\Office;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function show(): View|RedirectResponse
    {
        if (! setting('agencies.registration_open', true)) {
            return redirect()->route('login')->with('error', 'Agency registration is currently closed.');
        }

        $offices = Office::where('is_active', true)
            ->where(function ($query) {
                $query->where('is_external', true)
                    ->orWhere('type', 'external');
            })
            ->orderBy('name')
            ->get();

        return view('agency.register', compact('offices'));
    }

    public function store(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        if (! setting('agencies.registration_open', true)) {
            return redirect()->route('login')->with('error', 'Agency registration is currently closed.');
        }

        $validated = $request->validate([
            'office_id' => ['nullable', 'exists:offices,id'],
            'agency_name_claimed' => ['required', 'string', 'max:150'],
            'name' => ['required', 'string', 'max:120'],
            'position_title' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'contact_number' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
            'id_document' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'consent' => ['accepted'],
        ]);

        $storedPath = DB::transaction(function () use ($validated, $request, $auditLogger) {
            $extension = $request->file('id_document')->getClientOriginalExtension();
            $filename = (string) Str::uuid().'.'.$extension;
            $path = $request->file('id_document')->storeAs('agency-docs', $filename, 'private');

            $user = User::create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'role' => 'agency',
                'office_id' => $validated['office_id'] ?? null,
                'is_active' => false,
            ]);

            $isFirstForOffice = false;
            if (! empty($validated['office_id'])) {
                $existingCount = AgencyRegistration::where('office_id', $validated['office_id'])
                    ->whereIn('status', ['approved', 'pending'])
                    ->count();
                $isFirstForOffice = ($existingCount === 0);
            }

            $registration = AgencyRegistration::create([
                'user_id' => $user->id,
                'office_id' => $validated['office_id'] ?? null,
                'agency_name_claimed' => $validated['agency_name_claimed'],
                'position_title' => $validated['position_title'],
                'contact_number' => $validated['contact_number'],
                'id_document_path' => $path,
                'status' => 'pending',
                'is_office_admin' => $isFirstForOffice,
            ]);

            $auditLogger->log(
                $user,
                'agency.registered',
                $registration,
                [],
                ['agency' => $validated['agency_name_claimed'], 'email' => $validated['email']]
            );

            return $path;
        });

        return redirect()->route('agency.pending');
    }

    public function pending(): View
    {
        return view('agency.pending');
    }
}
