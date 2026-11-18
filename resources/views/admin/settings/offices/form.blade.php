@extends('layouts.admin')

@section('title', $office->exists ? 'Edit Office or Agency' : 'Add Office or Agency')
@section('page-heading', $office->exists ? 'Edit office or agency' : 'Add office or agency')

@section('content')
@php
  $selectedType = old('type', $office->exists ? $office->directoryType() : 'internal');
  if (!in_array($selectedType, ['internal', 'external'], true)) {
      $selectedType = ($office->exists && !$office->is_internal) ? 'external' : 'internal';
  }
@endphp
<div class="page-heading">
  <h2>{{ $office->exists ? 'Update office or agency' : 'Add office or agency' }}</h2>
  <p>Offices and agencies linked to categories, routing rules, reports, or referrals cannot be deleted, but can be deactivated at any time.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.settings.offices.index') }}">Back to directory</a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <form method="POST"
    action="{{ $office->exists ? route('admin.settings.offices.update', $office) : route('admin.settings.offices.store') }}"
    class="contact-form-grid"
    data-office-form>
    @csrf
    @if($office->exists)
      @method('PUT')
    @endif

    <label for="office-name">Name
      <input id="office-name" name="name" type="text" maxlength="255" required
        value="{{ old('name', $office->name) }}" placeholder="e.g. Barangay Sanitation Office or City Engineering Office">
      @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <label for="office-type">Directory type
      <select id="office-type" name="type" required data-office-type-select>
        @foreach(\App\Models\Office::TYPES as $typeKey => $typeLabel)
          <option value="{{ $typeKey }}" @selected($selectedType === $typeKey)>{{ $typeLabel }}</option>
        @endforeach
      </select>
      @error('type') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <label for="office-description" style="grid-column: 1 / -1;">Short description
      <input id="office-description" name="description" type="text" maxlength="500"
        value="{{ old('description', $office->description) }}" placeholder="Brief summary of responsibilities (e.g. Handles clogged canals and solid waste collection)">
      @error('description') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <label for="office-contact-person">Contact person
      <input id="office-contact-person" name="contact_person" type="text" maxlength="255"
        value="{{ old('contact_person', $office->contact_person) }}" placeholder="e.g. Engr. Ana Reyes">
      @error('contact_person') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <label for="office-phone">Phone
      <input id="office-phone" name="contact_phone" type="text" maxlength="100"
        value="{{ old('contact_phone', $office->contact_phone) }}" placeholder="e.g. (062) 991-0001">
      @error('contact_phone') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <label for="office-email">Email <span class="field-hint">(external agencies mainly)</span>
      <input id="office-email" name="contact_email" type="email" maxlength="255"
        value="{{ old('contact_email', $office->contact_email) }}" placeholder="e.g. agency@example.gov.ph">
      @error('contact_email') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <label for="office-address">Address / Location <span class="field-hint">(optional)</span>
      <input id="office-address" name="address" type="text" maxlength="255"
        value="{{ old('address', $office->address ?? $office->contact) }}" placeholder="e.g. Barangay Hall or City Hall">
      @error('address') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <label for="office-default-assignee" data-internal-only-field @if($selectedType === 'external') hidden @endif>Default assignee <span class="field-hint">(internal offices only)</span>
      <select id="office-default-assignee" name="default_assignee_id" @disabled($selectedType === 'external')>
        <option value="">Unassigned</option>
        @foreach($staffUsers ?? [] as $staffUser)
          <option value="{{ $staffUser->id }}" @selected((string) old('default_assignee_id', $office->default_assignee_id) === (string) $staffUser->id)>
            {{ $staffUser->name }} ({{ ucfirst($staffUser->role) }})
          </option>
        @endforeach
      </select>
      @error('default_assignee_id') <span class="field-error">{{ $message }}</span> @enderror
    </label>

    <label class="contact-active-control" style="grid-column: 1 / -1;">
      <input type="hidden" name="is_active" value="0">
      <input type="checkbox" name="is_active" value="1"
        @checked((bool) old('is_active', $office->exists ? $office->is_active : true))>
      Active (available in Route and assign)
    </label>
    @error('is_active') <span class="field-error">{{ $message }}</span> @enderror

    <div class="contact-actions" style="grid-column: 1 / -1;">
      <button type="submit" class="btn btn-gold">{{ $office->exists ? 'Save changes' : 'Create entry' }}</button>
      <a class="btn btn-outline" href="{{ route('admin.settings.offices.index') }}">Cancel</a>
    </div>
  </form>
</section>

@push('scripts')
<script>
(() => {
  const form = document.querySelector('[data-office-form]');
  if (!form) return;
  const typeSelect = form.querySelector('[data-office-type-select]');
  const internalField = form.querySelector('[data-internal-only-field]');
  const assigneeSelect = internalField?.querySelector('select');
  if (!typeSelect || !internalField) return;

  const syncType = () => {
    const isInternal = typeSelect.value === 'internal';
    internalField.hidden = !isInternal;
    if (assigneeSelect) {
      assigneeSelect.disabled = !isInternal;
      if (!isInternal) assigneeSelect.value = '';
    }
  };
  typeSelect.addEventListener('change', syncType);
  syncType();
})();
</script>
@endpush
@endsection
