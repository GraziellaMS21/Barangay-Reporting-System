<?php

namespace App\Http\Controllers;

use App\Models\CaseType;
use App\Models\ComplaintIntake;
use App\Services\GeminiService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    /**
     * Show the resident dispute complaint intake form.
     */
    public function create(Request $request): View
    {
        $caseTypes = CaseType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('resident.complaint.create', [
            'caseTypes' => $caseTypes,
        ]);
    }

    /**
     * Store a citizen dispute complaint request as a pending intake.
     */
    public function store(Request $request): RedirectResponse
    {
        // Honeypot spam defense: bots filling hidden dummy field are redirected silently
        if ($request->filled('honeypot_website') || $request->filled('sp_check')) {
            return redirect()->route('complaint.received');
        }

        $validated = $request->validate([
            'complainant_name' => ['required', 'string', 'max:255'],
            'complainant_alias' => ['nullable', 'string', 'max:100'],
            'complainant_age' => ['nullable', 'integer', 'min:1', 'max:130'],
            'complainant_contact' => ['required', 'string', 'max:50'],
            'complainant_address' => ['required', 'string', 'max:1000'],
            'complainant_residency_claim' => ['nullable', 'string', 'in:resident,non_resident,transient,unverified'],

            // Respondent unlinked free text
            'respondent_name' => ['required', 'string', 'max:255'],
            'respondent_alias' => ['nullable', 'string', 'max:100'],
            'respondent_address' => ['required', 'string', 'max:1000'],
            'respondent_contact' => ['nullable', 'string', 'max:50'],

            // Section 3 & 4: Natural language narratives with backward compatibility
            'incident_narrative' => ['required_without:what_happened', 'nullable', 'string', 'min:10', 'max:5000'],
            'rights_and_relief' => ['required_without:rights_violated', 'nullable', 'string', 'min:10', 'max:5000'],

            // Legacy 5Ws (optional for backward compatibility)
            'what_happened' => ['nullable', 'string', 'min:10', 'max:4000'],
            'who_involved' => ['nullable', 'string', 'max:1000'],
            'when_happened' => ['nullable', 'date', 'before_or_equal:now'],
            'where_happened' => ['nullable', 'string', 'max:1000'],
            'why_how_happened' => ['nullable', 'string', 'max:4000'],
            'rights_violated' => ['nullable', 'string', 'max:2000'],
            'relief_requested' => ['nullable', 'string', 'max:2000'],
            'case_type_suggestion' => ['nullable', 'string', 'max:100'],

            // Explicit RA 10173 data privacy consent
            'consent' => ['accepted'],
        ], [
            'complainant_name.required' => 'Please provide your full legal name as complainant.',
            'complainant_contact.required' => 'Please provide a valid contact number or email address.',
            'complainant_address.required' => 'Please provide your complete residential address.',
            'respondent_name.required' => 'Please provide the name of the respondent.',
            'respondent_address.required' => 'Please provide the respondent complete address for summons delivery.',
            'incident_narrative.required_without' => 'Please describe what incident occurred in the Incident Narrative field.',
            'incident_narrative.min' => 'The Incident Narrative must be at least 10 characters.',
            'rights_and_relief.required_without' => 'Please state the rights violated and relief requested.',
            'rights_and_relief.min' => 'The Rights Violated & Relief Requested must be at least 10 characters.',
            'what_happened.required' => 'Please describe what incident occurred.',
            'what_happened.min' => 'The incident description must be at least 10 characters.',
            'consent.accepted' => 'You must agree to the Data Privacy Notice in accordance with RA 10173.',
        ]);

        $narrative = $validated['incident_narrative'] ?? $validated['what_happened'] ?? '';
        $rightsRelief = $validated['rights_and_relief'] ?? trim(($validated['rights_violated'] ?? '').' '.($validated['relief_requested'] ?? ''));

        $intake = ComplaintIntake::create([
            'complainant_name' => $validated['complainant_name'],
            'complainant_alias' => $validated['complainant_alias'] ?? null,
            'complainant_age' => $validated['complainant_age'] ?? null,
            'complainant_contact' => $validated['complainant_contact'],
            'complainant_address' => $validated['complainant_address'],
            'complainant_residency_claim' => $validated['complainant_residency_claim'] ?? 'resident',
            'respondent_name' => $validated['respondent_name'],
            'respondent_alias' => $validated['respondent_alias'] ?? null,
            'respondent_address' => $validated['respondent_address'],
            'respondent_contact' => $validated['respondent_contact'] ?? null,
            'incident_narrative' => $narrative,
            'rights_and_relief' => $rightsRelief,
            'what_happened' => $validated['what_happened'] ?? $narrative,
            'who_involved' => $validated['who_involved'] ?? null,
            'when_happened' => $validated['when_happened'] ?? null,
            'where_happened' => $validated['where_happened'] ?? null,
            'why_how_happened' => $validated['why_how_happened'] ?? null,
            'rights_violated' => $validated['rights_violated'] ?? $rightsRelief,
            'relief_requested' => $validated['relief_requested'] ?? null,
            'case_type_suggestion' => $validated['case_type_suggestion'] ?? null,
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'status' => 'pending',
            'ai_status' => 'pending',
            'submitted_at' => now(),
        ]);

        try {
            app(GeminiService::class)->analyzeComplaint($intake);
        } catch (\Throwable $e) {
            Log::warning('Complaint AI analysis exception in controller: '.$e->getMessage());
        }

        return redirect()->route('complaint.received');
    }

    /**
     * Display the private acknowledgement screen.
     */
    public function received(): View
    {
        return view('resident.complaint.received');
    }
}
