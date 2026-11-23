@extends('layouts.print')

@section('title', 'Case Endorsement Letter · Case ' . $case->case_number)

@section('content')
<x-ui.draft-watermark />

<div style="text-align: right; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <strong>Date:</strong> {{ now()->format('F d, Y') }}
</div>

<div style="margin-bottom: 2rem;">
    <p style="margin: 0; font-weight: bold; font-size: 1rem;">
        TO: {{ strtoupper($escalation?->destination?->name ?? 'THE PRESIDING JUDGE / CHIEF OF POLICE / PROSECUTOR') }}
    </p>
    <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: #475569;">
        Zamboanga City
    </p>
</div>

<div style="margin-bottom: 1.5rem;">
    <h3 style="font-size: 1.1rem; text-transform: uppercase; font-weight: bold; border-bottom: 2px solid #0f172a; padding-bottom: 0.25rem;">
        SUBJECT: ENDORSEMENT OF CASE NO. {{ $case->case_number }}
    </h3>
    <p style="font-size: 0.9rem; margin-top: 0.5rem; color: #334155;">
        <strong>Matter of:</strong> {{ $case->title }}
    </p>
</div>

<div style="margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.8; text-align: justify;">
    <p style="margin-bottom: 1.25rem;">
        Sir / Madam:
    </p>

    <p style="margin-bottom: 1.25rem;">
        Respectfully endorsed to your office is the above-captioned dispute between complainant(s)
        <strong>{{ $case->complainants->pluck('full_name')->implode(', ') ?: 'the Complainant' }}</strong>
        and respondent(s)
        <strong>{{ $case->respondents->pluck('full_name')->implode(', ') ?: 'the Respondent' }}</strong>,
        which underwent barangay mediation and conciliation proceedings before the Lupong Tagapamayapa of Barangay Calarian pursuant to Katarungang Pambarangay Law (RA 7160).
    </p>

    <p style="margin-bottom: 1.25rem;">
        Despite proper summons notices and conciliation conferences, no amicable settlement was arrived at between the parties. Attached hereto is the official
        <strong>Certification to File Action (CFA No. {{ $escalation?->cfa_number ?? 'Attached' }})</strong>
        issued on {{ $escalation?->escalated_at?->format('F d, Y') ?? now()->format('F d, Y') }}, enabling the complainant to seek judicial or administrative relief before your good office.
    </p>

    @if($escalation?->reason)
        <div style="margin: 1.5rem 0; padding: 1rem; border-left: 3px solid #0f172a; background: #f8fafc; font-size: 0.875rem;">
            <strong>Case Summary & Grounds:</strong>
            <p style="margin-top: 0.25rem; white-space: pre-wrap;">{{ $escalation->reason }}</p>
        </div>
    @endif

    <p style="margin-bottom: 1.25rem;">
        Thank you very much for your continuous cooperation and support in upholding peace and justice in our community.
    </p>
</div>
@endsection

@section('signatories')
    @include('partials.signatory-block', ['signatories' => $signatories])
@endsection
