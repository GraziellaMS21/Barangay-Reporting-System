@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-slate-500">Case {{ $case->case_number }}</p>
                <h1 class="text-2xl font-bold text-slate-900">Issue Summons</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $case->title }}</p>
            </div>
            <x-ui.button href="{{ route('admin.cases.show', $case) }}" variant="secondary">
                Back to Case
            </x-ui.button>
        </div>

        <x-admin.case-actions.summons-counter :case="$case" />

        @if($errors->has('case'))
            <div class="rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                {{ $errors->first('case') }}
            </div>
        @endif

        @if(! $canIssueSummons)
            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700" role="status">
                Summons may only be issued for active formal complaints.
            </div>
        @elseif($respondents->isEmpty())
            <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900" role="status">
                Add a respondent to this case before issuing a summons.
            </div>
        @else
            <form method="POST" action="{{ route('admin.cases.summons.store', $case) }}" class="space-y-6">
                @csrf

                <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900">Summons details</h2>
                        <p class="mt-1 text-sm text-slate-600">The next summons number is assigned when you issue this notice.</p>
                    </div>

                    <x-ui.form-field label="Respondent" name="respondent_case_party_id" required>
                        <select
                            id="respondent_case_party_id"
                            name="respondent_case_party_id"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                            <option value="">Select a respondent</option>
                            @foreach($respondents as $respondent)
                                <option value="{{ $respondent->id }}" @selected(old('respondent_case_party_id') == $respondent->id)>
                                    {{ $respondent->party?->full_name ?? "Case party #{$respondent->id}" }}
                                </option>
                            @endforeach
                        </select>
                    </x-ui.form-field>

                    <x-ui.form-field label="Hearing date and time" name="hearing_at" required>
                        <input
                            id="hearing_at"
                            name="hearing_at"
                            type="datetime-local"
                            value="{{ old('hearing_at', now()->addWeek()->format('Y-m-d\TH:i')) }}"
                            min="{{ now()->format('Y-m-d\TH:i') }}"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                    </x-ui.form-field>

                    <x-ui.form-field label="Hearing venue" name="venue" required>
                        <input
                            id="venue"
                            name="venue"
                            type="text"
                            value="{{ old('venue', setting('barangay.hall_address')) }}"
                            maxlength="255"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-200"
                        >
                    </x-ui.form-field>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <x-ui.button href="{{ route('admin.cases.show', $case) }}" variant="ghost">
                        Cancel
                    </x-ui.button>
                    <x-ui.button type="submit" variant="primary">
                        Issue Summons
                    </x-ui.button>
                </div>
            </form>
        @endif
    </div>
@endsection
