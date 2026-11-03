@extends('layouts.admin')

@section('title', 'Emergency Alert Emails')
@section('page-heading', 'Emergency alert emails')

@section('content')
<div class="page-heading">
  <h2>Official alert recipients</h2>
  <p>Enter one valid email address per line. Changes are recorded in the audit log.</p>
  <a class="btn btn-outline btn-small" href="{{ route('admin.settings.index') }}">Back to administration</a>
</div>

<section class="card">
  <form method="POST" action="{{ route('admin.settings.emergency-alert-emails.update') }}" class="status-form">
    @csrf
    @method('PUT')
    <label for="emergency-alert-emails">Recipient email addresses
      <textarea id="emergency-alert-emails" name="email_addresses" rows="8" maxlength="5000"
        aria-describedby="emergency-alert-email-hint">{{ old('email_addresses', $emailAddresses) }}</textarea>
    </label>
    <p id="emergency-alert-email-hint" class="field-hint">Separate addresses with a new line or comma. Leave empty to clear the list.</p>
    @error('email_addresses') <p class="field-error">{{ $message }}</p> @enderror
    <button type="submit" class="btn btn-gold">
      <i class="fa-solid fa-floppy-disk" aria-hidden="true"></i> Save recipients
    </button>
  </form>
</section>
@endsection
