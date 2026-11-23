<aside class="rounded-lg border border-amber-200 bg-amber-50 p-4" aria-labelledby="{{ $id }}">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-1">
            <h3 id="{{ $id }}" class="text-sm font-semibold text-slate-900">{{ $heading }}</h3>
            <p class="text-sm leading-5 text-slate-700">{{ $message }}</p>
            @if(! empty($note))
                <p class="text-xs leading-5 text-slate-600">{{ $note }}</p>
            @endif
        </div>
        <span class="inline-flex w-fit shrink-0 items-center rounded-full border border-amber-300 bg-white px-2.5 py-1 text-xs font-semibold text-amber-900">
            Not available yet
        </span>
    </div>
</aside>
