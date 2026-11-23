<?php

namespace App\Http\Controllers\Agency;

use App\Http\Controllers\Controller;
use App\Models\AgencyInvitation;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        if (! ($user->agencyRegistration?->is_office_admin)) {
            abort(403, 'Only agency office administrators can access team management.');
        }

        $officeId = $user->office_id;

        $members = User::where('role', 'agency')
            ->where('office_id', $officeId)
            ->with('agencyRegistration')
            ->orderBy('name')
            ->get();

        $invitations = AgencyInvitation::where('office_id', $officeId)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->latest()
            ->get();

        return view('agency.team.index', compact('members', 'invitations'));
    }

    public function invite(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        if (! ($user->agencyRegistration?->is_office_admin)) {
            abort(403, 'Only agency office administrators can invite colleagues.');
        }

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
        ]);

        $rawToken = Str::random(40);

        DB::transaction(function () use ($user, $validated, $rawToken, $auditLogger) {
            $invitation = AgencyInvitation::create([
                'office_id' => $user->office_id,
                'email' => $validated['email'],
                'token_hash' => hash('sha256', $rawToken),
                'invited_by' => $user->id,
                'expires_at' => now()->addDays(7),
            ]);

            $auditLogger->log(
                $user,
                'agency.team.invited',
                $invitation,
                [],
                ['email' => $validated['email']]
            );
        });

        return redirect()->route('agency.team.index')->with('success', "Invitation sent to {$validated['email']}.");
    }

    public function revoke(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        if (! ($user->agencyRegistration?->is_office_admin)) {
            abort(403, 'Only agency office administrators can revoke invitations.');
        }

        $validated = $request->validate([
            'invitation_id' => ['required', 'exists:agency_invitations,id'],
        ]);

        $invitation = AgencyInvitation::where('id', $validated['invitation_id'])
            ->where('office_id', $user->office_id)
            ->firstOrFail();

        $invitation->delete();

        $auditLogger->log(
            $user,
            'agency.team.revoked_invite',
            $invitation,
            ['email' => $invitation->email],
            []
        );

        return redirect()->route('agency.team.index')->with('success', 'Invitation revoked.');
    }

    public function deactivate(Request $request, AuditLogger $auditLogger): RedirectResponse
    {
        $user = $request->user();
        if (! ($user->agencyRegistration?->is_office_admin)) {
            abort(403, 'Only agency office administrators can deactivate members.');
        }

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        if ($validated['user_id'] == $user->id) {
            return back()->with('error', 'You cannot deactivate your own account.');
        }

        $targetUser = User::where('id', $validated['user_id'])
            ->where('office_id', $user->office_id)
            ->firstOrFail();

        $targetUser->update(['is_active' => false]);

        $auditLogger->log(
            $user,
            'agency.team.deactivated_member',
            $targetUser,
            ['is_active' => true],
            ['is_active' => false]
        );

        return redirect()->route('agency.team.index')->with('success', "Member {$targetUser->name} has been deactivated.");
    }
}
