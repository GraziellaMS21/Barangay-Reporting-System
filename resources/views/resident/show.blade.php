@extends('layouts.app')
@section('title', 'Report ' . $report->tracking_code)

@section('content')
<div class="bg-white p-6 rounded shadow">
  <p class="text-sm text-gray-500">Tracking code</p>
  <p class="text-2xl font-mono font-bold mb-3">{{ $report->tracking_code }}</p>

  <p><span class="font-medium">Status:</span>
    <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded text-sm">
      {{ ucfirst(str_replace('_', ' ', $report->status)) }}
    </span>
  </p>
  <p class="text-sm mt-1"><span class="font-medium">Submitted:</span>
    {{ $report->created_at->format('M d, Y h:i A') }}</p>
  @if($report->purok)
    <p class="text-sm"><span class="font-medium">Purok:</span> {{ $report->purok->name }}</p>
  @endif

  <h2 class="font-semibold mt-6 mb-3">Progress</h2>
  <ol class="border-l-2 border-green-700 ml-2 space-y-4">
    @foreach($report->statusLogs as $log)
      <li class="ml-4">
        <p class="font-medium">{{ ucfirst(str_replace('_', ' ', $log->to_status)) }}</p>
        <p class="text-xs text-gray-500">{{ $log->created_at->format('M d, Y h:i A') }}</p>
        @if($log->note)<p class="text-sm">{{ $log->note }}</p>@endif
      </li>
    @endforeach
  </ol>
</div>
@endsection