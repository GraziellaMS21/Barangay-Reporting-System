@extends('layouts.admin')

@section('title', 'Routing Rules')
@section('page-heading', 'Routing rules')

@section('content')
<div class="page-heading">
  <h2>Category routing and jurisdiction</h2>
  <p>Each category can have one responsible office and a jurisdiction decision for staff to review.</p>
  <a class="btn btn-gold btn-small" href="{{ route('admin.settings.routing-rules.create') }}">
    <i class="fa-solid fa-plus" aria-hidden="true"></i> Add routing rule
  </a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">Category</th>
          <th scope="col">Office</th>
          <th scope="col">Within authority</th>
          <th scope="col">Jurisdiction note</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($routingRules as $routingRule)
          <tr>
            <td data-label="Category">{{ $routingRule->category?->name ?? 'Category unavailable' }}</td>
            <td data-label="Office">{{ $routingRule->office?->name ?? 'Office unavailable' }}</td>
            <td data-label="Within authority">{{ $routingRule->within_authority ? 'Yes' : 'No' }}</td>
            <td data-label="Jurisdiction note">{{ $routingRule->jurisdiction_note ?: 'No note' }}</td>
            <td data-label="Action">
              <div class="contact-actions">
                <a class="btn btn-outline btn-small"
                  href="{{ route('admin.settings.routing-rules.edit', $routingRule) }}">Edit</a>
                <form method="POST" action="{{ route('admin.settings.routing-rules.destroy', $routingRule) }}"
                  data-confirm="Delete this routing rule? The category default office will be used instead.">
                  @csrf
                  @method('DELETE')
                  <button type="submit" class="btn btn-danger btn-small">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="5" data-label="">No routing rules have been created.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
