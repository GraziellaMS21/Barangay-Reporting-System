{{-- x-engine.ai-panel; prop: $report --}}
@php $ai = $report->aiResult; @endphp
@if($report->ai_status === 'done' && $ai && $ai->error === null)
  <div class="engine-ai-panel card">
    <p class="review-ai-label">AI suggestion — staff review required</p>
    <p>{{ $ai->summary }}</p>
  </div>
@endif