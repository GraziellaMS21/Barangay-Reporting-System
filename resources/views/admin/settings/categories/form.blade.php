@extends('layouts.admin')

@section('title', $category->exists ? 'Edit Category' : 'Add Category')
@section('page-heading', $category->exists ? 'Edit category' : 'Add category')

@section('content')
<div class="page-heading">
  <h2>{{ $category->exists ? 'Update category details' : 'Create a report category' }}</h2>
  <p>Changes to active categories affect new reports. Existing reports keep their saved category.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.settings.categories.index') }}">Back to categories</a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <form method="POST"
    action="{{ $category->exists ? route('admin.settings.categories.update', $category) : route('admin.settings.categories.store') }}"
    class="contact-form-grid">
    @csrf
    @if($category->exists)
      @method('PUT')
    @endif
    <label for="category-name">Category name
      <input id="category-name" name="name" type="text" maxlength="255" required
        value="{{ old('name', $category->name) }}">
      @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="category-risk-weight">Risk weight
      <input id="category-risk-weight" name="risk_weight" type="number" min="0" max="1000" required
        value="{{ old('risk_weight', $category->risk_weight ?? 1) }}">
      @error('risk_weight') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="category-default-office">Default office
      <select id="category-default-office" name="default_office_id">
        <option value="">No default office</option>
        @foreach($offices as $office)
          <option value="{{ $office->id }}"
            @selected((string) old('default_office_id', $category->default_office_id) === (string) $office->id)>
            {{ $office->name }}
          </option>
        @endforeach
      </select>
      @error('default_office_id') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label class="contact-active-control">
      <input type="hidden" name="is_active" value="0">
      <input type="checkbox" name="is_active" value="1"
        @checked((bool) old('is_active', $category->exists ? $category->is_active : true))>
      Available for new reports
    </label>
    @error('is_active') <span class="field-error">{{ $message }}</span> @enderror
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">
        {{ $category->exists ? 'Save category' : 'Create category' }}
      </button>
      <a class="btn btn-outline" href="{{ route('admin.settings.categories.index') }}">Cancel</a>
    </div>
  </form>
</section>
@endsection
