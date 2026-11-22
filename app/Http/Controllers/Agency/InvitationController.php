<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyInvitation;
use App\Models\AgencyRegistration;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class InvitationController extends Controller
{
    public function show(string $token): View|RedirectResponse
    {
        $tokenHash = hash('sha256', $token);

        $invitation = AgencyInvitation::where('token_hash', $tokenHash)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->with(['office', 'inviter'])
            ->first();

        if (! $invitation) {
            return redirect()->route('login')->with('error', 'This agency invitation is invalid or has expired.');
        }

        return view('agency.invite', compact('invitation', 'token'));
    }

    public function accept(Request $request, string $token, AuditLogger $auditLogger): RedirectResponse
    {
        $tokenHash = hash('sha256', $token);

        $invitation = AgencyInvitation::where('token_hash', $tokenHash)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->first();

        if (! $invitation) {
            return redirect()->route('login')->with('error', 'This agency invitation is invalid or has expired.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'position_title' => ['required', 'string', 'max:100'],
            'contact_number' => ['required', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:10', 'confirmed'],
        ]);

        DB::transaction(function () use ($invitation, $validated, $auditLogger) {
            $user = User::create([
                'name' => $validated['name'],
                'email' => $invitation->email,
                'password' => Hash::make($validated['password']),
                'role' => 'agency',
                'office_id' => $invitation->office_id,
                'is_active' => false,
                'email_verified_at' => now(),
            ]);

            $registration = AgencyRegistration::create([
                'user_id' => $user->id,
                'office_id' => $invitation->office_id,
                'agency_name_claimed' => $invitation->office->name,
                'position_title' => $validated['position_title'],
                'contact_number' => $validated['contact_number'],
                'id_document_path' => 'vouched_by_invitation:'.$invitation->invited_by,
                'status' => 'pending',
                'is_office_admin' => false,
            ]);

            $invitation->update(['accepted_at' => now()]);

            $auditLogger->log(
                $user,
                'agency.invitation_accepted',
                $registration,
                [],
                ['office_id' => $invitation->office_id, 'invited_by' => $invitation->invited_by]
            );
        });

        return redirect()->route('agency.pending');
    }
}
