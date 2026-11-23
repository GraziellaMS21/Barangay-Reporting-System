@extends('layouts.admin')

@section('content')
    <div class="mx-auto max-w-4xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        <header class="border-b border-slate-200 pb-4">
            <p class="text-sm font-medium text-slate-500">WS4 placeholder</p>
            <h1 class="mt-1 text-2xl font-bold text-slate-900">{{ $title }}</h1>
            @if(! empty($context))
                <p class="mt-1 text-sm text-slate-600">{{ $context }}</p>
            @endif
        </header>

        @include('admin.case-actions.partials.feature-placeholder', [
            'id' => 'ws4-placeholder-status',
            'heading' => 'This feature is not available yet',
            'message' => $message,
            'note' => 'No information has been saved, changed, or sent from this screen.',
        ])

        @if(! empty($backUrl))
            <a
                href="{{ $backUrl }}"
                class="inline-flex min-h-11 items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                {{ $backLabel ?? 'Back' }}
            </a>
        @endif
    </div>
@endsection
