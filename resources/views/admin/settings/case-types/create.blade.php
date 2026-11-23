@extends('layouts.admin')

@section('title', 'Add Case Classification')

@section('content')
<div class="max-w-3xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <div class="border-b border-slate-200 pb-4">
        <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
            <ol class="flex items-center space-x-2">
                <li><a href="{{ route('admin.case-types.index') }}" class="hover:text-slate-800">Case Types</a></li>
                <li><span class="text-slate-400">/</span></li>
                <li class="font-semibold text-slate-800">New Type</li>
            </ol>
        </nav>
        <h1 class="text-2xl font-bold text-slate-900">Add Case Classification</h1>
        <p class="text-sm text-slate-600 mt-1">Register a dispute category for amicable settlement under Katarungang Pambarangay</p>
    </div>

    @if ($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-sm text-red-800 font-medium">
            {{ $errors->first() }}
        </div>
    @endif

    <form method="POST" action="{{ route('admin.case-types.store') }}" class="space-y-6">
        @csrf

        <x-ui.card class="p-6 border border-slate-200 space-y-4">
            <div>
                <label for="name" class="block text-xs font-semibold text-slate-700 mb-1">
                    Classification Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    name="name"
                    id="name"
                    value="{{ old('name') }}"
                    required
                    maxlength="80"
                    placeholder="e.g. Boundary or Property Dispute"
                    class="w-full text-sm border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500"
                />
            </div>

            <div>
                <label for="description" class="block text-xs font-semibold text-slate-700 mb-1">
                    Scope & Description
                </label>
                <textarea
                    name="description"
                    id="description"
                    rows="3"
                    maxlength="500"
                    placeholder="Describe the nature of disputes covered under this classification..."
                    class="w-full text-sm border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500"
                >{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="sort_order" class="block text-xs font-semibold text-slate-700 mb-1">
                        Sort Order
                    </label>
                    <input
                        type="number"
                        name="sort_order"
                        id="sort_order"
                        value="{{ old('sort_order', 0) }}"
                        min="0"
                        class="w-full text-sm border border-slate-300 rounded p-2 focus:ring-1 focus:ring-blue-500"
                    />
                </div>

                <div class="flex items-center pt-6">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true)) class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-xs font-medium text-slate-700">Active for new case filings</span>
                    </label>
                </div>
            </div>
        </x-ui.card>

        <div class="flex items-center justify-between">
            <a href="{{ route('admin.case-types.index') }}" class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800">
                Cancel
            </a>
            <button type="submit" class="px-5 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                Save Classification
            </button>
        </div>
    </form>
</div>
@endsection
