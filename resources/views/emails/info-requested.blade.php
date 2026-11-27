<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Additional Information Needed</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 34rem; margin: 0 auto; padding: 1.5rem; color: #1e293b; line-height: 1.5;">
  <p style="margin: 0; color: #1e3a8a; font-weight: 700;">Barangay Calarian &mdash; Report follow-up</p>
  <h1 style="font-size: 1.2rem; margin: .5rem 0 1rem; color: #0f172a;">Additional information requested</h1>
  
  <p style="margin: 0 0 1rem;">
    {{ !empty($reporterName) ? 'Dear '.$reporterName.',' : 'Dear Resident,' }}
  </p>
  
  <p style="margin: 0 0 1rem;">
    Barangay personnel are reviewing report <strong>{{ $trackingCode }}</strong> and need further clarification to proceed with field investigation or routing:
  </p>

  <div style="background-color: #fefce8; border-left: 4px solid #eab308; padding: 0.9rem 1rem; margin: 1.25rem 0; border-radius: 0 6px 6px 0;">
    <p style="margin: 0 0 0.35rem; font-size: 0.85rem; font-weight: 700; color: #854d0e;">Staff Question:</p>
    <p style="margin: 0; font-size: 0.95rem; color: #713f12; font-style: italic;">
      &ldquo;{{ $question }}&rdquo;
    </p>
  </div>

  <p style="margin: 0 0 1rem; font-size: 0.92rem; color: #475569;">
    Please open your report tracking page to submit your response directly. Your answer will be immediately attached to the case thread for staff review.
  </p>

  <p style="margin: 1.25rem 0;">
    <a href="{{ $trackingUrl }}" style="display: inline-block; background-color: #1e40af; color: #ffffff; text-decoration: none; padding: 0.65rem 1.25rem; border-radius: 6px; font-weight: 600; font-size: 0.92rem;">
      Provide information on tracking page &rarr;
    </a>
  </p>

  <p style="margin: 1.75rem 0 0; padding-top: 1rem; border-top: 1px solid #e2e8f0; color: #64748b; font-size: .85rem;">
    For life-threatening emergencies call 911. This online system is not monitored in real time.
  </p>
</body>
</html>
