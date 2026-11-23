@extends('layouts.print')

@section('title', 'Referral Letter · ' . $referral->reference_no)

@section('content')
<x-ui.draft-watermark />

<div class="no-print" style="margin-bottom: 1.5rem; display: flex; justify-content: space-between; align-items: center;">
    <a href="{{ $report ? route('admin.reports.show', $report) : ($referral->case ? route('admin.cases.show', $referral->case) : route('admin.referrals.index')) }}" style="font-size: 0.875rem; color: #475569; text-decoration: none;">
        &larr; Back
    </a>
    <button type="button" onclick="window.print()" style="padding: 0.4rem 0.8rem; font-size: 0.875rem; font-weight: 600; background: #0f172a; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
        Print / Save as PDF
    </button>
</div>

<div style="text-align: right; margin-bottom: 1.5rem; font-size: 0.875rem;">
    <strong>Date:</strong> {{ $referral->sent_at?->format('F d, Y') ?? now()->format('F d, Y') }}
</div>

<div style="margin-bottom: 2rem;">
    <p style="margin: 0; font-weight: bold; font-size: 1rem;">
        TO: {{ strtoupper($office?->name ?? 'PARTNER AGENCY') }}
    </p>
    @if($office?->address)
        <p style="margin: 0.25rem 0 0; font-size: 0.875rem; color: #475569;">
            {{ $office->address }}
        </p>
    @endif
</div>

<div style="margin-bottom: 1.5rem;">
    <h3 style="font-size: 1.1rem; text-transform: uppercase; font-weight: bold; border-bottom: 2px solid #0f172a; padding-bottom: 0.25rem;">
        SUBJECT: OFFICIAL REFERRAL NOTICE (REF NO. {{ $referral->reference_no }})
    </h3>
    <p style="font-size: 0.875rem; margin-top: 0.5rem; color: #334155;">
        @if($report)
            <strong>Tracking Code:</strong> {{ $report->tracking_code }} · <strong>Category:</strong> {{ $report->category?->name ?? 'Community Concern' }}
        @elseif($referral->case)
            <strong>Case Docket:</strong> {{ $referral->case->case_number }} · <strong>Title:</strong> {{ $referral->case->title }}
        @endif
    </p>
</div>

<div style="margin-bottom: 2rem; font-size: 0.95rem; line-height: 1.8; text-align: justify;">
    <p style="margin-bottom: 1.25rem;">
        Greetings:
    </p>

    <div style="white-space: pre-wrap; margin-bottom: 1.5rem;">
{{ $referral->referral_text }}
    </div>

    <p style="margin-bottom: 1.25rem;">
        We respectfully request your good office to conduct appropriate inspection, investigation, or official action on this matter. Updates and resolutions may be posted directly through the Barangay Calarian Agency Portal or communicated to our barangay secretariat.
    </p>

    <p style="margin-bottom: 1.25rem;">
        Thank you very much for your continuous cooperation in service of the community.
    </p>
</div>
@endsection

@section('signatories')
    @include('partials.signatory-block', ['signatories' => $signatories ?? []])
@endsection
