@extends('layouts.app')
@section('title', 'Report ' . $report->tracking_code)

@section('content')
<p class="mb-4">
  <a href="{{ route('admin.reports.index') }}" class="text-green-800 underline">&larr; Back to reports</a>
</p>

@if(session('success'))
  <div role="status" class="mb-4 rounded border border-green-300 bg-green-100 p-3 text-green-800">
    {{ session('success') }}
  </div>
@endif

<div class="grid gap-6 lg:grid-cols-3">
  <section class="space-y-4 rounded bg-white p-5 shadow lg:col-span-2">
    <div class="flex flex-wrap items-start justify-between gap-3">
      <div>
        <p class="text-sm text-gray-600">Tracking code</p>
        <h1 class="text-2xl font-bold font-mono">{{ $report->tracking_code }}</h1>
      </div>
      <x-status-badge :status="$report->status" />
    </div>

    <div>
      <h2 class="mb-1 font-semibold">Description</h2>
      <p class="whitespace-pre-line">{{ $report->description }}</p>
    </div>

    @if($report->photo_path)
      <div>
        <h2 class="mb-2 font-semibold">Attached photo</h2>
        <a href="{{ asset('storage/' . $report->photo_path) }}" target="_blank" rel="noopener noreferrer">
          <img src="{{ asset('storage/' . $report->photo_path) }}" alt="Photo attached to this report"
            class="max-h-96 rounded border object-contain">
        </a>
      </div>
    @endif

    <dl class="grid gap-3 border-t pt-4 sm:grid-cols-2">
      <div>
        <dt class="text-sm text-gray-600">Submitted</dt>
        <dd>{{ $report->created_at->format('M d, Y h:i A') }}</dd>
      </div>
      <div>
        <dt class="text-sm text-gray-600">Purok</dt>
        <dd>{{ $report->purok?->name ?? 'Not specified' }}</dd>
      </div>
      <div>
        <dt class="text-sm text-gray-600">Landmark</dt>
        <dd>{{ $report->landmark ?: 'Not specified' }}</dd>
      </div>
      @if($report->reporter_email)
        <div>
          <dt class="text-sm text-gray-600">Reporter email</dt>
          <dd><a href="mailto:{{ $report->reporter_email }}" class="text-green-800 underline">{{ $report->reporter_email }}</a></dd>
        </div>
      @endif
      @if($report->lat !== null && $report->lng !== null)
        <div>
          <dt class="text-sm text-gray-600">Location</dt>
          <dd>
            <a href="https://www.google.com/maps?q={{ $report->lat }},{{ $report->lng }}"
              target="_blank" rel="noopener noreferrer" class="text-green-800 underline">
              View on map
            </a>
          </dd>
        </div>
      @endif
    </dl>
  </section>

  <aside class="space-y-6">
    <section class="rounded bg-white p-5 shadow">
      <h2 class="mb-4 text-lg font-semibold">Update status</h2>
      @if($errors->any())
        <div role="alert" class="mb-4 rounded border border-red-300 bg-red-100 p-3 text-sm text-red-800">
          {{ $errors->first() }}
        </div>
      @endif
      <form method="POST" action="{{ route('admin.reports.status', $report) }}" class="space-y-3">
        @csrf
        @method('PATCH')
        <label class="block">
          <span class="mb-1 block text-sm font-medium">New status</span>
          <select name="status" required class="w-full rounded border p-2">
            @foreach($manualStatuses as $status)
              <option value="{{ $status }}" @selected(old('status', $report->status) === $status)>
                {{ \App\Models\Report::STATUSES[$status] }}
              </option>
            @endforeach
          </select>
        </label>
        <label class="block">
          <span class="mb-1 block text-sm font-medium">Note <span class="font-normal text-gray-600">(required for out of scope)</span></span>
          <textarea name="note" rows="3" maxlength="500" class="w-full rounded border p-2">{{ old('note') }}</textarea>
        </label>
        <button type="submit" class="rounded bg-green-700 px-4 py-2 text-white hover:bg-green-800">
          Save status
        </button>
      </form>
    </section>

    <section class="rounded bg-white p-5 shadow">
      <h2 class="mb-4 text-lg font-semibold">Status history</h2>
      @forelse($report->statusLogs as $log)
        <article class="border-l-2 border-green-700 pb-4 pl-3 last:pb-0">
          <p class="font-medium">{{ \App\Models\Report::STATUSES[$log->to_status] ?? ucfirst(str_replace('_', ' ', $log->to_status)) }}</p>
          <p class="text-xs text-gray-600">{{ $log->created_at->format('M d, Y h:i A') }}</p>
          @if($log->user)
            <p class="text-xs text-gray-600">Updated by {{ $log->user->name }}</p>
          @endif
          @if($log->note)
            <p class="mt-1 text-sm">{{ $log->note }}</p>
          @endif
        </article>
      @empty
        <p class="text-sm text-gray-600">No status history yet.</p>
      @endforelse
    </section>
  </aside>
</div>
@endsection
