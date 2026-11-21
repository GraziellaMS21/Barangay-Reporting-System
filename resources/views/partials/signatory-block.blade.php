@props([
    'signatories' => [],
])

<div class="mt-8 pt-6 border-t border-slate-200 grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-8">
    @forelse($signatories as $sig)
        <div class="text-center space-y-1">
            <p class="text-xs font-medium text-slate-500 uppercase tracking-wider">{{ $sig['label'] ?? 'Prepared by' }}</p>
            <div class="h-14 flex items-center justify-center">
                @if(!empty($sig['signature_path']))
                    <img src="{{ asset('storage/' . $sig['signature_path']) }}" alt="Signature" class="max-h-12 max-w-full object-contain">
                @else
                    <div class="w-36 border-b border-slate-400"></div>
                @endif
            </div>
            <p class="text-sm font-bold text-slate-900">{{ $sig['name'] ?? '____________________' }}</p>
            <p class="text-xs text-slate-600">{{ $sig['position'] ?? 'Barangay Official' }}</p>
        </div>
    @empty
        <div class="col-span-full text-center py-4">
            <div class="w-48 border-b border-slate-400 mx-auto mb-2"></div>
            <p class="text-xs font-bold text-slate-800">Authorized Signature</p>
        </div>
    @endforelse
</div>
