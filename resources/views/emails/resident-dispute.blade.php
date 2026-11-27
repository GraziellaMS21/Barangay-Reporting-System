<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Resident Disputed Resolution</title>
</head>
<body style="font-family: Arial, sans-serif; max-width: 34rem; margin: 0 auto; padding: 1.5rem; color: #1e293b; line-height: 1.5;">
  <p style="margin: 0; color: #dc2626; font-weight: 700;">Barangay Calarian &mdash; Staff Action Alert</p>
  <h1 style="font-size: 1.2rem; margin: .5rem 0 1rem; color: #991b1b;">Resident disputed proposed resolution</h1>
  
  <p style="margin: 0 0 1rem;">
    A resident has inspected the proposed resolution for report <strong>{{ $trackingCode }}</strong> ({{ $categoryName }}, {{ $purokName }}) and indicated that the issue is <strong>not yet fixed</strong>.
  </p>

  <div style="background-color: #fef2f2; border-left: 4px solid #ef4444; padding: 0.9rem 1rem; margin: 1.25rem 0; border-radius: 0 6px 6px 0;">
    <p style="margin: 0 0 0.35rem; font-size: 0.85rem; font-weight: 700; color: #991b1b;">Resident Dispute Feedback:</p>
    <p style="margin: 0; font-size: 0.95rem; color: #7f1d1d; font-style: italic;">
      &ldquo;{{ !empty($disputeReason) ? $disputeReason : ($feedbackComment ?? 'No dispute explanation provided.') }}&rdquo;
    </p>
    @if(!empty($feedbackRating))
      <p style="margin: 0.5rem 0 0; font-size: 0.85rem; color: #991b1b;">
        <strong>Rating given:</strong> {{ $feedbackRating }} / 5 stars
      </p>
    @endif
  </div>

  <p style="margin: 0 0 1rem; font-size: 0.92rem; color: #475569;">
    The case has been moved back to the active queue (status: <strong>reopened</strong>). Please inspect the dispute notes, coordinate with the field crew, and determine necessary follow-up actions.
  </p>

  <p style="margin: 1.25rem 0;">
    <a href="{{ $reviewUrl }}" style="display: inline-block; background-color: #dc2626; color: #ffffff; text-decoration: none; padding: 0.65rem 1.25rem; border-radius: 6px; font-weight: 600; font-size: 0.92rem;">
      Open staff review page &rarr;
    </a>
  </p>

  <p style="margin: 1.75rem 0 0; padding-top: 1rem; border-top: 1px solid #e2e8f0; color: #64748b; font-size: .85rem;">
    This is an internal notification for authorized barangay personnel and assigned case handlers.
  </p>
</body>
</html>
