@extends('layouts.admin')

@section('title', 'General Settings')
@section('page-heading', 'General Settings')

@section('content')
<div class="max-w-4xl space-y-6">
    <x-ui.page-header
        title="General Settings"
        subtitle="Configure official barangay identity, territorial jurisdiction, hall address, and platform defaults." />

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.settings.general.update') }}" class="space-y-6">
        @csrf

        <!-- Barangay Identity -->
        <x-ui.card title="Barangay Identity & LGU Scope">
            <div class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.form-field label="Barangay Name" name="barangay_name" required>
                        <input type="text" name="barangay_name" id="barangay_name" required
                            value="{{ old('barangay_name', $settings['barangay_name']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-semibold" />
                    </x-ui.form-field>

                    <x-ui.form-field label="City / Municipality" name="city" required>
                        <input type="text" name="city" id="city" required
                            value="{{ old('city', $settings['city']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.form-field label="Province" name="province">
                        <input type="text" name="province" id="province"
                            value="{{ old('province', $settings['province']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>

                    <x-ui.form-field label="Administrative Region" name="region">
                        <input type="text" name="region" id="region"
                            value="{{ old('region', $settings['region']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>
                </div>
            </div>
        </x-ui.card>

        <!-- Location & Contact -->
        <x-ui.card title="Barangay Hall Physical Location & Official Contact">
            <div class="space-y-4">
                <x-ui.form-field label="Physical Hall Address" name="hall_address" required hint="Appears as venue on summons and hearing notices.">
                    <input type="text" name="hall_address" id="hall_address" required
                        value="{{ old('hall_address', $settings['hall_address']) }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                </x-ui.form-field>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.form-field label="Official Contact Number" name="contact_number" required>
                        <input type="text" name="contact_number" id="contact_number" required
                            value="{{ old('contact_number', $settings['contact_number']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>

                    <x-ui.form-field label="Official Email Address" name="email" required>
                        <input type="email" name="email" id="email" required
                            value="{{ old('email', $settings['email']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>
                </div>
            </div>
        </x-ui.card>

        <!-- Module Switches & Printing Preferences -->
        <x-ui.card title="Operational Modules & Printing Defaults">
            <div class="space-y-4">
                <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-lg">
                    <input type="checkbox" name="cases_enabled" id="cases_enabled" value="1"
                        @checked(old('cases_enabled', $settings['cases_enabled']))
                        class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    <label for="cases_enabled" class="text-sm">
                        <span class="font-semibold text-slate-900 block">Enable Katarungang Pambarangay & Blotter Case Management</span>
                        <span class="text-xs text-slate-500">Master switch activating complaint docketing, summonses, conciliation schedules, and CFAs.</span>
                    </label>
                </div>

                <div class="flex items-start gap-3 p-3 bg-slate-50 rounded-lg">
                    <input type="checkbox" name="agencies_registration_open" id="agencies_registration_open" value="1"
                        @checked(old('agencies_registration_open', $settings['agencies_registration_open']))
                        class="mt-1 rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    <label for="agencies_registration_open" class="text-sm">
                        <span class="font-semibold text-slate-900 block">Allow External Partner Agency Self-Registration</span>
                        <span class="text-xs text-slate-500">Permits verified partner agencies (PNP, CSWDO, MTCC) to register accounts for referral transmittals.</span>
                    </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 pt-2">
                    <x-ui.form-field label="Default Export / Output Format" name="default_format" required hint="Default format when generating official reports.">
                        <select name="default_format" id="default_format" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="print" @selected(old('default_format', $settings['default_format']) === 'print')>Print on Paper (Physical)</option>
                            <option value="pdf" @selected(old('default_format', $settings['default_format']) === 'pdf')>Portable Document Format (PDF)</option>
                            <option value="csv" @selected(old('default_format', $settings['default_format']) === 'csv')>Comma-Separated Values (CSV)</option>
                        </select>
                    </x-ui.form-field>

                    <x-ui.form-field label="Default Paper Size" name="paper_size" required hint="Paper standard for printable summonses and reports.">
                        <select name="paper_size" id="paper_size" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="letter" @selected(old('paper_size', $settings['paper_size']) === 'letter')>Letter (8.5" x 11")</option>
                            <option value="a4" @selected(old('paper_size', $settings['paper_size']) === 'a4')>A4 (210 x 297 mm)</option>
                            <option value="legal" @selected(old('paper_size', $settings['paper_size']) === 'legal')>Legal / Folio (8.5" x 13")</option>
                        </select>
                    </x-ui.form-field>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <x-ui.button type="submit" variant="primary">
                <i class="fa-solid fa-floppy-disk text-xs mr-1.5"></i>
                <span>Save General Settings</span>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
