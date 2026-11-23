<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\Hearing;
use App\Models\Summons;
use App\Services\OverdueService;
use App\Services\ReferralService;
use App\Support\CaseStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SecretaryDashboardController extends Controller
{
    public function __construct(
        protected OverdueService $overdueService,
        protected ReferralService $referralService
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        // 1. Today's hearings
        $todayHearings = Hearing::query()
            ->whereDate('scheduled_at', today())
            ->whereHas('case', fn ($q) => $q->visibleTo($user))
            ->with(['case', 'presidedBy', 'summons.respondentCaseParty.party'])
            ->orderBy('scheduled_at')
            ->get();

        // 2. Hearings in next 7 days
        $upcomingHearings = Hearing::query()
            ->whereBetween('scheduled_at', [today()->addDay()->startOfDay(), today()->addDays(7)->endOfDay()])
            ->whereHas('case', fn ($q) => $q->visibleTo($user))
            ->with(['case', 'presidedBy'])
            ->orderBy('scheduled_at')
            ->get();

        // 3. Summons awaiting service
        $pendingSummons = Summons::query()
            ->where('service_result', 'pending')
            ->whereNull('voided_at')
            ->whereHas('case', fn ($q) => $q->visibleTo($user))
            ->with(['case', 'respondentCaseParty.party', 'issuedBy'])
            ->orderBy('issued_on')
            ->get();

        // 4, 5, 6. Cases near deadline, overdue cases, ready for next step
        $activeCases = BarangayCase::query()
            ->visibleTo($user)
            ->whereIn('status', [
                CaseStatus::FILED,
                CaseStatus::IN_MEDIATION,
                CaseStatus::IN_CONCILIATION,
                CaseStatus::UNSETTLED,
            ])
            ->with(['caseParties.party', 'caseType'])
            ->get();

        $casesNearDeadline = collect();
        $casesOverdue = collect();
        $casesReadyForNextStep = collect();

        foreach ($activeCases as $case) {
            $overdueInfo = $this->overdueService->forCase($case);
            $case->setAttribute('overdue_info', $overdueInfo);

            if ($overdueInfo['is_overdue']) {
                $casesOverdue->push($case);
            } elseif (($overdueInfo['day'] ?? 0) >= 12 && ($overdueInfo['day'] ?? 0) < 15) {
                $casesNearDeadline->push($case);
            }

            if (($overdueInfo['day'] ?? 0) >= 15 || $case->status === CaseStatus::UNSETTLED) {
                $casesReadyForNextStep->push($case);
            }
        }

        // 7. Referrals awaiting follow-up
        try {
            $referralsDue = $this->referralService->dueForFollowUp();
        } catch (\Throwable) {
            $referralsDue = collect();
        }

        return view('admin.case-actions.secretary.index', [
            'todayHearings' => $todayHearings,
            'upcomingHearings' => $upcomingHearings,
            'pendingSummons' => $pendingSummons,
            'casesNearDeadline' => $casesNearDeadline,
            'casesOverdue' => $casesOverdue,
            'casesReadyForNextStep' => $casesReadyForNextStep,
            'referralsDue' => $referralsDue,
        ]);
    }
}
