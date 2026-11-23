@extends('setup.layout')

@section('title', 'Step 2: Administrator Account')

@section('step-content')
<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Step 2: Administrator Account</h2>
        <p class="text-sm text-slate-500 mt-1">Configure the primary barangay administrator account with full system governance.</p>
    </div>

    <form method="POST" action="{{ route('setup.store', ['step' => 2]) }}" class="space-y-5">
        @csrf

        <x-ui.form-field label="Administrator Full Name" name="name" required hint="e.g. Hon. Eduardo V. Alvarez">
            <input type="text" name="name" id="name" required
                value="{{ old('name', $admin?->name ?? 'Hon. Eduardo V. Alvarez') }}"
                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
        </x-ui.form-field>

        <x-ui.form-field label="Official Email Address" name="email" required hint="Used to sign in to the administrative portal.">
            <input type="email" name="email" id="email" required
                value="{{ old('email', $admin?->email ?? 'admin@calarian.gov.ph') }}"
                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
        </x-ui.form-field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.form-field label="Password" name="password" required hint="Minimum 8 characters.">
                <input type="password" name="password" id="password" required
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>

            <x-ui.form-field label="Confirm Password" name="password_confirmation" required hint="Re-type password.">
                <input type="password" name="password_confirmation" id="password_confirmation" required
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
            <a href="{{ route('setup.step', ['step' => 1]) }}" class="text-sm font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
                <i class="fa-solid fa-arrow-left text-xs"></i>
                <span>Back</span>
            </a>
            <x-ui.button type="submit" variant="primary">
                <span>Save and Continue</span>
                <i class="fa-solid fa-arrow-right text-xs ml-1.5"></i>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
