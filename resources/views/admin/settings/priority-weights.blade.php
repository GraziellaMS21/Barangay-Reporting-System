@extends('layouts.admin')

@section('title', 'Priority Weights')
@section('page-heading', 'Priority weights')

@section('content')
<div class="page-heading">
  <h2>Report priority scoring</h2>
  <p>These weights affect the priority score when a report is recalculated.</p>
  <a class="btn btn-ghost btn-small" href="{{ route('admin.settings.index') }}">Back to administration</a>
</div>

<section class="card">
  <div class="page-heading">
    <h2 class="card-title">Scoring weights</h2>
    <button type="button" class="btn btn-outline btn-small" data-help-open="priority-help">
      <i class="fa-solid fa-circle-question" aria-hidden="true"></i> How scoring works
    </button>
  </div>

  <form method="POST" action="{{ route('admin.settings.priority') }}" class="contact-form-grid">
    @csrf
    <label for="priority-urgency">Urgency weight
      <input id="priority-urgency" name="priority[urgency]" type="number" min="0" max="1000"
        value="{{ old('priority.urgency', $weights['urgency']) }}" required>
      @error('priority.urgency') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="priority-category">Category risk weight
      <input id="priority-category" name="priority[category]" type="number" min="0" max="1000"
        value="{{ old('priority.category', $weights['category']) }}" required>
      @error('priority.category') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="priority-sensitive-place">Sensitive-place weight
      <input id="priority-sensitive-place" name="priority[sensitive_place]" type="number" min="0" max="1000"
        value="{{ old('priority.sensitive_place', $weights['sensitive_place']) }}" required>
      @error('priority.sensitive_place') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="priority-supporting-reports">Supporting-reports weight
      <input id="priority-supporting-reports" name="priority[supporting_reports]" type="number" min="0" max="1000"
        value="{{ old('priority.supporting_reports', $weights['supporting_reports']) }}" required>
      @error('priority.supporting_reports') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="priority-age-days">Age-in-days weight
      <input id="priority-age-days" name="priority[age_days]" type="number" min="0" max="1000"
        value="{{ old('priority.age_days', $weights['age_days']) }}" required>
      @error('priority.age_days') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">
        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save weights
      </button>
    </div>
  </form>

  <form method="POST" action="{{ route('admin.settings.priority-weights.reset') }}"
    data-confirm="Reset every priority weight to its default value?">
    @csrf
    <button type="submit" class="btn btn-outline btn-small">Reset to defaults</button>
  </form>
</section>

<dialog id="priority-help" class="settings-help-dialog" aria-labelledby="priority-help-title">
  <div class="settings-help-content">
    <h2 id="priority-help-title">How priority scoring works</h2>
    <p>The score combines urgency, category risk, nearby sensitive places, supporting reports, and report age.</p>
    <ul>
      <li><strong>Urgency:</strong> urgency points multiplied by the urgency weight.</li>
      <li><strong>Category:</strong> the category risk value multiplied by this weight.</li>
      <li><strong>Sensitive place:</strong> this weight is added when a report is within 150 meters of a sensitive place.</li>
      <li><strong>Supporting reports:</strong> merged supporting reports multiplied by this weight.</li>
      <li><strong>Age:</strong> report age in days multiplied by this weight.</li>
    </ul>
    <p>Emergency reports are excluded from priority scoring. A higher score means a report appears earlier in priority-sorted queues.</p>
    <button type="button" class="btn btn-navy btn-small" data-help-close>Close</button>
  </div>
</dialog>
@endsection
