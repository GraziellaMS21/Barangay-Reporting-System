<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\CaseFieldConfig;
use App\Models\CaseNote;
use App\Models\CaseParty;
use App\Models\CaseStatusLog;
use App\Models\CaseType;
use App\Models\Party;
use App\Models\Purok;
use App\Models\Report;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\Cases\PartyMatchingService;
use App\Services\CaseStatusService;
use App\Services\FiscalYearContext;
use App\Services\FiscalYearGuard;
use App\Services\FiscalYearService;
use App\Services\NumberingService;
use App\Services\SummonsCounterService;
use App\Support\CaseStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CaseController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected CaseStatusService $statusService,
        protected FiscalYearService $fiscalYearService,
        protected FiscalYearContext $fiscalYearContext,
        protected FiscalYearGuard $fiscalYearGuard,
        protected NumberingService $numberingService,
        protected SummonsCounterService $summonsCounter,
        protected PartyMatchingService $partyMatching
    ) {}

    /**
     * Display a paginated docket list of barangay cases.
     */
    public function index(Request $request): View
    {
        $user = $request->user();
        $viewingFy = $this->fiscalYearContext->viewing();
        $selectedFy = $request->query('fiscal_year_id', $viewingFy?->id);

        $status = $request->query('status');
        $caseTypeId = $request->query('case_type_id');
        $purokId = $request->query('purok_id');
        $residency = $request->query('residency');
        $assignedTo = $request->query('assigned_to');
        $search = trim($request->query('search', ''));

        $query = BarangayCase::query()
            ->visibleTo($user)
            ->with([
                'caseType',
                'purok',
                'fiscalYear',
                'assignedTo',
                'complainants',
                'respondents',
                'activeSummons',
            ])
            ->when($selectedFy && $selectedFy !== 'all', fn ($q) => $q->where('fiscal_year_id', $selectedFy))
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($caseTypeId, fn ($q) => $q->where('case_type_id', $caseTypeId))
            ->when($purokId, fn ($q) => $q->where('purok_id', $purokId))
            ->when($assignedTo, fn ($q) => $q->where('assigned_to', $assignedTo))
            ->when($residency, function ($q) use ($residency) {
                $q->whereHas('parties', fn ($p) => $p->where('parties.residency_status', $residency));
            })
            ->when($search, function ($q, $term) {
                $q->where(function ($sub) use ($term) {
                    $sub->where('case_number', 'like', "%{$term}%")
                        ->orWhere('title', 'like', "%{$term}%")
                        ->orWhereHas('parties', function ($p) use ($term) {
                            $p->where('full_name', 'like', "%{$term}%")
                                ->orWhere('alias', 'like', "%{$term}%");
                        });
                });
            })
            ->orderBy('filed_on', 'desc')
            ->orderBy('id', 'desc');

        $cases = $query->paginate(20)->withQueryString();

        $caseTypes = CaseType::query()->where('is_active', true)->orderBy('sort_order')->get();
        $puroks = Purok::query()->orderBy('name')->get();
        $staffMembers = User::query()->where('is_active', true)->orderBy('name')->get()
            ->filter(fn (User $u) => $u->hasPermission('cases.view'));

        return view('admin.cases.index', compact(
            'cases',
            'caseTypes',
            'puroks',
            'staffMembers',
            'selectedFy',
            'status',
            'caseTypeId',
            'purokId',
            'residency',
            'assignedTo',
            'search'
        ));
    }

    /**
     * Export filtered docket cases as streaming CSV.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $user = $request->user();
        $viewingFy = $this->fiscalYearContext->viewing();
        $selectedFy = $request->query('fiscal_year_id', $viewingFy?->id);

        $cases = BarangayCase::query()
            ->visibleTo($user)
            ->with(['caseType', 'complainants', 'respondents', 'fiscalYear'])
            ->when($selectedFy && $selectedFy !== 'all', fn ($q) => $q->where('fiscal_year_id', $selectedFy))
            ->orderBy('filed_on', 'desc')
            ->get();

        $fileName = 'cases-docket-'.now()->format('Ymd-His').'.csv';

        return Response::stream(function () use ($cases) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Case Number',
                'Fiscal Year',
                'Case Type',
                'Title',
                'Status',
                'Filed On',
                'Complainants',
                'Respondents',
                'Confidential',
            ]);

            foreach ($cases as $case) {
                fputcsv($handle, [
                    $case->case_number,
                    $case->fiscalYear?->label ?? 'N/A',
                    $case->caseType?->name ?? 'Unclassified',
                    $case->title,
                    $case->status,
                    $case->filed_on?->format('Y-m-d'),
                    $case->complainants->pluck('full_name')->implode('; '),
                    $case->respondents->pluck('full_name')->implode('; '),
                    $case->is_confidential ? 'Yes' : 'No',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$fileName}\"",
        ]);
    }

    /**
     * Show the form for docketing a new case.
     */
    public function create(Request $request): View
    {
        $sourceReportId = $request->query('source_report_id');
        $sourceReport = $sourceReportId ? Report::find($sourceReportId) : null;

        $caseTypes = CaseType::query()->where('is_active', true)->orderBy('sort_order')->get();
        $puroks = Purok::query()->orderBy('name')->get();
        $fieldConfigs = CaseFieldConfig::query()->where('is_visible', true)->orderBy('sort_order')->get()->groupBy('entity');

        return view('admin.cases.create', compact('sourceReport', 'caseTypes', 'puroks', 'fieldConfigs'));
    }

    /**
     * Store a newly docketed case in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'case_type_id' => ['required', 'exists:case_types,id'],
            'filed_on' => ['required', 'date', 'before_or_equal:today'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:filed_on'],
            'incident_location' => ['nullable', 'string', 'max:255'],
            'purok_id' => ['nullable', 'exists:puroks,id'],
            'title' => ['nullable', 'string', 'max:150'],
            'narrative' => ['required', 'string', 'min:20', 'max:5000'],
            'source' => ['nullable', 'string', 'in:walk_in,online_report,referral,other'],
            'source_report_id' => ['nullable', 'exists:reports,id'],
            'is_confidential' => ['nullable', 'boolean'],
            'parties' => ['required', 'array', 'min:2'],
            'parties.*.role' => ['required', 'in:complainant,respondent,witness'],
            'parties.*.party_id' => ['nullable', 'exists:parties,id'],
            'parties.*.full_name' => ['required_without:parties.*.party_id', 'nullable', 'string', 'max:120'],
            'parties.*.residency_status' => ['required_without:parties.*.party_id', 'nullable', 'in:resident,non_resident,unverified'],
            'parties.*.purok_id' => ['nullable', 'exists:puroks,id'],
            'parties.*.contact_number' => ['nullable', 'string', 'max:40'],
            'parties.*.address_line' => ['nullable', 'string', 'max:255'],
            'parties.*.home_barangay' => ['nullable', 'string', 'max:100'],
            'parties.*.home_city' => ['nullable', 'string', 'max:100'],
            'parties.*.residency_basis' => ['nullable', 'string', 'max:200'],
        ]);

        // Require at least one complainant and one respondent
        $complainantCount = collect($validated['parties'])->where('role', 'complainant')->count();
        $respondentCount = collect($validated['parties'])->where('role', 'respondent')->count();

        if ($complainantCount < 1 || $respondentCount < 1) {
            return back()->withInput()->withErrors([
                'parties' => 'A case must have at least one complainant and at least one respondent.',
            ]);
        }

        $case = DB::transaction(function () use ($validated, $request) {
            $fy = $this->fiscalYearService->forNewRecord();
            $caseNumber = $this->numberingService->next('case', $fy);

            $caseType = CaseType::find($validated['case_type_id']);

            // Auto-generate title if omitted
            $title = $validated['title'] ?? null;
            if (empty($title)) {
                $compName = collect($validated['parties'])->firstWhere('role', 'complainant')['full_name'] ?? 'Complainant';
                $respName = collect($validated['parties'])->firstWhere('role', 'respondent')['full_name'] ?? 'Respondent';
                $title = "{$caseType->name}: {$compName} vs. {$respName}";
            }

            $case = BarangayCase::create([
                'case_number' => $caseNumber,
                'fiscal_year_id' => $fy->id,
                'case_type_id' => $caseType->id,
                'source' => $validated['source'] ?? (! empty($validated['source_report_id']) ? 'online_report' : 'walk_in'),
                'source_report_id' => $validated['source_report_id'] ?? null,
                'title' => substr($title, 0, 150),
                'narrative' => $validated['narrative'],
                'filed_on' => $validated['filed_on'],
                'incident_date' => $validated['incident_date'] ?? null,
                'incident_location' => $validated['incident_location'] ?? null,
                'purok_id' => $validated['purok_id'] ?? null,
                'status' => CaseStatus::FILED,
                'encoded_by' => $request->user()->id,
                'is_confidential' => (bool) ($validated['is_confidential'] ?? false),
            ]);

            // Attach parties
            foreach ($validated['parties'] as $partyInput) {
                $party = null;
                if (! empty($partyInput['party_id'])) {
                    $party = Party::find($partyInput['party_id']);
                } else {
                    $party = Party::create([
                        'full_name' => $partyInput['full_name'],
                        'residency_status' => $partyInput['residency_status'] ?? 'unverified',
                        'purok_id' => $partyInput['purok_id'] ?? null,
                        'contact_number' => $partyInput['contact_number'] ?? null,
                        'address_line' => $partyInput['address_line'] ?? null,
                        'home_barangay' => $partyInput['home_barangay'] ?? null,
                        'home_city' => $partyInput['home_city'] ?? null,
                        'residency_basis' => $partyInput['residency_basis'] ?? null,
                    ]);
                }

                CaseParty::create([
                    'case_id' => $case->id,
                    'party_id' => $party->id,
                    'role' => $partyInput['role'],
                    'residency_at_filing' => $party->residency_status,
                ]);
            }

            // Create initial status log
            CaseStatusLog::create([
                'case_id' => $case->id,
                'user_id' => $request->user()->id,
                'from_status' => null,
                'to_status' => CaseStatus::FILED,
                'note' => 'Case docketed in system.',
                'created_at' => now(),
            ]);

            $this->audit->log($request->user(), 'case.created', $case, [], [
                'case_number' => $case->case_number,
                'status' => $case->status,
                'title' => $case->title,
            ]);

            return $case;
        });

        if ($request->input('action') === 'issue_summons') {
            return redirect()->route('admin.cases.summons.create', $case)
                ->with('success', "Case {$case->case_number} docketed. You may now issue the first summons.");
        }

        return redirect()->route('admin.cases.show', $case)
            ->with('success', "Case {$case->case_number} successfully docketed.");
    }

    /**
     * Display full case workspace.
     */
    public function show(Request $request, BarangayCase $case): View
    {
        $user = $request->user();

        // Enforce confidentiality visibility rule
        if (! BarangayCase::visibleTo($user)->where('id', $case->id)->exists()) {
            abort(404);
        }

        // Throttle audit log for viewing confidential or sensitive case (max 1 per hour per user)
        $cacheKey = "case_view_audit_{$case->id}_{$user->id}";
        if (! Cache::has($cacheKey)) {
            $this->audit->log($user, 'case.viewed', $case);
            Cache::put($cacheKey, true, now()->addMinutes(60));
        }

        $case->load([
            'caseType',
            'fiscalYear',
            'purok',
            'encodedBy',
            'assignedTo',
            'caseParties.party.purok',
            'activeSummons.respondentCaseParty.party',
            'summons.respondentCaseParty.party',
            'summons.servedBy',
            'hearings.presidedBy',
            'hearings.summons',
            'escalations.destination',
            'escalations.requestedByParty.party',
            'escalations.signedByPersonnel',
            'statusLogs.user',
            'notes.user',
            'sourceReport',
        ]);

        $staffMembers = User::query()->where('is_active', true)->orderBy('name')->get()
            ->filter(fn (User $u) => $u->hasPermission('cases.view'));

        $summonsStats = $this->summonsCounter->forCase($case);

        return view('admin.cases.show', compact('case', 'staffMembers', 'summonsStats'));
    }

    /**
     * Show form for editing case particulars.
     */
    public function edit(BarangayCase $case): View
    {
        $this->fiscalYearGuard->assertWritable($case);

        if (! BarangayCase::visibleTo(request()->user())->where('id', $case->id)->exists()) {
            abort(404);
        }

        $caseTypes = CaseType::query()->where('is_active', true)->orderBy('sort_order')->get();
        $puroks = Purok::query()->orderBy('name')->get();

        return view('admin.cases.edit', compact('case', 'caseTypes', 'puroks'));
    }

    /**
     * Update case particulars.
     */
    public function update(Request $request, BarangayCase $case): RedirectResponse
    {
        $this->fiscalYearGuard->assertWritable($case);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'case_type_id' => ['required', 'exists:case_types,id'],
            'incident_date' => ['nullable', 'date', 'before_or_equal:filed_on'],
            'incident_location' => ['nullable', 'string', 'max:255'],
            'purok_id' => ['nullable', 'exists:puroks,id'],
            'narrative' => ['required', 'string', 'min:20', 'max:5000'],
            'is_confidential' => ['nullable', 'boolean'],
        ]);

        $old = $case->only(['title', 'case_type_id', 'incident_date', 'incident_location', 'purok_id', 'narrative', 'is_confidential']);
        $case->update([
            'title' => $validated['title'],
            'case_type_id' => $validated['case_type_id'],
            'incident_date' => $validated['incident_date'] ?? null,
            'incident_location' => $validated['incident_location'] ?? null,
            'purok_id' => $validated['purok_id'] ?? null,
            'narrative' => $validated['narrative'],
            'is_confidential' => (bool) ($validated['is_confidential'] ?? false),
        ]);

        $this->audit->log($request->user(), 'case.updated', $case, $old, $case->only(array_keys($old)));

        return redirect()->route('admin.cases.show', $case)->with('success', 'Case particulars updated successfully.');
    }

    /**
     * Transition case status.
     */
    public function status(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'to_status' => ['required', 'string', 'in:'.implode(',', CaseStatus::all())],
            'note' => ['required', 'string', 'min:5', 'max:1000'],
        ]);

        $this->statusService->transition($case, $validated['to_status'], $validated['note'], $request->user());

        return redirect()->route('admin.cases.show', $case)->with('success', 'Case status transitioned successfully.');
    }

    /**
     * Assign mediator or clerk to case.
     */
    public function assign(Request $request, BarangayCase $case): RedirectResponse
    {
        $this->fiscalYearGuard->assertWritable($case);

        $validated = $request->validate([
            'assigned_to' => ['nullable', 'exists:users,id'],
        ]);

        $oldAssignee = $case->assignedTo?->name ?? 'Unassigned';
        $case->update(['assigned_to' => $validated['assigned_to']]);
        $newAssignee = $case->fresh()->assignedTo?->name ?? 'Unassigned';

        $this->audit->log($request->user(), 'case.assigned', $case, ['assigned_to' => $oldAssignee], ['assigned_to' => $newAssignee]);

        return redirect()->route('admin.cases.show', $case)->with('success', "Case assigned to {$newAssignee}.");
    }

    /**
     * Store internal staff note.
     */
    public function storeNote(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:3', 'max:3000'],
        ]);

        CaseNote::create([
            'case_id' => $case->id,
            'user_id' => $request->user()->id,
            'body' => $validated['body'],
        ]);

        $this->audit->log($request->user(), 'case.note_added', $case, [], ['note_length' => strlen($validated['body'])]);

        return redirect()->route('admin.cases.show', $case)->with('success', 'Case note recorded.');
    }

    /**
     * Render printable case docket summary.
     */
    public function print(BarangayCase $case): View
    {
        if (! BarangayCase::visibleTo(request()->user())->where('id', $case->id)->exists()) {
            abort(404);
        }

        $case->load([
            'caseType',
            'fiscalYear',
            'purok',
            'complainants',
            'respondents',
            'witnesses',
            'statusLogs.user',
            'summons',
            'hearings',
        ]);

        return view('admin.cases.print', compact('case'));
    }

    /**
     * Hook to convert a problem report into a blotter case.
     */
    public function convertFromReport(Request $request, Report $report): RedirectResponse
    {
        return redirect()->route('admin.cases.create', [
            'source_report_id' => $report->id,
        ]);
    }
}
