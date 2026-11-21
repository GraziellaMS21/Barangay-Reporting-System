@props([
    'id',
    'title',
])

<dialog id="{{ $id }}" class="rounded-xl shadow-xl p-0 border border-slate-200 backdrop:bg-slate-900/40 w-full max-w-lg m-auto">
    <div class="bg-white rounded-xl overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-900">{{ $title }}</h3>
            <button type="button" onclick="document.getElementById('{{ $id }}').close()" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="p-6">
            {{ $slot }}
        </div>
        @if(isset($footer))
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-100 flex items-center justify-end gap-3">
                {{ $footer }}
            </div>
        @endif
    </div>
</dialog>
