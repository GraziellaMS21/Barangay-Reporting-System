@extends('layouts.admin')

@section('title', 'Audit Log')
@section('page-heading', 'Audit log')

@section('content')
<div class="page-heading">
  <h2>Staff actions and setting changes</h2>
  <p>Review administrative changes, AI suggestion overrides, and report status history.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.settings.index') }}">Back to administration</a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <h2 class="card-title">Filter audit history</h2>
  <form method="POST" action="{{ route('admin.audit-log.filter') }}" class="contact-form-grid">
    @csrf
    <label for="audit-action">Action
      <select id="audit-action" name="action">
        <option value="">All actions</option>
        @foreach(['created' => 'Created', 'updated' => 'Updated', 'deleted' => 'Deleted', 'ai_override' => 'AI override', 'staff_review' => 'Staff review'] as $action => $label)
          <option value="{{ $action }}" @selected(($filters['action'] ?? '') === $action)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label for="audit-model">Record type
      <select id="audit-model" name="model">
        <option value="">All record types</option>
        @foreach($modelLabels as $modelClass => $label)
          <option value="{{ $modelClass }}" @selected(($filters['model'] ?? '') === $modelClass)>{{ $label }}</option>
        @endforeach
      </select>
    </label>
    <label for="audit-user">Changed by
      <select id="audit-user" name="user_id">
        <option value="">All staff</option>
        @foreach($users as $user)
          <option value="{{ $user->id }}" @selected((string) ($filters['user_id'] ?? '') === (string) $user->id)>
            {{ $user->name }}
          </option>
        @endforeach
      </select>
    </label>
    <label for="audit-from">From
      <input id="audit-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}">
    </label>
    <label for="audit-to">To
      <input id="audit-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}">
      @error('to') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">Apply filters</button>
      <a class="btn btn-outline" href="{{ route('admin.audit-log.index') }}">Clear filters</a>
    </div>
  </form>
</section>

<section class="card">
  <h2 class="card-title">Settings and staff overrides</h2>
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">When</th>
          <th scope="col">Who</th>
          <th scope="col">Action</th>
          <th scope="col">Record</th>
          <th scope="col">Old values</th>
          <th scope="col">New values</th>
        </tr>
      </thead>
      <tbody>
        @forelse($auditLogs as $auditLog)
          <tr>
            <td data-label="When">{{ $auditLog->created_at->format('M j, Y g:i A') }}</td>
            <td data-label="Who">{{ $auditLog->user?->name ?? 'System' }}</td>
            <td data-label="Action">{{ str($auditLog->action)->replace('_', ' ')->title() }}</td>
            <td data-label="Record">
              {{ $modelLabels[$auditLog->model] ?? class_basename($auditLog->model ?? 'Record') }}
              @if($auditLog->model_id) #{{ $auditLog->model_id }} @endif
            </td>
            <td data-label="Old values">
              <pre class="audit-values">{{ $auditLog->old_values ? json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—' }}</pre>
            </td>
            <td data-label="New values">
              <pre class="audit-values">{{ $auditLog->new_values ? json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) : '—' }}</pre>
            </td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="6" data-label="">No matching audit entries.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $auditLogs->links() }}
</section>

<section class="card">
  <h2 class="card-title">Report status changes</h2>
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">When</th>
          <th scope="col">Who</th>
          <th scope="col">Report</th>
          <th scope="col">Change</th>
          <th scope="col">Note</th>
        </tr>
      </thead>
      <tbody>
        @forelse($statusLogs as $statusLog)
          <tr>
            <td data-label="When">{{ $statusLog->created_at->format('M j, Y g:i A') }}</td>
            <td data-label="Who">{{ $statusLog->user?->name ?? 'System' }}</td>
            <td data-label="Report">{{ $statusLog->report?->tracking_code ?? 'Report unavailable' }}</td>
            <td data-label="Change">{{ str($statusLog->from_status)->replace('_', ' ')->title() }}
              → {{ str($statusLog->to_status)->replace('_', ' ')->title() }}</td>
            <td data-label="Note">{{ $statusLog->note }}</td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="5" data-label="">No matching status changes.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $statusLogs->links() }}
</section>
@endsection
