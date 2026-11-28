      {{-- TAB 2: TIMELINE & NOTES --}}
      <div class="dossier-panel rw-fade-in p-4 space-y-4 hidden" data-dossier-panel="timeline">
        @unless($isCompleted || $emergencyActionOnly)
          <form method="POST" action="{{ route('admin.reports.internal-notes.store', $report) }}" class="rounded-xl bg-slate-50 border border-slate-200 p-3 space-y-2" data-prevent-double-submit>
            @csrf
            <label for="timeline-internal-note" class="block text-[11px] font-bold text-slate-700">
              <i class="fa-solid fa-lock text-slate-400 mr-1" aria-hidden="true"></i> Add internal note · Visible to staff only
            </label>
            <textarea id="timeline-internal-note" name="body" rows="2" maxlength="3000" required
                      placeholder="Record staff observations, coordination, or call notes..."
                      class="w-full rounded-lg border border-slate-300 bg-white px-2.5 py-1.5 text-xs text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30"></textarea>
            <div class="flex justify-end">
              <button type="submit" class="rounded-lg bg-[#0b1f4b] hover:bg-[#172e63] text-white text-xs font-bold px-3 py-1.5 transition" data-submit-once>
                Save note
              </button>
            </div>
          </form>
        @endunless

        <ol class="review-activity-list">
          @forelse($activityEntries as $activity)
            @php
              $isInternal = isset($activity['visible_to_resident']) ? !$activity['visible_to_resident'] : str_contains($activity['title'], '(Internal)');
              $isInfoReq = str_contains(strtolower($activity['title']), 'info');
            @endphp
            <li class="relative pl-4 border-l-2 {{ $isInternal ? 'border-slate-300' : ($isInfoReq ? 'border-amber-400' : 'border-blue-500') }}">
              <span class="absolute -left-[5px] top-1.5 w-2 h-2 rounded-full {{ $isInternal ? 'bg-slate-400' : ($isInfoReq ? 'bg-amber-500' : 'bg-blue-600') }}"></span>
              <div class="flex flex-wrap items-center justify-between gap-1">
                <div class="flex items-center gap-1.5 flex-wrap">
                  <strong class="text-xs text-slate-900">{{ $activity['title'] }}</strong>
                  @if($isInternal)
                    <span class="rounded px-1.5 py-0.2 text-[10px] font-bold bg-slate-100 text-slate-600">Staff only</span>
                  @elseif($isInfoReq)
                    <span class="rounded px-1.5 py-0.2 text-[10px] font-bold bg-amber-50 text-amber-800">Info request</span>
                  @else
                    <span class="rounded px-1.5 py-0.2 text-[10px] font-bold bg-blue-50 text-blue-700">Public</span>
                  @endif
                </div>
                <time datetime="{{ $activity['at']?->toIso8601String() }}" class="text-[10px] text-slate-400">{{ $activity['at']?->format('M d, Y h:i A') }}</time>
              </div>
              @if($activity['user'])
                <div class="text-[11px] text-slate-500 mt-0.5">{{ $activity['user'] }}</div>
              @endif
              @if($activity['body'])
                <p class="text-xs text-slate-700 mt-1 bg-slate-50 border border-slate-100 rounded-lg px-2.5 py-1.5 leading-relaxed mb-0">{{ $activity['body'] }}</p>
              @endif
            </li>
          @empty
            <li class="text-xs text-slate-500 italic">No activity recorded yet.</li>
          @endforelse
        </ol>
      </div>