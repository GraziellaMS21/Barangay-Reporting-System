        {{-- Reporter Strip --}}
        <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3 flex items-start gap-3">
          <div class="w-9 h-9 rounded-full bg-blue-100 text-blue-700 flex items-center justify-center text-xs font-bold flex-shrink-0">
            <i class="fa-solid fa-user" aria-hidden="true"></i>
          </div>
          <div class="min-w-0 flex-1 text-xs">
            <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Reported by</div>
            @if($report->is_anonymous || (! $isVoiceOrPhone && ! filled($report->reporter_name)))
              <div class="font-semibold text-slate-600 italic mt-0.5">Not stated (anonymous report)</div>
            @else
              <div class="font-bold text-slate-900 truncate mt-0.5">
                @if(filled($report->reporter_name))
                  {{ $report->reporter_name }}
                @else
                  <span class="italic font-normal text-slate-500">Not provided</span>
                @endif
                @if(!empty($voiceProvenance['reporter_name']['edited']))
                  <span class="ml-1 inline-flex items-center rounded-full bg-amber-50 text-amber-700 border border-amber-200 px-1.5 py-0.2 text-[10px] font-bold">Edited after recording</span>
                @elseif(!empty($voiceProvenance['reporter_name']['filled']))
                  <span class="ml-1 inline-flex items-center rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-1.5 py-0.2 text-[10px] font-bold">From recording</span>
                @endif
              </div>
              <div class="text-slate-600 mt-1 space-y-0.5">
                <div class="flex items-center gap-1.5">
                  <i class="fa-solid fa-phone text-[10px] text-slate-400 w-3" aria-hidden="true"></i>
                  @if(filled($report->reporter_contact))
                    <span>{{ $report->reporter_contact }}</span>
                  @else
                    <span class="italic text-slate-400">Not provided</span>
                  @endif
                </div>
                <div class="flex items-center gap-1.5 truncate">
                  <i class="fa-regular fa-envelope text-[10px] text-slate-400 w-3" aria-hidden="true"></i>
                  @if(filled($report->reporter_email))
                    <span class="truncate">{{ $report->reporter_email }}</span>
                  @else
                    <span class="italic text-slate-400">Not provided</span>
                  @endif
                </div>
              </div>
            @endif
          </div>
        </div>

<div class="text-[11px] text-slate-400 mt-1.5 pt-1.5 border-t border-slate-200/60 italic">
  Reporter residential address is not collected. Location fields describe the incident site.
</div>