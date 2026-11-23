@extends('layouts.admin')

@section('title', 'Upload Legacy File — Step 1 of 3')
@section('page-heading', 'Legacy Import Wizard')

@section('content')
<div class="max-w-3xl mx-auto space-y-6">
    <div class="text-xs text-slate-500 flex items-center gap-2">
        <a href="{{ route('admin.imports.index') }}" class="hover:text-blue-700">Legacy File Imports</a>
        <span>/</span>
        <span class="text-slate-800 font-medium">Step 1 of 3: Upload Spreadsheet</span>
    </div>

    <x-ui.page-header
        title="Step 1 of 3: Upload Historical Excel / CSV File"
        subtitle="Upload an Excel (.xlsx) or CSV (.csv) spreadsheet containing historical cases, blotter entries, and party records."
    >
        <x-slot:actions>
            <x-ui.button variant="secondary" href="{{ route('admin.imports.template') }}">
                <i class="fa-solid fa-download" aria-hidden="true"></i>
                <span>Blank Excel Template (.xlsx)</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Progress Stepper -->
    <div class="grid grid-cols-3 gap-3 text-xs font-semibold">
        <div class="p-3 rounded-lg bg-blue-600 text-white flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-white text-blue-700 inline-flex items-center justify-center font-bold">1</span>
            <span>1. Upload File</span>
        </div>
        <div class="p-3 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 inline-flex items-center justify-center font-bold">2</span>
            <span>2. Map Columns</span>
        </div>
        <div class="p-3 rounded-lg bg-white border border-slate-200 text-slate-500 flex items-center gap-2">
            <span class="w-5 h-5 rounded-full bg-slate-100 text-slate-600 inline-flex items-center justify-center font-bold">3</span>
            <span>3. Validate, Preview & Commit</span>
        </div>
    </div>

    @if($errors->any())
        <div role="alert" class="bg-red-50 border border-red-200 text-red-800 rounded-lg p-4 text-sm">
            <p class="font-semibold mb-1">
                <i class="fa-solid fa-triangle-exclamation mr-1" aria-hidden="true"></i>
                Please correct the following errors:
            </p>
            <ul class="list-disc list-inside text-xs space-y-1">
                @foreach($errors->all() as $err)
                    <li>{{ $err }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.imports.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <x-ui.card title="1. Select Spreadsheet File">
            <div class="space-y-4">
                <div>
                    <label for="file" class="block text-sm font-semibold text-slate-800 mb-1">
                        Excel or CSV File <span class="text-red-600">*</span>
                    </label>
                    <input
                        type="file"
                        id="file"
                        name="file"
                        accept=".xlsx,.csv,.txt"
                        required
                        class="block w-full text-sm text-slate-700 border border-slate-300 rounded-lg p-2.5 bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-600"
                    >
                    <p class="text-xs text-slate-500 mt-1.5">
                        Accepted formats: <strong>.xlsx</strong> or <strong>.csv</strong> (maximum 10 MB). Stored on the private disk. Handwritten logbooks must be encoded into the blank template first — no AI or OCR is run on party records.
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                    <div>
                        <label for="target_fiscal_year" class="block text-sm font-semibold text-slate-800 mb-1">
                            Default Target Fiscal Year (Optional)
                        </label>
                        <select
                            id="target_fiscal_year"
                            name="target_fiscal_year"
                            class="w-full h-10 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                        >
                            <option value="">Derive automatically from each row's Filed Date</option>
                            @foreach($allowedYears as $year)
                                <option value="{{ $year }}" @selected((int) old('target_fiscal_year') === $year)>
                                    FY {{ $year }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">
                            Lookback window: {{ min($allowedYears) }} to {{ max($allowedYears) }} (setting <code>records.lookback_years</code>).
                        </p>
                    </div>

                    <div>
                        <label for="source_name" class="block text-sm font-semibold text-slate-800 mb-1">
                            Saved Column Mapping Preset (Optional)
                        </label>
                        <select
                            id="source_name"
                            name="source_name"
                            class="w-full h-10 px-3 py-2 text-sm border border-slate-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-600"
                        >
                            <option value="">Auto-detect columns from header row</option>
                            @foreach(array_keys($savedMappings) as $presetName)
                                <option value="{{ $presetName }}" @selected(old('source_name') === $presetName)>
                                    {{ $presetName }}
                                </option>
                            @endforeach
                        </select>
                        <p class="text-xs text-slate-500 mt-1">
                            Reuses a previously saved column and status mapping for your legacy program or spreadsheet format.
                        </p>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <x-ui.card title="2. Blank Template Column Reference">
            <p class="text-xs text-slate-600 mb-3">
                If you are copying tables from Word documents or encoding old logbooks, download the blank template. Required columns are marked with <span class="text-red-600 font-bold">*</span>:
            </p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                @foreach($targetFields as $key => $meta)
                    <div class="flex items-center justify-between p-2 rounded border border-slate-200 bg-slate-50">
                        <span class="font-medium text-slate-800">
                            {{ $meta['label'] }}
                            @if($meta['required'])
                                <span class="text-red-600 font-bold">*</span>
                            @endif
                        </span>
                        <code class="text-[11px] text-slate-500">{{ $key }}</code>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        <div class="sticky bottom-0 bg-white border-t border-slate-200 p-4 rounded-lg shadow-lg flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-3">
            <x-ui.button variant="ghost" href="{{ route('admin.imports.index') }}">
                Cancel
            </x-ui.button>
            <x-ui.button type="submit" variant="primary">
                <span>Upload & Continue to Column Mapping</span>
                <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
