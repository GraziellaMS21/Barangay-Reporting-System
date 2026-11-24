@php
    $checklist = $case->eligibility_checklist ?? [];
    $isAllChecked = !empty($checklist['same_barangay']) && !empty($checklist['kp_matter']) && !empty($checklist['non_confidential']) && !empty($checklist['residency_verified']);
@endphp

<x-ui.card class="p-5 border border-slate-200 bg-white shadow-sm space-y-4">
    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
        <div class="flex items-center gap-2">
            <span class="inline-flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-700">
                <i class="fa-solid fa-scale-balanced text-sm"></i>
            </span>
            <div>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-900">
                    Pre-Summons KP Eligibility Screening
                </h3>
                <p class="text-[11px] text-slate-500">
                    Statutory screening under RA 7160 (Katarungang Pambarangay)
                </p>
            </div>
        </div>

        @if($isAllChecked)
            <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 border border-emerald-200">
                <i class="fa-solid fa-circle-check text-emerald-600"></i> Eligible
            </span>
        @else
            <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-semibold text-amber-700 border border-amber-200">
                <i class="fa-solid fa-triangle-exclamation text-amber-600"></i> Pending Review
            </span>
        @endif
    </div>

    <!-- Statutory Checklist Form -->
    <form method="POST" action="{{ route('admin.cases.eligibility', $case) }}" class="space-y-2.5 text-xs">
        @csrf
        <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
            <input
                type="checkbox"
                name="same_barangay"
                value="1"
                @checked(!empty($checklist['same_barangay']))
                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            >
            <div class="space-y-0.5">
                <span class="font-medium text-slate-900">Territorial Jurisdiction</span>
                <p class="text-[11px] text-slate-500">Parties reside in Barangay Calarian (or adjacent barangays per RA 7160 Sec. 408).</p>
            </div>
        </label>

        <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
            <input
                type="checkbox"
                name="kp_matter"
                value="1"
                @checked(!empty($checklist['kp_matter']))
                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            >
            <div class="space-y-0.5">
                <span class="font-medium text-slate-900">KP Covered Subject Matter</span>
                <p class="text-[11px] text-slate-500">Civil dispute or petty offense punishable by &le; 1 year imprisonment or &le; 5,000 PHP fine.</p>
            </div>
        </label>

        <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
            <input
                type="checkbox"
                name="non_confidential"
                value="1"
                @checked(!empty($checklist['non_confidential']))
                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            >
            <div class="space-y-0.5">
                <span class="font-medium text-slate-900">Non-Confidential Matter</span>
                <p class="text-[11px] text-slate-500">Matter does NOT involve domestic violence (VAWC), child abuse, or statutory exclusions.</p>
            </div>
        </label>

        <label class="flex items-start gap-2.5 p-2 rounded-lg hover:bg-slate-50 cursor-pointer border border-transparent hover:border-slate-200">
            <input
                type="checkbox"
                name="residency_verified"
                value="1"
                @checked(!empty($checklist['residency_verified']))
                class="mt-0.5 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500"
            >
            <div class="space-y-0.5">
                <span class="font-medium text-slate-900">Parties' Residency Verified</span>
                <p class="text-[11px] text-slate-500">Parties' identities and barangay residence checked against registry records.</p>
            </div>
        </label>

        <div class="flex justify-end pt-2 border-t border-slate-100">
            <button
                type="submit"
                class="rounded-lg bg-slate-900 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-slate-800"
            >
                Save Screening Checklist
            </button>
        </div>
    </form>

    <!-- Statutory 15-Day Mediation Clock / First Meeting Section -->
    <div class="mt-4 pt-4 border-t border-slate-100 space-y-2">
        <h4 class="text-[11px] font-bold uppercase tracking-wider text-slate-700">
            Mediation Clock & First Appearance
        </h4>

        @if($case->first_meeting_on)
            <div class="flex items-center justify-between p-2.5 rounded-lg bg-blue-50 border border-blue-200 text-xs">
                <div>
                    <span class="text-blue-900 font-semibold">First Meeting Date:</span>
                    <span class="text-blue-800 font-medium ml-1">{{ $case->first_meeting_on->format('M d, Y') }}</span>
                    <p class="text-[11px] text-blue-700 mt-0.5">Statutory 15-working-day mediation clock initiated from this date.</p>
                </div>
            </div>
        @else
            <form method="POST" action="{{ route('admin.cases.first-meeting', $case) }}" class="space-y-2 text-xs">
                @csrf
                <p class="text-[11px] text-slate-500">
                    Record the date of the first personal confrontation before the Punong Barangay to begin the statutory 15-working-day mediation period:
                </p>
                <div class="flex gap-2">
                    <input
                        type="date"
                        name="first_meeting_on"
                        value="{{ old('first_meeting_on', today()->toDateString()) }}"
                        required
                        class="rounded border border-slate-300 p-1.5 text-xs bg-white text-slate-900 focus:ring-1 focus:ring-blue-500"
                    >
                    <button
                        type="submit"
                        class="rounded-lg bg-blue-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-blue-700"
                    >
                        Start 15-Day Clock
                    </button>
                </div>
            </form>
        @endif
    </div>
</x-ui.card>
