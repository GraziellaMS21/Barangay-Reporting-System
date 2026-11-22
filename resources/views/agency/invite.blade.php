@extends('layouts.app')

@section('title', 'Accept Agency Invitation')

@section('content')
<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-xl mx-auto">
  <div class="mb-8 text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 text-white shadow-lg mb-4">
      <i class="fa-solid fa-user-plus text-2xl"></i>
    </div>
    <h1 class="text-2xl font-bold tracking-tight" style="color: #ffffff;">Join {{ $invitation->office->name }}</h1>
    <p class="text-sm mt-2" style="color: #dbe5ff;">
      You have been invited by <strong style="color: #ffffff;">{{ $invitation->inviter?->name }}</strong> to join the agency portal team for <strong style="color: #ffffff;">{{ $invitation->office->name }}</strong>.
    </p>
  </div>

  <x-ui.card>
    <form method="POST" action="{{ route('agency.invite.accept', $token) }}" class="space-y-4">
      @csrf

      <x-ui.form-field name="email" label="Invited Email Address">
        <input type="email" value="{{ $invitation->email }}" class="w-full rounded-lg border-slate-300 bg-slate-50 text-slate-600 text-sm shadow-sm" readonly>
      </x-ui.form-field>

      <x-ui.form-field name="name" label="Your Full Name" :required="true">
        <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="e.g. Officer Maria Santos" required>
      </x-ui.form-field>

      <x-ui.form-field name="position_title" label="Position / Designation" :required="true">
        <input type="text" name="position_title" value="{{ old('position_title') }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="e.g. Liaison Officer" required>
      </x-ui.form-field>

      <x-ui.form-field name="contact_number" label="Contact Number" :required="true">
        <input type="text" name="contact_number" value="{{ old('contact_number') }}" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" placeholder="09123456789" required>
      </x-ui.form-field>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-ui.form-field name="password" label="Password" :required="true">
          <input type="password" name="password" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" minlength="10" required>
        </x-ui.form-field>

        <x-ui.form-field name="password_confirmation" label="Confirm Password" :required="true">
          <input type="password" name="password_confirmation" class="w-full rounded-lg border-slate-300 text-sm shadow-sm" minlength="10" required>
        </x-ui.form-field>
      </div>

      <div class="pt-4 border-t border-slate-200">
        <x-ui.button type="submit" variant="primary" class="w-full justify-center">
          <i class="fa-solid fa-check mr-1.5"></i> Complete Account Setup
        </x-ui.button>
      </div>
    </form>
  </x-ui.card>
</div>
@endsection
