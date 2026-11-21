@extends('layouts.admin')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-4">
        <div>
            <div class="flex items-center gap-3">
                <h1 class="text-2xl font-bold text-slate-900">{{ $case->case_number }}</h1>
                @if($case->is_confidential)
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-red-100 text-red-800 border border-red-200">
                        Confidential
                    </span>
                @endif
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                    {{ ucfirst(str_replace('_', ' ', $case->status)) }}
                </span>
            </div>
            <p class="text-sm text-slate-600 mt-1">{{ $case->title }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.cases.index') }}" class="px-4 py-2 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 bg-white hover:bg-slate-50">
                Back to Cases
            </a>
        </div>
    </div>

    <!-- Case Navigation Tabs -->
    <div class="border-b border-slate-200">
        <nav class="flex space-x-6" aria-label="Tabs">
            <span class="border-b-2 border-blue-600 py-3 px-1 text-sm font-semibold text-blue-600">Overview</span>
            <span class="py-3 px-1 text-sm font-medium text-slate-500">Parties</span>
            <span class="py-3 px-1 text-sm font-medium text-slate-500">Summons</span>
            <span class="py-3 px-1 text-sm font-medium text-slate-500">Hearings</span>
            <span class="py-3 px-1 text-sm font-medium text-slate-500">Escalation</span>
            <span class="py-3 px-1 text-sm font-medium text-slate-500">Notes</span>
            <span class="py-3 px-1 text-sm font-medium text-slate-500">Timeline</span>
        </nav>
    </div>

    <!-- Tab Contents / Partials -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-900">Case Narrative</h2>
                <p class="text-sm text-slate-700 leading-relaxed whitespace-pre-wrap">{{ $case->narrative }}</p>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-4">
                <h2 class="text-base font-semibold text-slate-900">Summons & Conciliation</h2>
                @include('admin.case-actions.partials.summons-tab')
                @include('admin.case-actions.partials.hearings-tab')
                @include('admin.case-actions.partials.escalation-tab')
            </div>
        </div>

        <div class="space-y-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6 space-y-3 text-sm">
                <h3 class="font-semibold text-slate-900">Case Metadata</h3>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Filed On</span>
                    <span class="font-medium text-slate-900">{{ $case->filed_on?->format('M d, Y') ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Fiscal Year</span>
                    <span class="font-medium text-slate-900">{{ $case->fiscalYear?->label ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between py-1 border-b border-slate-100">
                    <span class="text-slate-500">Source</span>
                    <span class="font-medium text-slate-900">{{ ucfirst(str_replace('_', ' ', $case->source)) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
