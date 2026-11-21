@props([
    'title',
    'description' => null,
    'icon' => 'fa-solid fa-folder-open',
])

<div {{ $attributes->merge(['class' => 'text-center py-12 px-4 rounded-xl border-2 border-dashed border-slate-200 bg-slate-50/50']) }}>
    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-slate-100 text-slate-500 mb-3">
        <i class="{{ $icon }} text-xl"></i>
    </div>
    <h3 class="text-sm font-semibold text-slate-900">{{ $title }}</h3>
    @if($description)
        <p class="text-sm text-slate-500 mt-1 max-w-sm mx-auto">{{ $description }}</p>
    @endif
    @if(isset($action))
        <div class="mt-4">
            {{ $action }}
        </div>
    @endif
</div>
