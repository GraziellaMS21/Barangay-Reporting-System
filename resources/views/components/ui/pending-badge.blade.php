@props([
    'count' => 0,
])

@if($count > 0)
    <a href="{{ route('admin.pending.index') }}" class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800 border border-amber-300 hover:bg-amber-200 transition-colors" title="Items pending barangay confirmation">
        <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
        <span>{{ $count }} Pending</span>
    </a>
@endif
