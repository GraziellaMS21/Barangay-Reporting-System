@extends('layouts.admin')

@section('title', 'Edit Emergency Contact')
@section('page-heading', 'Edit emergency contact')

@section('content')
<div class="page-heading">
  <h2>Update contact details</h2>
  <p>Changes to active contacts appear on resident pages immediately.</p>
</div>

<section class="card">
  <form method="POST" action="{{ route('admin.settings.emergency-contacts.update', $contact) }}" class="contact-form-grid">
    @csrf
    @method('PUT')
    <label for="contact-name">Name
      <input id="contact-name" name="name" type="text" value="{{ old('name', $contact->name) }}" maxlength="255" required>
      @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="contact-number">Number
      <input id="contact-number" name="number" type="text" value="{{ old('number', $contact->number) }}" maxlength="50" required>
      @error('number') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="contact-type">Type
      <select id="contact-type" name="type" required>
        @foreach(\App\Models\EmergencyContact::TYPES as $type)
          <option value="{{ $type }}" @selected(old('type', $contact->type) === $type)>{{ ucfirst($type) }}</option>
        @endforeach
      </select>
      @error('type') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="contact-sort-order">Sort order
      <input id="contact-sort-order" name="sort_order" type="number" min="0"
        value="{{ old('sort_order', $contact->sort_order) }}" required>
      @error('sort_order') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label class="contact-active-control">
      <input type="hidden" name="is_active" value="0">
      <input type="checkbox" name="is_active" value="1" @checked((string) old('is_active', (int) $contact->is_active) === '1')>
      Show this contact to residents
    </label>
    @error('is_active') <span class="field-error">{{ $message }}</span> @enderror
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">
        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save changes
      </button>
      <a class="btn btn-outline" href="{{ route('admin.settings.emergency-contacts.index') }}">Cancel</a>
    </div>
  </form>
</section>
@endsection
