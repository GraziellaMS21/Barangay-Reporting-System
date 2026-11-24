@extends('layouts.admin')

@section('title', 'Audit Log')
@section('page-heading', 'Audit log')

@section('content')
<div class="page-heading">
  <div>
    <h2>Staff actions and setting changes</h2>
    <p>Review administrative configuration changes, AI suggestion overrides, and report lifecycle status transitions.</p>
  </div>
  <div>
    <a class="btn btn-outline btn-small" href="{{ route('admin.settings.index') }}">
      <i class="fa-solid fa-arrow-left" aria-hidden="true" style="margin-right:0.3rem;"></i> Back to administration
    </a>
  </div>
</div>

@include('admin.settings.partials.validation-errors')

<!-- Filter Bar -->
<section class="card" style="margin-bottom:1.5rem;">
  <h2 class="card-title" style="font-size:1rem;margin-bottom:0.75rem;">
    <i class="fa-solid fa-filter" style="color:var(--blue,#2563eb);margin-right:0.35rem;" aria-hidden="true"></i>
    Filter audit history
  </h2>
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
    <div class="contact-actions" style="grid-column:1 / -1;display:flex;align-items:center;gap:0.5rem;margin-top:0.5rem;">
      <button type="submit" class="btn btn-gold">
        <i class="fa-solid fa-magnifying-glass" aria-hidden="true" style="margin-right:0.3rem;"></i> Apply filters
      </button>
      <a class="btn btn-outline" href="{{ route('admin.audit-log.index') }}">Clear filters</a>
    </div>
  </form>
</section>

