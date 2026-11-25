@extends('layouts.admin')

@section('title', 'AI Integration (Gemini)')
@section('page-heading', 'AI Integration (Gemini)')

@section('content')
<div class="max-w-2xl space-y-6">
    <x-ui.page-header
        title="AI Integration (Gemini)"
        subtitle="Configure the Gemini model and an optional encrypted API key override without editing server files." />

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <x-ui.card>
        <form method="POST" action="{{ route('admin.settings.ai.update') }}" class="p-6 space-y-5">
            @csrf
            @method('PUT')

            <x-ui.form-field label="Gemini Model Version" name="gemini_model" required hint="Recommended: gemini-2.5-flash or gemini-3.1-flash-lite.">
                <input type="text" name="gemini_model" id="gemini_model" required
                    value="{{ old('gemini_model', $currentModel) }}"
                    list="gemini-model-presets"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono" />
                <datalist id="gemini-model-presets">
                    <option value="gemini-2.5-flash">
                    <option value="gemini-2.5-pro">
                    <option value="gemini-3.1-flash-lite">
                </datalist>
            </x-ui.form-field>

            <x-ui.form-field label="Custom Gemini API Key (Encrypted)" name="gemini_api_key"
                hint="{{ $hasCustomKey ? 'A custom encrypted API key is currently stored. Leave blank to keep it.' : ($envFallbackConfigured ? 'Currently using server environment key (.env). Enter a key here to override it.' : 'No API key is currently configured. Enter a key to enable AI triage.') }}">
                <input type="password" name="gemini_api_key" id="gemini_api_key" autocomplete="new-password"
                    placeholder="{{ $hasCustomKey ? '••••••••••••••••••••••••' : 'AIza...' }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-mono" />
            </x-ui.form-field>

            @if($hasCustomKey)
                <label class="inline-flex items-center gap-2 text-xs text-slate-600 cursor-pointer">
                    <input type="checkbox" name="clear_api_key" value="1" class="rounded border-slate-300 text-rose-600 focus:ring-rose-500" />
                    <span>Clear custom API key and revert to server environment default</span>
                </label>
            @endif

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <x-ui.button href="{{ route('admin.settings.index') }}" variant="secondary">Back</x-ui.button>
                <x-ui.button type="submit" variant="primary">Save AI Configuration</x-ui.button>
            </div>
        </form>
    </x-ui.card>
</div>
@endsection
