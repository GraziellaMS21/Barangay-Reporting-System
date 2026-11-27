<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resolution Proposed</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 34rem; margin: 0 auto; padding: 1.5rem; color: #1e293b; line-height: 1.5;">
  <p style="margin: 0; color: #1e3a8a; font-weight: 700;">Barangay Calarian &mdash; Action update</p>
  <h1 style="font-size: 1.2rem; margin: .5rem 0 1rem; color: #0f172a;">Resolution proposed for your report</h1>
  
  <p style="margin: 0 0 1rem;">
    {{ !empty($reporterName) ? 'Dear '.$reporterName.',' : 'Dear Resident,' }}
  </p>
  
  <p style="margin: 0 0 1rem;">
    Barangay personnel have completed remediation work for report <strong>{{ $trackingCode }}</strong> and have uploaded photo proof for your review.
  </p>

  <div style="background-color: #f0fdf4; border-left: 4px solid #22c55e; padding: 0.9rem 1rem; margin: 1.25rem 0; border-radius: 0 6px 6px 0;">
    <p style="margin: 0 0 0.35rem; font-size: 0.85rem; font-weight: 700; color: #15803d;">Staff Resolution Summary:</p>
    <p style="margin: 0; font-size: 0.95rem; color: #166534;">
      {{ $resolutionNote }}
    </p>
  </div>

  <p style="margin: 0 0 1rem; font-size: 0.92rem; color: #475569;">
    <em>Please note: A proposed resolution does not permanently close your report. We ask that you inspect the work and confirm whether the problem is fixed.</em>
  </p>

  <p style="margin: 1.25rem 0;">
    <a href="{{ $trackingUrl }}" style="display: inline-block; background-color: #15803d; color: #ffffff; text-decoration: none; padding: 0.65rem 1.25rem; border-radius: 6px; font-weight: 600; font-size: 0.92rem;">
      Review proof &amp; confirm resolution &rarr;
    </a>
  </p>

  <p style="margin: 0; font-size: 0.85rem; color: #64748b;">
    On the tracking page, you can view the Before &amp; After photo comparison and choose to either <strong>Confirm Fix</strong> or <strong>Dispute Resolution</strong> if further work is required.
  </p>

  <p style="margin: 1.75rem 0 0; padding-top: 1rem; border-top: 1px solid #e2e8f0; color: #64748b; font-size: .85rem;">
    For life-threatening emergencies call 911. This online system is not monitored in real time.
  </p>
</body>
</html>
