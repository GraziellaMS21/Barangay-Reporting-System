@props(['provenance' => 'resident'])

@php
    $provenance = strtolower($provenance ?? 'resident');
    $config = match($provenance) {
        'ai' => [
            'label' => 'AI Suggested',
            'icon' => 'fa-solid fa-wand-magic-sparkles',
            'class' => 'bg-purple-50 text-purple-700 border-purple-200',
        ],
        'staff_edited', 'staff' => [
            'label' => 'Staff Verified',
            'icon' => 'fa-solid fa-user-pen',
            'class' => 'bg-amber-50 text-amber-700 border-amber-200',
        ],
        default => [
            'label' => 'Resident Original',
            'icon' => 'fa-solid fa-user',
            'class' => 'bg-slate-100 text-slate-700 border-slate-200',
        ],
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold border ' . $config['class']]) }}>
    <i class="{{ $config['icon'] }} text-[10px]"></i>
    <span>{{ $config['label'] }}</span>
</span>
