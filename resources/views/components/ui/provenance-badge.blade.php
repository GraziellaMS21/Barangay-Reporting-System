@props([
    'provenance' => 'resident',
])

@php
    $prov = strtolower($provenance ?? 'resident');
    $config = match($prov) {
        'ai' => [
            'label' => 'AI Suggested',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'class' => 'bg-purple-50 text-purple-700 border-purple-200',
        ],
        'not_provided' => [
            'label' => 'Not provided',
            'icon' => 'fa-solid fa-minus',
            'class' => 'bg-slate-100 text-slate-500 border-slate-200',
        ],
        'staff_edited', 'staff' => [
            'label' => 'Staff Verified',
            'icon' => 'fa-solid fa-user-pen',
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
        ],
        'resident' => [
            'label' => 'Resident Original',
            'icon' => 'fa-solid fa-user',
            'class' => 'bg-blue-50 text-blue-700 border-blue-200',
        ],
        default => null,
    };
@endphp

@if($config)
    <span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border ' . $config['class']]) }}>
        <i class="{{ $config['icon'] }} text-[10px]"></i>
        <span>{{ $config['label'] }}</span>
    </span>
@endif
