@extends('layouts.admin')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-8 text-center">
        <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-blue-50 text-blue-600 mb-4">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-slate-900 mb-2">{{ $title ?? 'Workstream Stub Page' }}</h2>
        <p class="text-slate-600 mb-6">Coming soon, owned by <span class="font-semibold text-blue-600">{{ $owner ?? 'Team' }}</span>.</p>
        <div class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 rounded-lg text-sm text-slate-700">
            <span class="w-2 h-2 rounded-full bg-amber-400"></span>
            <span>Feature module in development under Phase 0 foundation</span>
        </div>
    </div>
</div>
@endsection
