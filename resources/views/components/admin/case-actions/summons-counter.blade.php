<section id="summons-counter" class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 flex items-center justify-between gap-3" role="status" aria-live="polite">
    <div class="flex items-center gap-2 text-xs text-slate-700">
        <i class="fa-solid fa-envelope-open-text text-slate-500"></i>
        <span><strong>{{ $issued }}</strong> {{ $issued === 1 ? 'summons' : 'summonses' }} issued (record only)</span>
    </div>
</section>
