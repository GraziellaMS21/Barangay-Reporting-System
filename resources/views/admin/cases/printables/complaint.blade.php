@extends('layouts.print')

@section('title', 'Formal Complaint (KP Form 1) · Case ' . $case->case_number)

@section('content')
<x-ui.draft-watermark :confirmed="$isConfirmed" />

<div style="text-align: right; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <strong>Date Filed:</strong> {{ $case->filed_on?->format('F d, Y') ?? now()->format('F d, Y') }}
</div>

<div style="text-align: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.35rem; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: bold;">
        COMPLAINT
    </h2>
    <p style="margin-top: 0.25rem; font-size: 0.9rem; color: #475569;">
        KP Form No. 1 · Office of the Lupong Tagapamayapa
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
                <p style="font-size: 0.8rem; color: #475569; margin: 0;">{{ $cp->party?->address_line ?? '' }}</p>
            @endforeach
        </div>
        <div style="align-self: center; font-weight: bold; font-size: 1.2rem; color: #64748b;">
            — AGAINST —
        </div>
        <div style="flex: 1; text-align: right;">
            <p style="font-weight: bold; text-decoration: underline; margin-bottom: 4px;">Respondent(s):</p>
            @foreach($case->caseParties->where('role', 'respondent') as $rp)
                <p style="margin: 2px 0;"><strong>{{ $rp->party?->full_name ?? '' }}</strong></p>
                <p style="font-size: 0.8rem; color: #475569; margin: 0;">{{ $rp->party?->address_line ?? '' }}</p>
            @endforeach
        </div>
    </div>
</div>

<div style="margin-bottom: 1.5rem;">
    <p style="font-size: 0.95rem; line-height: 1.6;">
        I / WE hereby accuse the above-named respondent(s) of violating my / our rights and the peace of the community, specifically regarding:
    </p>
    <p style="font-size: 1rem; font-weight: bold; margin: 0.5rem 0; color: #0f172a;">
        For: {{ $case->title }} ({{ $case->caseType?->name ?? '' }})
    </p>
</div>

<div style="margin-bottom: 2rem;">
    <h3 style="font-size: 1rem; font-weight: bold; border-bottom: 1px solid #0f172a; padding-bottom: 4px; margin-bottom: 8px;">
        STATEMENT OF CAUSE OF ACTION / NARRATIVE
    </h3>
    <div style="font-size: 0.95rem; line-height: 1.8; text-align: justify; font-family: Georgia, serif;">
        {{ $case->narrative }}
    </div>
</div>

<div style="margin-bottom: 2rem;">
    <h3 style="font-size: 1rem; font-weight: bold; border-bottom: 1px solid #0f172a; padding-bottom: 4px; margin-bottom: 8px;">
        PRAYER / RELIEF SOUGHT
    </h3>
    <p style="font-size: 0.95rem; line-height: 1.6; text-align: justify;">
        WHEREFORE, complainant(s) respectfully pray that the Lupong Tagapamayapa mediate and facilitate an amicable settlement, restitution, or appropriate peaceful resolution of the dispute in accordance with Katarungang Pambarangay law (RA 7160).
    </p>
</div>

<div style="margin-top: 3rem; margin-bottom: 1.5rem;">
    <p style="font-size: 0.875rem; margin-bottom: 2rem;">
        Made and filed this <strong>{{ $case->filed_on?->format('jS') ?? now()->format('jS') }}</strong> day of <strong>{{ $case->filed_on?->format('F, Y') ?? now()->format('F, Y') }}</strong> at Barangay Calarian, City of Zamboanga.
    </p>
    <div style="display: flex; justify-content: space-between; gap: 2rem;">
        <div style="text-align: center; width: 45%;">
            <div style="border-top: 1px solid #0f172a; margin-top: 2.5rem; padding-top: 4px;">
                <p style="font-weight: bold; font-size: 0.875rem;">Signature of Complainant(s)</p>
            </div>
        </div>
        <div style="text-align: center; width: 45%;">
            <div style="border-top: 1px solid #0f172a; margin-top: 2.5rem; padding-top: 4px;">
                <p style="font-weight: bold; font-size: 0.875rem;">Received & Sworn before Lupon</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('signatories')
@include('partials.signatory-block', ['signatories' => $signatories])
@endsection
