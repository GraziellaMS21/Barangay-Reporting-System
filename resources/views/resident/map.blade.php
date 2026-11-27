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

  .lang-segmented-control {
    display: inline-flex;
    background: #f1f5f9;
    border: 1px solid #e2e8f0;
    border-radius: 999px;
    padding: 3px;
    gap: 2px;
  }

  .lang-segmented-btn {
    border: none;
    background: transparent;
    padding: 0.3rem 0.85rem;
    border-radius: 999px;
    font-size: 0.8rem;
    font-weight: 700;
    color: #64748b;
    cursor: pointer;
    transition: all 0.2s ease;
  }

  .lang-segmented-btn:hover:not(.active) {
    color: var(--ink);
  }

  .lang-segmented-btn.active {
    background: var(--ink);
    color: #ffffff;
    box-shadow: 0 2px 4px rgba(11, 31, 75, 0.2);
  }

  /* Interactive Filters Toolbar */
  .map-toolbar-wrap {
    display: flex;
    flex-direction: column;
    gap: 0.85rem;
    margin-bottom: 1.25rem;
  }

  .map-filter-chips-row {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    overflow-x: auto;
  }

  .map-filter-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.4rem 0.85rem;
    border: 1.5px solid #cbd5e1;
    border-radius: 999px;
    background: #ffffff;
    color: var(--tx);
    font-size: 0.82rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    user-select: none;
    min-height: 38px;
    white-space: nowrap;
  }

  .map-filter-btn:hover {
    border-color: #93c5fd;
    background: #eff6ff;
    color: var(--blue);
  }

  .map-filter-btn.active {
    background: var(--blue);
    border-color: var(--blue);
    color: #ffffff;
    box-shadow: 0 2px 6px rgba(36, 86, 214, 0.25);
  }

  .map-filter-badge {
    background: #f1f5f9;
    color: #475569;
    font-size: 0.72rem;
    padding: 0.1rem 0.45rem;
    border-radius: 999px;
    font-weight: 800;
    transition: all 0.2s;
  }

  .map-filter-btn.active .map-filter-badge {
    background: rgba(255, 255, 255, 0.25);
    color: #ffffff;
  }

  .map-view-frame {
    min-height: 520px;
    border-radius: 14px;
    overflow: hidden;
    border: 1.5px solid #cbd5e1;
    box-shadow: inset 0 1px 3px rgba(0, 0, 0, 0.06);
  }

  /* Custom Leaflet Popup */
  .leaflet-popup-content-wrapper {
    border-radius: 14px !important;
    padding: 0 !important;
    overflow: hidden;
    box-shadow: 0 10px 25px -5px rgba(11, 31, 75, 0.15) !important;
  }

  .leaflet-popup-content {
    margin: 0 !important;
    line-height: 1.4 !important;
  }

  .map-popup-card {
    padding: 1rem 1.15rem;
    min-width: 220px;
    max-width: 280px;
    font-family: var(--font-sans, system-ui);
  }

  .map-popup-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.5rem;
    margin-bottom: 0.5rem;
  }
</style>

<div class="page-heading" style="margin-bottom: 1.25rem;">
  <h2 data-i18n="page_title">Public Map</h2>
  <p data-i18n="page_desc">Live map of active community concerns in Barangay Calarian. Emergency reports are hidden to protect resident privacy.</p>
</div>

