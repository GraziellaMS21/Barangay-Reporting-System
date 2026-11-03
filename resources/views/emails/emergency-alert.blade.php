<!DOCTYPE html>
<html lang="en">
<body>
  <h1>Emergency report requires staff attention</h1>
  <p>A report has been confirmed as a possible emergency. This system does not contact police or emergency services.</p>
  <p><strong>Tracking code:</strong> {{ $trackingCode }}</p>
  <p><strong>Purok:</strong> {{ $purokName }}</p>
  <p><strong>Submitted:</strong> {{ $reportedAt->format('M d, Y h:i A') }}</p>
  <p><a href="{{ $reviewUrl }}">Open the staff review page</a></p>
</body>
</html>
