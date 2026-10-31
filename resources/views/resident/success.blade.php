@extends('layouts.app')
@section('title', 'Report Submitted')

@section('content')
<div class="bg-white p-6 rounded shadow text-center">
  <h1 class="text-2xl font-bold text-green-700 mb-2">Report received</h1>
  <p>Please save your tracking code:</p>
  <p class="text-3xl font-mono font-bold my-4">{{ $report->tracking_code }}</p>
  <a href="{{ route('track.show', $report->tracking_code) }}"
     class="inline-block bg-green-700 text-white px-5 py-2 rounded">Track this report</a>
  <p class="text-sm text-gray-600 mt-6">
    If this is an emergency, call 911 or the local police. This system is not monitored in real time.
  </p>
</div>
@endsection