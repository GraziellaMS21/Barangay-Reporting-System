@extends('layouts.admin')

@section('title', 'Reports Center')
@section('page-heading', 'Reports Center')

@section('content')
<x-ui.page-header title="Official Reports & Statutory Compliance Exports" subtitle="Generate DILG, Katarungang Pambarangay, and internal barangay audit reports with official signature blocks.">
    <x-slot:actions>
        <x-ui.badge color="blue">
            Viewing: {{ $viewingFiscalYear ? $viewingFiscalYear->label : 'All Fiscal Years' }}
        </x-ui.badge>
        <x-ui.button variant="secondary" :href="route('admin.reports-center.history')">
            <i class="fa-solid fa-clock-rotate-left"></i> Generated History
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
    @foreach($templates as $template)
        <x-ui.card :title="$template->title">
            <div class="flex flex-col justify-between h-full space-y-4">
                <div class="space-y-2">
                    <p class="text-sm text-slate-600">{{ $template->description }}</p>
                    <div class="flex flex-wrap items-center gap-2 pt-1">
                        <x-ui.badge color="slate">{{ strtoupper($template->orientation) }}</x-ui.badge>
                        @if(! $template->is_confirmed)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-amber-50 text-amber-800 border border-dashed border-amber-300">
                                DRAFT: Unconfirmed
                            </span>
                        @else
                            <x-ui.badge color="green">Official Template</x-ui.badge>
                        @endif
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-100 flex items-center justify-between">
                    <span class="text-xs font-mono text-slate-400">{{ $template->key }}</span>
                    <x-ui.button size="sm" variant="primary" :href="route('admin.reports-center.show', $template->key)">
                        Open Report <i class="fa-solid fa-arrow-right"></i>
                    </x-ui.button>
                </div>
            </div>
        </x-ui.card>
    @endforeach
</div>
@endsection
