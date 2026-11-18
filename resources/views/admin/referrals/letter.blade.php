<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>{{ $referral->reference_no }} — Referral letter</title>
  <style>
    * { box-sizing: border-box; }
    body {
      font-family: Arial, Helvetica, sans-serif;
      color: #111827;
      max-width: 46rem;
      margin: 0 auto;
      padding: 1.5rem;
      line-height: 1.6;
      font-size: 15px;
    }
    .letter-toolbar {
      display: flex;
      gap: .5rem;
      margin-bottom: 1rem;
    }
    .letter-toolbar button {
      padding: .5rem .9rem;
      border: 1px solid #9ca3af;
      border-radius: 6px;
      background: #f9fafb;
      cursor: pointer;
      font-size: .9rem;
    }
    .letter-body {
      white-space: pre-wrap;
      border: 1px solid #e5e7eb;
      border-radius: 8px;
      padding: 1.5rem;
      background: #fff;
    }
    .letter-meta {
      color: #6b7280;
      font-size: .8rem;
      margin-bottom: 1rem;
    }
    @media print {
      .letter-toolbar, .letter-meta { display: none; }
      body { padding: 0; }
      .letter-body { border: none; padding: 0; }
    }
  </style>
</head>
<body>
  <div class="letter-toolbar">
    <button type="button" onclick="window.print(); return false;">Print / Save as PDF</button>
    <a href="{{ route('admin.reports.show', $report) }}">Back to report</a>
  </div>
  <p class="letter-meta">
    {{ $referral->reference_no }} · Tracking code {{ $report->tracking_code }} ·
    Agency: {{ $office?->name ?? 'Unknown' }} · Status: {{ \App\Models\Referral::STATUSES[$referral->status] ?? $referral->status }}
  </p>
  <div class="letter-body">{{ $referral->referral_text }}</div>
</body>
</html>
