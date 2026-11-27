<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Report Received</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 34rem; margin: 0 auto; padding: 1.5rem; color: #1e293b; line-height: 1.5;">
  <p style="margin: 0; color: #1e3a8a; font-weight: 700;">Barangay Calarian &mdash; Problem Reporting System</p>
  <h1 style="font-size: 1.2rem; margin: .5rem 0 1rem; color: #0f172a;">Your report has been received</h1>
  
  <p style="margin: 0 0 1rem;">
    {{ !empty($reporterName) ? 'Dear '.$reporterName.',' : 'Dear Resident,' }}
  </p>
  
  <p style="margin: 0 0 1rem;">
    Thank you for submitting a report regarding <strong>{{ $categoryName }}</strong> in <strong>{{ $purokName }}</strong>. Your report has been logged and assigned the tracking code <strong>{{ $trackingCode }}</strong>.
  </p>

  <div style="background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 1rem; margin: 1.25rem 0;">
    <p style="margin: 0 0 0.5rem; font-size: 0.9rem; color: #64748b;"><strong>Report Details:</strong></p>
    <ul style="margin: 0; padding-left: 1.25rem; font-size: 0.9rem; color: #334155;">
      <li><strong>Tracking Code:</strong> {{ $trackingCode }}</li>
      <li><strong>Category:</strong> {{ $categoryName }}</li>
      <li><strong>Purok:</strong> {{ $purokName }}</li>
      <li><strong>Date Submitted:</strong> {{ $reportedAt->format('M d, Y h:i A') }}</li>
    </ul>
  </div>

  <p style="margin: 0 0 1rem; font-size: 0.92rem; color: #475569;">
    <em>Please note: Receiving this report confirms it has entered the barangay triage queue. It does not mean the issue has already been resolved or field work has commenced.</em>
  </p>

  <p style="margin: 1.25rem 0;">
    <a href="{{ $trackingUrl }}" style="display: inline-block; background-color: #1e40af; color: #ffffff; text-decoration: none; padding: 0.65rem 1.25rem; border-radius: 6px; font-weight: 600; font-size: 0.92rem;">
      Track your report &rarr;
    </a>
  </p>

  <p style="margin: 0; font-size: 0.85rem; color: #64748b;">
    You may use the button above or visit the tracking portal with code <strong>{{ $trackingCode }}</strong> to monitor updates.
  </p>

  <p style="margin: 1.75rem 0 0; padding-top: 1rem; border-top: 1px solid #e2e8f0; color: #64748b; font-size: .85rem;">
    For life-threatening emergencies call 911. This online system is not monitored in real time.
  </p>
</body>
</html>
