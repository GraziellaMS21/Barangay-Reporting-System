@extends('layouts.admin')

@section('title', $routingRule->exists ? 'Edit Routing Rule' : 'Add Routing Rule')
@section('page-heading', $routingRule->exists ? 'Edit routing rule' : 'Add routing rule')

@section('content')
<div class="page-heading">
  <h2>{{ $routingRule->exists ? 'Update category routing' : 'Configure category routing' }}</h2>
  <p>Staff review the recommended office and jurisdiction before a report is routed.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.settings.routing-rules.index') }}">Back to routing rules</a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <form method="POST"
    action="{{ $routingRule->exists ? route('admin.settings.routing-rules.update', $routingRule) : route('admin.settings.routing-rules.store') }}"
    class="contact-form-grid">
    @csrf
    @if($routingRule->exists)
      @method('PUT')
    @endif
    <label for="routing-category">Category
      <select id="routing-category" name="category_id" required>
        <option value="">Choose a category</option>
        @foreach($categories as $category)
          <option value="{{ $category->id }}"
            @selected((string) old('category_id', $routingRule->category_id) === (string) $category->id)>
            {{ $category->name }}
          </option>
        @endforeach
      </select>
      @error('category_id') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="routing-office">Responsible office
      <select id="routing-office" name="office_id" required>
        <option value="">Choose an office</option>
        @foreach($offices as $office)
          <option value="{{ $office->id }}"
            @selected((string) old('office_id', $routingRule->office_id) === (string) $office->id)>
            {{ $office->name }}
          </option>
        @endforeach
      </select>
      @error('office_id') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="routing-note">Jurisdiction note
      <textarea id="routing-note" name="jurisdiction_note" rows="4" maxlength="2000"
        aria-describedby="routing-note-hint">{{ old('jurisdiction_note', $routingRule->jurisdiction_note) }}</textarea>
      @error('jurisdiction_note') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <p id="routing-note-hint" class="field-hint">Explain which office has authority or why the report should be referred.</p>
    <label class="contact-active-control">
      <input type="hidden" name="within_authority" value="0">
      <input type="checkbox" name="within_authority" value="1"
        @checked((bool) old('within_authority', $routingRule->exists ? $routingRule->within_authority : true))>
      Within barangay authority
    </label>
    @error('within_authority') <span class="field-error">{{ $message }}</span> @enderror
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">
        {{ $routingRule->exists ? 'Save routing rule' : 'Create routing rule' }}
      </button>
      <a class="btn btn-outline" href="{{ route('admin.settings.routing-rules.index') }}">Cancel</a>
    </div>
  </form>
</section>
@endsection
