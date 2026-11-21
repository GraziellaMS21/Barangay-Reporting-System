@props([
    'label',
    'name' => null,
    'error' => null,
    'required' => false,
    'hint' => null,
])

<div {{ $attributes->merge(['class' => 'space-y-1.5']) }}>
    @if($label)
        <label @if($name) for="{{ $name }}" @endif class="block text-sm font-semibold text-slate-700">
            {{ $label }}
            @if($required)
                <span class="text-red-500">*</span>
            @endif
        </label>
    @endif

    {{ $slot }}

    @if($hint)
        <p class="text-xs text-slate-500">{{ $hint }}</p>
    @endif

    @if($error)
        <p class="text-xs text-red-600 font-medium">{{ $error }}</p>
    @elseif($name && $errors->has($name))
        <p class="text-xs text-red-600 font-medium">{{ $errors->first($name) }}</p>
    @endif
</div>
