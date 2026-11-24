@extends('layouts.print')

@section('title', 'Certified Blotter Extract · Case ' . $case->case_number)

@section('content')
<x-ui.draft-watermark :confirmed="$isConfirmed" />

<div style="text-align: right; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <strong>Date Issued:</strong> {{ now()->format('F d, Y') }}
</div>

<div style="text-align: center; margin-bottom: 2rem;">
    <h2 style="font-size: 1.35rem; margin: 0; text-transform: uppercase; letter-spacing: 0.05em; font-weight: bold;">
        CERTIFIED BLOTTER EXTRACT
    </h2>
    <p style="margin-top: 0.25rem; font-size: 0.9rem; color: #475569;">
        Office of the Barangay Secretary · Official Police/Barangay Blotter Excerpt
    </p>
    <div style="margin-top: 0.5rem; font-weight: bold; font-size: 1rem;">
        Blotter Entry / Case No.: {{ $case->case_number }}
    </div>
</div>

<div style="border: 1px solid #cbd5e1; padding: 1.25rem; margin-bottom: 1.5rem; border-radius: 4px; background: #f8fafc;">
    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
        <tr>
            <td style="width: 25%; font-weight: bold; padding: 4px 0;">Entry Date & Time:</td>
            <td style="padding: 4px 0;">{{ $case->created_at?->format('F d, Y h:i A') ?? '' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Incident Date:</td>
            <td style="padding: 4px 0;">{{ $case->incident_date?->format('F d, Y') ?? '' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Incident Location:</td>
            <td style="padding: 4px 0;">{{ $case->incident_location ?? '' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Purok / Area:</td>
            <td style="padding: 4px 0;">{{ $case->purok?->name ?? '' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Nature of Blotter / Case:</td>
            <td style="padding: 4px 0;">{{ $case->caseType?->name ?? '' }}</td>
        </tr>
    </table>
</div>

<div style="margin-bottom: 1.5rem;">
    <h3 style="font-size: 1rem; font-weight: bold; border-bottom: 1px solid #0f172a; padding-bottom: 4px; margin-bottom: 8px;">
        PARTIES INVOLVED
    </h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem;">
        <tr>
            <td style="width: 25%; font-weight: bold; padding: 4px 0; vertical-align: top;">Complainant(s):</td>
            <td style="padding: 4px 0;">
                @foreach($case->caseParties->where('role', 'complainant') as $cp)
                    <div><strong>{{ $cp->party?->full_name ?? '' }}</strong> {{ $cp->party?->address_line ? '(' . $cp->party->address_line . ')' : '' }}</div>
                @endforeach
            </td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0; vertical-align: top;">Respondent(s):</td>
            <td style="padding: 4px 0;">
                @foreach($case->caseParties->where('role', 'respondent') as $rp)
                    <div><strong>{{ $rp->party?->full_name ?? '' }}</strong> {{ $rp->party?->address_line ? '(' . $rp->party->address_line . ')' : '' }}</div>
                @endforeach
            </td>
        </tr>
    </table>
</div>

<div style="margin-bottom: 2rem;">
    <h3 style="font-size: 1rem; font-weight: bold; border-bottom: 1px solid #0f172a; padding-bottom: 4px; margin-bottom: 8px;">
        RECORDED NARRATIVE / FACTS OF THE CASE
    </h3>
    <div style="font-size: 0.95rem; line-height: 1.8; text-align: justify; font-family: Georgia, serif;">
        {{ $case->narrative }}
    </div>
</div>

<div style="margin-bottom: 2rem; font-size: 0.875rem; line-height: 1.6; text-align: justify;">
    <p>
        I HEREBY CERTIFY that the foregoing is a true, faithful, and accurate extract from the official Barangay Blotter and Dispute Registry of Barangay Calarian, City of Zamboanga, maintained under my official custody in accordance with the provisions of Republic Act No. 7160.
    </p>
    <p style="margin-top: 0.5rem;">
        Issued this <strong>{{ now()->format('jS') }}</strong> day of <strong>{{ now()->format('F, Y') }}</strong> upon request of the interested party for legal reference and verification purposes.
    </p>
</div>
@endsection

@section('signatories')
@include('partials.signatory-block', ['signatories' => $signatories])
@endsection
