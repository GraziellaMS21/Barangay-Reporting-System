@extends('layouts.admin')

@section('title', 'Edit Party: ' . $party->full_name)

@section('content')
<div class="max-w-4xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <div class="border-b border-slate-200 pb-4">
        <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('admin.parties.index') }}" class="hover:text-slate-800">Parties Directory</a></li>
                <li><span class="text-slate-400">/</span></li>
                <li><a href="{{ route('admin.parties.show', $party) }}" class="hover:text-slate-800">{{ $party->full_name }}</a></li>
                <li><span class="text-slate-400">/</span></li>
                <li class="font-semibold text-slate-800">Edit</li>
            </ol>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900">Edit Person Record</h1>
        <p class="text-sm text-slate-600 mt-1">Update personal identification, address, and residency classification</p>
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

    <form method="POST" action="{{ route('admin.parties.update', $party) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pb-2">
                Identity & Classification
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="full_name" class="block text-xs font-semibold text-slate-700 mb-1">
                        Full Legal Name <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="full_name"
                        id="full_name"
                        value="{{ old('full_name', $party->full_name) }}"
                        required
                        maxlength="120"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="alias" class="block text-xs font-semibold text-slate-700 mb-1">
                        Alias / Known Name
                    </label>
                    <input
                        type="text"
                        name="alias"
                        id="alias"
                        value="{{ old('alias', $party->alias) }}"
                        maxlength="80"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="residency_status" class="block text-xs font-semibold text-slate-700 mb-1">
                        Residency Status <span class="text-red-500">*</span>
                    </label>
                    <select name="residency_status" id="residency_status" required class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500">
                        <option value="resident" @selected(old('residency_status', $party->residency_status) === 'resident')>Resident</option>
                        <option value="non_resident" @selected(old('residency_status', $party->residency_status) === 'non_resident')>Non-Resident</option>
                        <option value="unverified" @selected(old('residency_status', $party->residency_status) === 'unverified')>Unverified</option>
                    </select>
                </div>

                <div>
                    <label for="purok_id" class="block text-xs font-semibold text-slate-700 mb-1">
                        Assigned Purok
                    </label>
                    <select name="purok_id" id="purok_id" class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">Select Purok...</option>
                        @foreach($puroks as $pk)
                            <option value="{{ $pk->id }}" @selected(old('purok_id', $party->purok_id) == $pk->id)>{{ $pk->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="birth_date" class="block text-xs font-semibold text-slate-700 mb-1">
                        Birth Date
                    </label>
                    <input
                        type="date"
                        name="birth_date"
                        id="birth_date"
                        value="{{ old('birth_date', $party->birth_date?->format('Y-m-d')) }}"
                        max="{{ now()->toDateString() }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label for="sex" class="block text-xs font-semibold text-slate-700 mb-1">Sex</label>
                    <select name="sex" id="sex" class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500">
                        <option value="">Unspecified</option>
                        <option value="male" @selected(old('sex', $party->sex) === 'male')>Male</option>
                        <option value="female" @selected(old('sex', $party->sex) === 'female')>Female</option>
                        <option value="other" @selected(old('sex', $party->sex) === 'other')>Other</option>
                    </select>
                </div>

                <div>
                    <label for="civil_status" class="block text-xs font-semibold text-slate-700 mb-1">Civil Status</label>
                    <input
                        type="text"
                        name="civil_status"
                        id="civil_status"
                        value="{{ old('civil_status', $party->civil_status) }}"
                        placeholder="Single, Married, etc."
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="occupation" class="block text-xs font-semibold text-slate-700 mb-1">Occupation</label>
                    <input
                        type="text"
                        name="occupation"
                        id="occupation"
                        value="{{ old('occupation', $party->occupation) }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>

            <h2 class="text-sm font-bold text-slate-900 uppercase tracking-wider border-b border-slate-100 pt-4 pb-2">
                Contact & Address
            </h2>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="contact_number" class="block text-xs font-semibold text-slate-700 mb-1">Contact Number</label>
                    <input
                        type="text"
                        name="contact_number"
                        id="contact_number"
                        value="{{ old('contact_number', $party->contact_number) }}"
                        placeholder="0917-000-0000"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">Email Address</label>
                    <input
                        type="email"
                        name="email"
                        id="email"
                        value="{{ old('email', $party->email) }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>

            <div>
                <label for="address_line" class="block text-xs font-semibold text-slate-700 mb-1">Local Address</label>
                <input
                    type="text"
                    name="address_line"
                    id="address_line"
                    value="{{ old('address_line', $party->address_line) }}"
                    class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label for="home_barangay" class="block text-xs font-semibold text-slate-700 mb-1">Home Barangay (Non-Residents)</label>
                    <input
                        type="text"
                        name="home_barangay"
                        id="home_barangay"
                        value="{{ old('home_barangay', $party->home_barangay) }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <div>
                    <label for="home_city" class="block text-xs font-semibold text-slate-700 mb-1">Home City (Non-Residents)</label>
                    <input
                        type="text"
                        name="home_city"
                        id="home_city"
                        value="{{ old('home_city', $party->home_city) }}"
                        class="w-full text-sm border border-slate-300 rounded-md p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.parties.show', $party) }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                Save Person Details
            </button>
        </div>
    </form>
</div>
@endsection
