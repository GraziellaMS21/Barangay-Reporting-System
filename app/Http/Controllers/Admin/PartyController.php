<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Party;
use App\Models\Purok;
use App\Services\AuditLogger;
use App\Services\Cases\PartyMatchingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PartyController extends Controller
{
    public function __construct(
        protected AuditLogger $audit,
        protected PartyMatchingService $matchingService
    ) {}

    /**
     * Display a listing of master parties directory.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');
        $residency = $request->query('residency');
        $purokId = $request->query('purok_id');

        $parties = Party::query()
            ->active()
            ->with(['purok', 'verifiedBy'])
            ->withCount('cases')
            ->when($search, function ($q, $term) {
                $q->where(function ($query) use ($term) {
                    $query->where('full_name', 'like', "%{$term}%")
                        ->orWhere('alias', 'like', "%{$term}%")
                        ->orWhere('contact_number', 'like', "%{$term}%")
                        ->orWhere('id_number', 'like', "%{$term}%");
                });
            })
            ->when($residency && $residency !== 'all', fn ($q) => $q->where('residency_status', $residency))
            ->when($purokId, fn ($q) => $q->where('purok_id', $purokId))
            ->orderBy('full_name')
            ->paginate(20)
            ->withQueryString();

        $puroks = Purok::query()->orderBy('name')->get();

        return view('admin.parties.index', compact('parties', 'puroks', 'search', 'residency', 'purokId'));
    }

    /**
     * Autocomplete search for party finder.
     */
    public function search(Request $request): JsonResponse
    {
        $term = trim($request->query('q', ''));
        if (strlen($term) < 2) {
            return response()->json(['parties' => []]);
        }

        $parties = Party::query()
            ->active()
            ->with('purok')
            ->where(function ($q) use ($term) {
                $q->where('full_name', 'like', "%{$term}%")
                    ->orWhere('alias', 'like', "%{$term}%")
                    ->orWhere('contact_number', 'like', "%{$term}%");
            })
            ->limit(10)
            ->get()
            ->map(function (Party $party) {
                return [
                    'id' => $party->id,
                    'full_name' => $party->full_name,
                    'alias' => $party->alias,
                    'residency_status' => $party->residency_status,
                    'purok_name' => $party->purok?->name ?? 'N/A',
                    'age' => $party->birth_date ? $party->birth_date->age : null,
                    'address_line' => $party->address_line,
                    'contact_number' => $party->contact_number,
                ];
            });

        return response()->json(['parties' => $parties]);
    }

    /**
     * Display party profile and docket history.
     */
    public function show(Party $party): View|RedirectResponse
    {
        if ($party->merged_into_id) {
            return redirect()->route('admin.parties.show', $party->merged_into_id)
                ->with('info', "Record was previously merged into {$party->mergedInto?->full_name}.");
        }

        $party->load([
            'purok',
            'verifiedBy',
            'caseParties.case.caseType',
            'caseParties.case.fiscalYear',
            'mergedParties',
        ]);

        $puroks = Purok::query()->orderBy('name')->get();

        return view('admin.parties.show', compact('party', 'puroks'));
    }

    /**
     * Show form for editing a party.
     */
    public function edit(Party $party): View
    {
        $puroks = Purok::query()->orderBy('name')->get();

        return view('admin.parties.edit', compact('party', 'puroks'));
    }

    /**
     * Update party details.
     */
    public function update(Request $request, Party $party): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'alias' => ['nullable', 'string', 'max:80'],
            'birth_date' => ['nullable', 'date', 'before:today'],
            'sex' => ['nullable', 'string', 'in:male,female,other'],
            'civil_status' => ['nullable', 'string', 'max:40'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'contact_number' => ['nullable', 'regex:/^(\+63|0)[0-9\s\-]{9,13}$/'],
            'email' => ['nullable', 'email', 'max:150'],
            'address_line' => ['nullable', 'string', 'max:255'],
            'purok_id' => ['nullable', 'exists:puroks,id'],
            'home_barangay' => ['nullable', 'string', 'max:100'],
            'home_city' => ['nullable', 'string', 'max:100'],
            'residency_status' => ['required', 'in:resident,non_resident,unverified'],
            'residency_basis' => ['nullable', 'string', 'max:200'],
            'id_type' => ['nullable', 'string', 'max:50'],
            'id_number' => ['nullable', 'string', 'max:60'],
        ]);

        $old = $party->only(array_keys($validated));
        $party->update($validated);

        $this->audit->log(
            $request->user(),
            'party.updated',
            $party,
            $old,
            $party->only(array_keys($validated))
        );

        return redirect()->route('admin.parties.show', $party)->with('success', 'Party profile updated successfully.');
    }

    /**
     * Verify residency status of a party.
     */
    public function verifyResidency(Request $request, Party $party): RedirectResponse
    {
        $validated = $request->validate([
            'residency_status' => ['required', 'in:resident,non_resident,unverified'],
            'residency_basis' => ['nullable', 'string', 'max:200'],
            'verification_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $old = [
            'residency_status' => $party->residency_status,
            'residency_basis' => $party->residency_basis,
        ];

        $party->update([
            'residency_status' => $validated['residency_status'],
            'residency_basis' => $validated['residency_basis'] ?? $party->residency_basis,
            'residency_verified_by' => $request->user()->id,
            'residency_verified_at' => now(),
        ]);

        $this->audit->log(
            $request->user(),
            'party.residency_verified',
            $party,
            $old,
            [
                'residency_status' => $party->residency_status,
                'residency_basis' => $party->residency_basis,
                'notes' => $validated['verification_notes'] ?? null,
            ]
        );

        return redirect()->route('admin.parties.show', $party)->with('success', 'Residency status verified and recorded.');
    }

    /**
     * Show preview interface to merge duplicate party records.
     */
    public function mergeCreate(Request $request, Party $party): View
    {
        $targetPartyId = $request->query('target_id');
        $targetParty = $targetPartyId ? Party::query()->active()->find($targetPartyId) : null;

        // If no target selected yet, provide possible matches
        $potentialMatches = $this->matchingService->findPossibleDuplicates(
            $party->full_name,
            $party->birth_date?->format('Y-m-d'),
            $party->contact_number,
            $party->id
        );

        return view('admin.parties.merge', compact('party', 'targetParty', 'potentialMatches'));
    }

    /**
     * Execute merge of duplicate party records.
     */
    public function mergeStore(Request $request, Party $party): RedirectResponse
    {
        $validated = $request->validate([
            'keep_party_id' => ['required', 'exists:parties,id', 'different:source_party_id'],
            'note' => ['required', 'string', 'min:5', 'max:500'],
            'confirm_understanding' => ['accepted'],
        ]);

        $keepParty = Party::query()->active()->findOrFail($validated['keep_party_id']);

        DB::transaction(function () use ($party, $keepParty, $validated, $request) {
            // Re-point case parties, avoid duplicate pivot rows
            $existingRoles = $keepParty->caseParties()->get(['case_id', 'role'])->map(fn ($cp) => "{$cp->case_id}-{$cp->role}")->all();

            foreach ($party->caseParties as $caseParty) {
                $key = "{$caseParty->case_id}-{$caseParty->role}";
                if (in_array($key, $existingRoles, true)) {
                    $caseParty->delete();
                } else {
                    $caseParty->update(['party_id' => $keepParty->id]);
                    $existingRoles[] = $key;
                }
            }

            // Fill empty fields on keepParty from party
            $attributesToBackfill = [
                'alias', 'birth_date', 'sex', 'civil_status', 'occupation',
                'contact_number', 'email', 'address_line', 'purok_id',
                'home_barangay', 'home_city', 'id_type', 'id_number',
            ];

            $updates = [];
            foreach ($attributesToBackfill as $field) {
                if (empty($keepParty->{$field}) && ! empty($party->{$field})) {
                    $updates[$field] = $party->{$field};
                }
            }

            if (! empty($updates)) {
                $keepParty->update($updates);
            }

            // Mark source party as merged
            $party->update([
                'merged_into_id' => $keepParty->id,
                'merged_at' => now(),
            ]);

            $this->audit->log(
                $request->user(),
                'party.merged',
                $keepParty,
                ['merged_party_id' => $party->id, 'merged_name' => $party->full_name],
                ['note' => $validated['note'], 'backfilled_fields' => array_keys($updates)]
            );
        });

        return redirect()->route('admin.parties.show', $keepParty)
            ->with('success', "Party {$party->full_name} successfully merged into {$keepParty->full_name}.");
    }
}
