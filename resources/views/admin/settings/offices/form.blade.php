@extends('layouts.admin')

@section('title', $office->exists ? 'Edit Office' : 'Add Office')
@section('page-heading', $office->exists ? 'Edit office' : 'Add office')

@section('content')
<div class="page-heading">
  <h2>{{ $office->exists ? 'Update office details' : 'Create an office' }}</h2>
  <p>Offices linked to categories, routing rules, reports, or referrals cannot be deleted.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.settings.offices.index') }}">Back to offices</a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <form method="POST"
    action="{{ $office->exists ? route('admin.settings.offices.update', $office) : route('admin.settings.offices.store') }}"
    class="contact-form-grid">
    @csrf
    @if($office->exists)
      @method('PUT')
    @endif
    <label for="office-name">Office name
      <input id="office-name" name="name" type="text" maxlength="255" required
        value="{{ old('name', $office->name) }}">
      @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="office-type">Office type
      <input id="office-type" name="type" type="text" maxlength="100" required
        value="{{ old('type', $office->type) }}">
      @error('type') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="office-contact">Contact details
      <input id="office-contact" name="contact" type="text" maxlength="255"
        value="{{ old('contact', $office->contact) }}">
      @error('contact') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label class="contact-active-control">
      <input type="hidden" name="is_internal" value="0">
      <input type="checkbox" name="is_internal" value="1"
        @checked((bool) old('is_internal', $office->exists ? $office->is_internal : true))>
      Internal barangay office
    </label>
    @error('is_internal') <span class="field-error">{{ $message }}</span> @enderror
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">{{ $office->exists ? 'Save office' : 'Create office' }}</button>
      <a class="btn btn-outline" href="{{ route('admin.settings.offices.index') }}">Cancel</a>
    </div>
  </form>
</section>
@endsection
