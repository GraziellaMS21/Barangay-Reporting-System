@props([
    'tabs' => [],
    'active' => null,
])

<div {{ $attributes->merge(['class' => 'border-b border-slate-200 mb-6']) }}>
    <nav class="-mb-px flex space-x-6 overflow-x-auto" aria-label="Tabs">
        @foreach($tabs as $key => $label)
            @php
                $isActive = (string)$active === (string)$key;
            @endphp
            <a href="?tab={{ $key }}" @class([
                'whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm transition-colors',
                'border-blue-600 text-blue-600 font-semibold' => $isActive,
                'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' => ! $isActive,
            ])>
                {{ $label }}
            </a>
        @endforeach
    </nav>
</div>
