@extends('layouts.admin')

@section('title', 'Official Calendar Holidays')
@section('page-heading', 'Official Calendar Holidays')

@section('content')
<div class="max-w-4xl space-y-6">
    <x-ui.page-header
        title="Official Calendar Holidays"
        subtitle="Manage official non-working days excluded from statutory KP mediation deadlines (15 working days) and referral follow-up SLAs.">
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" onclick="document.getElementById('modal-add-holiday').showModal()">
                <i class="fa-solid fa-plus text-xs mr-1.5"></i>
                <span>Add Holiday</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

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

    <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600 leading-relaxed flex items-start gap-2.5">
        <i class="fa-solid fa-calendar-days text-slate-400 mt-0.5 text-sm"></i>
        <div>
            <strong>Statutory Working Days Calculator:</strong> Days selected below are treated as official working days; unselected weekdays and dates in the holiday table below are excluded in <code class="font-bold text-slate-800">WorkingDays::addWorkingDays()</code> and <code class="font-bold text-slate-800">WorkingDays::countWorkingDays()</code>.
        </div>
    </div>

    <x-ui.card>
        <div class="p-5">
            <h3 class="text-sm font-bold text-slate-900 mb-1">Weekly Working Days</h3>
            <p class="text-xs text-slate-500 mb-4">Choose which days of the week the Barangay Hall / Lupon operates (default: Monday to Friday).</p>
            <form method="POST" action="{{ route('admin.calendar.working-days.update') }}" class="space-y-4">
                @csrf
                @php
                    $dayLabels = [
                        1 => 'Monday',
                        2 => 'Tuesday',
                        3 => 'Wednesday',
                        4 => 'Thursday',
                        5 => 'Friday',
                        6 => 'Saturday',
                        7 => 'Sunday',
                    ];
                @endphp
                <div class="flex flex-wrap gap-3">
                    @foreach($dayLabels as $isoDay => $dayName)
                        <label class="inline-flex items-center gap-2 px-3 py-2 rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-700 cursor-pointer hover:bg-slate-50">
                            <input type="checkbox" name="working_days[]" value="{{ $isoDay }}"
                                @checked(in_array($isoDay, $workingDays ?? [1,2,3,4,5], true))
                                class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                            <span>{{ $dayName }}</span>
                        </label>
                    @endforeach
                </div>
                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="secondary" size="sm">
                        <i class="fa-solid fa-save text-xs mr-1.5"></i>
                        <span>Save Working Days</span>
                    </x-ui.button>
                </div>
            </form>
        </div>
    </x-ui.card>

    <x-ui.card>
        <div class="overflow-hidden">
            <table class="w-full text-left border-collapse text-sm table-fixed responsive-card-table">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider bg-slate-50/50">
                        <th class="py-3 px-4 w-[25%]">Date</th>
                        <th class="py-3 px-4 w-[20%]">Day of Week</th>
                        <th class="py-3 px-4 w-[40%]">Holiday Description</th>
                        <th class="py-3 px-4 w-[15%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($holidays as $h)
                        @php
                            $carbonDate = \Illuminate\Support\Carbon::parse($h['date']);
                        @endphp
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td data-label="Date" class="py-3.5 px-4 font-mono font-semibold text-slate-900 text-xs">
                                {{ $h['date'] }}
                            </td>
                            <td data-label="Day of Week" class="py-3.5 px-4 text-xs text-slate-600">
                                {{ $carbonDate->format('l') }}
                            </td>
                            <td data-label="Holiday Description" class="py-3.5 px-4 text-sm font-medium text-slate-800">
                                {{ $h['label'] }}
                            </td>
                            <td data-label="Actions" class="py-3.5 px-4 text-right">
                                <form method="POST" action="{{ route('admin.calendar.destroy', ['date' => $h['date']]) }}" onsubmit="return confirm('Remove this holiday?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline">
                                        Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400 text-sm">
                                No official holidays added. Only standard weekends (Saturday & Sunday) are currently excluded.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>

<!-- Add Modal -->
<dialog id="modal-add-holiday" class="rounded-xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/40">
    <div class="p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Add Official Holiday</h3>
            <button type="button" onclick="document.getElementById('modal-add-holiday').close()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.calendar.store') }}" class="mt-4 space-y-4">
            @csrf
            <x-ui.form-field label="Holiday Date (YYYY-MM-DD)" name="date" required hint="e.g. 2026-06-12">
                <input type="date" name="date" id="date" required
                    value="{{ old('date', now()->toDateString()) }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono" />
            </x-ui.form-field>

            <x-ui.form-field label="Holiday Name / Description" name="label" required hint="e.g. Independence Day, Eid al-Fitr, Zamboanga Hermosa Day">
                <input type="text" name="label" id="label" required
                    value="{{ old('label') }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <x-ui.button type="button" variant="ghost" onclick="document.getElementById('modal-add-holiday').close()">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">Add Holiday</x-ui.button>
            </div>
        </form>
    </div>
</dialog>
@endsection
