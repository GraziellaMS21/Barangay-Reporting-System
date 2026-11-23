@extends('layouts.admin')

@section('title', 'Edit Case ' . $case->case_number)

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <div class="border-b border-slate-200 pb-4">
        <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('admin.cases.index') }}" class="hover:text-slate-800">Cases</a></li>
                <li><span class="text-slate-400">/</span></li>
                <li><a href="{{ route('admin.cases.show', $case) }}" class="hover:text-slate-800 font-mono">{{ $case->case_number }}</a></li>
                <li><span class="text-slate-400">/</span></li>
                <li class="font-semibold text-slate-800">Edit</li>
            </ol>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900">Edit Blotter Case Particulars</h1>
        <p class="text-sm text-slate-600 mt-1">Update case title, grievance facts, or confidentiality classification</p>
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

    <form method="POST" action="{{ route('admin.cases.update', $case) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <!-- Title -->
            <div>
                <label for="title" class="block text-xs font-semibold text-slate-700 mb-1">
                    Case Title / Caption <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="title"
                    id="title"
                    value="{{ old('title', $case->title) }}"
                    required
                    maxlength="150"
                    class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Case Type -->
                <div>
                    <label for="case_type_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Case Classification <span class="text-red-500">*</span>
                    </label>
                    <select name="case_type_id" id="case_type_id" required class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500">
                        @foreach($caseTypes as $type)
                            <option value="{{ $type->id }}" @selected(old('case_type_id', $case->case_type_id) == $type->id)>{{ $type->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Purok -->
                <div>
                    <label for="purok_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Incident Purok
                    </label>
                    <select name="purok_id" id="purok_id" class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">Select Purok (Optional)...</option>
                        @foreach($puroks as $pk)
                            <option value="{{ $pk->id }}" @selected(old('purok_id', $case->purok_id) == $pk->id)>{{ $pk->name }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <!-- Incident Date -->
                <div>
                    <label for="incident_date" class="block text-xs font-semibold text-slate-700 mb-1">
                        Incident Date
                    </label>
                    <input
                        type="date"
                        name="incident_date"
                        id="incident_date"
                        value="{{ old('incident_date', $case->incident_date?->format('Y-m-d')) }}"
                        max="{{ now()->toDateString() }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <!-- Incident Location -->
                <div>
                    <label for="incident_location" class="block text-xs font-semibold text-slate-700 mb-1">
                        Specific Location / Landmark
                    </label>
                    <input
                        type="text"
                        name="incident_location"
                        id="incident_location"
                        value="{{ old('incident_location', $case->incident_location) }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>

            <!-- Narrative -->
            <div>
                <label for="narrative" class="block text-xs font-semibold text-slate-700 mb-1">
                    Complaint Facts & Narrative <span class="text-red-500">*</span>
                </label>
                <textarea
                    name="narrative"
                    id="narrative"
                    rows="5"
                    required
                    minlength="20"
                    maxlength="5000"
                    class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                >{{ old('narrative', $case->narrative) }}</textarea>
            </div>

            <!-- Confidentiality -->
            <div class="pt-2 border-t border-slate-100">
                <label class="flex items-center gap-2 cursor-pointer">
                    <input type="checkbox" name="is_confidential" value="1" @checked(old('is_confidential', $case->is_confidential)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                    <span class="text-xs font-medium text-slate-700">Mark as confidential case (restricted access per RA 10173)</span>
                </label>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.cases.show', $case) }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                Save Changes
            </button>
        </div>
    </form>
</div>
@endsection
