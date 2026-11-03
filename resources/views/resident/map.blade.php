@extends('layouts.app')

@section('title', 'Barangay map')

@section('content')
<div class="page-heading">
  <h2>Public map</h2>
  <p>Current service requests visible on the public map. Emergency reports are hidden to protect resident privacy.</p>
</div>

<section class="card">
  <div id="public-map" class="map-frame" style="min-height: 420px;" aria-label="Public map of barangay reports"></div>
</section>

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    if (!window.L) {
      return;
    }

    const map = L.map('public-map').setView([6.918, 122.081], 13);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(map);

    @foreach($reports as $report)
      @if($report->lat !== null && $report->lng !== null)
        L.marker([{{ $report->lat }}, {{ $report->lng }}])
          .addTo(map)
          .bindPopup('{{ $report->category?->name ?? 'Report' }}<br>{{ $report->status }}');
      @endif
    @endforeach
  });
</script>
@endpush
@endsection
