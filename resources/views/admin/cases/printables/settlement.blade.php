@extends('layouts.print')

@section('title', 'Amicable Settlement (KP Form 16) · Case ' . $case->case_number)

@section('content')
<x-ui.draft-watermark :confirmed="$isConfirmed" />

<div style="text-align: right; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <strong>Date Settled:</strong> {{ $case->settled_on?->format('F d, Y') ?? now()->format('F d, Y') }}
</div>

<div style="text-align: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.35rem; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: bold;">
        AMICABLE SETTLEMENT
    </h2>
    <p style="margin-top: 0.25rem; font-size: 0.9rem; color: #475569;">
        KP Form No. 16 · Office of the Lupong Tagapamayapa
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
        We, the complainant(s) and respondent(s) in the above-captioned case, do hereby agree to settle our dispute amicably on the following terms and conditions:
    </p>

    <div style="margin: 1.25rem 0; padding: 1.25rem; border: 1px solid #0f172a; border-radius: 4px; background: #fff; font-family: Georgia, serif; line-height: 1.8;">
        <strong>TERMS AND CONDITIONS OF SETTLEMENT:</strong>
        <div style="margin-top: 0.5rem; white-space: pre-wrap;">{{ $case->settlement_terms ?: $case->outcome_note }}</div>

        @if($case->settlement_amount && $case->settlement_amount > 0)
            <div style="margin-top: 1rem; padding-top: 0.5rem; border-top: 1px dashed #cbd5e1; font-weight: bold;">
                Agreed Financial / Restitution Amount: PHP {{ number_format((float) $case->settlement_amount, 2) }}
            </div>
        @endif
    </div>

    <p style="margin-top: 1rem;">
        We hereby bind ourselves to faithfully comply with and respect the terms of this settlement.
    </p>
    <p style="margin-top: 0.5rem; font-size: 0.85rem; color: #334155;">
        <em>Notice: Under Section 416 of the Local Government Code of 1991 (RA 7160), this amicable settlement shall have the force and effect of a final judgment of a court upon the expiration of ten (10) days from the date hereof, unless repudiation is made within said period.</em>
    </p>
</div>

<!-- Signatures of Parties -->
<div style="margin-top: 2rem; margin-bottom: 2rem;">
    <p style="font-size: 0.875rem; margin-bottom: 1.5rem; font-weight: bold;">
        IN WITNESS WHEREOF, the parties have signed this Amicable Settlement this {{ $case->settled_on?->format('jS \d\a\y \o\f F, Y') ?? now()->format('jS \d\a\y \o\f F, Y') }}:
    </p>
    <div style="display: flex; justify-content: space-between; gap: 2rem;">
        <div style="width: 45%; text-align: center;">
            <div style="border-top: 1px solid #0f172a; margin-top: 3rem; padding-top: 4px;">
                <p style="font-size: 0.85rem; font-weight: bold;">Signature of Complainant(s)</p>
            </div>
        </div>
        <div style="width: 45%; text-align: center;">
            <div style="border-top: 1px solid #0f172a; margin-top: 3rem; padding-top: 4px;">
                <p style="font-size: 0.85rem; font-weight: bold;">Signature of Respondent(s)</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('signatories')
@include('partials.signatory-block', ['signatories' => $signatories])
@endsection
