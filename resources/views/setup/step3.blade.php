@extends('setup.layout')

@section('title', 'Step 3: Barangay Profile')

@section('step-content')
<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Step 3: Barangay Profile & Jurisdiction</h2>
        <p class="text-sm text-slate-500 mt-1">Official municipal details appearing on summonses, transmittals, and referral letters.</p>
    </div>

    <form method="POST" action="{{ route('setup.store', ['step' => 3]) }}" class="space-y-5">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.form-field label="Barangay Name" name="barangay_name" required>
                <input type="text" name="barangay_name" id="barangay_name" required
                    value="{{ old('barangay_name', $profile['name']) }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>

            <x-ui.form-field label="City / Municipality" name="city" required>
                <input type="text" name="city" id="city" required
                    value="{{ old('city', $profile['city']) }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.form-field label="Province" name="province">
                <input type="text" name="province" id="province"
                    value="{{ old('province', $profile['province']) }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>

            <x-ui.form-field label="Region" name="region">
                <input type="text" name="region" id="region"
                    value="{{ old('region', $profile['region']) }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>
        </div>

        <x-ui.form-field label="Barangay Hall Physical Address" name="hall_address" required hint="Used on summons venues and return notices.">
            <input type="text" name="hall_address" id="hall_address" required
                value="{{ old('hall_address', $profile['hall_address']) }}"
                class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
        </x-ui.form-field>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.form-field label="Official Contact Number" name="contact_number" required>
                <input type="text" name="contact_number" id="contact_number" required
                    value="{{ old('contact_number', $profile['contact_number']) }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>

            <x-ui.form-field label="Official Email Address" name="email" required>
                <input type="email" name="email" id="email" required
                    value="{{ old('email', $profile['email']) }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>
        </div>

        <div class="pt-4 border-t border-slate-100">
            <h3 class="text-sm font-bold text-slate-800 mb-1">Local Emergency Hotlines (Optional)</h3>
            <p class="text-xs text-slate-500 mb-3">Displayed in the resident portal's Emergency / 911 directory. Leave blank to configure later.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.form-field label="Local Police Station Hotline" name="police_hotline">
                    <input type="text" name="police_hotline" id="police_hotline"
                        value="{{ old('police_hotline', $profile['police_hotline'] ?? '') }}"
                        placeholder="e.g. 0919-777-7377"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </x-ui.form-field>

                <x-ui.form-field label="Local Fire Station Hotline" name="fire_hotline">
                    <input type="text" name="fire_hotline" id="fire_hotline"
                        value="{{ old('fire_hotline', $profile['fire_hotline'] ?? '') }}"
                        placeholder="e.g. 0917-555-0199"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </x-ui.form-field>
            </div>
        </div>

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
            <a href="{{ route('setup.step', ['step' => 2]) }}" class="text-sm font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
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
