@extends('layouts.admin')

@section('title', 'Party Profile: ' . $party->full_name)

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Header & Action Buttons -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 border-b border-slate-200 pb-5">
        <div>
            <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="{{ route('admin.parties.index') }}" class="hover:text-slate-800">Parties Directory</a></li>
                    <li><span class="text-slate-400">/</span></li>
                    <li class="font-semibold text-slate-800">Profile</li>
                </ol>
            </nav>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">{{ $party->full_name }}</h1>
                @php
                    $resColor = match($party->residency_status) {
                        'resident' => 'green',
                        'non_resident' => 'blue',
                        default => 'amber',
                    };
                @endphp
                <x-ui.badge :color="$resColor" :dot="true">
                    {{ ucfirst(str_replace('_', ' ', $party->residency_status)) }}
                </x-ui.badge>
            </div>
            @if($party->alias)
                <p class="text-xs text-slate-500 mt-0.5">Known Alias: <strong class="text-slate-700">"{{ $party->alias }}"</strong></p>
            @endif
        </div>

        <!-- Action Cluster -->
        <div class="flex items-center flex-wrap gap-2">
            @can('cases.edit')
                <button type="button" onclick="openVerifyModal()" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-emerald-700 bg-emerald-50 border border-emerald-300 rounded-md hover:bg-emerald-100 shadow-sm transition-colors">
                    <i class="fa-solid fa-id-card-clip"></i>
                    <span>Verify Residency</span>
                </button>

                <a href="{{ route('admin.parties.merge.create', $party) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-amber-800 bg-amber-50 border border-amber-300 rounded-md hover:bg-amber-100 shadow-sm transition-colors">
                    <i class="fa-solid fa-code-merge"></i>
                    <span>Merge Duplicate</span>
                </a>

                <a href="{{ route('admin.parties.edit', $party) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-700 bg-white border border-slate-300 rounded-md hover:bg-slate-50 shadow-sm transition-colors">
                    <i class="fa-solid fa-pen-to-square"></i>
                    <span>Edit Profile</span>
                </a>
            @endcan

            <a href="{{ route('admin.parties.index') }}" class="px-3 py-1.5 text-xs font-medium text-slate-500 hover:text-slate-800">
                Back to Directory
            </a>
        </div>
    </div>

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-sm text-emerald-800 font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Profile Overview Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        <!-- Left 2 Cols: Tabs & History -->
        <div class="lg:col-span-2 space-y-6">

            <!-- Case History Table -->
            <x-ui.card class="p-6 border border-slate-200 space-y-4">
                <div class="border-b border-slate-100 pb-2">
                    <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider flex items-center gap-2">
                        <i class="fa-solid fa-scale-balanced text-blue-600"></i>
                        <span>Blotter Docket Case History ({{ $party->caseParties->count() }})</span>
                    </h2>
                    <p class="text-xs text-slate-500">Every dispute where this individual was named as complainant, respondent, or witness</p>
                </div>

                <div class="overflow-hidden">
                    <table class="w-full divide-y divide-slate-200 text-left text-xs table-fixed responsive-card-table">
                        <thead class="bg-slate-50 font-bold text-slate-500 uppercase">
                            <tr>
                                <th class="py-2.5 px-3 w-[15%]">Role</th>
                                <th class="py-2.5 px-3 w-[22%]">Case No.</th>
                                <th class="py-2.5 px-3 w-[25%]">Classification</th>
                                <th class="py-2.5 px-3 w-[18%]">Filing Date</th>
                                <th class="py-2.5 px-3 w-[20%]">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse($party->caseParties as $cp)
                                @php $c = $cp->case; @endphp
                                @if($c)
                                    <tr class="hover:bg-slate-50">
                                        <td data-label="Role" class="py-2.5 px-3 font-semibold capitalize text-slate-800">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold {{ $cp->role === 'complainant' ? 'bg-emerald-100 text-emerald-800' : ($cp->role === 'respondent' ? 'bg-amber-100 text-amber-800' : 'bg-slate-100 text-slate-700') }}">
                                                {{ $cp->role }}
                                            </span>
                                        </td>
                                        <td data-label="Case No." class="py-2.5 px-3 font-mono font-bold text-blue-600">
                                            <a href="{{ route('admin.cases.show', $c) }}" class="hover:underline">
                                                {{ $c->case_number }}
                                            </a>
                                        </td>
                                        <td data-label="Classification" class="py-2.5 px-3 text-slate-600 truncate">{{ $c->caseType?->name ?? 'Dispute' }}</td>
                                        <td data-label="Filing Date" class="py-2.5 px-3 text-slate-500">{{ $c->filed_on?->format('M d, Y') ?? 'N/A' }}</td>
                                        <td data-label="Status" class="py-2.5 px-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-700">
                                                {{ \App\Support\CaseStatus::label($c->status) }}
                                            </span>
                                        </td>
                                    </tr>
                                @endif
                            @empty
                                <tr>
                                    <td colspan="5" class="py-8 text-center text-slate-400 italic">
                                        No active cases associated with this person record.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-ui.card>

            @if($party->mergedParties->isNotEmpty())
                <x-ui.card class="p-6 border border-slate-200 space-y-3">
                    <h3 class="text-xs font-bold text-slate-700 uppercase tracking-wider">Merged Duplicate Records</h3>
                    <div class="divide-y divide-slate-100 text-xs">
                        @foreach($party->mergedParties as $merged)
                            <div class="py-2 flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-slate-800">{{ $merged->full_name }}</span>
                                    <span class="text-slate-400">· Merged on {{ $merged->merged_at?->format('M d, Y') }}</span>
                                </div>
                                <span class="text-slate-500 font-mono text-[11px]">ID #{{ $merged->id }}</span>
                            </div>
                        @endforeach
                    </div>
                </x-ui.card>
            @endif

        </div>

        <!-- Right 1 Col: Particulars & Verification -->
        <div class="space-y-6">

            <!-- Personal Metadata Card -->
            <x-ui.card class="p-5 border border-slate-200 space-y-3 text-xs">
                <h3 class="font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">Person Details</h3>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Contact Number</span>
                    <span class="font-medium text-slate-900">{{ $party->contact_number ?: 'None' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Email Address</span>
                    <span class="font-medium text-slate-900">{{ $party->email ?: 'None' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Address Line</span>
                    <span class="font-medium text-slate-900 text-right max-w-[180px]">{{ $party->address_line ?: 'Unspecified' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Purok Area</span>
                    <span class="font-medium text-slate-900">{{ $party->purok?->name ?? 'N/A' }}</span>
                </div>

                @if($party->home_barangay || $party->home_city)
                    <div class="flex justify-between py-1 border-b border-slate-50">
                        <span class="text-slate-500">Origin Location</span>
                        <span class="font-medium text-slate-900">{{ $party->home_barangay ? "{$party->home_barangay}, {$party->home_city}" : $party->home_city }}</span>
                    </div>
                @endif

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Occupation</span>
                    <span class="font-medium text-slate-900">{{ $party->occupation ?: 'Unspecified' }}</span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Birth Date / Age</span>
                    <span class="font-medium text-slate-900">
                        {{ $party->birth_date ? $party->birth_date->format('M d, Y') . " ({$party->birth_date->age} y/o)" : 'Unspecified' }}
                    </span>
                </div>

                <div class="flex justify-between py-1 border-b border-slate-50">
                    <span class="text-slate-500">Civil Status / Sex</span>
                    <span class="font-medium text-slate-900">{{ ucfirst($party->civil_status ?: 'N/A') }} / {{ ucfirst($party->sex ?: 'N/A') }}</span>
                </div>
            </x-ui.card>

            <!-- Verification Status Card -->
            <x-ui.card class="p-5 border border-slate-200 space-y-3 text-xs bg-slate-50/50">
                <h3 class="font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">Residency Verification</h3>

                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Verification Status</span>
                    <span class="font-bold text-slate-900">{{ ucfirst($party->residency_status) }}</span>
                </div>

                <div class="flex justify-between py-1">
                    <span class="text-slate-500">Verification Basis</span>
                    <span class="font-medium text-slate-900">{{ $party->residency_basis ?: 'None recorded' }}</span>
                </div>

                @if($party->residency_verified_at)
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">Verified By</span>
                        <span class="font-medium text-slate-900">{{ $party->verifiedBy?->name ?? 'Authorized Official' }}</span>
                    </div>
                    <div class="flex justify-between py-1">
                        <span class="text-slate-500">Verified At</span>
                        <span class="font-medium text-slate-900">{{ $party->residency_verified_at->format('M d, Y') }}</span>
                    </div>
                @endif
            </x-ui.card>

        </div>
    </div>
</div>

<!-- Modal: Verify Residency -->
<div id="verify-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full p-6 space-y-4 text-xs">
        <div class="flex items-center justify-between border-b border-slate-100 pb-3">
            <h3 class="text-base font-bold text-slate-900">Verify Barangay Residency</h3>
            <button type="button" onclick="closeVerifyModal()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <form method="POST" action="{{ route('admin.parties.verify-residency', $party) }}" class="space-y-4">
            @csrf
            <div>
                <label class="block font-semibold text-slate-700 mb-1">Residency Status <span class="text-red-500">*</span></label>
                <select name="residency_status" required class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500">
                    <option value="resident" @selected($party->residency_status === 'resident')>Resident (Calarian)</option>
                    <option value="non_resident" @selected($party->residency_status === 'non_resident')>Non-Resident (Other Barangay/City)</option>
                    <option value="unverified" @selected($party->residency_status === 'unverified')>Unverified</option>
                </select>
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Verification Document / Basis <span class="text-red-500">*</span></label>
                <input
                    type="text"
                    name="residency_basis"
                    value="{{ old('residency_basis', $party->residency_basis) }}"
                    required
                    placeholder="e.g. Barangay ID #1042, Household Roster, Purok Certification"
                    class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <div>
                <label class="block font-semibold text-slate-700 mb-1">Officer Notes (Optional)</label>
                <textarea
                    name="verification_notes"
                    rows="2"
                    placeholder="Additional context or verification remarks..."
                    class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500"
                ></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2 border-t border-slate-100">
                <button type="button" onclick="closeVerifyModal()" class="px-3 py-1.5 text-slate-600 font-medium">Cancel</button>
                <button type="submit" class="px-4 py-1.5 font-semibold text-white bg-blue-600 rounded hover:bg-blue-700">Confirm Verification</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openVerifyModal() { document.getElementById('verify-modal').classList.remove('hidden'); }
    function closeVerifyModal() { document.getElementById('verify-modal').classList.add('hidden'); }
</script>
@endsection
