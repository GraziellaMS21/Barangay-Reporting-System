@extends('layouts.admin')

@section('title', 'Staff Map')
@section('page-heading', 'Staff map')

@section('content')
<div class="page-heading">
  <h2>Map overview</h2>
  <p>Filter report locations, review hotspots, and open a report for staff follow-up.</p>
</div>

<div class="staff-map-layout">
  <aside class="card staff-map-controls" aria-labelledby="staff-map-controls-heading">
    <h2 id="staff-map-controls-heading" class="card-title">Map filters</h2>

    <div class="staff-map-filters">
      <label>
        Category
        <select name="category-filter" data-map-filter="category">
          <option value="">All categories</option>
          <option value="__unclassified__">Unclassified</option>
          @foreach($categories as $category)
            <option value="{{ $category->id }}">{{ $category->name }}</option>
          @endforeach
        </select>
      </label>

      <label>
        Purok
        <select name="purok-filter" data-map-filter="purok">
          <option value="">All puroks</option>
          <option value="__unassigned__">Unassigned</option>
          @foreach($puroks as $purok)
            <option value="{{ $purok->id }}">{{ $purok->name }}</option>
          @endforeach
        </select>
      </label>

      <label>
        Status
        <select name="status-filter" data-map-filter="status">
          <option value="">All statuses</option>
          @foreach(\App\Models\Report::STATUSES as $status => $label)
            <option value="{{ $status }}">{{ $label }}</option>
          @endforeach
        </select>
      </label>

      <label>
        From date
        <input type="date" name="date-from" data-map-filter="date-from">
      </label>

      <label>
        To date
        <input type="date" name="date-to" data-map-filter="date-to">
      </label>
    </div>

    <p class="map-results-count" data-map-results aria-live="polite">{{ $mapReports->count() }} locations shown</p>

    <div class="map-urgency-legend" aria-label="Marker colors show report urgency">
      <span><i class="map-urgency-dot urgency-low" aria-hidden="true"></i>Low</span>
      <span><i class="map-urgency-dot urgency-medium" aria-hidden="true"></i>Medium</span>
      <span><i class="map-urgency-dot urgency-high" aria-hidden="true"></i>High</span>
      <span><i class="map-urgency-dot urgency-critical" aria-hidden="true"></i>Critical</span>
    </div>

    <div class="staff-map-actions">
      <button type="button" class="btn btn-navy btn-small" data-map-show-all>Show all reports</button>
      <button type="button" class="btn btn-outline btn-small" data-map-clear-hotspot disabled>Clear hotspot</button>
    </div>

    <section aria-labelledby="map-hotspots-heading">
      <h3 id="map-hotspots-heading" class="map-panel-heading">Busiest hotspots</h3>
      @if($hotspots->isNotEmpty())
        <ul class="map-hotspot-list">
          @foreach($hotspots as $cell => $total)
            <li>
              <button type="button" data-hotspot-cell="{{ $cell }}" aria-pressed="false">{{ $cell }}</button>
              <span><strong>{{ $total }}</strong> {{ \Illuminate\Support\Str::plural('report', $total) }}</span>
            </li>
          @endforeach
        </ul>
      @else
        <p class="map-empty-state">No report locations are available yet.</p>
      @endif
    </section>

    <section aria-labelledby="map-weekly-trend-heading">
      <h3 id="map-weekly-trend-heading" class="map-panel-heading">Reports per week</h3>
      <ul class="map-weekly-trend">
        @foreach($weeklyTrend as $week)
          <li><span>{{ $week['week'] }}</span><strong>{{ $week['total'] }}</strong></li>
        @endforeach
      </ul>
    </section>
  </aside>

  <section class="card staff-map-card" aria-labelledby="staff-map-heading">
    <h2 id="staff-map-heading" class="card-title">Report locations</h2>
    <div id="staff-map" class="map-frame staff-map-canvas"
      data-staff-report-map="{{ json_encode($mapReports, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
      aria-label="Map of report locations"></div>
  </section>
</div>
@endsection
