<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CaseType;
use App\Models\EscalationDestination;
use App\Models\Position;
use App\Models\ReportTemplate;
use App\Models\Setting;
use App\Models\SignatoryAssignment;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PendingConfirmationController extends Controller
{
    public function __construct(
        protected SettingsService $settingsService
    ) {}

    public function index(): View
    {
        $pendingSettings = Setting::where('is_confirmed', false)->where('key', '!=', 'system.setup_completed')->get();
        $pendingPositions = Position::where('is_confirmed', false)->get();
        $pendingCaseTypes = CaseType::where('is_confirmed', false)->get();
        $pendingSignatories = SignatoryAssignment::with('position')->where('is_confirmed', false)->get();
        $pendingDestinations = EscalationDestination::where('is_confirmed', false)->get();
        $pendingTemplates = ReportTemplate::where('is_confirmed', false)->get();

        $totalPending = $pendingSettings->count()
            + $pendingPositions->count()
            + $pendingCaseTypes->count()
            + $pendingSignatories->count()
            + $pendingDestinations->count()
            + $pendingTemplates->count();

        return view('admin.settings.pending', compact(
            'pendingSettings',
            'pendingPositions',
            'pendingCaseTypes',
            'pendingSignatories',
            'pendingDestinations',
            'pendingTemplates',
            'totalPending'
        ));
    }

    public function confirm(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'type' => ['required', 'string', 'in:setting,position,case_type,signatory,destination,template'],
            'id' => ['required'],
        ]);

        $user = $request->user();

        match ($validated['type']) {
            'setting' => $this->confirmModel(Setting::find($validated['id']), $user),
            'position' => $this->confirmModel(Position::find($validated['id']), $user),
            'case_type' => $this->confirmModel(CaseType::find($validated['id']), $user),
            'signatory' => $this->confirmModel(SignatoryAssignment::find($validated['id']), $user),
            'destination' => $this->confirmModel(EscalationDestination::find($validated['id']), $user),
            'template' => $this->confirmModel(ReportTemplate::find($validated['id']), $user),
        };

        return redirect()->route('admin.pending.index')->with('success', 'Item confirmed officially.');
    }

    public function confirmGroup(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'group' => ['required', 'string', 'in:settings,positions,case_types,signatories,destinations,templates'],
        ]);

        $user = $request->user();

        match ($validated['group']) {
            'settings' => Setting::where('is_confirmed', false)->where('key', '!=', 'system.setup_completed')->get()->each(fn ($m) => $this->confirmModel($m, $user)),
            'positions' => Position::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user)),
            'case_types' => CaseType::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user)),
            'signatories' => SignatoryAssignment::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user)),
            'destinations' => EscalationDestination::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user)),
            'templates' => ReportTemplate::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user)),
        };

        return redirect()->route('admin.pending.index')->with('success', 'All items in group confirmed.');
    }

    public function confirmAll(Request $request): RedirectResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user): void {
            Setting::where('is_confirmed', false)->where('key', '!=', 'system.setup_completed')->get()->each(fn ($m) => $this->confirmModel($m, $user));
            Position::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user));
            CaseType::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user));
            SignatoryAssignment::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user));
            EscalationDestination::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user));
            ReportTemplate::where('is_confirmed', false)->get()->each(fn ($m) => $this->confirmModel($m, $user));
        });

        return redirect()->route('admin.pending.index')->with('success', 'All baseline defaults confirmed officially.');
    }

    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="pending_confirmations_'.now()->format('Ymd_His').'.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Type', 'Item Label / Name', 'Current Value', 'Questionnaire Reference', 'Group / Area']);

            // Settings
            foreach (Setting::where('is_confirmed', false)->where('key', '!=', 'system.setup_completed')->get() as $s) {
                fputcsv($handle, ['Setting', $s->label ?? $s->key, $s->value, $s->source_question ?? 'N/A', $s->group ?? 'general']);
            }

            // Positions
            foreach (Position::where('is_confirmed', false)->get() as $p) {
                fputcsv($handle, ['Position', $p->name, $p->code, 'Q14: Positions & Lupon', 'Governance']);
            }

            // Case Types
            foreach (CaseType::where('is_confirmed', false)->get() as $c) {
                fputcsv($handle, ['Case Type', $c->name, $c->slug, 'Q18: Dispute Categories', 'Cases']);
            }

            // Signatories
            foreach (SignatoryAssignment::where('is_confirmed', false)->get() as $sa) {
                fputcsv($handle, ['Signatory', $sa->document_type.' - '.$sa->role_label, $sa->position?->name ?? 'Unassigned', 'Q15: Official Signatories', 'Documents']);
            }

            // Destinations
            foreach (EscalationDestination::where('is_confirmed', false)->get() as $d) {
                fputcsv($handle, ['Destination', $d->name, 'External Venue', 'Q28: Escalation Targets', 'Escalations']);
            }

            // Templates
            foreach (ReportTemplate::where('is_confirmed', false)->get() as $t) {
                fputcsv($handle, ['Template', $t->title, $t->key, 'Q32: Statutory Reporting', 'Reports']);
            }

            fclose($handle);
        }, 200, $headers);
    }

    protected function confirmModel(mixed $model, mixed $user): void
    {
        if (! $model) {
            return;
        }

        $model->update([
            'is_confirmed' => true,
            'confirmed_by' => $user?->id,
            'confirmed_at' => now(),
        ]);
    }
}
