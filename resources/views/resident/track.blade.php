@extends('layouts.app')
@section('title', 'Track a Report')

@section('content')
<h1 class="text-2xl font-bold mb-4">Track a Report</h1>

@if($errors->any())
  <div class="bg-red-100 border border-red-300 text-red-800 rounded p-3 mb-4 text-sm">
    {{ $errors->first() }}
  </div>
@endif

<form method="POST" action="{{ route('track.search') }}" class="bg-white p-5 rounded shadow flex gap-3">
  @csrf
  <input type="text" name="tracking_code" placeholder="BRG-XXXXXX"
    class="flex-1 border rounded p-2 font-mono uppercase" required>
  <button class="bg-green-700 text-white px-5 py-2 rounded">Search</button>
</form>
@endsection