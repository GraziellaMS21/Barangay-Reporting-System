@extends('layouts.admin')

@section('title', 'Emergency Contacts')
@section('page-heading', 'Emergency contacts')

@section('content')
<div class="page-heading">
  <h2>Emergency contact directory</h2>
  <p>Active contacts appear on the resident report form and emergency reminder, in the order set here.</p>
</div>

<section class="card">
  <h2 class="card-title"><i class="fa-solid fa-plus" aria-hidden="true"></i> Add a contact</h2>
  <form method="POST" action="{{ route('admin.settings.emergency-contacts.store') }}" class="contact-form-grid">
    @csrf
    <label for="contact-name">Name
      <input id="contact-name" name="name" type="text" value="{{ old('name') }}" maxlength="255" required>
      @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="contact-number">Number
      <input id="contact-number" name="number" type="text" value="{{ old('number') }}" maxlength="50" required>
      @error('number') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="contact-type">Type
      <select id="contact-type" name="type" required>
        <option value="">Select type</option>
        @foreach(\App\Models\EmergencyContact::TYPES as $type)
          <option value="{{ $type }}" @selected(old('type') === $type)>{{ ucfirst($type) }}</option>
        @endforeach
      </select>
      @error('type') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="contact-sort-order">Sort order
      <input id="contact-sort-order" name="sort_order" type="number" min="0" value="{{ old('sort_order', 0) }}" required>
      @error('sort_order') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label class="contact-active-control">
      <input type="hidden" name="is_active" value="0">
      <input type="checkbox" name="is_active" value="1" @checked(old('is_active', '1') === '1')>
      Show this contact to residents
    </label>
    @error('is_active') <span class="field-error">{{ $message }}</span> @enderror
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">
        <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Add contact
      </button>
    </div>
  </form>
</section>

<section class="emergency-contact-list" aria-label="Emergency contacts">
  @forelse($contacts as $contact)
    <article class="card emergency-contact-card">
      <div>
        <h2>{{ $contact->name }}</h2>
        <p>{{ $contact->number }} · {{ ucfirst($contact->type) }}</p>
        <span class="contact-state {{ $contact->is_active ? 'is-active' : 'is-inactive' }}">
          {{ $contact->is_active ? 'Active' : 'Inactive' }} · Order {{ $contact->sort_order }}
        </span>
      </div>
      <div class="contact-actions">
        <a class="btn btn-outline btn-small" href="{{ route('admin.settings.emergency-contacts.edit', $contact) }}">
          <i class="fa-solid fa-pen" aria-hidden="true"></i> Edit
        </a>
        <form method="POST" action="{{ route('admin.settings.emergency-contacts.destroy', $contact) }}"
          data-confirm="Delete {{ $contact->name }} from the emergency contact directory?">
          @csrf
          @method('DELETE')
          <button type="submit" class="btn btn-danger btn-small">
            <i class="fa-solid fa-trash" aria-hidden="true"></i> Delete
          </button>
        </form>
      </div>
    </article>
  @empty
    <div class="card empty-state">No emergency contacts have been added.</div>
  @endforelse
</section>
@endsection
