@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <p class="text-sm font-medium text-slate-500">Case {{ $case->case_number }}</p>
            <h1 class="text-2xl font-bold text-slate-900">Escalate Case & Issue CFA</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $case->title }}</p>
        </div>
        <x-ui.button href="{{ route('admin.cases.show', ['case' => $case, 'tab' => 'escalation']) }}" variant="secondary">
            Back to Case
        </x-ui.button>
    </div>

    @if($hasSoftWarning)
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 shadow-sm" role="alert">
            <div class="flex items-start gap-3">
                <i class="fa-solid fa-triangle-exclamation text-amber-600 text-lg mt-0.5"></i>
                <div class="space-y-1">
                    <h3 class="text-sm font-bold text-amber-900">Advisory: Conciliation Period Not Completed</h3>
                    <p class="text-xs text-amber-800 leading-relaxed">
                        {{ $warningMessage }}
                    </p>
                    <p class="text-xs font-semibold text-amber-900 pt-1">
                        Officials decide whether to proceed with issuing the Certificate to File Action.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.cases.escalations.store', $case) }}" class="space-y-6">
        @csrf

        <section class="space-y-4 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-2">
                Escalation Petition & Authority Details
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.form-field label="Requesting Complainant" name="requested_by_case_party_id" required>
                    <select
                        name="requested_by_case_party_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">Select Complainant...</option>
                        @foreach($complainants as $cp)
                            <option value="{{ $cp->id }}" @selected(old('requested_by_case_party_id') == $cp->id)>
                                {{ $cp->party?->full_name ?? "Complainant #{$cp->id}" }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-field>

                <x-ui.form-field label="Date of Complainant's Request" name="requested_on" required>
                    <input
                        type="date"
                        name="requested_on"
                        value="{{ old('requested_on', date('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                </x-ui.form-field>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.form-field label="Escalation Destination Agency" name="escalation_destination_id" required>
                    <select
                        name="escalation_destination_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">Select Court or Agency...</option>
                        @foreach($destinations as $dest)
                            <option value="{{ $dest->id }}" @selected(old('escalation_destination_id') == $dest->id)>
                                {{ $dest->name }}
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-field>

                <x-ui.form-field label="Pangkat Chairman / Signatory" name="signed_by_personnel_id">
                    <select
                        name="signed_by_personnel_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                        <option value="">Default Signatory / Case Pangkat Chairman</option>
                        @foreach($personnel as $p)
                            <option value="{{ $p->id }}" @selected(old('signed_by_personnel_id', $case->pangkat_chairman_personnel_id) == $p->id)>
                                {{ $p->full_name }} ({{ $p->position?->name ?? 'Official' }})
                            </option>
                        @endforeach
                    </select>
                </x-ui.form-field>
            </div>

            <x-ui.form-field label="Grounds & Reason for Impasse" name="reason" required>
                <textarea
                    name="reason"
                    rows="3"
                    required
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    placeholder="Specify why amicable settlement was not achieved (e.g. repeated failure of respondent to appear, repudiation of terms, irreconcilable differences)..."
                >{{ old('reason') }}</textarea>
            </x-ui.form-field>

            @if($hasSoftWarning)
                <x-ui.form-field label="Early Escalation Official Justification / Note" name="override_note">
                    <textarea
                        name="override_note"
                        rows="2"
                        class="w-full rounded-lg border border-amber-300 bg-amber-50/50 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                        placeholder="State reason why escalation is permitted prior to 15 working days completion (e.g. respondent outrightly refused conciliation)..."
                    >{{ old('override_note') }}</textarea>
                </x-ui.form-field>
            @endif
        </section>

        <div class="flex items-center justify-end gap-3">
            <x-ui.button href="{{ route('admin.cases.show', ['case' => $case, 'tab' => 'escalation']) }}" variant="secondary">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" variant="danger">
                <i class="fa-solid fa-file-signature mr-1.5"></i> Confirm & Issue CFA
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
