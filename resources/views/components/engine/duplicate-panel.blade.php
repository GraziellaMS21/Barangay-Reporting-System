{{-- x-engine.duplicate-panel; prop: $report --}}
@if($report->duplicateSuggestions->where('decision','pending')->isNotEmpty())
  <div class="engine-duplicate-panel card">
    <p>Pending duplicate suggestion(s).</p>
  </div>
@endif