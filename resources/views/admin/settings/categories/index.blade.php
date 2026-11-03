@extends('layouts.admin')

@section('title', 'Categories')
@section('page-heading', 'Categories')

@section('content')
<div class="page-heading">
  <h2>Report categories</h2>
  <p>Manage category risk values and default offices. Deactivate categories to remove them from new resident reports without changing existing records.</p>
  <a class="btn btn-gold btn-small" href="{{ route('admin.settings.categories.create') }}">
    <i class="fa-solid fa-plus" aria-hidden="true"></i> Add category
  </a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">Category</th>
          <th scope="col">Risk weight</th>
          <th scope="col">Default office</th>
          <th scope="col">Availability</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($categories as $category)
          <tr>
            <td data-label="Category"><strong>{{ $category->name }}</strong><br><small>{{ $category->slug }}</small></td>
            <td data-label="Risk weight">{{ $category->risk_weight }}</td>
            <td data-label="Default office">{{ $category->defaultOffice?->name ?? 'Not set' }}</td>
            <td data-label="Availability">{{ $category->is_active ? 'Active' : 'Inactive' }}</td>
            <td data-label="Action">
              <a class="btn btn-outline btn-small" href="{{ route('admin.settings.categories.edit', $category) }}">Edit</a>
            </td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="5" data-label="">No categories have been created.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</section>
@endsection
