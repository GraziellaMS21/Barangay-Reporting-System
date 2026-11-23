<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\People\UpdateSignatoriesRequest;
use App\Models\Position;
use App\Models\SignatoryAssignment;
use App\Services\AuditLogger;
use App\Services\SignatoryResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SignatoryController extends Controller
{
    /**
     * Canonical document matrix per PLACEHOLDERS.md B2 and config/documents.php.
     *
     * @return array<string, array{label: string, roles: array<string, string>}>
     */
    public static function documentMatrixDefinitions(): array
    {
        $configured = config('documents.types', []);
        $defaults = [
            'referral_letter' => 'External Referral Letter',
            'summons' => 'Summons',
            'case_endorsement' => 'Case Endorsement Letter',
            'certificate_to_file_action' => 'Certification to File Action (CFA)',
            'settlement_agreement' => 'Amicable Settlement Agreement',
            'monthly_report' => 'Monthly Compliance Report (DILG)',
            'annual_report' => 'Annual Accomplishment Report (DILG)',
        ];

        $matrix = [];
        foreach ($defaults as $docKey => $label) {
            $matrix[$docKey] = [
                'label' => $configured[$docKey]['label'] ?? $label,
                'roles' => [
                    'prepared_by' => 'Prepared by',
                    'noted_by' => 'Noted by',
                    'approved_by' => 'Approved by',
                ],
            ];
        }

        foreach ($configured as $docKey => $meta) {
            if (! isset($matrix[$docKey])) {
                $matrix[$docKey] = [
                    'label' => $meta['label'] ?? ucwords(str_replace('_', ' ', $docKey)),
                    'roles' => [
                        'prepared_by' => 'Prepared by',
                        'noted_by' => 'Noted by',
                        'approved_by' => 'Approved by',
                    ],
                ];
            }
        }

        return $matrix;
    }

    public function __construct(
        protected SignatoryResolver $signatoryResolver,
        protected AuditLogger $audit,
    ) {}

    public function edit(): View
    {
        $definitions = self::documentMatrixDefinitions();
        $positions = Position::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
        $allAssignments = SignatoryAssignment::query()->with('position')->get();

        $matrixRows = [];
        $resolvedPreviews = [];
        foreach ($definitions as $docType => $meta) {
            $docAssignments = $allAssignments->where('document_type', $docType);
            $byRole = [];

            foreach ($meta['roles'] as $roleKey => $roleHuman) {
                $match = $docAssignments->first(function (SignatoryAssignment $a) use ($roleKey, $roleHuman): bool {
                    $norm = strtolower(str_replace(' ', '_', $a->role_label));

                    return $norm === $roleKey || strcasecmp($a->role_label, $roleHuman) === 0;
                });

                if ($roleKey === 'approved_by' && ! $match) {
                    $match = $docAssignments->first(fn (SignatoryAssignment $a) => in_array(strtolower($a->role_label), ['presided by', 'presided_by'], true));
                }

                $byRole[$roleKey] = $match;
            }

            $matrixRows[$docType] = [
                'label' => $meta['label'],
                'assignments' => $byRole,
                'is_confirmed' => $docAssignments->isNotEmpty() && $docAssignments->every(fn (SignatoryAssignment $a) => $a->is_confirmed),
            ];

            $resolvedPreviews[$docType] = $this->signatoryResolver->forDocument($docType);
        }

        $unconfirmedCount = $allAssignments->where('is_confirmed', false)->count();

        return view('admin.signatories.edit', compact(
            'matrixRows',
            'positions',
            'resolvedPreviews',
            'unconfirmedCount'
        ));
    }

    public function update(UpdateSignatoriesRequest $request): RedirectResponse
    {
        $submitted = $request->validated('assignments', []);
        $confirmAll = $request->boolean('confirm_all');
        $actor = $request->user();

        $roleSortOrder = [
            'prepared_by' => 1,
            'noted_by' => 2,
            'approved_by' => 3,
        ];

        $roleLabels = [
            'prepared_by' => 'Prepared by',
            'noted_by' => 'Noted by',
            'approved_by' => 'Approved by',
        ];

        DB::transaction(function () use ($submitted, $confirmAll, $actor, $roleSortOrder, $roleLabels): void {
            foreach ($submitted as $docType => $roles) {
                if (! is_array($roles)) {
                    continue;
                }

                foreach ($roles as $roleKey => $positionId) {
                    $humanLabel = $roleLabels[$roleKey] ?? ucwords(str_replace('_', ' ', (string) $roleKey));

                    $existing = SignatoryAssignment::query()
                        ->where('document_type', $docType)
                        ->whereIn('role_label', [$roleKey, $humanLabel, ($roleKey === 'approved_by' ? 'Presided by' : $humanLabel)])
                        ->first();

                    if (empty($positionId)) {
                        if ($existing) {
                            $old = $existing->toArray();
                            $existing->delete();
                            $this->audit->log($actor, 'signatory.removed', $existing, $old, []);
                        }

                        continue;
                    }

                    if ($existing) {
                        $old = $existing->toArray();
                        $existing->update([
                            'role_label' => $humanLabel,
                            'position_id' => (int) $positionId,
                            'sort_order' => $roleSortOrder[$roleKey] ?? 1,
                            'is_confirmed' => $confirmAll ? true : $existing->is_confirmed,
                        ]);
                        $this->audit->log($actor, 'signatory.updated', $existing, $old, $existing->fresh()->toArray());
                    } else {
                        $created = SignatoryAssignment::create([
                            'document_type' => $docType,
                            'role_label' => $humanLabel,
                            'position_id' => (int) $positionId,
                            'sort_order' => $roleSortOrder[$roleKey] ?? 1,
                            'is_confirmed' => $confirmAll,
                        ]);
                        $this->audit->log($actor, 'signatory.created', $created, [], $created->toArray());
                    }
                }
            }
        });

        return redirect()->route('admin.signatories.edit')->with('success', 'Signatories matrix updated.');
    }
}
