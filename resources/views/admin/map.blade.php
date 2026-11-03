@extends('layouts.admin')

@section('title', 'Staff Map')
@section('page-heading', 'Staff map')

@section('content')
<div class="page-heading">
  <h2>Map overview</h2>
  <p>Filter by status and purok to see where the active workload is concentrated.</p>
</div>

<section class="card">
  <div id="staff-map" class="map-frame" data-map="staff" style="min-height: 420px;" aria-label="Map of reports"></div>
</section>

<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.L) {
      return;
    }

    const map = L.map('staff-map').setView([6.918, 122.081], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    @foreach($reports as $report)
      @if($report->lat !== null && $report->lng !== null)
        L.marker([{{ $report->lat }}, {{ $report->lng }}])
          .addTo(map)
          .bindPopup('{{ $report->tracking_code }}<br>{{ $report->status }}');
      @endif
    @endforeach
  });
</script>
@endsection
