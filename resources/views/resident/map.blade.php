@extends('layouts.app')

@section('title', 'Barangay Public Map - Barangay Calarian')

@section('content')
<style>
  .public-map-card {
    border: 1px solid #e2e8f0;
    border-radius: 20px;
    padding: 1.75rem;
    background: #ffffff;
    box-shadow: 0 4px 6px -1px rgba(11, 31, 75, 0.04), 0 20px 25px -5px rgba(11, 31, 75, 0.08);
    position: relative;
  }

  @media (max-width: 640px) {
    .public-map-card {
      padding: 1.25rem;
      border-radius: 16px;
    }
  }

  .map-card-topbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
    margin-bottom: 1.25rem;
  }

  .map-legend-wrap {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1.25rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
  }

  .map-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    font-size: 0.8rem;
    font-weight: 600;
    color: var(--tx);
  }

  .map-view-frame {
    min-height: 500px;
    border-radius: 14px;
    overflow: hidden;
    border: 1.5px solid #cbd5e1;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.06);
  }
</style>

<div class="page-heading" style="margin-bottom: 1.25rem;">
  <h2>Public Map</h2>
  <p>Live map of active community concerns in Barangay Calarian. Emergency reports are hidden to protect resident privacy.</p>
</div>

<section class="card public-map-card">
  <div class="map-card-topbar">
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <div style="width:40px;height:40px;border-radius:10px;background:#eff6ff;display:grid;place-items:center;color:var(--blue);font-size:1.15rem;">
        <i class="fa-solid fa-map-location-dot"></i>
      </div>
      <div>
        <h3 style="margin:0;font-size:1.15rem;font-weight:800;color:var(--ink);">Active Barangay Reports</h3>
        <p style="margin:0.15rem 0 0;font-size:0.84rem;color:#64748b;">Click pins on the map to view status and category details</p>
      </div>
    </div>

    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
      <span style="display:inline-flex;align-items:center;gap:0.35rem;background:#f0fdf4;border:1px solid #86efac;color:#166534;font-size:0.78rem;font-weight:700;padding:0.25rem 0.65rem;border-radius:999px;">
        <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
        Public Transparency Mode
      </span>
      <a href="{{ route('reports.create') }}" class="btn btn-gold btn-small" style="font-weight:800;">
        <i class="fa-solid fa-plus"></i> Report a Problem
      </a>
    </div>
  </div>

  <div class="map-legend-wrap">
    <span style="font-size:0.78rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:0.04em;margin-right:0.35rem;">
      Categories:
    </span>
    <span class="map-legend-item"><i class="fa-solid fa-water" style="color:#0284c7;"></i> Drainage</span>
    <span style="color:#cbd5e1;">·</span>
    <span class="map-legend-item"><i class="fa-solid fa-trash-can" style="color:#16a34a;"></i> Garbage</span>
    <span style="color:#cbd5e1;">·</span>
    <span class="map-legend-item"><i class="fa-solid fa-road" style="color:#ea580c;"></i> Road Damage</span>
    <span style="color:#cbd5e1;">·</span>
    <span class="map-legend-item"><i class="fa-solid fa-lightbulb" style="color:#ca8a04;"></i> Streetlight</span>
    <span style="color:#cbd5e1;">·</span>
    <span class="map-legend-item"><i class="fa-solid fa-house-flood-water" style="color:#2563eb;"></i> Flooding</span>
    <span style="color:#cbd5e1;">·</span>
    <span class="map-legend-item"><i class="fa-solid fa-ellipsis" style="color:#64748b;"></i> Other</span>
  </div>

  <div id="public-map" class="map-frame map-view-frame" aria-label="Public map of barangay reports"></div>

  <div style="margin-top:1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;font-size:0.82rem;color:#64748b;">
    <span><i class="fa-solid fa-shield-halved" style="color:var(--blue);margin-right:0.3rem;"></i> Precise street addresses and personal details are protected in compliance with RA 10173.</span>
    <span>Showing {{ $reports->count() }} active report(s)</span>
  </div>
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
          .bindPopup('<div style="font-family:system-ui;font-size:0.9rem;padding:0.2rem;"><strong>{{ addslashes($report->category?->name ?? 'Report') }}</strong><br><span style="font-size:0.8rem;color:#64748b;">Status: {{ ucfirst(str_replace('_', ' ', $report->status)) }}</span></div>');
      @endif
    @endforeach
  });
</script>
@endpush
@endsection
