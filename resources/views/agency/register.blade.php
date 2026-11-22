@extends('layouts.app')

@section('title', 'Partner Agency Registration')

@section('content')
<div class="py-12 px-4 sm:px-6 lg:px-8 max-w-2xl mx-auto">
  <div class="mb-8 text-center">
    <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-600 text-white shadow-lg mb-4">
      <i class="fa-solid fa-building-shield text-2xl"></i>
    </div>
    <h1 class="text-2xl font-bold tracking-tight text-slate-900">Partner Agency Access Request</h1>
    <p class="text-sm text-slate-600 mt-2">
      Request an agency portal account to receive, acknowledge, and action official referrals from {{ setting('barangay.name', 'Barangay Calarian') }}.
    </p>
  </div>

  <x-ui.card>
    @if(session('error'))
      <div class="mb-6 p-4 rounded-lg bg-red-50 border border-red-200 text-sm text-red-700">
        {{ session('error') }}
      </div>
    @endif

    <form method="POST" action="{{ route('agency.register.store') }}" enctype="multipart/form-data" class="space-y-6">
      @csrf

      <div class="border-b border-slate-200 pb-4 mb-4">
        <h2 class="text-base font-semibold text-slate-900">Agency Information</h2>
        <p class="text-xs text-slate-500">Select your agency or specify your department/unit.</p>
      </div>

      <x-ui.form-field name="office_id" label="Designated Partner Office" :required="false">
        <select id="office_id" name="office_id" class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm">
          <option value="">Select recognized agency (or leave blank if other)</option>
          @foreach($offices as $office)
            <option value="{{ $office->id }}" @selected(old('office_id') == $office->id)>
              {{ $office->name }}
            </option>
          @endforeach
        </select>
      </x-ui.form-field>

      <x-ui.form-field name="agency_name_claimed" label="Official Agency / Department Name" :required="true">
        <input type="text" id="agency_name_claimed" name="agency_name_claimed" value="{{ old('agency_name_claimed') }}"
          class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
          placeholder="e.g. Philippine National Police - Station 8, CSWDO Zamboanga" required>
      </x-ui.form-field>

      <div class="border-b border-slate-200 pb-4 mb-4 pt-2">
        <h2 class="text-base font-semibold text-slate-900">Representative Details</h2>
        <p class="text-xs text-slate-500">Provide official contact information for the account holder.</p>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-ui.form-field name="name" label="Representative Full Name" :required="true">
          <input type="text" id="name" name="name" value="{{ old('name') }}"
            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            placeholder="e.g. Officer Juan Dela Cruz" required>
        </x-ui.form-field>

        <x-ui.form-field name="position_title" label="Position / Title" :required="true">
          <input type="text" id="position_title" name="position_title" value="{{ old('position_title') }}"
            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            placeholder="e.g. Desk Investigator / Social Worker" required>
        </x-ui.form-field>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-ui.form-field name="email" label="Official Email Address" :required="true">
          <input type="email" id="email" name="email" value="{{ old('email') }}"
            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            placeholder="official.email@agency.gov.ph" required>
        </x-ui.form-field>

        <x-ui.form-field name="contact_number" label="Official Contact / Hotline Number" :required="true">
          <input type="text" id="contact_number" name="contact_number" value="{{ old('contact_number') }}"
            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            placeholder="09123456789 or (062) 991-0000" required>
        </x-ui.form-field>
      </div>

      <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <x-ui.form-field name="password" label="Account Password" :required="true">
          <input type="password" id="password" name="password"
            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            minlength="10" required>
        </x-ui.form-field>

        <x-ui.form-field name="password_confirmation" label="Confirm Password" :required="true">
          <input type="password" id="password_confirmation" name="password_confirmation"
            class="w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 text-sm"
            minlength="10" required>
        </x-ui.form-field>
      </div>

      <div class="border-b border-slate-200 pb-4 mb-4 pt-2">
        <h2 class="text-base font-semibold text-slate-900">Verification Document</h2>
        <p class="text-xs text-slate-500">Upload official Agency ID or official endorsement letter (JPG, PNG, PDF up to 5MB).</p>
      </div>

      <x-ui.form-field name="id_document" label="Official Agency ID / Endorsement Document" :required="true">
        <input type="file" id="id_document" name="id_document" accept=".jpg,.jpeg,.png,.pdf"
          class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100"
          required>
      </x-ui.form-field>

      <div class="bg-slate-50 p-4 rounded-lg border border-slate-200 space-y-2">
        <label class="flex items-start gap-3 cursor-pointer">
          <input type="checkbox" name="consent" value="1" class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500" required>
          <span class="text-xs text-slate-600">
            I confirm that I am an authorized representative of the agency stated above. I consent to the verification of my agency credentials in accordance with the Data Privacy Act (RA 10173).
          </span>
        </label>
        @error('consent')
          <p class="text-xs text-red-600 font-semibold">{{ $message }}</p>
        @enderror
      </div>

      <div class="flex items-center justify-between pt-4 border-t border-slate-200">
        <a href="{{ route('login') }}" class="text-xs text-slate-500 hover:text-slate-900 font-medium">
          <i class="fa-solid fa-arrow-left mr-1"></i> Back to Login
        </a>
        <x-ui.button type="submit" variant="primary">
          <i class="fa-solid fa-paper-plane mr-1.5"></i> Submit Access Request
        </x-ui.button>
      </div>
    </form>
  </x-ui.card>
</div>
@endsection
