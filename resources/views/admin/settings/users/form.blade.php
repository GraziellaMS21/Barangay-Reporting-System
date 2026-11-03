@extends('layouts.admin')

@php($isSelf = $isSelf ?? false)
@section('title', $managedUser->exists ? 'Edit Staff Account' : 'Add Staff Account')
@section('page-heading', $managedUser->exists ? 'Edit staff account' : 'Add staff account')

@section('content')
<div class="page-heading">
  <h2>{{ $managedUser->exists ? 'Update account access' : 'Create a staff account' }}</h2>
  <p>Inactive accounts cannot sign in. The last active administrator cannot be deactivated or demoted.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.settings.users.index') }}">Back to staff accounts</a>
</div>

@include('admin.settings.partials.validation-errors')

<section class="card">
  <form method="POST"
    action="{{ $managedUser->exists ? route('admin.settings.users.update', $managedUser) : route('admin.settings.users.store') }}"
    class="contact-form-grid">
    @csrf
    @if($managedUser->exists)
      @method('PUT')
    @endif
    <label for="user-name">Name
      <input id="user-name" name="name" type="text" maxlength="255" required
        value="{{ old('name', $managedUser->name) }}">
      @error('name') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="user-email">Email
      <input id="user-email" name="email" type="email" maxlength="255" required
        value="{{ old('email', $managedUser->email) }}">
      @error('email') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="user-role">Role
      @if($isSelf)
        <input type="hidden" name="role" value="{{ $managedUser->role }}">
        <select id="user-role" disabled>
          <option value="{{ $managedUser->role }}">{{ $managedUser->isAdmin() ? 'Administrator' : 'Staff' }}</option>
        </select>
        <span class="field-hint">You cannot change your own role.</span>
      @else
        <select id="user-role" name="role" required>
          <option value="staff" @selected(old('role', $managedUser->role ?? 'staff') === 'staff')>Staff</option>
          <option value="admin" @selected(old('role', $managedUser->role) === 'admin')>Administrator</option>
        </select>
      @endif
      @error('role') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    @if($managedUser->exists)
      @if($isSelf)
        <input type="hidden" name="is_active" value="1">
        <p class="field-hint">Your account remains active while you edit your profile.</p>
      @else
        <label class="contact-active-control">
          <input type="hidden" name="is_active" value="0">
          <input type="checkbox" name="is_active" value="1"
            @checked((bool) old('is_active', $managedUser->is_active))>
          Account is active
        </label>
      @endif
      @error('is_active') <span class="field-error">{{ $message }}</span> @enderror
    @endif
    <label for="user-password">Password
      <input id="user-password" name="password" type="password" autocomplete="new-password"
        @required(!$managedUser->exists)>
      @if($managedUser->exists)
        <span class="field-hint">Leave blank to keep the current password.</span>
      @endif
      @error('password') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <label for="user-password-confirmation">Confirm password
      <input id="user-password-confirmation" name="password_confirmation" type="password"
        autocomplete="new-password" @required(!$managedUser->exists)>
      @error('password_confirmation') <span class="field-error">{{ $message }}</span> @enderror
    </label>
    <div class="contact-actions">
      <button type="submit" class="btn btn-gold">
        {{ $managedUser->exists ? 'Save account' : 'Create account' }}
      </button>
      <a class="btn btn-outline" href="{{ route('admin.settings.users.index') }}">Cancel</a>
    </div>
  </form>
</section>
@endsection
