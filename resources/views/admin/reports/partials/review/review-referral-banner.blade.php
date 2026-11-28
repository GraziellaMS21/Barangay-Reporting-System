@php
  $activeReferral = $report->activeReferral ?? $report->referrals()->latest('id')->first();
@endphp
@if($activeReferral)
  @php
    $refOffice = $activeReferral->office;
    $latestUpdate = $activeReferral->updates->first();
  @endphp
  <section class="rounded-xl border border-indigo-200 bg-indigo-50/50 p-4 space-y-2.5" aria-label="External Referral Summary">
    <div class="flex flex-wrap items-center justify-between gap-2">
      <div class="flex items-center gap-2 flex-wrap">
        <span class="inline-flex items-center gap-1 font-mono text-xs font-bold text-indigo-900 bg-white border border-indigo-200 px-2.5 py-1 rounded-lg">
          <i class="fa-solid fa-share-from-square text-indigo-600 text-[11px]" aria-hidden="true"></i>
          <span>{{ $activeReferral->reference_no }}</span>
        </span>
        <strong class="text-xs text-slate-900">{{ $refOffice?->name ?? 'External Agency' }}</strong>
        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ $activeReferral->status === 'closed' ? 'bg-slate-100 text-slate-700 border border-slate-300' : 'bg-indigo-100 text-indigo-800 border border-indigo-200' }}">
          {{ \App\Models\Referral::STATUSES[$activeReferral->status] ?? ucfirst($activeReferral->status) }}
        </span>
        @if($activeReferral->outcome)
          <span class="inline-flex items-center rounded-full bg-emerald-50 text-emerald-800 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold">
            Outcome: {{ \App\Models\Referral::OUTCOMES[$activeReferral->outcome] ?? ucfirst($activeReferral->outcome) }}
          </span>
        @endif
        @if($activeReferral->isOverdue())
          <span class="inline-flex items-center rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2 py-0.5 text-[10px] font-bold">
            Follow-up overdue
          </span>
        @endif
      </div>
      <div class="flex items-center gap-2">
        <a href="{{ route('admin.referrals.letter', $activeReferral) }}" target="_blank" rel="noopener" data-turbo="false" class="inline-flex items-center gap-1 rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3 py-1.5 transition">
          <i class="fa-solid fa-print text-[11px]" aria-hidden="true"></i> Print Referral Letter
        </a>
      </div>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 pt-2 border-t border-indigo-100 text-xs text-slate-600">
      <div>
        <span class="text-slate-400 font-medium">Dispatched:</span>
        <span class="font-semibold text-slate-800">{{ $activeReferral->sent_at ? $activeReferral->sent_at->format('M d, Y') : 'Not yet transmitted' }}</span>
        @if($activeReferral->sent_via)
          <span class="text-slate-500">({{ \App\Models\Referral::SENT_VIA[$activeReferral->sent_via] ?? $activeReferral->sent_via }})</span>
        @endif
      </div>
      <div>
        <span class="text-slate-400 font-medium">Follow-up SLA:</span>
        <span class="font-semibold {{ $activeReferral->isOverdue() ? 'text-rose-700 font-bold' : 'text-slate-800' }}">
          {{ $activeReferral->follow_up_at ? $activeReferral->follow_up_at->format('M d, Y') : 'None scheduled' }}
        </span>
      </div>
      <div>
        <span class="text-slate-400 font-medium">Parent Report Status:</span>
        <x-status-badge :status="$report->status" class="text-[10px]" />
      </div>
    </div>

    @if($latestUpdate)
      <div class="pt-2 border-t border-indigo-100 text-xs">
        <span class="text-slate-400 font-medium">Latest agency touchpoint ({{ $latestUpdate->occurred_at ? $latestUpdate->occurred_at->format('M d, Y') : $latestUpdate->created_at->format('M d, Y') }}):</span>
        <span class="text-slate-700 italic">"{{ \Illuminate\Support\Str::limit($latestUpdate->note, 140) }}"</span>
      </div>
    @endif

    @if($activeReferral->status === 'closed' && $activeReferral->outcome === 'resolved' && $report->status === 'in_progress')
      <div class="mt-1 rounded-lg bg-emerald-50 border border-emerald-200 p-2 text-xs text-emerald-900 flex items-center gap-2">
        <i class="fa-solid fa-clipboard-check text-emerald-600" aria-hidden="true"></i>
        <span>Agency completed their action. Barangay staff physical site inspection and proof photos are required before final resolution.</span>
      </div>
    @endif
  </section>
@endif
