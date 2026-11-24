@extends('layouts.print')

@section('title', 'Summons (KP Form 7) · Case ' . $case->case_number)

@section('content')
<x-ui.draft-watermark :confirmed="$isConfirmed" />

<div style="text-align: right; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <strong>Date Issued:</strong> {{ $summons?->issued_on?->format('F d, Y') ?? now()->format('F d, Y') }}
</div>

<div style="text-align: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.35rem; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: bold;">
        SUMMONS
    </h2>
    <p style="margin-top: 0.25rem; font-size: 0.9rem; color: #475569;">
        KP Form No. 7 · Office of the Lupong Tagapamayapa
    </p>
    <div style="margin-top: 0.5rem; font-weight: bold; font-size: 1rem;">
        Summons No.: {{ $summons?->summons_number ?? ('SUM-' . now()->year . '-' . $case->case_number) }}
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
            @if($summons?->respondentCaseParty?->party)
                <p style="margin: 2px 0;"><strong>{{ $summons->respondentCaseParty->party->full_name }}</strong></p>
                <p style="font-size: 0.8rem; color: #475569; margin: 0;">{{ $summons->respondentCaseParty->party->address_line ?? '' }}</p>
            @else
                @foreach($case->caseParties->where('role', 'respondent') as $rp)
                    <p style="margin: 2px 0;"><strong>{{ $rp->party?->full_name ?? '' }}</strong></p>
                    <p style="font-size: 0.8rem; color: #475569; margin: 0;">{{ $rp->party?->address_line ?? '' }}</p>
                @endforeach
            @endif
        </div>
    </div>
</div>

<div style="margin-bottom: 1.5rem; font-size: 0.95rem; line-height: 1.8; text-align: justify;">
    <p>
        <strong>TO:</strong>
        @if($summons?->respondentCaseParty?->party)
            <strong>{{ $summons->respondentCaseParty->party->full_name }}</strong>, resident of {{ $summons->respondentCaseParty->party->address_line ?? 'Barangay Calarian, City of Zamboanga' }}.
        @else
            The Above-Named Respondent(s).
        @endif
    </p>

    <p style="margin-top: 1rem;">
        You are hereby summoned to appear personally, without counsel or representative, before the Punong Barangay / Lupong Tagapamayapa on:
    </p>

    <div style="margin: 1.25rem 0; padding: 1rem; border-left: 4px solid #2563eb; background: #eff6ff;">
        <p style="margin: 0; font-size: 1rem; font-weight: bold; color: #1e3a8a;">
            Date & Time: {{ $summons?->hearing_at?->format('F d, Y (l) \a\t h:i A') ?? 'Scheduled hearing time' }}
        </p>
        <p style="margin: 0.25rem 0 0; font-size: 0.9rem; color: #1e40af;">
            Venue: {{ $summons?->venue ?? 'Barangay Hall Mediation Room, Barangay Calarian, City of Zamboanga' }}
        </p>
    </div>

    <p>
        To answer and give your side regarding the complaint filed by the complainant(s) above for <strong>{{ $case->title }}</strong>.
    </p>

    <p style="margin-top: 1rem;">
        <strong>WARNING:</strong> Failure on your part to appear in response to this summons without justifiable excuse may result in your being barred from filing any counterclaim arising from the said complaint in any court of justice, and may warrant the issuance of a Certification to File Action and the assessment of legal sanctions under Sec. 412(b) of Republic Act No. 7160.
    </p>
</div>

<!-- Officer's Return of Service Section -->
<div style="margin-top: 2rem; border-top: 2px dashed #94a3b8; padding-top: 1.5rem;">
    <h3 style="font-size: 0.95rem; font-weight: bold; text-transform: uppercase; margin-bottom: 0.5rem;">
        OFFICER'S RETURN OF SERVICE
    </h3>
    <p style="font-size: 0.85rem; line-height: 1.6; color: #334155;">
        I HEREBY CERTIFY that on this ______ day of __________________, 20____, I have served the foregoing Summons upon the respondent by:
    </p>
    <div style="font-size: 0.85rem; margin-top: 0.5rem; line-height: 1.8;">
        <div>[ &nbsp; ] Personal service upon respondent in person.</div>
        <div>[ &nbsp; ] Substituted service leaving a copy at respondent's residence with a person of sufficient age and discretion.</div>
        <div>[ &nbsp; ] Refusal of respondent to receive/sign; copy tendered and left at premises.</div>
    </div>
    <div style="margin-top: 2rem; display: flex; justify-content: space-between;">
        <div style="width: 40%; text-align: center;">
            <div style="border-top: 1px solid #0f172a; margin-top: 2rem; padding-top: 4px;">
                <p style="font-size: 0.8rem; font-weight: bold;">Signature of Respondent / Recipient</p>
            </div>
        </div>
        <div style="width: 40%; text-align: center;">
            <div style="border-top: 1px solid #0f172a; margin-top: 2rem; padding-top: 4px;">
                <p style="font-size: 0.8rem; font-weight: bold;">Serving Officer / Barangay Tanod</p>
            </div>
        </div>
    </div>
</div>
@endsection

@section('signatories')
@include('partials.signatory-block', ['signatories' => $signatories])
@endsection
