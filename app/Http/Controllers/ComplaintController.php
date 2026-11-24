<?php

namespace App\Http\Controllers;

use App\Models\CaseType;
use App\Models\ComplaintIntake;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ComplaintController extends Controller
{
    /**
     * Show the resident dispute complaint intake form with VAWC screening.
     */
    public function create(Request $request): View
    {
        $caseTypes = CaseType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $hotlines = [
            'vawc_national' => Setting::value('hotline.vawc.national') ?? '911',
            'vawc_pnp' => Setting::value('hotline.vawc.pnp') ?? '0919-777-7377',
            'bcpc_national' => Setting::value('hotline.bcpc.national') ?? '163',
            'vawc_barangay' => Setting::value('hotline.vawc.barangay') ?? '0917-123-4567',
            'bcpc_cswdo' => Setting::value('hotline.bcpc.cswdo') ?? '0917-765-4321',
        ];

        return view('resident.complaint.create', [
            'caseTypes' => $caseTypes,
            'hotlines' => $hotlines,
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

        // Confidentiality gate: If user selected VAWC/child protection, do NOT save; direct to hotlines
        if ($request->boolean('involves_confidential_matter')) {
            return redirect()->route('complaint.create', ['confidential_alert' => '1']);
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

            // 5Ws
            'what_happened' => ['required', 'string', 'min:10', 'max:4000'],
            'who_involved' => ['required', 'string', 'min:5', 'max:1000'],
            'when_happened' => ['required', 'date', 'before_or_equal:now'],
            'where_happened' => ['required', 'string', 'min:5', 'max:1000'],
            'why_how_happened' => ['required', 'string', 'min:10', 'max:4000'],

            // Legal relief
            'rights_violated' => ['required', 'string', 'min:10', 'max:2000'],
            'relief_requested' => ['required', 'string', 'min:10', 'max:2000'],
            'case_type_suggestion' => ['nullable', 'string', 'max:100'],

            // Explicit RA 10173 data privacy consent
            'consent' => ['accepted'],
        ], [
            'complainant_name.required' => 'Please provide your full legal name as complainant.',
            'complainant_contact.required' => 'Please provide a valid contact number or email address.',
            'complainant_address.required' => 'Please provide your complete residential address.',
            'respondent_name.required' => 'Please provide the name of the respondent.',
            'respondent_address.required' => 'Please provide the respondent complete address for summons delivery.',
            'what_happened.required' => 'Please describe what incident occurred.',
            'what_happened.min' => 'The incident description must be at least 10 characters.',
            'who_involved.required' => 'Please list the persons involved.',
            'when_happened.required' => 'Please provide the date and time when the incident took place.',
            'when_happened.before_or_equal' => 'The incident date cannot be in the future.',
            'where_happened.required' => 'Please state the location where the incident occurred.',
            'why_how_happened.required' => 'Please provide the circumstances or cause of the dispute.',
            'rights_violated.required' => 'Please state what rights were violated or damage was suffered.',
            'relief_requested.required' => 'Please state what remedy or settlement terms you are requesting.',
            'consent.accepted' => 'You must agree to the Data Privacy Notice in accordance with RA 10173.',
        ]);

        ComplaintIntake::create([
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
            'what_happened' => $validated['what_happened'],
            'who_involved' => $validated['who_involved'],
            'when_happened' => $validated['when_happened'],
            'where_happened' => $validated['where_happened'],
            'why_how_happened' => $validated['why_how_happened'],
            'rights_violated' => $validated['rights_violated'],
            'relief_requested' => $validated['relief_requested'],
            'case_type_suggestion' => $validated['case_type_suggestion'] ?? null,
            'confidentiality_screen_passed' => true,
            'consent_at' => now(),
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

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
