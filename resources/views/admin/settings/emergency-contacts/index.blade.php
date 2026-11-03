@extends('layouts.admin')

@section('title', 'Emergency Contacts')
@section('page-heading', 'Emergency contacts')

@section('content')
<div class="page-heading">
  <h2>Emergency contact directory</h2>
  <p>Active contacts appear on the resident report form and emergency reminder, in the order set here.</p>
  <button type="button" class="btn btn-gold" data-dialog-open="add-contact-dialog">
    <i class="fa-solid fa-plus" aria-hidden="true"></i> Add a contact
  </button>
</div>

<section class="card" aria-label="Emergency contacts">
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">Name</th>
          <th scope="col">Number</th>
          <th scope="col">Type</th>
          <th scope="col">Visibility</th>
          <th scope="col">Order</th>
          <th scope="col"><span class="sr-only">Actions</span></th>
        </tr>
      </thead>
      <tbody>
        @forelse($contacts as $contact)
          <tr>
            <td data-label="Name">{{ $contact->name }}</td>
            <td data-label="Number">{{ $contact->number }}</td>
            <td data-label="Type">{{ ucfirst($contact->type) }}</td>
            <td data-label="Visibility">
              <span class="contact-state {{ $contact->is_active ? 'is-active' : 'is-inactive' }}">
                {{ $contact->is_active ? 'Active' : 'Inactive' }}
              </span>
            </td>
            <td data-label="Order">{{ $contact->sort_order }}</td>
            <td data-label="Actions">
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
            </td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="6" data-label="">No emergency contacts have been added.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>

<dialog id="add-contact-dialog" class="settings-help-dialog contact-dialog" aria-labelledby="add-contact-heading"
  data-contact-dialog @if($errors->any() || old('name') !== null || old('number') !== null) data-open-on-load @endif>
  <div class="settings-help-content">
    <h2 id="add-contact-heading" class="card-title">
      <i class="fa-solid fa-plus" aria-hidden="true"></i> Add an emergency contact
    </h2>
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
        <button type="button" class="btn btn-outline" data-dialog-close>Cancel</button>
        <button type="submit" class="btn btn-gold">
          <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Add contact
        </button>
      </div>
    </form>
  </div>
</dialog>
@endsection
