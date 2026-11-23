<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BarangayCase;
use App\Models\Hearing;
use App\Services\AuditLogger;
use App\Services\CaseStatusService;
use App\Support\CaseStatus;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class HearingController extends Controller
{
    public function __construct(
        protected CaseStatusService $statusService,
        protected AuditLogger $auditLogger
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $query = Hearing::query()
            ->with(['case.caseParties.party', 'summons', 'presidedBy'])
            ->whereHas('case', function ($q) use ($user): void {
                if ($user) {
                    $q->visibleTo($user);
                }
            });

        $filter = $request->query('filter', 'upcoming');

        if ($filter === 'today') {
            $query->whereDate('scheduled_at', today());
        } elseif ($filter === 'upcoming') {
            $query->where('scheduled_at', '>=', now()->startOfDay());
        } elseif ($filter === 'past') {
            $query->where('scheduled_at', '<', now()->startOfDay());
        }

        $hearings = $query->orderBy('scheduled_at')->paginate(20)->withQueryString();

        $todayCount = Hearing::query()
            ->whereDate('scheduled_at', today())
            ->whereHas('case', function ($q) use ($user): void {
                if ($user) {
                    $q->visibleTo($user);
                }
            })->count();

        $upcomingCount = Hearing::query()
            ->where('scheduled_at', '>=', now()->startOfDay())
            ->whereHas('case', function ($q) use ($user): void {
                if ($user) {
                    $q->visibleTo($user);
                }
            })->count();

        return view('admin.case-actions.hearings.index', [
            'hearings' => $hearings,
            'filter' => $filter,
            'todayCount' => $todayCount,
            'upcomingCount' => $upcomingCount,
        ]);
    }

    public function store(Request $request, BarangayCase $case): RedirectResponse
    {
        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'presided_by_personnel_id' => ['nullable', 'integer', Rule::exists('personnel', 'id')],
            'summons_id' => [
                'nullable',
                'integer',
                Rule::exists('summons', 'id')->where('case_id', $case->id),
            ],
        ]);

        $hearing = Hearing::create([
            'case_id' => $case->id,
            'scheduled_at' => $validated['scheduled_at'],
            'venue' => $validated['venue'] ?? 'Barangay Hall Mediation Room',
            'presided_by_personnel_id' => $validated['presided_by_personnel_id'] ?? null,
            'summons_id' => $validated['summons_id'] ?? null,
            'status' => 'scheduled',
        ]);

        if ($case->status === CaseStatus::FILED) {
            $this->statusService->transition(
                $case,
                CaseStatus::IN_MEDIATION,
                'Hearing scheduled; case placed under mediation.',
                $request->user()
            );
        }

        $this->auditLogger->log(
            $request->user(),
            'case.hearing_scheduled',
            $case,
            [],
            ['hearing_id' => $hearing->id, 'scheduled_at' => $hearing->scheduled_at->toIso8601String()]
        );

        return redirect()->route('admin.cases.show', ['case' => $case, 'tab' => 'hearings'])
            ->with('success', 'Hearing successfully scheduled.');
    }

    public function update(Request $request, BarangayCase $case, Hearing $hearing): RedirectResponse
    {
        abort_unless($hearing->case_id === $case->id, 404);

        $validated = $request->validate([
            'scheduled_at' => ['required', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'presided_by_personnel_id' => ['nullable', 'integer', Rule::exists('personnel', 'id')],
            'status' => ['nullable', 'string', Rule::in(['scheduled', 'held', 'cancelled', 'reset'])],
        ]);

        $old = $hearing->only(['scheduled_at', 'venue', 'presided_by_personnel_id', 'status']);

        $hearing->update([
            'scheduled_at' => $validated['scheduled_at'],
            'venue' => $validated['venue'] ?? $hearing->venue,
            'presided_by_personnel_id' => $validated['presided_by_personnel_id'] ?? $hearing->presided_by_personnel_id,
            'status' => $validated['status'] ?? $hearing->status,
        ]);

        $this->auditLogger->log(
            $request->user(),
            'case.hearing_updated',
            $case,
            $old,
            $hearing->only(['scheduled_at', 'venue', 'presided_by_personnel_id', 'status'])
        );

        return redirect()->route('admin.cases.show', ['case' => $case, 'tab' => 'hearings'])
            ->with('success', 'Hearing details updated.');
    }

    public function outcome(Request $request, BarangayCase $case, Hearing $hearing): RedirectResponse
    {
        abort_unless($hearing->case_id === $case->id, 404);

        $validated = $request->validate([
            'outcome_note' => ['required', 'string'],
            'status' => ['nullable', 'string', Rule::in(['scheduled', 'held', 'cancelled', 'reset'])],
        ]);

        $status = $validated['status'] ?? 'held';

        $hearing->update([
            'outcome_note' => $validated['outcome_note'],
            'status' => $status,
        ]);

        $this->auditLogger->log(
            $request->user(),
            'case.hearing_outcome_recorded',
            $case,
            [],
            ['hearing_id' => $hearing->id, 'outcome_note' => $validated['outcome_note'], 'status' => $status]
        );

        return redirect()->route('admin.cases.show', ['case' => $case, 'tab' => 'hearings'])
            ->with('success', 'Hearing proceedings and outcome recorded.');
    }
}
