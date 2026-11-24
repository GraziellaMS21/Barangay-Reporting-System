@extends('layouts.print')

@section('title', 'Notice of Hearing (KP Form 8/9) · Case ' . $case->case_number)

@section('content')
<x-ui.draft-watermark :confirmed="$isConfirmed" />

<div style="text-align: right; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <strong>Date:</strong> {{ now()->format('F d, Y') }}
</div>

<div style="text-align: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.35rem; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: bold;">
        NOTICE OF HEARING
    </h2>
    <p style="margin-top: 0.25rem; font-size: 0.9rem; color: #475569;">
        KP Form No. 8 / KP Form No. 9 · Conciliation Hearing before Pangkat Tagapagkasundo
    </p>
    <div style="margin-top: 0.5rem; font-weight: bold; font-size: 1rem;">
        Barangay Case No.: {{ $case->case_number }}
    </div>
</div>

<div style="margin-bottom: 1.5rem; border: 1px solid #cbd5e1; padding: 1.25rem; border-radius: 4px; background: #f8fafc;">
    <div style="display: flex; justify-content: space-between; gap: 2rem;">
        <div style="flex: 1;">
            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 4px;">Complainant(s):</p>
            @foreach($case->caseParties->where('role', 'complainant') as $cp)
                <p style="margin: 2px 0;"><strong>{{ $cp->party?->full_name ?? '' }}</strong></p>
            @endforeach
        </div>
        <div style="align-self: center; font-weight: bold; font-size: 1.2rem; color: #64748b;">
            — AGAINST —
        </div>
        <div style="flex: 1; text-align: right;">
            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 4px;">Respondent(s):</p>
            @foreach($case->caseParties->where('role', 'respondent') as $rp)
                <p style="margin: 2px 0;"><strong>{{ $rp->party?->full_name ?? '' }}</strong></p>
            @endforeach
        </div>
    </div>
</div>

<div style="margin-bottom: 1.5rem; font-size: 0.95rem; line-height: 1.8; text-align: justify;">
    <p>
        <strong>TO: Both Complainant(s) and Respondent(s)</strong>
    </p>

    <p style="margin-top: 1rem;">
        You are hereby required to appear personally before the <strong>Pangkat Tagapagkasundo</strong> on the following date, time, and venue for the conciliation and amicable settlement of your dispute:
    </p>

    <div style="margin: 1.25rem 0; padding: 1rem; border-left: 4px solid #0d9488; background: #f0fdfa;">
        <p style="margin: 0; font-size: 1rem; font-weight: bold; color: #115e59;">
            Hearing Schedule: {{ $hearing?->scheduled_at?->format('F d, Y (l) \a\t h:i A') ?? 'Scheduled hearing time' }}
        </p>
        <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #0f766e;">
            Venue: {{ $hearing?->venue ?? 'Barangay Hall Mediation Room, Barangay Calarian, City of Zamboanga' }}
        </p>
        @if($hearing?->presidedBy)
            <p style="margin: 0.25rem 0 0; font-size: 0.85rem; color: #134e4a;">
                Presiding: {{ $hearing->presidedBy->full_name }} ({{ $hearing->presidedBy->position?->name ?? 'Lupon Member' }})
            </p>
        @endif
    </div>

    <p>
        This hearing is conducted pursuant to the statutory 15-working-day conciliation period under Section 410(b) of the Local Government Code of 1991 (Republic Act No. 7160).
    </p>

    <p style="margin-top: 1rem;">
        Your personal appearance is strictly required. Non-appearance by the complainant without justifiable reason may cause the dismissal of the complaint, while non-appearance by the respondent shall bar the respondent from asserting any defense or counterclaim and may warrant the issuance of a Certificate to File Action.
    </p>
</div>
@endsection

@section('signatories')
@include('partials.signatory-block', ['signatories' => $signatories])
@endsection
