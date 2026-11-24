@extends('setup.layout')

@section('title', 'Step 5b: Staff Onboarding & Permission Presets')

@section('step-content')
<div>
    <div class="mb-6">
        <div class="flex items-center gap-2 mb-1">
            <span class="rounded-full bg-blue-100 px-2.5 py-0.5 text-xs font-semibold text-blue-800">Optional Step</span>
        </div>
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Step 5b: Staff Onboarding & Role Presets</h2>
        <p class="text-sm text-slate-500 mt-1">
            Optionally provision initial barangay staff accounts (Desk Officers, VAW Focal Persons, Lupon Clerks, or BCPC Kagawads) before locking setup.
        </p>
    </div>

    @if($staffMembers->isNotEmpty())
        <div class="mb-6 rounded-xl border border-slate-200 bg-slate-50/50 p-4">
            <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 mb-2">Currently Provisioned Staff ({{ $staffMembers->count() }})</h3>
            <div class="divide-y divide-slate-200 text-xs">
                @foreach($staffMembers as $sm)
                    <div class="py-2 flex items-center justify-between">
                        <div>
                            <span class="font-bold text-slate-800">{{ $sm->name }}</span>
                            <span class="text-slate-500 ml-1">({{ $sm->email }})</span>
                        </div>
                        <span class="rounded bg-slate-200 px-2 py-0.5 text-[10px] font-semibold text-slate-700">Staff Account</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mb-6 p-4 rounded-xl bg-blue-50 border border-blue-200 text-xs text-blue-900 leading-relaxed flex items-start gap-2.5">
        <i class="fa-solid fa-user-shield text-blue-600 mt-0.5 text-sm"></i>
        <div>
            <strong>Permission Presets:</strong> Choosing a preset automatically assigns appropriate permissions (such as confidential dispute case review for VAW Focal or BCPC Kagawad roles). Staff can also be invited and managed post-setup at <strong>Admin &gt; Users &amp; Permissions</strong>.
        </div>
    </div>

    <form method="POST" action="{{ route('setup.store', ['step' => '5b']) }}" class="space-y-4">
        @csrf

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.form-field label="Staff Full Name" name="name" required>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    placeholder="e.g. Maria Santos"
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
            </x-ui.form-field>

            <x-ui.form-field label="Staff Email Address" name="email" required>
                <input
                    type="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    placeholder="e.g. maria.santos@calarian.gov.ph"
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
            </x-ui.form-field>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <x-ui.form-field label="Temporary Password" name="password" required>
                <input
                    type="password"
                    name="password"
                    required
                    minlength="8"
                    placeholder="Minimum 8 characters"
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
            </x-ui.form-field>

            <x-ui.form-field label="Permission Preset" name="preset">
                <select
                    name="preset"
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">Select Permission Preset...</option>
                    @foreach($presets as $key => $preset)
                        <option value="{{ $key }}" @selected(old('preset') === $key)>
                            {{ $preset['label'] }}
                        </option>
                    @endforeach
                </select>
            </x-ui.form-field>
        </div>

        @if(!empty($positions) && $positions->isNotEmpty())
            <x-ui.form-field label="Barangay Position (Optional)" name="position_id">
                <select
                    name="position_id"
                    class="w-full text-xs rounded-lg border border-slate-300 p-2.5 bg-white focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">None / Unassigned Position</option>
                    @foreach($positions as $pos)
                        <option value="{{ $pos->id }}" @selected(old('position_id') == $pos->id)>
                            {{ $pos->name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.form-field>
        @endif

        <div class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-6 border-t border-slate-100">
            <a href="{{ route('setup.step', ['step' => 5]) }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1">
                <i class="fa-solid fa-arrow-left text-[10px]"></i>
                <span>Back to Review</span>
            </a>

            <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
                <button
                    type="submit"
                    name="skip"
                    value="1"
                    formnovalidate
                    class="px-3.5 py-2 text-xs font-semibold text-slate-600 hover:text-slate-900 border border-slate-300 rounded-lg bg-white shadow-sm hover:bg-slate-50"
                >
                    Skip &amp; Complete Setup
                </button>

                <button
                    type="submit"
                    name="add_another"
                    value="1"
                    class="px-3.5 py-2 text-xs font-semibold text-blue-700 hover:text-blue-800 border border-blue-200 rounded-lg bg-blue-50 shadow-sm hover:bg-blue-100"
                >
                    Save &amp; Add Another
                </button>

                <x-ui.button type="submit" variant="amber">
                    <span>Save &amp; Complete Setup</span>
                </x-ui.button>
            </div>
        </div>
    </form>
</div>
@endsection
