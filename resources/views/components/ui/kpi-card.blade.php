@props([
    'title',
    'value',
    'icon' => null,
    'subtitle' => null,
    'unit' => null,
    'url' => null,
    'color' => 'blue',
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-slate-200 p-5 flex flex-col justify-between']) }}>
    <div class="flex items-start justify-between">
        <div class="min-w-0">
            <p class="text-xs font-semibold tracking-wider text-slate-500 uppercase">{{ $title }}</p>
            <div class="mt-2 flex items-baseline gap-1.5">
                <span class="text-2xl sm:text-3xl font-bold text-slate-900 tracking-tight">{{ $value }}</span>
                @if($unit)
                    <span class="text-xs sm:text-sm font-medium text-slate-500">{{ $unit }}</span>
                @endif
            </div>
            @if($subtitle)
                <p class="mt-1 text-xs text-slate-500">{{ $subtitle }}</p>
            @endif
        </div>
        @if($icon)
            <div @class([
                'w-10 h-10 rounded-lg flex items-center justify-center shrink-0 ml-2',
                'bg-blue-50 text-blue-600' => $color === 'blue',
                'bg-emerald-50 text-emerald-600' => $color === 'green',
                'bg-amber-50 text-amber-600' => $color === 'amber',
                'bg-rose-50 text-rose-600' => $color === 'red',
                'bg-slate-50 text-slate-600' => $color === 'slate',
            ])>
                <i class="{{ $icon }} text-lg"></i>
            </div>
        @endif
    </div>
    @if($url)
        <div class="mt-4 pt-3 border-t border-slate-100">
            <a href="{{ $url }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 flex items-center gap-1">
                <span>View details</span>
                <i class="fa-solid fa-arrow-right text-[10px]"></i>
            </a>
        </div>
    @endif
</div>
