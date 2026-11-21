@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
])

@php
$baseClasses = 'inline-flex items-center justify-center font-medium transition-colors rounded-lg focus:outline-none focus:ring-2 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed';

$sizes = [
    'sm' => 'px-3 py-1.5 text-xs gap-1.5',
    'md' => 'px-4 py-2 text-sm gap-2',
    'lg' => 'px-5 py-2.5 text-base gap-2.5',
];

$variants = [
    'primary' => 'bg-blue-600 hover:bg-blue-700 text-white focus:ring-blue-500 shadow-sm',
    'amber' => 'bg-amber-500 hover:bg-amber-600 text-slate-900 font-semibold focus:ring-amber-400 shadow-sm',
    'secondary' => 'bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 focus:ring-slate-400 shadow-sm',
    'danger' => 'bg-red-600 hover:bg-red-700 text-white focus:ring-red-500 shadow-sm',
    'ghost' => 'bg-transparent hover:bg-slate-100 text-slate-700 focus:ring-slate-400',
];

$classes = "{$baseClasses} {$sizes[$size]} {$variants[$variant]}";
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        {{ $slot }}
    </button>
@endif
