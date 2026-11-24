@extends('layouts.print')

@section('title', 'Certificate to File Action (KP Form 20) · Case ' . $case->case_number)

@section('content')
<x-ui.draft-watermark :confirmed="$isConfirmed" />

<div style="text-align: right; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <strong>Date Issued:</strong> {{ now()->format('F d, Y') }}
</div>

<div style="text-align: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.35rem; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: bold;">
        CERTIFICATION TO FILE ACTION (CFA)
    </h2>
    <p style="margin-top: 0.25rem; font-size: 0.9rem; color: #475569;">
        KP Form No. 20 · Office of the Lupong Tagapamayapa
    </p>
    <div style="margin-top: 0.5rem; font-weight: bold; font-size: 1rem;">
        CFA No.: {{ $escalation?->cfa_number ?? ('CFA-' . now()->year . '-' . $case->case_number) }}
    </div>
</div>

<div style="border: 1px solid #cbd5e1; padding: 1.25rem; margin-bottom: 1.5rem; border-radius: 4px; background: #f8fafc;">
    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
        <tr>
            <td style="width: 25%; font-weight: bold; padding: 4px 0;">Case Docket No.:</td>
            <td style="padding: 4px 0;">{{ $case->case_number }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Case Title:</td>
            <td style="padding: 4px 0;">{{ $case->title }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Complainant(s):</td>
            <td style="padding: 4px 0;">
                @foreach($case->caseParties->where('role', 'complainant') as $c)
                    <span style="font-weight: 600;">{{ $c->party?->full_name ?? '' }}</span>@if(!$loop->last), @endif
                @endforeach
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Respondent(s):</td>
            <td style="padding: 4px 0;">
                @foreach($case->caseParties->where('role', 'respondent') as $r)
                    <span style="font-weight: 600;">{{ $r->party?->full_name ?? '' }}</span>@if(!$loop->last), @endif
                @endforeach
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Destination Agency / Court:</td>
            <td style="padding: 4px 0; font-weight: bold;">{{ $escalation?->destination?->name ?? 'Competent Court / External Agency' }}</td>
        </tr>
    </table>
</div>

<div style="margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.8; text-align: justify;">
    <p style="margin-bottom: 1rem;">
        This is to certify that:
    </p>
    <ol style="padding-left: 1.5rem; margin-bottom: 1.5rem;">
        <li style="margin-bottom: 0.75rem;">
            There was a personal confrontation between the parties before the Punong Barangay / Pangkat Tagapagkasundo, but mediation and conciliation efforts failed;
        </li>
        <li style="margin-bottom: 0.75rem;">
            A settlement was reached but has been repudiated in accordance with law; or no settlement could be reached despite proper summons notices;
        </li>
        <li style="margin-bottom: 0.75rem;">
            The complainant formally petitioned for the issuance of this Certification to File Action pursuant to Section 412 of Republic Act No. 7160 (The Local Government Code of 1991).
        </li>
    </ol>
    <p>
        Therefore, the corresponding complaint for the dispute may now be filed in the competent Court or Government Office having jurisdiction.
    </p>

    @if($escalation?->reason)
        <div style="margin-top: 1.5rem; padding: 1rem; border-left: 3px solid #0f172a; background: #f1f5f9; font-size: 0.875rem;">
            <strong>Grounds / Impasse Summary:</strong>
            <p style="margin-top: 0.25rem; white-space: pre-wrap;">{{ $escalation->reason }}</p>
        </div>
    @endif
</div>
@endsection

@section('signatories')
@include('partials.signatory-block', ['signatories' => $signatories])
@endsection
