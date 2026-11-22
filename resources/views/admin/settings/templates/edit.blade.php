@extends('layouts.admin')

@section('title', 'Edit Response Template')
@section('page-heading', 'Edit Response Template')

@section('content')
<div class="max-w-3xl space-y-6">
    <x-ui.page-header
        title="Edit Response Template"
        subtitle="Update boilerplate text and categorization." />

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.templates.update', $template) }}" class="space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card>
            <div class="space-y-5">
                <x-ui.form-field label="Template Title *" name="title" required>
                    <input type="text" name="title" id="title" required
                        value="{{ old('title', $template->title) }}"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-semibold" />
                </x-ui.form-field>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-ui.form-field label="Template Kind *" name="kind" required>
                        <select name="kind" id="kind" required class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="report_status" @selected(old('kind', $template->kind) === 'report_status')>Report Status Update</option>
                            <option value="hearing_notice" @selected(old('kind', $template->kind) === 'hearing_notice')>Hearing Notice / Summons Remark</option>
                            <option value="referral_note" @selected(old('kind', $template->kind) === 'referral_note')>Agency Referral Transmittal</option>
                            <option value="general" @selected(old('kind', $template->kind) === 'general')>General Correspondence</option>
                        </select>
                    </x-ui.form-field>

                    <x-ui.form-field label="Target Category (Optional)" name="category_id">
                        <select name="category_id" id="category_id" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">-- All Categories --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" @selected(old('category_id', $template->category_id) == $cat->id)>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </x-ui.form-field>
                </div>

                <x-ui.form-field label="Template Body Content *" name="body" required>
                    <textarea name="body" id="body" required rows="6"
                        class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-sans">{{ old('body', $template->body) }}</textarea>
                </x-ui.form-field>

                <div class="flex items-center gap-2 pt-2">
                    <input type="checkbox" name="is_active" id="is_active" value="1"
                        @checked(old('is_active', $template->is_active))
                        class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                    <label for="is_active" class="text-xs text-slate-700 font-semibold">Active and visible in response selectors</label>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-between pt-4 border-t border-slate-200">
            <a href="{{ route('admin.templates.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-800">
                Cancel
            </a>
            <x-ui.button type="submit" variant="primary">
                Update Template
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
