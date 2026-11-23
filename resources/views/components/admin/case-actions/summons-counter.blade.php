<section id="summons-counter" class="rounded-lg border border-amber-200 bg-amber-50 p-4" role="status" aria-live="polite">
    <div>
        <h3 class="font-semibold text-slate-900">Summons counter</h3>
        <p class="text-sm text-slate-700">
            {{ $issued }} {{ $issued === 1 ? 'issued summons' : 'issued summonses' }}
        </p>
    </div>

    <p class="mt-2 text-xs leading-5 text-slate-600">
        Summonses are numbered sequentially for information and record-keeping only. This count does not determine escalation or change the case status.
    </p>
</div>