<section class="card public-map-card">
  <div class="map-card-topbar">
    <div style="display:flex;align-items:center;gap:0.75rem;">
      <div style="width:40px;height:40px;border-radius:10px;background:#eff6ff;display:grid;place-items:center;color:var(--blue);font-size:1.15rem;">
        <i class="fa-solid fa-map-location-dot"></i>
      </div>
      <div>
        <h3 style="margin:0;font-size:1.15rem;font-weight:800;color:var(--ink);" data-i18n="card_title">Active Barangay Reports</h3>
        <p style="margin:0.15rem 0 0;font-size:0.84rem;color:#64748b;" data-i18n="card_desc">Click pins on the map to view status and category details</p>
      </div>
    </div>

    <div style="display:flex;align-items:center;gap:0.65rem;flex-wrap:wrap;">
      <div class="lang-segmented-control" role="group" aria-label="Select Language">
        <button type="button" class="lang-segmented-btn js-map-lang-en active" onclick="setMapLanguage('en')">EN</button>
        <button type="button" class="lang-segmented-btn js-map-lang-fil" onclick="setMapLanguage('fil')">FIL</button>
      </div>

      <span style="display:inline-flex;align-items:center;gap:0.35rem;background:#f0fdf4;border:1px solid #86efac;color:#166534;font-size:0.78rem;font-weight:700;padding:0.3rem 0.65rem;border-radius:999px;">
        <span style="width:8px;height:8px;border-radius:50%;background:#22c55e;display:inline-block;"></span>
        <span data-i18n="transparency_pill">Public Transparency Mode</span>
      </span>

      <a href="{{ route('reports.create') }}" class="btn btn-gold btn-small" style="font-weight:800;min-height:38px;">
        <i class="fa-solid fa-plus"></i> <span data-i18n="btn_report_problem">Report a Concern</span>
      </a>
    </div>
  </div>

  <!-- Filters Toolbar: Interactive Category Buttons & Purok Selector -->
  <div class="map-toolbar-wrap">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:0.75rem;flex-wrap:wrap;">
      <div class="map-filter-chips-row" style="flex:1;">
        <span style="font-size:0.78rem;font-weight:700;text-transform:uppercase;color:#64748b;letter-spacing:0.04em;margin-right:0.25rem;" data-i18n="lbl_filter_category">
          Category:
        </span>

        <button type="button" class="map-filter-btn active" data-cat-filter="all" onclick="filterReportsByCategory('all', this)">
          <i class="fa-solid fa-layer-group"></i> <span data-i18n="cat_all">All</span>
          <span class="map-filter-badge" id="badge-count-all">{{ $reports->count() }}</span>
        </button>

        @foreach($categories as $cat)
          @php
            $catCount = $reports->where('category_id', $cat->id)->count();
          @endphp
          <button type="button" class="map-filter-btn" data-cat-filter="{{ $cat->id }}" onclick="filterReportsByCategory({{ $cat->id }}, this)">
            @if($cat->slug === 'drainage') <i class="fa-solid fa-water" style="color:#0284c7;"></i>
            @elseif($cat->slug === 'garbage') <i class="fa-solid fa-trash-can" style="color:#16a34a;"></i>
            @elseif($cat->slug === 'road-damage') <i class="fa-solid fa-road" style="color:#ea580c;"></i>
            @elseif($cat->slug === 'streetlight') <i class="fa-solid fa-lightbulb" style="color:#ca8a04;"></i>
            @elseif($cat->slug === 'flooding') <i class="fa-solid fa-house-flood-water" style="color:#2563eb;"></i>
            @else <i class="fa-solid fa-tag"></i>
            @endif
            <span>{{ $cat->name }}</span>
            <span class="map-filter-badge">{{ $catCount }}</span>
          </button>
        @endforeach
      </div>

      <!-- Purok Quick-Zoom Selector -->
      <div style="display:inline-flex;align-items:center;gap:0.45rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;padding:0.4rem 0.75rem;">
        <label for="purok-zoom-select" style="font-size:0.8rem;font-weight:700;color:#64748b;white-space:nowrap;" data-i18n="lbl_purok_zoom">
          <i class="fa-solid fa-location-dot" style="color:var(--red);"></i> Purok:
        </label>
        <select id="purok-zoom-select" onchange="filterReportsByPurok(this.value)" style="border:1px solid #cbd5e1;border-radius:8px;padding:0.3rem 0.65rem;font-size:0.82rem;font-weight:600;color:var(--ink);background:#ffffff;">
          <option value="all" data-i18n="opt_all_puroks">All Puroks</option>
          @foreach($puroks as $purok)
            <option value="{{ $purok->id }}">{{ $purok->name }}</option>
          @endforeach
        </select>
      </div>
    </div>
  </div>

  <div id="public-map" class="map-frame map-view-frame" aria-label="Public map of barangay reports"></div>

  <div style="margin-top:1rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;font-size:0.82rem;color:#64748b;">
    <span data-i18n="privacy_note">
      <i class="fa-solid fa-shield-halved" style="color:var(--blue);margin-right:0.3rem;"></i> Precise street addresses and personal details are protected in compliance with RA 10173.
    </span>
    <span id="reports-visible-count">Showing {{ $reports->count() }} active report(s)</span>
  </div>
