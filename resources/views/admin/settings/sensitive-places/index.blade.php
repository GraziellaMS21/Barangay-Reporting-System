@extends('layouts.admin')

@section('title', 'Sensitive Places')
@section('page-heading', 'Sensitive places')

@section('content')
<div class="page-heading">
  <h2>Priority-sensitive locations</h2>
  <p>Reports near these locations receive the configured sensitive-place priority weight.</p>
  <a class="btn btn-gold btn-small" href="{{ route('admin.settings.sensitive-places.create') }}">
    <i class="fa-solid fa-plus" aria-hidden="true"></i> Add location
  </a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">Place</th>
          <th scope="col">Type</th>
          <th scope="col">Coordinates</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($sensitivePlaces as $sensitivePlace)
          <tr>
            <td data-label="Place">{{ $sensitivePlace->name }}</td>
            <td data-label="Type">{{ $sensitivePlace->type }}</td>
            <td data-label="Coordinates">{{ $sensitivePlace->lat }}, {{ $sensitivePlace->lng }}</td>
            <td data-label="Action">
              <div class="contact-actions">
                <a class="btn btn-outline btn-small"
                  href="{{ route('admin.settings.sensitive-places.edit', $sensitivePlace) }}">Edit</a>
                <form method="POST"
                  action="{{ route('admin.settings.sensitive-places.destroy', $sensitivePlace) }}"
                  data-confirm="Delete {{ $sensitivePlace->name }}?">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-danger btn-small">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="4" data-label="">No sensitive places have been added.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
