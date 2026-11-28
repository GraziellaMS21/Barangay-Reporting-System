      {{-- TAB 3: DUPLICATES --}}
      <div class="dossier-panel rw-fade-in p-4 space-y-3 hidden" id="duplicate-suggestions-section" data-dossier-panel="duplicates">
        <div class="flex items-center justify-between gap-2">
          <h2 class="text-xs font-bold text-slate-900 m-0">Possible Duplicate Reports</h2>
          @if($canMergeDuplicate)
            <button type="button" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-2.5 py-1 transition" data-open-dialog="duplicate-modal">
              <i class="fa-solid fa-code-merge mr-1" aria-hidden="true"></i> Manual merge
            </button>
          @endif
        </div>

        @if($actionableDuplicates->isEmpty() || $report->status === 'merged')
          <div class="rounded-xl border border-dashed border-slate-200 bg-slate-50 p-5 text-center text-xs text-slate-500">
            No pending duplicate suggestions for <strong>{{ $report->tracking_code }}</strong>.
          </div>
        @else
          <div class="space-y-3">
            @foreach($actionableDuplicates as $index => $suggestion)
              @php
                $candidate = $suggestion->candidate;
                $similarityPct = (int) round((float) $suggestion->score * 100);
                $candidateCategory = $candidate->category?->name ?? $candidate->residentCategory?->name ?? 'Uncategorized';
                $candidatePurok = $candidate->purok?->name ?? $candidate->residentPurok?->name;
                $candidateIsEmergency = $candidate->is_emergency || $candidate->status === 'emergency_flagged';
              @endphp
              <article class="rounded-xl border border-slate-200 bg-white p-3 space-y-2" data-duplicate-card="{{ $suggestion->id }}">
                <div class="flex flex-wrap items-center justify-between gap-1.5">
                  <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="rounded-full bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 text-[10px] font-bold">{{ $similarityPct }}% match</span>
                    <a href="{{ route('admin.reports.show', $candidate) }}" target="_blank" rel="noopener noreferrer" class="font-mono text-xs font-bold text-blue-700 hover:underline">
                      {{ $candidate->tracking_code }}
                    </a>
                    <x-status-badge :status="$candidate->status" class="text-[10px]" />
                  </div>
                </div>
                <div class="text-[11px] text-slate-600">
                  <strong>{{ $candidateCategory }}</strong> · {{ $candidatePurok ?? 'No purok' }}@if($candidate->landmark) · {{ \Illuminate\Support\Str::limit($candidate->landmark, 45) }}@endif
                </div>
                <p class="text-xs text-slate-700 bg-slate-50 rounded-lg p-2 border border-slate-100 m-0">{{ \Illuminate\Support\Str::limit($candidate->description, 140) }}</p>
                <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                  <form method="POST" action="{{ route('admin.reports.duplicate.dismiss', [$report, $suggestion]) }}" class="flex items-center gap-1.5 flex-1">
                    @csrf
                    @method('PATCH')
                    <input type="text" name="note" maxlength="500" placeholder="Dismissal note..." class="text-xs border border-slate-300 rounded-lg px-2 py-1 flex-1 min-w-[100px] bg-white">
                    <button type="submit" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-2.5 py-1">Dismiss</button>
                  </form>
                  @if($canMergeDuplicate && ! $candidateIsEmergency)
                    <button type="button" class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-2.5 py-1"
                            data-open-dialog="duplicate-modal" data-duplicate-candidate-id="{{ $candidate->id }}">
                      Merge
                    </button>
                  @endif
                </div>
              </article>
            @endforeach
          </div>
        @endif
      </div>