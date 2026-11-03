@extends('layouts.admin')

@section('title', $sensitivePlace->exists ? 'Edit Sensitive Place' : 'Add Sensitive Place')
@section('page-heading', $sensitivePlace->exists ? 'Edit sensitive place' : 'Add sensitive place')

@section('content')
<div class="page-heading">
  <h2>{{ $sensitivePlace->exists ? 'Update location details' : 'Add a priority-sensitive location' }}</h2>
  <p>Use decimal latitude and longitude coordinates for the place.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.settings.sensitive-places.index') }}">Back to sensitive places</a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <form method="POST"
    action="{{ $sensitivePlace->exists ? route('admin.settings.sensitive-places.update', $sensitivePlace) : route('admin.settings.sensitive-places.store') }}"
    class="contact-form-grid">
    @csrf
    @if($sensitivePlace->exists)
      @method('PUT')
    @endif
    <label for="sensitive-place-name">Place name
      <input id="sensitive-place-name" name="name" type="text" maxlength="255" required
        value="{{ old('name', $sensitivePlace->name) }}">
      @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="sensitive-place-type">Place type
      <input id="sensitive-place-type" name="type" type="text" maxlength="100" required
        value="{{ old('type', $sensitivePlace->type) }}">
      @error('type') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="sensitive-place-lat">Latitude
      <input id="sensitive-place-lat" name="lat" type="number" step="any" min="-90" max="90" required
        value="{{ old('lat', $sensitivePlace->lat) }}">
      @error('lat') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="sensitive-place-lng">Longitude
      <input id="sensitive-place-lng" name="lng" type="number" step="any" min="-180" max="180" required
        value="{{ old('lng', $sensitivePlace->lng) }}">
      @error('lng') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">
        {{ $sensitivePlace->exists ? 'Save location' : 'Create location' }}
      </button>
      <a class="btn btn-outline" href="{{ route('admin.settings.sensitive-places.index') }}">Cancel</a>
    </div>
  </form>
</section>
@endsection
