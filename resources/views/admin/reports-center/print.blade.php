@extends('layouts.print')

@section('title', $template->title)

@push('styles')
<style>
    @page {
        size: {{ strtolower($paperSize ?? 'a4') }} {{ $template->orientation === 'landscape' ? 'landscape' : 'portrait' }};
        margin: 15mm;
    }
    .report-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 10pt;
        margin-top: 1rem;
    }
    .report-table th,
    .report-table td {
        border: 1px solid #334155;
        padding: 6px 8px;
    }
    .report-table th {
        background: #eef2ff;
        font-weight: bold;
    }
    .report-table tfoot td {
        background: #f1f5f9;
        font-weight: bold;
    }
    .text-left { text-align: left; }
    .text-center { text-align: center; }
    .text-right { text-align: right; }
    .draft-banner {
        border: 1px dashed #d97706;
        background: #fffbeb;
        color: #92400e;
        padding: 4px 10px;
        font-size: 9pt;
        font-weight: bold;
        text-transform: uppercase;
        display: inline-block;
        margin-bottom: 8px;
    }
    .signatories-grid {
        display: table;
        width: 100%;
        margin-top: 36px;
    }
    .signatory-col {
        display: table-cell;
        text-align: center;
        vertical-align: bottom;
        padding: 0 12px;
    }
    .sig-line {
        border-bottom: 1px solid #0f172a;
        width: 180px;
        margin: 30px auto 4px;
    }
</style>
@endpush

@section('content')
    @if(empty($isPdf))
        <div class="no-print" style="margin-bottom: 1rem; display: flex; justify-content: space-between; align-items: center;">
            <a href="{{ route('admin.reports-center.show', $template->key) }}" style="color: #1d4ed8; text-decoration: none; font-size: 0.875rem;">&larr; Back to Report Preview</a>
            <button type="button" onclick="window.print()" style="background: #1d4ed8; color: #fff; border: 0; padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer; font-weight: bold;">
                Print Document
            </button>
        </div>
    @endif

    @if(! $template->is_confirmed)
        <div class="draft-banner">DRAFT — Pending Barangay Confirmation</div>
    @endif

    <h2 style="text-align: center; font-size: 13pt; margin: 0 0 4px; text-transform: uppercase;">{{ $template->title }}</h2>
    <p style="text-align: center; font-size: 9pt; color: #475569; margin: 0 0 12px;">
        @foreach($applied_filters as $fLabel => $fVal)
            {{ $fLabel }}: {{ $fVal }} ·
        @endforeach
        Generated {{ now()->format('M d, Y h:i A') }}
    </p>

    <table class="report-table">
        <thead>
            <tr>
                @foreach($columns as $col)
                    <th class="text-{{ $col['align'] ?? 'left' }}">{{ $col['label'] }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach($columns as $col)
                        <td class="text-{{ $col['align'] ?? 'left' }}">{{ $row[$col['key']] ?? '—' }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" class="text-center">No records found.</td>
                </tr>
            @endforelse
        </tbody>
        @if(!empty($totals))
            <tfoot>
                <tr>
                    @foreach($columns as $col)
                        <td class="text-{{ $col['align'] ?? 'left' }}">{{ $totals[$col['key']] ?? '' }}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>
@endsection

@section('signatories')
    <div class="signatories-grid">
        @forelse($signatories as $sig)
            <div class="signatory-col">
                <div style="font-size: 8.5pt; color: #475569; text-transform: uppercase;">{{ $sig['label'] ?? 'Prepared by' }}</div>
                @if(!empty($sig['signature_data_uri']))
                    <div style="margin-top: 10px;">
                        <img src="{{ $sig['signature_data_uri'] }}" alt="Signature" style="max-height: 42px;">
                    </div>
                @else
                    <div class="sig-line"></div>
                @endif
                <div style="font-weight: bold; font-size: 10pt; text-transform: uppercase;">{{ $sig['name'] ?? '____________________' }}</div>
                <div style="font-size: 8.5pt; color: #475569;">{{ $sig['position'] ?? 'Barangay Official' }}</div>
            </div>
        @empty
            <div class="signatory-col">
                <div style="font-size: 8.5pt; color: #475569; text-transform: uppercase;">Prepared by</div>
                <div class="sig-line"></div>
                <div style="font-size: 8.5pt; color: #475569;">Authorized Signature</div>
                <div style="font-size: 8pt; color: #64748b;">Barangay Official</div>
            </div>
            <div class="signatory-col">
                <div style="font-size: 8.5pt; color: #475569; text-transform: uppercase;">Approved by</div>
                <div class="sig-line"></div>
                <div style="font-size: 8.5pt; color: #475569;">Authorized Signature</div>
                <div style="font-size: 8pt; color: #64748b;">Barangay Official</div>
            </div>
        @endforelse
    </div>
@endsection
