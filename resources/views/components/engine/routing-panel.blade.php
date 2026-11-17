{{-- x-engine.routing-panel; prop: $report --}}
@php $office = $report->getRecommendedOffice(); @endphp
@if($office)
  <div class="engine-routing-panel card">
    <p><strong>Recommended office:</strong> {{ $office->name }}</p>
  </div>
@endif