</section>

@push('scripts')
<script>
  const mapI18n = {
    en: {
      page_title: "Public Map",
      page_desc: "Live map of active community concerns in Barangay Calarian. Emergency reports are hidden to protect resident privacy.",
      card_title: "Active Barangay Reports",
      card_desc: "Click pins on the map to view status and category details",
      transparency_pill: "Public Transparency Mode",
      btn_report_problem: "Report a Concern",
      lbl_filter_category: "Category:",
      cat_all: "All",
      lbl_purok_zoom: "Purok:",
      opt_all_puroks: "All Puroks",
      privacy_note: "Precise street addresses and personal details are protected in compliance with RA 10173.",
      showing_prefix: "Showing",
      showing_suffix: "active report(s)",
      btn_track: "Track Report",
      lbl_purok: "Purok",
      lbl_status: "Status",
      lbl_reported: "Reported"
    },
    fil: {
      page_title: "Pampublikong Mapa",
      page_desc: "Live na mapa ng mga aktibong suliranin sa komunidad ng Barangay Calarian. Nakatago ang mga ulat na pang-emergency para sa privacy.",
      card_title: "Mga Aktibong Ulat sa Barangay",
      card_desc: "Pindutin ang mga pin sa mapa upang makita ang estado at mga detalye",
      transparency_pill: "Mode ng Pampublikong Transparansya",
      btn_report_problem: "Mag-ulat ng Concern",
      lbl_filter_category: "Kategorya:",
      cat_all: "Lahat",
      lbl_purok_zoom: "Purok:",
      opt_all_puroks: "Lahat ng Purok",
      privacy_note: "Ang mga eksaktong address at personal na detalye ay protektado alinsunod sa RA 10173.",
      showing_prefix: "Ipinapakita ang",
      showing_suffix: "aktibong ulat",
      btn_track: "Subaybayan",
      lbl_purok: "Purok",
      lbl_status: "Katayuan",
      lbl_reported: "Iniulat"
    }
  };

  let mapLang = 'en';

  function setMapLanguage(lang) {
    mapLang = lang;
    document.querySelectorAll('.js-map-lang-en').forEach(b => b.classList.toggle('active', lang === 'en'));
    document.querySelectorAll('.js-map-lang-fil').forEach(b => b.classList.toggle('active', lang === 'fil'));

    const dict = mapI18n[lang] || mapI18n.en;
    document.querySelectorAll('[data-i18n]').forEach(el => {
      const key = el.getAttribute('data-i18n');
      if (dict[key]) {
        const span = el.querySelector('span');
        if (span) span.textContent = dict[key];
        else el.textContent = dict[key];
      }
    });

    try {
      localStorage.setItem('calarian_lang', lang);
    } catch (e) { }

    updateReportsVisibleCount();
  }

  // Reports data for map plotting and client-side filtering
  const reportsData = [
    @foreach($reports as $report)
      @if($report->lat !== null && $report->lng !== null)
        {
          id: {{ $report->id }},
          tracking_code: "{{ $report->tracking_code }}",
          category_id: {{ $report->category_id ?? 0 }},
          category_name: "{{ addslashes($report->category?->name ?? 'Community Concern') }}",
          purok_id: {{ $report->purok_id ?? 0 }},
          purok_name: "{{ addslashes($report->purok?->name ?? 'Barangay Calarian') }}",
          status: "{{ $report->status }}",
          status_label: "{{ ucfirst(str_replace('_', ' ', $report->status)) }}",
          lat: {{ $report->lat }},
          lng: {{ $report->lng }},
          created_relative: "{{ $report->created_at->diffForHumans() }}"
        },
      @endif
    @endforeach
  ];

  let mapInstance = null;
  let allMarkers = [];
  let currentCategoryFilter = 'all';
  let currentPurokFilter = 'all';

  function createMarkerPopup(r) {
    const dict = mapI18n[mapLang] || mapI18n.en;
    return `
      <div class="map-popup-card">
        <div class="map-popup-header">
          <strong style="color:var(--ink);font-size:0.95rem;display:flex;align-items:center;gap:0.35rem;">
            <i class="fa-solid fa-location-dot" style="color:var(--blue);"></i> ${r.category_name}
          </strong>
        </div>
        <div style="font-size:0.8rem;color:#64748b;margin-bottom:0.35rem;">
          <strong>${dict.lbl_purok}:</strong> ${r.purok_name}
        </div>
        <div style="font-size:0.8rem;color:#64748b;margin-bottom:0.5rem;">
          <strong>${dict.lbl_status}:</strong>
          <span style="display:inline-block;background:#eff6ff;color:var(--blue);font-weight:700;padding:0.15rem 0.45rem;border-radius:6px;font-size:0.75rem;">
            ${r.status_label}
          </span>
        </div>
        <div style="font-size:0.78rem;color:#94a3b8;margin-bottom:0.65rem;">
          ${dict.lbl_reported} ${r.created_relative}
        </div>
        <div style="border-top:1px solid #f1f5f9;padding-top:0.5rem;text-align:right;">
          <a href="/track/${r.tracking_code}" class="btn btn-outline btn-small" style="padding:0.25rem 0.65rem;font-size:0.78rem;font-weight:700;text-decoration:none;">
            ${dict.btn_track} &rarr;
          </a>
        </div>
      </div>
    `;
  }

  function filterReportsByCategory(catId, btnEl) {
    currentCategoryFilter = catId;
    document.querySelectorAll('.map-filter-btn').forEach(b => b.classList.remove('active'));
    if (btnEl) btnEl.classList.add('active');
    applyFilters();
  }

  function filterReportsByPurok(purokId) {
    currentPurokFilter = purokId;
    applyFilters();
  }

  function applyFilters() {
    let visibleCount = 0;
    const bounds = [];

    allMarkers.forEach(({ marker, data }) => {
      const matchCat = (currentCategoryFilter === 'all' || data.category_id == currentCategoryFilter);
      const matchPurok = (currentPurokFilter === 'all' || data.purok_id == currentPurokFilter);

      if (matchCat && matchPurok) {
        if (!mapInstance.hasLayer(marker)) {
          marker.addTo(mapInstance);
        }
        marker.setPopupContent(createMarkerPopup(data));
        bounds.push([data.lat, data.lng]);
        visibleCount++;
      } else {
        if (mapInstance.hasLayer(marker)) {
          mapInstance.removeLayer(marker);
        }
      }
    });

    if (bounds.length > 0 && mapInstance) {
      if (bounds.length === 1) {
        mapInstance.setView(bounds[0], 15);
      } else {
        mapInstance.fitBounds(bounds, { padding: [30, 30], maxZoom: 16 });
      }
    }

    updateReportsVisibleCount(visibleCount);
  }

  function updateReportsVisibleCount(count) {
    const el = document.getElementById('reports-visible-count');
    if (!el) return;
    const dict = mapI18n[mapLang] || mapI18n.en;
    const finalCount = count !== undefined ? count : reportsData.length;
    el.textContent = `${dict.showing_prefix} ${finalCount} ${dict.showing_suffix}`;
  }

  document.addEventListener('DOMContentLoaded', function () {
    try {
      const savedLang = localStorage.getItem('calarian_lang');
      if (savedLang && (savedLang === 'en' || savedLang === 'fil')) {
        setMapLanguage(savedLang);
      }
    } catch (e) { }

    if (!window.L) {
      return;
    }

    const publicMapEl = document.getElementById('public-map');
    mapInstance = L.map('public-map', { scrollWheelZoom: false }).setView([6.918, 122.081], 13);
    
    if (window.setupCooperativeMapGestures && publicMapEl) {
      window.setupCooperativeMapGestures(mapInstance, publicMapEl);
    }

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 19,
      attribution: '&copy; OpenStreetMap contributors'
    }).addTo(mapInstance);

    // Plot all initial markers
    reportsData.forEach(r => {
      const marker = L.marker([r.lat, r.lng])
        .addTo(mapInstance)
        .bindPopup(createMarkerPopup(r));
      allMarkers.push({ marker, data: r });
    });

    if (reportsData.length > 0) {
      const initialBounds = reportsData.map(r => [r.lat, r.lng]);
      mapInstance.fitBounds(initialBounds, { padding: [40, 40], maxZoom: 15 });
    }
  });
</script>
@endpush
@endsection
