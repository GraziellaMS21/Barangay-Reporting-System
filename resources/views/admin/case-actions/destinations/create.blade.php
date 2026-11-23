@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-2xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Add Escalation Destination</h1>
            <p class="mt-1 text-sm text-slate-600">Register an external court, prosecutor office, or police station.</p>
        </div>
        <x-ui.button href="{{ route('admin.escalation-destinations.index') }}" variant="secondary">
            Cancel
        </x-ui.button>
    </div>

    <form method="POST" action="{{ route('admin.escalation-destinations.store') }}" class="space-y-6">
        @csrf

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm space-y-4">
            <x-ui.form-field label="Destination Name" name="name" required>
                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    placeholder="e.g. Municipal Trial Court in Cities (MTCC) Branch 1"
                >
            </x-ui.form-field>

            <x-ui.form-field label="Linked Partner Office (Optional)" name="office_id">
                <select
                    name="office_id"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                >
                    <option value="">None / External</option>
                    @foreach($offices as $office)
                        <option value="{{ $office->id }}" @selected(old('office_id') == $office->id)>
                            {{ $office->name }}
                        </option>
                    @endforeach
                </select>
            </x-ui.form-field>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <x-ui.form-field label="Display Sort Order" name="sort_order">
                    <input
                        type="number"
                        name="sort_order"
                        value="{{ old('sort_order', 0) }}"
                        min="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900 focus:border-blue-500 focus:ring-1 focus:ring-blue-500"
                    >
                </x-ui.form-field>

                <div class="flex items-center pt-6">
                    <label class="inline-flex items-center gap-2 cursor-pointer text-sm font-medium text-slate-700">
                        <input
                            type="checkbox"
                            name="is_active"
                            value="1"
                            @checked(old('is_active', true))
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        >
                        Active destination
                    </label>
                </div>
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <x-ui.button href="{{ route('admin.escalation-destinations.index') }}" variant="secondary">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" variant="primary">
                Save Destination
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
