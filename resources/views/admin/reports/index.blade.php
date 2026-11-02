@extends('layouts.app')
@section('title', 'Staff Dashboard')

@section('content')
<div class="mb-6">
  <h1 class="text-2xl font-bold">Staff Dashboard</h1>
  <p class="text-sm text-gray-600">Review and manage reports submitted by residents.</p>
</div>

@if(session('success'))
  <div role="status" class="mb-4 rounded border border-green-300 bg-green-100 p-3 text-green-800">
    {{ session('success') }}
  </div>
@endif

<section aria-label="Report totals" class="mb-6 grid grid-cols-2 gap-3 sm:grid-cols-5">
  @foreach([
      'total' => 'All reports',
      'new' => 'New',
      'under_review' => 'Under review',
      'in_progress' => 'In progress',
      'resolved' => 'Resolved',
  ] as $key => $label)
    <div class="rounded border bg-white p-3">
      <p class="text-sm text-gray-600">{{ $label }}</p>
      <p class="text-2xl font-bold">{{ $statTotals[$key] }}</p>
    </div>
  @endforeach
</section>

<form method="GET" action="{{ route('admin.reports.index') }}"
  class="mb-4 grid gap-3 rounded bg-white p-4 sm:grid-cols-4">
  <label class="sm:col-span-2">
    <span class="mb-1 block text-sm font-medium">Search</span>
    <input type="search" name="q" value="{{ request('q') }}"
      placeholder="Tracking code or description" class="w-full rounded border p-2">
  </label>
  <label>
    <span class="mb-1 block text-sm font-medium">Status</span>
    <select name="status" class="w-full rounded border p-2">
      <option value="">All statuses</option>
      @foreach(\App\Models\Report::STATUSES as $value => $label)
        <option value="{{ $value }}" @selected(request('status') === $value)>{{ $label }}</option>
      @endforeach
    </select>
  </label>
  <label>
    <span class="mb-1 block text-sm font-medium">Purok</span>
    <select name="purok_id" class="w-full rounded border p-2">
      <option value="">All puroks</option>
      @foreach($puroks as $purok)
        <option value="{{ $purok->id }}" @selected((string) request('purok_id') === (string) $purok->id)>
          {{ $purok->name }}
        </option>
      @endforeach
    </select>
  </label>
  <div class="sm:col-span-4">
    <button type="submit" class="rounded bg-green-700 px-4 py-2 text-white hover:bg-green-800">Apply filters</button>
    <a href="{{ route('admin.reports.index') }}" class="ml-2 text-sm text-green-800 underline">Clear</a>
  </div>
</form>

<div class="overflow-x-auto rounded bg-white shadow">
  <table class="w-full text-left text-sm">
    <thead class="border-b bg-gray-100">
      <tr>
        <th scope="col" class="p-3">Tracking code</th>
        <th scope="col" class="p-3">Description</th>
        <th scope="col" class="p-3">Purok</th>
        <th scope="col" class="p-3">Status</th>
        <th scope="col" class="p-3">Submitted</th>
      </tr>
    </thead>
    <tbody>
      @forelse($reports as $report)
        <tr class="border-b last:border-0">
          <td class="whitespace-nowrap p-3 font-mono">
            <a href="{{ route('admin.reports.show', $report) }}" class="text-green-800 underline">
              {{ $report->tracking_code }}
            </a>
          </td>
          <td class="min-w-56 p-3">{{ \Illuminate\Support\Str::limit($report->description, 100) }}</td>
          <td class="whitespace-nowrap p-3">{{ $report->purok?->name ?? '—' }}</td>
          <td class="whitespace-nowrap p-3"><x-status-badge :status="$report->status" /></td>
          <td class="whitespace-nowrap p-3">{{ $report->created_at->format('M d, Y') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="p-6 text-center text-gray-600">No reports found.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</div>

<div class="mt-4">{{ $reports->links() }}</div>
@endsection
