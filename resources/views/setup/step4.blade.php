@extends('setup.layout')

@section('title', 'Step 4: Fiscal Year')

@section('step-content')
<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Step 4: Active Fiscal Year</h2>
        <p class="text-sm text-slate-500 mt-1">Configure the operational calendar year for complaints, summonses, and reports scoping.</p>
    </div>

    <form method="POST" action="{{ route('setup.store', ['step' => 4]) }}" class="space-y-6">
        @csrf

        <div class="p-4 rounded-xl bg-blue-50/50 border border-blue-200">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-calendar-check text-blue-600 mt-1"></i>
                <div class="text-xs text-blue-900 leading-relaxed">
                    <p class="font-semibold text-sm mb-0.5">Annual Scoping Architecture</p>
                    All case dockets, referrals, and blotter number sequences reset every fiscal year. Closed years are archived and protected against retroactive modification.
                </div>
            </div>
        </div>

        <x-ui.form-field label="Initial Operational Fiscal Year" name="year" required hint="Typically the current calendar year.">
            <input type="number" name="year" id="year" required min="2020" max="2050"
                value="{{ old('year', $fiscalYear?->year ?? $currentYear) }}"
                class="w-full sm:w-48 rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-bold" />
        </x-ui.form-field>

        <div class="flex items-center justify-between pt-6 border-t border-slate-100">
            <a href="{{ route('setup.step', ['step' => 3]) }}" class="text-sm font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
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
