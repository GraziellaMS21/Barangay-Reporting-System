@props([
    'color' => 'blue',
    'dot' => false,
])

@php
$colors = [
    'blue' => 'bg-blue-50 text-blue-700 border-blue-200',
    'green' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
    'amber' => 'bg-amber-50 text-amber-800 border-amber-200',
    'red' => 'bg-red-50 text-red-700 border-red-200',
    'slate' => 'bg-slate-100 text-slate-700 border-slate-200',
    'purple' => 'bg-purple-50 text-purple-700 border-purple-200',
];

$dotColors = [
    'blue' => 'bg-blue-500',
    'green' => 'bg-emerald-500',
    'amber' => 'bg-amber-500',
    'red' => 'bg-red-500',
    'slate' => 'bg-slate-500',
    'purple' => 'bg-purple-500',
];

$classes = "inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-semibold border " . ($colors[$color] ?? $colors['blue']);
@endphp

<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($dot)
        <span class="w-1.5 h-1.5 rounded-full {{ $dotColors[$color] ?? 'bg-blue-500' }}"></span>
    @endif
    {{ $slot }}
</span>
