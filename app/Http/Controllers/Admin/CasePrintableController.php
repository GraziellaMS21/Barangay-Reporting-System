<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\Hearing;
use App\Models\Summons;
use App\Services\SignatoryResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CasePrintableController extends Controller
{
    public function __construct(
        protected SignatoryResolver $signatoryResolver
    ) {}

    /**
     * Certified Blotter Extract
     */
    public function blotterExtract(Request $request, BarangayCase $case): View
    {
        $this->authorizeView($case);
        $case->loadMissing(['caseType', 'fiscalYear', 'purok', 'encodedBy', 'caseParties.party']);

        $signatories = $this->signatoryResolver->forDocument('blotter_extract', now(), $case);
        $isConfirmed = $this->isConfirmed($signatories);

        return view('admin.cases.printables.blotter-extract', [
            'case' => $case,
            'signatories' => $signatories,
            'isConfirmed' => $isConfirmed,
        ]);
    }

    /**
     * Formal Complaint (KP Form No. 1)
     */
    public function complaint(Request $request, BarangayCase $case): View
    {
        $this->authorizeView($case);
        $case->loadMissing(['caseType', 'purok', 'caseParties.party']);

        $signatories = $this->signatoryResolver->forDocument('complaint', now(), $case);
        $isConfirmed = $this->isConfirmed($signatories);

        return view('admin.cases.printables.complaint', [
            'case' => $case,
            'signatories' => $signatories,
            'isConfirmed' => $isConfirmed,
        ]);
    }

    /**
     * Summons Notice (KP Form No. 7)
     */
    public function summons(Request $request, BarangayCase $case, ?Summons $summons = null): View
    {
        $this->authorizeView($case);
        $case->loadMissing(['caseParties.party', 'purok']);

        if (! $summons) {
            $summons = $case->activeSummons()->latest()->first();
        }

        if ($summons) {
            $summons->loadMissing(['respondentCaseParty.party', 'issuedBy', 'servedBy']);
        }

        $signatories = $this->signatoryResolver->forDocument('summons', now(), $case);
        $isConfirmed = $this->isConfirmed($signatories);

        return view('admin.cases.printables.summons', [
            'case' => $case,
            'summons' => $summons,
            'signatories' => $signatories,
            'isConfirmed' => $isConfirmed,
        ]);
    }

    /**
     * Notice of Conciliation Hearing (KP Form No. 8/9)
     */
    public function conciliationNotice(Request $request, BarangayCase $case, ?Hearing $hearing = null): View
    {
        $this->authorizeView($case);
        $case->loadMissing(['caseParties.party', 'pangkatChairman']);

        if (! $hearing) {
            $hearing = $case->hearings()->latest()->first();
        }

        if ($hearing) {
            $hearing->loadMissing(['presidedBy', 'summons']);
        }

        $signatories = $this->signatoryResolver->forDocument('conciliation_notice', now(), $case);
        $isConfirmed = $this->isConfirmed($signatories);

        return view('admin.cases.printables.conciliation-notice', [
            'case' => $case,
            'hearing' => $hearing,
            'signatories' => $signatories,
            'isConfirmed' => $isConfirmed,
        ]);
    }

    /**
     * Amicable Settlement Agreement (KP Form No. 16)
     */
    public function settlement(Request $request, BarangayCase $case): View
    {
        $this->authorizeView($case);
        $case->loadMissing(['caseParties.party', 'pangkatChairman']);

        $signatories = $this->signatoryResolver->forDocument('settlement_agreement', $case->settled_on ?? now(), $case);
        $isConfirmed = $this->isConfirmed($signatories);

        return view('admin.cases.printables.settlement', [
            'case' => $case,
            'signatories' => $signatories,
            'isConfirmed' => $isConfirmed,
        ]);
    }

    /**
     * Certificate to File Action (KP Form No. 20)
     */
    public function cfa(Request $request, BarangayCase $case): View
    {
        $this->authorizeView($case);
        $case->loadMissing(['caseParties.party', 'pangkatChairman', 'escalations.destination', 'escalations.requestedByParty.party']);
        $escalation = $case->escalations()->latest()->first();

        $signatories = $this->signatoryResolver->forDocument('certificate_to_file_action', now(), $case);
        $isConfirmed = $this->isConfirmed($signatories);

        return view('admin.cases.printables.cfa', [
            'case' => $case,
            'escalation' => $escalation,
            'signatories' => $signatories,
            'isConfirmed' => $isConfirmed,
        ]);
    }

    protected function authorizeView(BarangayCase $case): void
    {
        $user = request()->user();
        if (! $case->is_confidential === false && $user) {
            abort_unless(
                $user->isAdmin() ||
                $user->hasPermission('cases.view_confidential') ||
                $case->encoded_by === $user->id ||
                $case->assigned_to === $user->id,
                403
            );
        }
    }

    /**
     * @param  array<int, array{is_confirmed?: bool}>  $signatories
     */
    protected function isConfirmed(array $signatories): bool
    {
        if (empty($signatories)) {
            return false;
        }

        foreach ($signatories as $sig) {
            if (empty($sig['is_confirmed'])) {
                return false;
            }
        }

        return true;
    }
}
