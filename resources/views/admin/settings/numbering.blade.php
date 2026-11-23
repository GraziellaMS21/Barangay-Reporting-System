@extends('layouts.admin')

@section('title', 'Document Numbering Schemes')
@section('page-heading', 'Document Numbering Schemes')

@section('content')
<div class="max-w-4xl space-y-6">
    <x-ui.page-header
        title="Document Numbering Schemes"
        subtitle="Configure sequential number masks, tokens, and annual reset formats for official barangay records." />

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

    <!-- Token Guide -->
    <div class="p-4 rounded-xl bg-blue-50/60 border border-blue-200">
        <h4 class="text-xs font-bold uppercase tracking-wider text-blue-900 mb-2 flex items-center gap-1.5">
            <i class="fa-solid fa-code text-blue-600"></i>
            <span>Supported Format Mask Tokens</span>
        </h4>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs text-blue-800">
            <div><code class="font-bold bg-white px-1.5 py-0.5 rounded border border-blue-200">{YEAR}</code> 4-digit operational year (2026)</div>
            <div><code class="font-bold bg-white px-1.5 py-0.5 rounded border border-blue-200">{YY}</code> 2-digit operational year (26)</div>
            <div><code class="font-bold bg-white px-1.5 py-0.5 rounded border border-blue-200">{MM}</code> 2-digit filing month (01–12)</div>
            <div><code class="font-bold bg-white px-1.5 py-0.5 rounded border border-blue-200">{SEQ:n}</code> Zero-padded sequence ({SEQ:4} = 0001)</div>
        </div>
    </div>

    <form method="POST" action="{{ route('admin.numbering.update') }}" class="space-y-6">
        @csrf

        <!-- 1. External Referrals -->
        <x-ui.card title="1. External Referral Transmittal Scheme">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-start">
                <div class="text-xs text-slate-500 sm:col-span-1 leading-relaxed">
                    Generates official reference numbers for transmittals referred to external agencies (PNP, CSWDO, MTCC).
                </div>
                <div class="sm:col-span-2 space-y-3">
                    <x-ui.form-field label="Format Mask *" name="referral_format" required>
                        <input type="text" name="referral_format" id="referral_format" required
                            value="{{ old('referral_format', $formats['referral']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm font-mono focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Live Preview:</span>
                        <code class="px-2 py-0.5 bg-slate-100 rounded border border-slate-200 font-bold text-blue-700">{{ $previews['referral'] }}</code>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <!-- 2. Formal Complaint Cases -->
        <x-ui.card title="2. Formal Complaint Case Scheme (Katarungang Pambarangay)">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-start">
                <div class="text-xs text-slate-500 sm:col-span-1 leading-relaxed">
                    Docketing mask for formal community disputes subject to Lupon Tagapamayapa mediation and conciliation.
                </div>
                <div class="sm:col-span-2 space-y-3">
                    <x-ui.form-field label="Format Mask *" name="case_format" required>
                        <input type="text" name="case_format" id="case_format" required
                            value="{{ old('case_format', $formats['case']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm font-mono focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Live Preview:</span>
                        <code class="px-2 py-0.5 bg-slate-100 rounded border border-slate-200 font-bold text-blue-700">{{ $previews['case'] }}</code>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <!-- 3. Incident Blotter Scheme -->
        <x-ui.card title="3. Incident Blotter Record Scheme">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-start">
                <div class="text-xs text-slate-500 sm:col-span-1 leading-relaxed">
                    Docketing mask for non-conciliation blotter reports and walk-in documentation entries.
                </div>
                <div class="sm:col-span-2 space-y-3">
                    <x-ui.form-field label="Format Mask *" name="blotter_format" required>
                        <input type="text" name="blotter_format" id="blotter_format" required
                            value="{{ old('blotter_format', $formats['blotter']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm font-mono focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Live Preview:</span>
                        <code class="px-2 py-0.5 bg-slate-100 rounded border border-slate-200 font-bold text-blue-700">{{ $previews['blotter'] }}</code>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <!-- 4. Summons Scheme -->
        <x-ui.card title="4. Summons & Hearing Notice Scheme">
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 items-start">
                <div class="text-xs text-slate-500 sm:col-span-1 leading-relaxed">
                    Identifier generated on legal summonses and conciliation subpoenas served by peace officers / tanods.
                </div>
                <div class="sm:col-span-2 space-y-3">
                    <x-ui.form-field label="Format Mask *" name="summons_format" required>
                        <input type="text" name="summons_format" id="summons_format" required
                            value="{{ old('summons_format', $formats['summons']) }}"
                            class="w-full rounded-lg border-slate-300 text-sm font-mono focus:border-blue-500 focus:ring-blue-500" />
                    </x-ui.form-field>
                    <div class="flex items-center gap-2 text-xs text-slate-600">
                        <span>Live Preview:</span>
                        <code class="px-2 py-0.5 bg-slate-100 rounded border border-slate-200 font-bold text-blue-700">{{ $previews['summons'] }}</code>
                    </div>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
            <x-ui.button type="submit" variant="primary">
                <i class="fa-solid fa-floppy-disk text-xs mr-1.5"></i>
                <span>Save Numbering Schemes</span>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
