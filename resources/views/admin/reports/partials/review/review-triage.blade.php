      {{-- ============================================================= --}}
      {{-- STEP 1 PANEL: REVIEW & TRIAGE                                 --}}
      {{-- ============================================================= --}}
      <div class="rw-step-panel rw-fade-in p-5 space-y-4" data-workspace-panel="1" aria-label="Review AI suggestions" @if($defaultWorkspaceStep !== 1) hidden @endif>
        {{-- Panel Header Strip --}}
        <div class="flex flex-wrap items-center justify-between gap-2 pb-3 border-b border-slate-100">
          <div class="flex flex-wrap items-center gap-2">
            @if($aiReady)
              <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 text-xs font-bold" data-ai-state-badge>
                <i class="fa-solid fa-wand-magic-sparkles text-[10px]" aria-hidden="true"></i> AI ready
              </span>
            @elseif($aiPending)
              <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-700 border border-slate-200 px-2.5 py-0.5 text-xs font-bold" data-ai-state-badge>
                <span class="review-spinner" aria-hidden="true"></span> AI analyzing...
              </span>
            @elseif($aiFailed && $aiConfigured)
              <span class="inline-flex items-center gap-1.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 text-xs font-bold" data-ai-state-badge>
                <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i> AI failed
              </span>
            @else
              <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-0.5 text-xs font-bold" data-ai-state-badge>
                <i class="fa-solid fa-circle-minus text-[10px]" aria-hidden="true"></i> AI unavailable
              </span>
            @endif

            <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-bold {{ $confidence !== null && $confidence >= $highConfidenceThreshold ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : ($confidence !== null && $confidence >= $lowConfidenceThreshold ? 'bg-amber-50 text-amber-800 border border-amber-200' : 'bg-rose-50 text-rose-700 border border-rose-200') }}"
                  data-ai-confidence-wrap @if(! $aiReady) hidden @endif>
              <span data-ai-confidence>Confidence {{ $confidence !== null ? number_format($confidence * 100) : '—' }}%</span>
            </span>

            <span class="inline-flex items-center rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 text-xs font-bold" data-low-confidence @if(! $lowConfidence) hidden @endif>
              Low confidence
            </span>

            @if(! $emergencyActionOnly)
              <span class="text-xs font-bold text-slate-600 bg-slate-100 px-2.5 py-0.5 rounded-full" data-reviewed-count>
                {{ $reviewedInitialCount }} of {{ $requiredFieldsCount }} reviewed
              </span>
            @endif
          </div>

          <div class="flex flex-wrap items-center gap-2">
            <span class="review-gate-hint review-card2-gate-summary text-xs font-bold" data-gate-reason-card2 aria-live="polite"></span>
            @if(! $emergencyActionOnly)
              @if($aiPending)
                <button type="submit" form="review-stop-ai-form" data-stop-ai
                        class="inline-flex items-center gap-1.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white text-xs font-bold px-3 py-1.5 shadow-xs transition">
                  <i class="fa-solid fa-stop" aria-hidden="true"></i> Stop
                </button>
              @endif
              @if($aiFailed && $aiConfigured)
                <button type="submit" form="review-retry-ai-form" aria-label="Retry AI analysis" title="Retry AI analysis"
                        class="inline-flex items-center gap-1.5 rounded-full bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3 py-1.5 shadow-xs transition">
                  <i class="fa-solid fa-rotate-right" aria-hidden="true"></i> Try again
                </button>
              @endif
              <button type="button" class="inline-flex items-center gap-1.5 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold px-3 py-1.5 shadow-xs transition"
                      data-accept-all @if(! $aiReady) hidden disabled @endif>
                <i class="fa-solid fa-check-double" aria-hidden="true"></i> Accept all AI suggestions
              </button>
            @endif
          </div>
        </div>

        <p class="text-xs text-slate-600 m-0" data-ai-state-message aria-live="polite">
          @if($aiReady)
            AI suggestions are ready. Click <strong>Accept all AI suggestions</strong> or apply individual fields below.
            @if($lowConfidence) Check carefully because confidence is low. @endif
          @elseif($aiPending)
            AI is analyzing this report. Your final values will not be changed when suggestions arrive.
          @elseif($aiFailed && $aiConfigured)
            AI suggestions are not available. Enter or confirm Category and Urgency manually. Click <strong>Try again</strong> if needed.
          @else
            AI suggestions are not available. Enter or confirm Category and Urgency manually.
          @endif
        </p>

        @if($report->infoRequests->isNotEmpty() && isset($infoThreadSummary))
          <div class="rounded-xl border border-blue-200 bg-blue-50/70 px-3.5 py-2 text-xs text-blue-900 flex items-center gap-2 flex-wrap" data-chat-summary-strip data-report-id="{{ $report->id }}">
            <i class="fa-solid fa-comments text-blue-600" aria-hidden="true"></i>
            <span class="font-bold" data-chat-message-count>{{ $infoThreadSummary['count'] }} message{{ $infoThreadSummary['count'] === 1 ? '' : 's' }} sent</span>
            <span aria-hidden="true">·</span>
            <span data-chat-awaiting @if(! $infoThreadSummary['awaiting_reply']) hidden @endif>Awaiting resident reply</span>
          </div>
        @endif

        {{-- Compact 2x2 Triage Field Grid --}}
        <div id="review-triage-grid" class="grid grid-cols-1 md:grid-cols-2 gap-3.5">
          {{-- Card 1: Category --}}
          <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-2.5" data-review-row data-field="category"
               data-resident-value="{{ $residentCategoryId ?? '' }}"
               data-resident-label="{{ $residentCategory?->name ?? '' }}"
               data-ai-value="{{ $aiCategoryId ?? '' }}"
               data-ai-label="{{ $aiCategoryLabel ?? '' }}"
               data-ai-present="{{ $aiCategoryPresent ? 'true' : 'false' }}"
               data-review-required="{{ $categoryReviewRequired ? 'true' : 'false' }}"
               data-reviewed="{{ $alreadyReviewed || ! $categoryReviewRequired ? 'true' : 'false' }}">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <label for="final-category" class="text-xs font-extrabold text-slate-900">Category <span class="text-rose-600">*</span></label>
              <div class="flex items-center gap-1.5 flex-wrap">
                @if(!empty($voiceProvenance['category']['filled']))
                  <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ !empty($voiceProvenance['category']['edited']) ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                    {{ !empty($voiceProvenance['category']['edited']) ? 'Edited after recording' : 'From recording' }}
                  </span>
                @endif
                <span class="review-badge review-badge-outline-green" data-comparison-badge hidden></span>
                <span class="review-badge {{ $categoryFinalState === 'Accepted' ? 'review-badge-green' : ($categoryFinalState === 'Edited' ? 'review-badge-amber' : ($categoryFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                      data-final-state>{{ $categoryFinalState }}</span>
              </div>
            </div>

            <div class="text-[11px] text-slate-600 space-y-0.5">
              <div>Resident: @if($residentCategory)<strong class="text-slate-800" data-resident-display>{{ $residentCategory->name }}</strong>@else<span class="italic text-slate-400">Not chosen by resident</span>@endif</div>
              <div>AI: <strong class="text-slate-800" data-ai-value-display>@if($aiReady){{ $aiCategoryLabel ?? 'Not provided' }}@elseif($aiPending)Waiting...@else Not available @endif</strong></div>
            </div>

            <select id="final-category" name="category_id" data-final-control data-required-field required @disabled($emergencyActionOnly)
                    class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
              <option value="">Select category</option>
              @foreach($categories as $category)
                <option value="{{ $category->id }}" @selected((string) $finalCategoryId === (string) $category->id)>{{ $category->name }}</option>
              @endforeach
            </select>

            <div class="flex items-center justify-between gap-2 pt-0.5 flex-wrap">
              <span class="review-gate-field-hint {{ $finalCategoryId ? 'is-ready' : 'is-missing' }}" data-gate-hint="category">
                <i class="fa-solid {{ $finalCategoryId ? 'fa-check' : 'fa-lock' }}" aria-hidden="true"></i>
                <span>{{ $finalCategoryId ? 'Ready to route' : 'Required to route' }}</span>
              </span>
              <div class="flex items-center gap-2 text-[11px] font-bold">
                <button type="button" class="text-blue-600 hover:underline" data-use-ai @disabled($emergencyActionOnly) @if(! $aiCategoryId || ! $aiCategoryPresent || $aiCategoryId === $residentCategoryId) hidden @endif>Use AI</button>
                <button type="button" class="text-blue-600 hover:underline" data-use-resident @disabled($emergencyActionOnly) @if($residentCategoryId === null || $residentCategoryId === $aiCategoryId) hidden @endif>Use resident's</button>
                <button type="button" class="text-emerald-600 hover:underline" data-accept-row @disabled($emergencyActionOnly) @if(! $aiCategoryId || ! $aiCategoryPresent) hidden @endif>Accept</button>
                <button type="button" class="text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
              </div>
            </div>
          </div>

          {{-- Card 2: Urgency (1-Tap Pill Selector) --}}
          <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-2.5" data-review-row data-field="urgency"
               data-resident-value=""
               data-resident-label=""
               data-ai-value="{{ $aiUrgency ?? '' }}"
               data-ai-label="{{ $aiUrgency ?? '' }}"
               data-ai-present="{{ $aiUrgencyPresent ? 'true' : 'false' }}"
               data-review-required="{{ $urgencyReviewRequired ? 'true' : 'false' }}"
               data-reviewed="{{ $alreadyReviewed || ! $urgencyReviewRequired ? 'true' : 'false' }}">
            <div class="flex items-center justify-between gap-2 flex-wrap">
              <label for="final-urgency" class="text-xs font-extrabold text-slate-900">Urgency <span class="text-rose-600">*</span></label>
              <div class="flex items-center gap-1.5">
                <span class="review-badge review-badge-outline-blue" data-comparison-badge hidden></span>
                <span class="review-badge {{ $urgencyFinalState === 'Accepted' ? 'review-badge-green' : ($urgencyFinalState === 'Edited' ? 'review-badge-amber' : ($urgencyFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                      data-final-state>{{ $urgencyFinalState }}</span>
              </div>
            </div>

            <div class="text-[11px] text-slate-600 space-y-0.5">
              <div>Resident: <span class="italic text-slate-400" data-resident-display>Not provided</span></div>
              <div>AI: <strong class="text-slate-800" data-ai-value-display>@if($aiReady){{ $aiUrgency ?? 'Not provided' }}@elseif($aiPending)Waiting...@else Not available @endif</strong></div>
            </div>

            {{-- Underlying form control synced with 1-tap pills --}}
            <select id="final-urgency" name="urgency" class="sr-only" tabindex="-1" aria-hidden="true" data-final-control data-required-field required @disabled($emergencyActionOnly)>
              <option value="">Select urgency</option>
              @foreach(['Low', 'Medium', 'High', 'Critical'] as $urgency)
                <option value="{{ $urgency }}" @selected($finalUrgency === $urgency)>{{ $urgency }}</option>
              @endforeach
            </select>

            <div class="grid grid-cols-4 gap-1 rounded-lg border border-slate-200 bg-slate-100 p-1" role="group" aria-label="Urgency level">
              @foreach(['Low', 'Medium', 'High', 'Critical'] as $urgency)
                <button type="button" data-urgency-pill-btn="{{ $urgency }}" @disabled($emergencyActionOnly)
                        class="py-1.5 px-1 rounded-md text-xs font-bold transition text-center {{ $finalUrgency === $urgency ? 'is-selected' : 'text-slate-600 hover:text-slate-900' }}">
                  {{ $urgency }}
                </button>
              @endforeach
            </div>

            <div class="flex items-center justify-between gap-2 pt-0.5 flex-wrap">
              <span class="review-gate-field-hint {{ $finalUrgency ? 'is-ready' : 'is-missing' }}" data-gate-hint="urgency">
                <i class="fa-solid {{ $finalUrgency ? 'fa-check' : 'fa-lock' }}" aria-hidden="true"></i>
                <span>{{ $finalUrgency ? 'Ready to route' : 'Required to route' }}</span>
              </span>
              <div class="flex items-center gap-2 text-[11px] font-bold">
                <button type="button" class="text-blue-600 hover:underline" data-use-ai @disabled($emergencyActionOnly) @if(! $aiUrgencyPresent) hidden @endif>Use AI</button>
                <button type="button" class="text-emerald-600 hover:underline" data-accept-row @disabled($emergencyActionOnly) @if(! $aiUrgencyPresent) hidden @endif>Accept</button>
                <button type="button" class="text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
              </div>
            </div>
          </div>

          {{-- Card 3: Purok --}}
          <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-2" data-review-row data-field="purok"
               data-resident-value="{{ $residentPurok?->id ?? '' }}"
               data-resident-label="{{ $residentPurok?->name ?? '' }}"
               data-ai-value="" data-ai-label="" data-ai-present="false"
               data-review-required="false" data-reviewed="true">
            <div class="flex items-center justify-between gap-2">
              <label for="final-purok" class="text-xs font-extrabold text-slate-900">Purok</label>
              <div class="flex items-center gap-1.5">
                @if(!empty($voiceProvenance['purok']['filled']))
                  <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ !empty($voiceProvenance['purok']['edited']) ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                    {{ !empty($voiceProvenance['purok']['edited']) ? 'Edited after recording' : 'From recording' }}
                  </span>
                @endif
                <span class="review-badge review-badge-gray" data-final-state>Resident</span>
              </div>
            </div>
            <div class="text-[11px] text-slate-600">
              Resident: @if($residentPurok)<strong class="text-slate-800" data-resident-display>{{ $residentPurok->name }}</strong>@else<span class="italic text-slate-400">Not provided</span>@endif
              <span data-ai-value-display class="sr-only"></span>
              <span data-comparison-badge hidden class="sr-only"></span>
            </div>
            <select id="final-purok" name="purok_id" data-final-control @disabled($emergencyActionOnly)
                    class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
              <option value="">Select purok</option>
              @foreach($puroks as $purok)
                <option value="{{ $purok->id }}" @selected((string) $finalPurokId === (string) $purok->id)>{{ $purok->name }}</option>
              @endforeach
            </select>
            <div class="flex justify-end">
              <button type="button" class="text-[11px] font-bold text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
            </div>
          </div>

          {{-- Card 4: Landmark --}}
          <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-2" data-review-row data-field="landmark"
               data-resident-value="{{ $residentLandmark ?? '' }}"
               data-resident-label="{{ $residentLandmark ?? '' }}"
               data-ai-value="" data-ai-label="" data-ai-present="false"
               data-review-required="false" data-reviewed="true">
            <div class="flex items-center justify-between gap-2">
              <label for="final-landmark" class="text-xs font-extrabold text-slate-900">Landmark</label>
              <div class="flex items-center gap-1.5">
                @if(!empty($voiceProvenance['landmark']['filled']))
                  <span class="rounded-full px-2 py-0.5 text-[10px] font-bold {{ !empty($voiceProvenance['landmark']['edited']) ? 'bg-amber-50 text-amber-700 border border-amber-200' : 'bg-blue-50 text-blue-700 border border-blue-200' }}">
                    {{ !empty($voiceProvenance['landmark']['edited']) ? 'Edited after recording' : 'From recording' }}
                  </span>
                @endif
                <span class="review-badge review-badge-gray" data-final-state>Resident</span>
              </div>
            </div>
            <div class="text-[11px] text-slate-600 truncate">
              Resident: @if(trim((string) $residentLandmark) !== '')<strong class="text-slate-800" data-resident-display>{{ $residentLandmark }}</strong>@else<span class="italic text-slate-400">Not provided</span>@endif
              <span data-ai-value-display class="sr-only"></span>
              <span data-comparison-badge hidden class="sr-only"></span>
            </div>
            <input id="final-landmark" type="text" name="landmark" maxlength="255"
                   value="{{ $finalLandmark }}" placeholder="Enter landmark or nearby reference" data-final-control @disabled($emergencyActionOnly)
                   class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500/30">
            <div class="flex justify-end">
              <button type="button" class="text-[11px] font-bold text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
            </div>
          </div>
        </div>

        {{-- Incident Description Comparison Block --}}
        <div class="rounded-xl border border-slate-200 p-3.5 bg-white space-y-3" data-review-row data-field="description"
             data-resident-value="{{ $residentDescription }}"
             data-resident-label="{{ $residentDescription }}"
             data-ai-value="{{ $aiDescription ?? '' }}"
             data-ai-label="{{ $aiDescription ?? '' }}"
             data-ai-present="{{ $aiDescriptionPresent ? 'true' : 'false' }}"
             data-review-required="true"
             data-reviewed="{{ $descriptionReviewed ? 'true' : 'false' }}">
          <div class="flex items-center justify-between gap-2 flex-wrap">
            <span class="text-xs font-extrabold text-slate-900">Incident Description</span>
            <div class="flex items-center gap-1.5 flex-wrap">
              <span class="review-badge review-badge-outline-green" data-comparison-badge hidden></span>
              <span class="review-badge {{ $descriptionFinalState === 'Accepted' ? 'review-badge-green' : ($descriptionFinalState === 'Edited' ? 'review-badge-amber' : ($descriptionFinalState === 'AI suggested' ? 'review-badge-blue' : 'review-badge-gray')) }}"
                    data-final-state>{{ $descriptionFinalState }}</span>
            </div>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
            <div class="rounded-lg bg-slate-50 border border-slate-200 p-2.5">
              <div class="text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1">Original Resident Text</div>
              <div class="text-xs text-slate-700 leading-relaxed max-h-24 overflow-y-auto">
                @if(trim((string) $residentDescription) !== '')
                  <span data-resident-display>{{ $residentDescription }}</span>
                @else
                  <span class="italic text-slate-400">Not provided</span>
                @endif
              </div>
            </div>

            <div class="rounded-lg bg-blue-50/70 border border-blue-200 p-2.5">
              <div class="text-[10px] font-bold uppercase tracking-wider text-blue-700 mb-1">AI Summary / Suggestion</div>
              <div class="text-xs text-blue-950 leading-relaxed max-h-24 overflow-y-auto">
                @if($aiReady && $aiDescriptionPresent)
                  <div class="review-description-diff" data-description-diff data-original="{{ $residentDescription }}" data-ai="{{ $aiDescription }}"></div>
                  <span data-ai-value-display class="sr-only">{{ $aiDescription }}</span>
                @else
                  <span data-ai-value-display>
                    @if($aiPending)Waiting for suggestion
                    @elseif($aiUnavailable)Not available
                    @else <span class="italic text-slate-400">Not provided</span>
                    @endif
                  </span>
                  <div class="review-description-diff" data-description-diff data-original="{{ $residentDescription }}" data-ai=""></div>
                @endif
              </div>
            </div>
          </div>

          <div class="flex flex-wrap items-center gap-3 text-xs font-bold">
            <button type="button" class="text-slate-600 hover:text-blue-700" data-use-original @disabled($emergencyActionOnly)>Use original</button>
            <button type="button" class="text-blue-600 hover:underline" data-use-ai @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>Use AI suggestion</button>
            <button type="button" class="text-emerald-600 hover:underline" data-accept-row @disabled($emergencyActionOnly) @if(! $aiDescriptionPresent) hidden @endif>Accept</button>
            <button type="button" class="text-slate-500 hover:underline" data-undo-row hidden>Undo</button>
          </div>

          <div>
            <label for="final-description" class="block text-[11px] font-bold uppercase tracking-wider text-slate-600 mb-1">
              Final Description (Official Record)
            </label>
            <textarea id="final-description" name="staff_description" rows="3" maxlength="2000"
                      class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-500/30 resize-none"
                      data-final-control @disabled($emergencyActionOnly)>{{ $finalDescription }}</textarea>
          </div>
        </div>

        {{-- Classification override reason: required when staff depart from a concrete AI suggestion. --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3.5 space-y-1.5" data-override-reason-wrap>
          <label for="override-reason" class="block text-xs font-extrabold text-amber-900">
            Reason for changing the AI recommendation
            <span class="font-normal text-amber-800">(required when you change the AI-suggested category or urgency)</span>
          </label>
          <textarea id="override-reason" name="override_reason" rows="2" maxlength="500" @disabled($emergencyActionOnly)
                    class="w-full rounded-lg border border-amber-300 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-400/40 resize-none"
                    placeholder="e.g. Resident described a blocked drainage canal, not the road damage the AI suggested.">{{ old('override_reason') }}</textarea>
          <p class="text-[11px] text-amber-800 m-0">Recorded in the review history with your name and timestamp. Accepting the AI suggestion needs no reason.</p>
        </div>

        {{-- Geographic jurisdiction assessment (separate from GIS result). --}}
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-3.5 space-y-1.5">
          <label for="jurisdiction-status" class="block text-xs font-extrabold text-slate-800">
            Incident jurisdiction assessment
          </label>
          <p class="text-[11px] text-slate-500 m-0">
            @if($report->lat !== null && $report->lng !== null)
              Coordinates: {{ number_format($report->lat, 6) }}, {{ number_format($report->lng, 6) }} (original submission)
            @else
              No coordinates submitted.
            @endif
            @if($report->resident_purok_id)
              · Resident-selected Purok: {{ $report->residentPurok?->name }}
            @endif
            @if($report->purok_id && $report->purok_id !== $report->resident_purok_id)
              · Current operational Purok: {{ $report->purok?->name }}
            @endif
          </p>
          <select id="jurisdiction-status" name="jurisdiction_status" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs text-slate-900">
            <option value="">Not assessed</option>
            <option value="within" @selected(old('jurisdiction_status', $report->jurisdiction_status) === 'within')>Within Barangay Calarian</option>
            <option value="outside" @selected(old('jurisdiction_status', $report->jurisdiction_status) === 'outside')>Outside Barangay Calarian</option>
            <option value="uncertain" @selected(old('jurisdiction_status', $report->jurisdiction_status) === 'uncertain')>Uncertain / needs verification</option>
          </select>
          <textarea id="jurisdiction-note" name="jurisdiction_note" rows="2" maxlength="1000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900" placeholder="Explanation / justification (required for outside or uncertain).">{{ old('jurisdiction_note', $report->jurisdiction_note) }}</textarea>
          <p class="text-[11px] text-slate-600 m-0">GIS results inform this decision; staff make the final assessment. Outside/uncertain requires a justification.</p>
        </div>

        @if($aiReady && ($aiResult->photo_description || $aiResult->key_facts))
          <details class="rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-xs">
            <summary class="font-bold text-slate-700 cursor-pointer">AI details</summary>
            @if($aiResult->photo_description)
              <p class="mt-2 mb-1 text-slate-700"><strong>Photo description:</strong> {{ $aiResult->photo_description }}</p>
            @endif
            @if($aiResult->key_facts)
              <ul class="list-disc pl-4 mt-1 mb-0 text-slate-700 space-y-0.5">
                @foreach($aiResult->key_facts as $fact)
                  <li>{{ $fact }}</li>
                @endforeach
              </ul>
            @endif
          </details>
        @endif

        {{-- Step 1 Footer Action Bar --}}
        <div class="border-t border-slate-100 pt-4 mt-2 flex flex-wrap items-center justify-between gap-3">
          <div class="flex items-center gap-2">
            <span class="review-gate-hint text-xs" data-gate-reason aria-live="polite"></span>
          </div>
          <div class="flex flex-wrap items-center gap-2">
            @if($canOpenInfoChat)
              <button type="button" class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2 transition"
                      data-chat-open data-report-id="{{ $report->id }}"
                      data-chat-tracking="{{ $report->tracking_code }}"
                      data-chat-category="{{ $report->category?->name ?? 'Category not assigned' }}"
                      data-chat-status="{{ \App\Models\Report::STATUSES[$report->status] ?? $report->status }}"
                      data-chat-summary="{{ $report->description }}{{ $report->purok ? ' — '.$report->purok->name : '' }}">
                Ask Reporter for More Info
              </button>
            @endif
            <button type="button" id="rw-btn-to-step2"
                    class="inline-flex items-center gap-2 rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs sm:text-sm font-bold px-4 py-2.5 shadow-xs transition">
              <span>Continue to Route &amp; Assign</span>
              <i class="fa-solid fa-arrow-right text-xs" aria-hidden="true"></i>
            </button>
          </div>
        </div>
      </div>
