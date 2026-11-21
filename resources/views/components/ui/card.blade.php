@props([
    'title' => null,
    'footer' => null,
])

<div {{ $attributes->merge(['class' => 'bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden']) }}>
    @if($title || isset($headerActions))
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between gap-4">
            @if($title)
                <h3 class="font-semibold text-slate-900">{{ $title }}</h3>
            @endif
            @if(isset($headerActions))
                <div class="flex items-center gap-2">
                    {{ $headerActions }}
                </div>
            @endif
        </div>
    @endif

    <div class="p-6">
        {{ $slot }}
    </div>

    @if($footer || isset($footerSlot))
        <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 text-sm text-slate-600">
            {{ $footer ?? $footerSlot }}
        </div>
    @endif
</div>
