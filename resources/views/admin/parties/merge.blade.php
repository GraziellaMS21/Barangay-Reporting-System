@extends('layouts.admin')

@section('title', 'Merge Duplicate Party: ' . $party->full_name)

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <div class="border-b border-slate-200 pb-4">
        <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('admin.parties.index') }}" class="hover:text-slate-800">Parties Directory</a></li>
                <li><span class="text-slate-400">/</span></li>
                <li><a href="{{ route('admin.parties.show', $party) }}" class="hover:text-slate-800">{{ $party->full_name }}</a></li>
                <li><span class="text-slate-400">/</span></li>
                <li class="font-semibold text-slate-800">Merge Duplicate</li>
            </ol>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900">Merge Duplicate Party Records</h1>
        <p class="text-sm text-slate-600 mt-1">Consolidate multiple person profiles into a single authoritative master record per SPEC ADDENDUM 10.6</p>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-sm text-red-800">
            <p class="font-semibold mb-1">Please correct the following errors:</p>
            <ul class="list-disc pl-5 text-xs space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Source Party Info -->
    <x-ui.card class="p-5 border border-slate-200 bg-slate-50">
        <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Source Record to be Merged:</h2>
        <div class="flex items-center justify-between text-sm">
            <div>
                <p class="font-bold text-slate-900 text-base">{{ $party->full_name }}</p>
                <p class="text-xs text-slate-500">
                    ID #{{ $party->id }} · {{ $party->purok?->name ?? 'No Purok' }} · Contact: {{ $party->contact_number ?: 'None' }} · Cases: {{ $party->caseParties->count() }}
                </p>
            </div>
            <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-200">
                Will be archived
            </span>
        </div>
    </x-ui.card>

    <form method="POST" action="{{ route('admin.parties.merge.store', $party) }}" class="space-y-6">
        @csrf
        <input type="hidden" name="source_party_id" value="{{ $party->id }}">

        <!-- Step 1: Select Master Record -->
        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                1. Select Master Record to Keep
            </h2>

            @if($potentialMatches->isNotEmpty())
                <div class="space-y-2">
                    <p class="text-xs text-slate-600 font-medium">System Detected Possible Duplicates:</p>
                    <div class="space-y-2">
                        @foreach($potentialMatches as $match)
                            <label class="flex items-center justify-between p-3 border rounded-lg cursor-pointer hover:bg-blue-50/50 transition-colors {{ old('keep_party_id') == $match->id ? 'border-blue-500 bg-blue-50' : 'border-slate-200' }}">
                                <div class="flex items-center gap-3">
                                    <input type="radio" name="keep_party_id" value="{{ $match->id }}" @checked(old('keep_party_id') == $match->id) required class="text-blue-600 focus:ring-blue-500">
                                    <div class="text-xs">
                                        <p class="font-bold text-slate-900">{{ $match->full_name }} (ID #{{ $match->id }})</p>
                                        <p class="text-slate-500">{{ $match->purok?->name ?? 'No Purok' }} · Phone: {{ $match->contact_number ?: 'N/A' }} · Cases: {{ $match->caseParties->count() }}</p>
                                    </div>
                                </div>
                                <span class="px-2 py-0.5 text-[10px] font-semibold rounded {{ $match->residency_status === 'resident' ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($match->residency_status) }}
                                </span>
                            </label>
                        @endforeach
                    </div>
                </div>
            @endif

            <div class="pt-2">
                <label for="manual_keep_id" class="block text-xs font-semibold text-slate-700 mb-1">
                    Or Enter Master Record Party ID Manually:
                </label>
                <input
                    type="number"
                    id="manual_keep_id"
                    placeholder="Enter Party ID number (e.g. 12)..."
                    class="w-full sm:w-1/2 text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500"
                    oninput="if(this.value) { document.querySelectorAll('input[name=keep_party_id]').forEach(r => r.checked = false); }"
                />
            </div>
        </x-ui.card>

        <!-- Step 2: Merge Reason & Confirmation -->
        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                2. Mandatory Justification & Audit Note
            </h2>

            <div>
                <label for="note" class="block text-xs font-semibold text-slate-700 mb-1">
                    Reason for Merging Records <span class="text-red-500">*</span>
                </label>
                <textarea
                    name="note"
                    id="note"
                    rows="3"
                    required
                    minlength="5"
                    maxlength="500"
                    placeholder="Explain why these records were determined to be the same individual (e.g. Typo in last name verified against Barangay ID)..."
                    class="w-full text-xs border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500"
                >{{ old('note') }}</textarea>
            </div>

            <div class="p-3 bg-amber-50 border border-amber-200 rounded-md">
                <label class="flex items-start gap-2.5 cursor-pointer text-xs">
                    <input type="checkbox" name="confirm_understanding" value="1" required class="mt-0.5 rounded border-amber-300 text-amber-600 focus:ring-amber-500">
                    <span class="text-amber-900 font-medium">
                        I understand this merge will repoint all case dockets, summonses, and hearings to the master record. This action cannot be reversed automatically.
                    </span>
                </label>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.parties.show', $party) }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-amber-600 rounded-lg hover:bg-amber-700 shadow-sm transition-colors">
                Merge Records
            </button>
        </div>
    </form>
</div>
@endsection
