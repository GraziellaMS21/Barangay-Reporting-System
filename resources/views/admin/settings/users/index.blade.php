@extends('layouts.admin')

@section('title', 'Staff Accounts')
@section('page-heading', 'Staff accounts')

@section('content')
<div class="page-heading">
  <h2>Staff and administrator accounts</h2>
  <p>Only active accounts can sign in. At least one active administrator must remain.</p>
  <a class="btn btn-gold btn-small" href="{{ route('admin.settings.users.create') }}">
    <i class="fa-solid fa-user-plus" aria-hidden="true"></i> Add account
  </a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <div class="table-wrap">
    <table class="data-table data-table-cards">
      <thead>
        <tr>
          <th scope="col">Name</th>
          <th scope="col">Email</th>
          <th scope="col">Access</th>
          <th scope="col">Status</th>
          <th scope="col">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users as $user)
          <tr>
            <td data-label="Name">{{ $user->name }}</td>
            <td data-label="Email">{{ $user->email }}</td>
            <td data-label="Access">{{ $user->isAdmin() ? 'Administrator' : 'Staff' }}</td>
            <td data-label="Status">{{ $user->is_active ? 'Active' : 'Inactive' }}</td>
            <td data-label="Action">
              <a class="btn btn-outline btn-small" href="{{ route('admin.settings.users.edit', $user) }}">Edit</a>
            </td>
          </tr>
        @empty
          <tr><td class="table-empty" colspan="5" data-label="">No staff accounts have been created.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  {{ $users->links() }}
</section>
@endsection