<!-- Settings and Overrides Table -->
<section class="card" style="margin-bottom:1.5rem;">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
    <div>
      <h2 class="card-title" style="font-size:1.05rem;margin:0;">
        <i class="fa-solid fa-clock-rotate-left" style="color:var(--blue,#2563eb);margin-right:0.35rem;" aria-hidden="true"></i>
        Settings and staff overrides
      </h2>
      <p class="text-muted" style="margin:0.2rem 0 0;font-size:0.83rem;">Chronological log of administrative parameter changes and AI triage adjustments.</p>
    </div>
    <span class="text-muted" style="font-size:0.8rem;">{{ $auditLogs->total() }} total entries</span>
  </div>

  <div class="table-wrap">
    <table class="data-table data-table-cards responsive-card-table" style="table-layout:fixed;width:100%;">
      <thead>
        <tr>
          <th scope="col" style="width:16%;">When</th>
          <th scope="col" style="width:18%;">Activity &amp; Author</th>
          <th scope="col" style="width:16%;">Record</th>
          <th scope="col" style="width:25%;">Previous value</th>
          <th scope="col" style="width:25%;">New value</th>
        </tr>
      </thead>
      <tbody>
        @forelse($auditLogs as $auditLog)
          @php
            $actionBadgeClass = match($auditLog->action) {
              'ai_override' => 'review-badge-amber',
              'created' => 'review-badge-green',
              'deleted' => 'review-badge-red',
              default => 'review-badge-blue',
            };
            $recordName = $modelLabels[$auditLog->model] ?? class_basename($auditLog->model ?? 'Record');
            $actorName = $auditLog->user?->name ?? 'System';
          @endphp
          <tr>
            <td data-label="When">
              <span style="font-weight:600;font-size:0.85rem;color:var(--ink,#0f172a);">
                {{ $auditLog->created_at->format('M j, Y') }}
              </span>
              <div class="text-muted" style="font-size:0.75rem;">
                {{ $auditLog->created_at->format('g:i A') }}
              </div>
            </td>
            <td data-label="Activity & Author">
              <div style="display:flex;align-items:center;gap:0.4rem;margin-bottom:0.25rem;">
                <span class="review-badge {{ $actionBadgeClass }}" style="font-size:0.72rem;">
                  @if($auditLog->action === 'ai_override')
                    <i class="fa-solid fa-wand-magic-sparkles" style="margin-right:0.2rem;" aria-hidden="true"></i>
                  @endif
                  {{ str($auditLog->action)->replace('_', ' ')->title() }}
                </span>
              </div>
              <div style="font-size:0.84rem;color:var(--ink,#0f172a);display:flex;align-items:center;gap:0.35rem;">
                <i class="fa-solid fa-user-circle text-muted" style="font-size:0.85rem;" aria-hidden="true"></i>
                <strong>{{ $actorName }}</strong>
              </div>
              <div class="text-muted" style="font-size:0.75rem;margin-top:0.15rem;">
                {{ $actorName }} {{ strtolower(str($auditLog->action)->replace('_', ' ')) }} {{ strtolower($recordName) }}
              </div>
            </td>
            <td data-label="Record">
              <span style="font-weight:600;color:var(--ink,#0f172a);font-size:0.85rem;">{{ $recordName }}</span>
              @if($auditLog->model_id)
                <span class="text-muted" style="font-size:0.78rem;display:block;">ID #{{ $auditLog->model_id }}</span>
              @endif
            </td>
            <td data-label="Old values">
              @if(is_array($auditLog->old_values) && count($auditLog->old_values) > 0)
                <dl class="audit-summary-list" style="margin:0;font-size:0.8rem;background:var(--surface-muted,#f8fafc);padding:0.4rem 0.6rem;border-radius:0.35rem;border:1px solid var(--line,#e2e8f0);">
                  @foreach($auditLog->old_values as $key => $val)
                    <div class="audit-kv-pair" style="display:flex;align-items:baseline;gap:0.4rem;margin-bottom:0.2rem;">
                      <dt class="audit-kv-key" style="font-weight:600;color:var(--muted,#64748b);font-size:0.74rem;">{{ str($key)->replace('_', ' ')->title() }}:</dt>
                      <dd class="audit-kv-val" style="margin:0;color:var(--ink,#0f172a);word-break:break-all;">{{ is_array($val) ? json_encode($val) : (is_bool($val) ? ($val ? 'true' : 'false') : ($val ?? '—')) }}</dd>
                    </div>
                  @endforeach
                </dl>
                <details class="audit-raw-details" style="margin-top:0.3rem;font-size:0.75rem;">
                  <summary style="cursor:pointer;color:var(--blue,#2563eb);">View raw JSON</summary>
                  <pre class="audit-values" style="max-height:160px;overflow:auto;background:#0f172a;color:#e2e8f0;padding:0.5rem;border-radius:0.25rem;font-size:0.72rem;margin-top:0.25rem;">{{ json_encode($auditLog->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
              @elseif(!empty($auditLog->old_values))
                <span class="audit-kv-val" style="font-size:0.82rem;">{{ (string) $auditLog->old_values }}</span>
              @else
                <span class="cell-muted" style="color:var(--muted,#94a3b8);">—</span>
              @endif
            </td>
            <td data-label="New values">
              @if(is_array($auditLog->new_values) && count($auditLog->new_values) > 0)
                <dl class="audit-summary-list" style="margin:0;font-size:0.8rem;background:#f0fdf4;padding:0.4rem 0.6rem;border-radius:0.35rem;border:1px solid #bbf7d0;">
                  @foreach($auditLog->new_values as $key => $val)
                    <div class="audit-kv-pair" style="display:flex;align-items:baseline;gap:0.4rem;margin-bottom:0.2rem;">
                      <dt class="audit-kv-key" style="font-weight:600;color:#166534;font-size:0.74rem;">{{ str($key)->replace('_', ' ')->title() }}:</dt>
                      <dd class="audit-kv-val" style="margin:0;color:#14532d;word-break:break-all;font-weight:500;">{{ is_array($val) ? json_encode($val) : (is_bool($val) ? ($val ? 'true' : 'false') : ($val ?? '—')) }}</dd>
                    </div>
                  @endforeach
                </dl>
                <details class="audit-raw-details" style="margin-top:0.3rem;font-size:0.75rem;">
                  <summary style="cursor:pointer;color:var(--blue,#2563eb);">View raw JSON</summary>
                  <pre class="audit-values" style="max-height:160px;overflow:auto;background:#0f172a;color:#e2e8f0;padding:0.5rem;border-radius:0.25rem;font-size:0.72rem;margin-top:0.25rem;">{{ json_encode($auditLog->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                </details>
              @elseif(!empty($auditLog->new_values))
                <span class="audit-kv-val" style="font-size:0.82rem;">{{ (string) $auditLog->new_values }}</span>
              @else
                <span class="cell-muted" style="color:var(--muted,#94a3b8);">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="5" data-label="" style="text-align:center;padding:2rem;">No matching audit entries.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:1rem;">
    {{ $auditLogs->links() }}
  </div>
</section>

<!-- Report Status Changes Table -->
<section class="card">
  <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:0.5rem;">
    <div>
      <h2 class="card-title" style="font-size:1.05rem;margin:0;">
        <i class="fa-solid fa-timeline" style="color:var(--blue,#2563eb);margin-right:0.35rem;" aria-hidden="true"></i>
        Report status changes
      </h2>
      <p class="text-muted" style="margin:0.2rem 0 0;font-size:0.83rem;">Audit trail of case lifecycle transitions and staff operational notes.</p>
    </div>
    <span class="text-muted" style="font-size:0.8rem;">{{ $statusLogs->total() }} status events</span>
  </div>

  <div class="table-wrap">
    <table class="data-table data-table-cards responsive-card-table" style="table-layout:fixed;width:100%;">
      <thead>
        <tr>
          <th scope="col" style="width:16%;">When</th>
          <th scope="col" style="width:18%;">Who</th>
          <th scope="col" style="width:18%;">Report</th>
          <th scope="col" style="width:24%;">Change</th>
          <th scope="col" style="width:24%;">Note</th>
        </tr>
      </thead>
      <tbody>
        @forelse($statusLogs as $statusLog)
          <tr>
            <td data-label="When">
              <span style="font-weight:600;font-size:0.85rem;color:var(--ink,#0f172a);">
                {{ $statusLog->created_at->format('M j, Y') }}
              </span>
              <div class="text-muted" style="font-size:0.75rem;">
                {{ $statusLog->created_at->format('g:i A') }}
              </div>
            </td>
            <td data-label="Who">
              <div style="display:flex;align-items:center;gap:0.35rem;font-size:0.85rem;color:var(--ink,#0f172a);">
                <i class="fa-solid fa-user-circle text-muted" aria-hidden="true"></i>
                <strong>{{ $statusLog->user?->name ?? 'System' }}</strong>
              </div>
            </td>
            <td data-label="Report">
              @if($statusLog->report)
                <a href="{{ route('admin.reports.show', $statusLog->report) }}" style="font-weight:700;font-family:monospace;color:var(--blue,#2563eb);text-decoration:none;">
                  {{ $statusLog->report->tracking_code }}
                </a>
              @else
                <span class="text-muted">Report unavailable</span>
              @endif
            </td>
            <td data-label="Change">
              <div style="display:flex;align-items:center;gap:0.4rem;font-size:0.82rem;flex-wrap:wrap;">
                <span class="status-badge status-{{ $statusLog->from_status }}" style="font-size:0.72rem;">
                  {{ str($statusLog->from_status)->replace('_', ' ')->title() }}
                </span>
                <i class="fa-solid fa-arrow-right text-muted" style="font-size:0.7rem;" aria-hidden="true"></i>
                <span class="status-badge status-{{ $statusLog->to_status }}" style="font-size:0.72rem;">
                  {{ str($statusLog->to_status)->replace('_', ' ')->title() }}
                </span>
              </div>
            </td>
            <td data-label="Note">
              @if($statusLog->note)
                <div style="font-size:0.83rem;color:var(--ink,#0f172a);background:var(--surface-muted,#f8fafc);padding:0.35rem 0.6rem;border-radius:0.35rem;border-left:3px solid var(--blue,#2563eb);">
                  {{ $statusLog->note }}
                </div>
              @else
                <span class="text-muted" style="font-size:0.8rem;">—</span>
              @endif
            </td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="5" data-label="" style="text-align:center;padding:2rem;">No matching status changes.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="margin-top:1rem;">
    {{ $statusLogs->links() }}
  </div>
</section>
@endsection
