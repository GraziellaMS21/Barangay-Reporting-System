        {{-- Original Resident Description --}}
        <div>
          <div class="flex items-center justify-between gap-2 mb-1.5">
            <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Original description</span>
            @if($voiceAudio && $voiceAudio->ai_description && trim($voiceAudio->ai_description) !== trim($residentDescription))
              <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 px-2 py-0.5 text-[10px] font-bold">
                <i class="fa-solid fa-user-pen" aria-hidden="true"></i> Edited by resident
              </span>
            @endif
          </div>
          <div class="bg-slate-50 border border-slate-200 rounded-xl p-3">
            <p id="dossier-orig-desc" class="rw-clamp text-xs sm:text-sm text-slate-800 leading-relaxed m-0">{{ $report->description }}</p>
            <button type="button" id="dossier-desc-toggle" class="hidden mt-1.5 text-xs font-bold text-blue-600 hover:underline">View more</button>
          </div>

          @if($report->people_at_risk || $report->is_ongoing || $report->is_recurring || count($answers) > 0)
            <div class="flex flex-wrap gap-1.5 mt-2.5">
              @if($report->people_at_risk)
                <span class="inline-flex items-center gap-1 rounded-lg bg-rose-50 text-rose-800 border border-rose-200 px-2 py-1 text-[11px] font-semibold">
                  <i class="fa-solid fa-person-circle-exclamation" aria-hidden="true"></i> Someone in danger
                </span>
              @endif
              @if($report->is_ongoing)
                <span class="inline-flex items-center gap-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 text-[11px] font-semibold">
                  <i class="fa-solid fa-clock" aria-hidden="true"></i> Happening now
                </span>
              @endif
              @if($report->is_recurring)
                <span class="inline-flex items-center gap-1 rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 text-[11px] font-semibold">
                  <i class="fa-solid fa-repeat" aria-hidden="true"></i> Recurring
                </span>
              @endif
              @foreach($questionSchema as $question)
                @php
                  $questionKey = $question['key'] ?? null;
                  $answerExists = $questionKey !== null && array_key_exists($questionKey, $answers) && $answers[$questionKey] !== null && $answers[$questionKey] !== '';
                  $answerValue = $answerExists ? $answers[$questionKey] : null;
                  $answerLabel = is_array($answerValue)
                      ? implode(', ', array_filter($answerValue, fn ($value) => $value !== null && $value !== ''))
                      : (in_array((string) $answerValue, ['0', '1'], true)
                          ? ((string) $answerValue === '1' ? 'Yes' : 'No')
                          : $answerValue);
                @endphp
                @if($answerExists)
                  <span class="inline-flex items-center rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 text-[11px] font-semibold">
                    {{ $question['label'] ?? $questionKey }}: {{ $answerLabel }}
                  </span>
                @endif
              @endforeach
              @foreach($answers as $answerKey => $answerValue)
                @php
                  $hasQuestionLabel = collect($questionSchema)->contains(fn ($q) => ($q['key'] ?? null) === $answerKey);
                  $fallbackAnswerExists = ! $hasQuestionLabel && $answerValue !== null && $answerValue !== '';
                  $fallbackAnswerLabel = is_array($answerValue)
                      ? implode(', ', array_filter($answerValue, fn ($value) => $value !== null && $value !== ''))
                      : (in_array((string) $answerValue, ['0', '1'], true)
                          ? ((string) $answerValue === '1' ? 'Yes' : 'No')
                          : $answerValue);
                @endphp
                @if($fallbackAnswerExists)
                  <span class="inline-flex items-center rounded-lg bg-blue-50 text-blue-800 border border-blue-200 px-2 py-1 text-[11px] font-semibold">
                    {{ str($answerKey)->replace('_', ' ')->title() }}: {{ $fallbackAnswerLabel }}
                  </span>
                @endif
              @endforeach
            </div>
          @endif
        </div>

        {{-- Attached Audio Player (if voice/phone report) --}}
        @if($report->audio)
          <div id="voice-audio-section" class="pt-3 border-t border-slate-100 space-y-2">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <span class="text-xs font-bold text-slate-800 flex items-center gap-1.5">
                <i class="fa-solid fa-microphone-lines text-blue-600" aria-hidden="true"></i>
                <span>{{ $report->audio->origin === 'staff' ? 'Hotline Phone Recording' : 'Resident Voice Recording' }}</span>
              </span>
              @if($report->audio->isVerified())
                <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 px-2 py-0.5 text-[10px] font-bold">
                  <i class="fa-solid fa-check" aria-hidden="true"></i> Verified
                </span>
              @endif
            </div>
            <audio controls src="{{ route('admin.reports.audio', $report) }}" preload="metadata" class="w-full h-9"></audio>
            @if($report->audio->transcript)
              <details class="rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-xs">
                <summary class="font-bold text-slate-700 cursor-pointer">What was said (AI transcript)</summary>
                <p class="mt-1.5 text-slate-700 leading-relaxed mb-0">{{ $report->audio->transcript }}</p>
              </details>
            @endif
          </div>
        @endif
