@props([
    'confirmed' => false,
])

@if(! $confirmed)
    <div class="border-2 border-dashed border-amber-300 bg-amber-50/70 text-amber-900 rounded-lg px-3 py-1.5 text-xs font-bold uppercase tracking-wider inline-flex items-center gap-1.5 mb-2">
        <i class="fa-solid fa-pen-ruler text-amber-600"></i>
        <span>Draft - Pending Barangay Confirmation</span>
    </div>
@endif
