      {{-- ============================================================= --}}
      {{-- STEP 2 PANEL: ROUTE & ASSIGN                                  --}}
      {{-- ============================================================= --}}
      <div class="rw-step-panel rw-fade-in p-5 space-y-4" data-workspace-panel="2" aria-label="Route and assign" @if($defaultWorkspaceStep !== 2) hidden @endif>
        @if(! $isAfterRoute && ! $emergencyActionOnly)
          @php
            $ruleWithin = (bool) ($routingDecision['within_authority'] ?? ($jurisdiction['within_authority'] ?? true));
            $initialMode = in_array(old('authority_mode'), ['within', 'beyond'], true)
                ? old('authority_mode')
                : ($ruleWithin ? 'within' : 'beyond');
            $isInitiallyWithin = $initialMode === 'within';
            $preferredExternalId = (old('office_id') && $externalOffices->contains('id', (int) old('office_id')))
                ? (int) old('office_id')
                : (int) ($routingDecision['office_id'] ?? $jurisdiction['office']?->id ?? 0);
            $defaultAgency = $externalOffices->firstWhere('id', $preferredExternalId) ?? $externalOffices->first();
            $defaultLetter = old('referral_letter', $letterTemplates[$defaultAgency?->id]['letter'] ?? '');
            $defaultResidentMessage = old('resident_message', $letterTemplates[$defaultAgency?->id]['resident_message'] ?? '');
            $defaultAgencyId = $defaultAgency?->id;
            $matchedRuleName = $routingDecision['reason'] ?? ($jurisdiction['jurisdiction_note'] ?? 'Category default');
            $isSuggestedMatch = $routingDecision && (string) $officeDefault === (string) ($routingDecision['office_id'] ?? '');
            $residentMessageChips = [
              'within' => [
                'The assigned office will inspect within 3 working days.',
                'Your report has been routed to the department in charge.',
                'Staff will assess the site and schedule necessary action.',
                'Work is being scheduled for this report.',
              ],
              'beyond' => [
                'We have referred this report to the appropriate agency.',
                'We will monitor this external referral and follow up.',
                'Your report is under review with the agency.',
                'We will update you as soon as the agency responds.',
              ],
            ];
          @endphp

          <input type="hidden" name="authority_mode" value="{{ $initialMode }}" data-authority-mode-input>
          <input type="hidden" name="jurisdiction_mode_override" value="0" data-jurisdiction-override-input>
          <input type="hidden" name="agency_office_id" value="{{ $defaultAgencyId }}" data-agency-hidden-input>
          <script type="application/json" data-routing-map>@json($routingDecisions)</script>
          <script type="application/json" data-letter-templates>@json($letterTemplates)</script>

          {{-- Jurisdiction Banner & Mode Switcher --}}
          <div class="review-mode-banner rounded-xl border border-l-[3px] p-4 flex flex-col md:flex-row md:items-center justify-between gap-3 transition-all {{ $isInitiallyWithin ? 'border-l-emerald-600 bg-emerald-50/60 border-emerald-200' : 'border-l-indigo-600 bg-indigo-50/60 border-indigo-200' }}"
               data-mode-banner data-rule-mode="{{ $ruleWithin ? 'within' : 'beyond' }}">
            <div class="min-w-0">
              <div class="flex items-center gap-2 flex-wrap">
                <strong class="text-sm font-bold text-slate-900" data-mode-badge>{{ $isInitiallyWithin ? 'Within barangay authority' : 'Beyond barangay authority' }}</strong>
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold border bg-white text-slate-700 border-slate-200" data-urgency-pill>
                  <span class="w-2 h-2 rounded-full bg-slate-400" data-urgency-dot></span>
                  <span>Urgency: <span data-urgency-label>{{ $priorityLabel }}</span></span>
                </span>
              </div>
              <p class="text-xs text-slate-600 mt-1 mb-0" data-mode-note>
                @if($isInitiallyWithin)
                  Matched rule: <span data-matched-rule-text>{{ $matchedRuleName }}</span>. Assign it to an office and staff member.
                @else
                  External referral required. A referral letter is prepared and 3-day follow-up is tracked.
                @endif
              </p>
            </div>

            <div class="inline-flex p-1 bg-slate-200/70 rounded-lg text-xs self-start md:self-center flex-shrink-0" role="tablist" aria-label="Authority mode toggle">
              <button type="button" class="mode-seg-btn px-3 py-1.5 rounded-md font-bold transition {{ $isInitiallyWithin ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600' }}" data-mode-btn="within">
                Within barangay
              </button>
              <button type="button" class="mode-seg-btn px-3 py-1.5 rounded-md font-bold transition {{ !$isInitiallyWithin ? 'bg-white text-slate-900 shadow-xs' : 'text-slate-600' }}" data-mode-btn="beyond">
                Refer to agency
              </button>
            </div>
            <button type="button" class="sr-only" data-mode-toggle data-other-mode="{{ $isInitiallyWithin ? 'beyond' : 'within' }}">Toggle mode</button>
          </div>

          {{-- Mode A: Internal Dispatch --}}
          <div class="review-mode-block space-y-4" data-mode-block="within" @if(!$isInitiallyWithin) hidden @endif>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label for="office_id" class="text-xs font-bold text-slate-700 flex items-center gap-2 mb-1">
                  <span>Office</span>
                  <span class="inline-flex items-center gap-1 text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200" data-suggested-badge @if(!$isSuggestedMatch) style="display:none;" @endif>
                    <i class="fa-solid fa-check text-[9px]" aria-hidden="true"></i> Suggested
                  </span>
                </label>
                <select id="office_id" name="office_id" class="w-full h-11 text-xs rounded-lg border border-slate-300 bg-white px-2.5" data-office-select @disabled(!$isInitiallyWithin)>
                  <option value="">Select an office</option>
                  @foreach($offices as $office)
                    <option value="{{ $office->id }}" data-default-assignee="{{ $office->default_assignee_id ?? '' }}" @selected((string) $officeDefault === (string) $office->id)>{{ $office->name }}</option>
                  @endforeach
                </select>
                <p class="text-[11px] text-slate-500 mt-1 mb-0">The department that handles this report</p>
                <div class="text-[11px] text-blue-700 font-medium mt-1.5" data-routing-reason>
                  @if($routingDecision)
                    Rule: {{ $categories->firstWhere('id', $selectedCategoryId)?->name }} → {{ $routingDecision['office_name'] ?? 'No office' }}
                  @else
                    Select a category to see the routing recommendation.
                  @endif
                </div>
              </div>

              <div>
                <label for="assigned_to" class="block text-xs font-bold text-slate-700 mb-1">Assigned to</label>
                <select id="assigned_to" name="assigned_to" class="w-full h-11 text-xs rounded-lg border border-slate-300 bg-white px-2.5" data-assigned-select>
                  <option value="">Unassigned</option>
                  @foreach($staffUsers as $staffUser)
                    <option value="{{ $staffUser->id }}" @selected((string) $assignedDefault === (string) $staffUser->id)>{{ $staffUser->name }}</option>
                  @endforeach
                </select>
                <p class="text-[11px] text-slate-500 mt-1 mb-0">The staff member responsible for follow-through</p>
                <div class="flex items-center gap-1.5 text-[11px] text-amber-700 font-medium mt-1.5" data-unassigned-note @if($assignedDefault) style="display:none;" @endif>
                  <i class="fa-solid fa-circle-exclamation text-amber-600" aria-hidden="true"></i>
                  <span>Optional — leaving Unassigned will not block routing.</span>
                </div>
                <input type="text" name="assign_reason" class="w-full h-9 text-xs rounded-lg border border-slate-300 bg-white px-2.5 mt-2" placeholder="Reason if replacing an existing assignee">
                <p class="text-[11px] text-slate-500 mt-1 mb-0">Assignment is responsibility, not acceptance. Acceptance happens when the report is routed or referred.</p>
              </div>
            </div>

            <div>
              <label for="review_note" class="block text-xs font-bold text-slate-700 mb-1">Message to resident <span class="text-slate-400 font-normal">(optional)</span></label>
              <div class="flex items-center gap-1.5 flex-wrap mb-2">
                @foreach($residentMessageChips['within'] as $chip)
                  <button type="button" class="btn-quick-fill text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 text-slate-700 border border-slate-200 transition" data-chip-target="review_note" data-chip-text="{{ $chip }}">
                    {{ $chip }}
                  </button>
                @endforeach
              </div>
              <textarea id="review_note" name="review_note" rows="2" maxlength="1000" class="w-full text-xs rounded-lg border border-slate-300 p-2.5" placeholder="e.g. The assigned office will inspect within 3 working days.">{{ $defaultMessage }}</textarea>
            </div>
          </div>

          {{-- Mode B: External Agency Referral --}}
          <div class="review-mode-block space-y-4" data-mode-block="beyond" @if($isInitiallyWithin) hidden @endif>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <div class="flex items-center justify-between mb-1">
                  <label for="agency_id" class="text-xs font-bold text-slate-700">External Agency</label>
                  <span class="rounded-full bg-indigo-50 text-indigo-700 border border-indigo-200 px-2 py-0.5 text-[10px] font-bold">3-day follow-up</span>
                </div>
                <select id="agency_id" name="office_id" class="w-full h-11 text-xs rounded-lg border border-slate-300 bg-white px-2.5" data-agency-select @disabled($isInitiallyWithin)>
                  <option value="">Select an agency</option>
                  @foreach($externalOffices as $externalOffice)
                    <option value="{{ $externalOffice->id }}" @selected((string) $defaultAgencyId === (string) $externalOffice->id)>{{ $externalOffice->name }}</option>
                  @endforeach
                </select>
              </div>
              <div>
                <label for="follow_up_owner_id" class="block text-xs font-bold text-slate-700 mb-1">Follow-up owner</label>
                <select id="follow_up_owner_id" name="follow_up_owner_id" class="w-full h-11 text-xs rounded-lg border border-slate-300 bg-white px-2.5" @disabled($isInitiallyWithin)>
                  <option value="">Unassigned</option>
                  @foreach($staffUsers as $staffUser)
                    <option value="{{ $staffUser->id }}" @selected((string) old('follow_up_owner_id', $currentUserId) === (string) $staffUser->id)>{{ $staffUser->name }}</option>
                  @endforeach
                </select>
              </div>
            </div>

            <div>
              <label for="referral_letter" class="block text-xs font-bold text-slate-700 mb-1">Editable Referral Endorsement Letter</label>
              <textarea id="referral_letter" name="referral_letter" rows="7" maxlength="20000"
                        class="w-full text-xs font-mono p-3 rounded-lg border border-slate-300"
                        data-referral-letter @disabled($isInitiallyWithin)>{{ $defaultLetter }}</textarea>
            </div>

            <div>
              <label for="resident_message" class="block text-xs font-bold text-slate-700 mb-1">Message to resident <span class="text-slate-400 font-normal">(optional)</span></label>
              <div class="flex items-center gap-1.5 flex-wrap mb-2">
                @foreach($residentMessageChips['beyond'] as $chip)
                  <button type="button" class="btn-quick-fill text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 text-slate-700 border border-slate-200 transition" data-chip-target="resident_message" data-chip-text="{{ $chip }}">
                    {{ $chip }}
                  </button>
                @endforeach
              </div>
              <textarea id="resident_message" name="resident_message" rows="2" maxlength="1000" class="w-full text-xs rounded-lg border border-slate-300 p-2.5" data-resident-message @disabled($isInitiallyWithin)>{{ $defaultResidentMessage }}</textarea>
            </div>
          </div>

          {{-- Step 2 Footer Action Bar --}}
          <div class="border-t border-slate-100 pt-4 mt-2 flex flex-wrap items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-wrap">
              <button type="button" id="rw-btn-back-step1" class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2 transition">
                &larr; Back to Review
              </button>
              <span class="rounded-full bg-slate-100 text-slate-700 px-3 py-1 text-xs font-bold" data-action-summary-text>
                @if($isInitiallyWithin)
                  Route to: {{ $officeDefault ? ($offices->firstWhere('id', $officeDefault)?->name ?? 'selected office') : 'selected office' }} · {{ $priorityLabel }}
                @else
                  Refer to: {{ $defaultAgency?->name ?? 'external agency' }} · {{ $priorityLabel }}
                @endif
              </span>
            </div>

            <div class="flex items-center gap-2">
              @if($isRouteable)
                <button type="submit" form="review-form" name="intent" value="route"
                        class="btn btn-gold text-xs font-extrabold py-2.5 px-4 rounded-xl inline-flex items-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                        data-route-submit @if(!$isInitiallyWithin) style="display:none;" @endif>
                  <i class="fa-solid fa-check" aria-hidden="true"></i>
                  <span>Confirm and route</span>
                </button>
              @endif
              <button type="submit" form="review-form" name="intent" value="referral"
                      class="btn btn-gold text-xs font-extrabold py-2.5 px-4 rounded-xl inline-flex items-center gap-1.5 shadow-sm disabled:opacity-50 disabled:cursor-not-allowed"
                      data-referral-submit @if($isInitiallyWithin) style="display:none;" @endif>
                <i class="fa-solid fa-paper-plane" aria-hidden="true"></i>
                <span>Prepare referral</span>
              </button>
            </div>
          </div>
        @else
          <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700 space-y-2">
            <div><strong>Current Routing:</strong> {{ $report->office?->name ?? 'Not assigned' }} · <strong>Assigned to:</strong> {{ $report->assignee?->name ?? 'Unassigned' }}</div>
            @if(! $emergencyActionOnly)
              <button type="button" class="rounded-lg bg-blue-600 text-white text-xs font-bold px-3 py-1.5" onclick="document.querySelector('[data-workspace-step=\'3\']').click()">
                Go to Work &amp; Resolve &rarr;
              </button>
            @endif
          </div>
        @endif
      </div>