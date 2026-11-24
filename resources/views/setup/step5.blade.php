@extends('setup.layout')

@section('title', 'Step 5: Personnel & Review')

@section('step-content')
<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Step 5: Review & Finalize Setup</h2>
        <p class="text-sm text-slate-500 mt-1">Verify your initial configuration before completing installation and initializing the system.</p>
    </div>

    <div class="space-y-4 mb-8">
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Administrator</span>
                <p class="text-sm font-bold text-slate-900 mt-1">{{ $admin?->name ?? 'Configured' }}</p>
                <p class="text-xs text-slate-500 truncate">{{ $admin?->email ?? 'admin@calarian.gov.ph' }}</p>
            </div>

            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Barangay</span>
                <p class="text-sm font-bold text-slate-900 mt-1">{{ setting('barangay.name', 'Barangay Calarian') }}</p>
                <p class="text-xs text-slate-500 truncate">{{ setting('barangay.city', 'Zamboanga City') }}</p>
            </div>

            <div class="p-4 rounded-xl border border-slate-200 bg-slate-50/50">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Active Fiscal Year</span>
                <p class="text-sm font-bold text-slate-900 mt-1">{{ $activeYear?->label ?? 'FY ' . now()->year }}</p>
                <p class="text-xs text-emerald-600 font-semibold flex items-center gap-1">
                    <i class="fa-solid fa-circle text-[8px]"></i> Active
                </p>
            </div>
        </div>

        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-xs text-amber-900 leading-relaxed flex items-start gap-2.5">
            <i class="fa-solid fa-circle-info text-amber-600 mt-0.5 text-sm"></i>
            <div>
                <strong>Initial Setup Finalization:</strong> Completing setup will seed baseline positions (Punong Barangay, Secretary, Lupon, Tanod), initialize sequential document counters, and lock the setup wizard. You will be redirected to the login portal.
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('setup.finish') }}" class="flex flex-col sm:flex-row items-center justify-between gap-3 pt-6 border-t border-slate-100">
        @csrf
        <a href="{{ route('setup.step', ['step' => 4]) }}" class="text-sm font-semibold text-slate-500 hover:text-slate-800 flex items-center gap-1.5">
            <i class="fa-solid fa-arrow-left text-xs"></i>
            <span>Back</span>
        </a>
        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <a href="{{ route('setup.step', ['step' => '5b']) }}" class="px-4 py-2 text-xs font-semibold text-blue-700 hover:text-blue-800 border border-blue-200 rounded-lg bg-blue-50 shadow-sm hover:bg-blue-100 flex items-center gap-1.5">
                <i class="fa-solid fa-user-plus text-xs"></i>
                <span>Add Staff Accounts (Optional)</span>
            </a>
            <x-ui.button type="submit" variant="amber">
                <i class="fa-solid fa-check text-xs mr-1.5"></i>
                <span>Complete Setup</span>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
