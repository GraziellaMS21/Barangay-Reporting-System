@extends('layouts.print')

@section('title', 'Case Summary: ' . $case->case_number)

@section('content')
<x-ui.draft-watermark />

<div style="text-align: right; margin-bottom: 1rem; font-size: 0.875rem;">
    <strong>Date Printed:</strong> {{ now()->format('F d, Y') }}
</div>

<div style="border: 1px solid #cbd5e1; padding: 1.5rem; margin-bottom: 1.5rem; border-radius: 4px;">
    <h2 style="margin-top: 0; font-size: 1.1rem; border-bottom: 1px solid #e2e8f0; padding-bottom: 0.5rem;">
        BLOTTER DOCKET RECORD: {{ $case->case_number }}
    </h2>

    <table style="width: 100%; border-collapse: collapse; font-size: 0.875rem; margin-top: 1rem;">
        <tr>
            <td style="width: 25%; font-weight: bold; padding: 4px 0;">Case Title:</td>
            <td style="padding: 4px 0;">{{ $case->title }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Classification:</td>
            <td style="padding: 4px 0;">{{ $case->caseType?->name ?? 'Unclassified' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Filing Date:</td>
            <td style="padding: 4px 0;">{{ $case->filed_on?->format('F d, Y') ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Current Status:</td>
            <td style="padding: 4px 0; text-transform: uppercase; font-weight: bold;">{{ \App\Support\CaseStatus::label($case->status) }}</td>
        </tr>
        <tr>
            <td style="font-weight: bold; padding: 4px 0;">Incident Location:</td>
            <td style="padding: 4px 0;">{{ $case->incident_location ?? ($case->purok?->name ?? 'Barangay Calarian') }}</td>
        </tr>
    </table>
</div>

<!-- Parties Section -->
<div style="margin-bottom: 1.5rem;">
    <h3 style="font-size: 1rem; border-bottom: 1px solid #0f172a; padding-bottom: 0.25rem;">PARTIES INVOLVED</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 0.85rem; margin-top: 0.5rem;" border="1" cellpadding="6">
        <thead>
            <tr style="background-color: #f1f5f9; text-align: left;">
                <th style="width: 20%;">Role</th>
                <th style="width: 40%;">Full Legal Name</th>
                <th style="width: 20%;">Residency</th>
                <th style="width: 20%;">Contact Info</th>
            </tr>
        </thead>
        <tbody>
            @foreach($case->caseParties as $cp)
                <tr>
                    <td style="text-transform: capitalize; font-weight: bold;">{{ $cp->role }}</td>
                    <td>{{ $cp->party->full_name }}</td>
                    <td style="text-transform: capitalize;">{{ $cp->residency_at_filing }}</td>
                    <td>{{ $cp->party->contact_number ?? 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<!-- Narrative Statement -->
<div style="margin-bottom: 2rem;">
    <h3 style="font-size: 1rem; border-bottom: 1px solid #0f172a; padding-bottom: 0.25rem;">COMPLAINT STATEMENT / GRIEVANCE FACTS</h3>
    <p style="font-size: 0.875rem; line-height: 1.6; white-space: pre-wrap; margin-top: 0.5rem;">{{ $case->narrative }}</p>
</div>

<!-- Status Logs -->
<div style="margin-bottom: 2rem;">
    <h3 style="font-size: 1rem; border-bottom: 1px solid #0f172a; padding-bottom: 0.25rem;">PROCEEDINGS & ACTION HISTORY</h3>
    <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-top: 0.5rem;" border="1" cellpadding="4">
        <thead>
            <tr style="background-color: #f8fafc; text-align: left;">
                <th style="width: 25%;">Date / Time</th>
                <th style="width: 25%;">Action / Status</th>
                <th style="width: 50%;">Official Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($case->statusLogs as $log)
                <tr>
                    <td>{{ $log->created_at->format('Y-m-d h:i A') }}</td>
                    <td style="font-weight: bold;">{{ \App\Support\CaseStatus::label($log->to_status) }}</td>
                    <td>{{ $log->note ?: 'Status updated' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection

@section('signatories')
    @include('partials.signatory-block', ['documentType' => 'case_summary'])
@endsection
