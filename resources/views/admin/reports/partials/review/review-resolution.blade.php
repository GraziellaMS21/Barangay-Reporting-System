      {{-- ============================================================= --}}
      {{-- STEP 3 PANEL: WORK & RESOLVE                                  --}}
      {{-- ============================================================= --}}
      <div class="rw-step-panel rw-fade-in p-5 space-y-4" data-workspace-panel="3" @if($defaultWorkspaceStep !== 3) hidden @endif>
        @if(! $isAfterRoute && ! $emergencyActionOnly)
          <div class="rounded-xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center space-y-2.5">
            <div class="text-sm font-bold text-slate-800">Report Not Yet Routed</div>
            <p class="text-xs text-slate-600 max-w-md mx-auto m-0">
              Complete Step 1 (Review &amp; Triage) and Step 2 (Route &amp; Assign) first before logging field progress or resolution proof.
            </p>
            <button type="button" class="rounded-xl bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-4 py-2 transition"
                    onclick="document.querySelector('[data-workspace-step=\'2\']').click()">
              Go to Route &amp; Assign
            </button>
          </div>
        @elseif($emergencyActionOnly)
          <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs text-rose-900 space-y-2">
            <strong>Emergency Handling Required</strong>
            <p class="m-0">Use the emergency banner above to either mark as <em>Not an emergency</em>, <em>Handle Locally (Tanod Response)</em>, or redirect to 911 via <em>Take No Action</em>.</p>
          </div>
        @else
          {{-- Active Work Header Strip --}}
          <div class="rounded-xl border border-blue-200 bg-blue-50/70 p-3.5 flex flex-wrap items-center justify-between gap-2">
            <div class="text-xs text-slate-800">
              <div class="font-bold text-sm text-slate-900">{{ $report->office?->name ?? 'Office not assigned' }}</div>
              <div>Assigned to: <strong>{{ $report->assignee?->name ?? 'Unassigned' }}</strong> · {{ $report->category?->name ?? 'Uncategorized' }} · {{ $report->urgency ?? 'Normal' }}</div>
            </div>
            <div class="flex items-center gap-2">
              <x-status-badge :status="$report->status" />
              @if($report->due_at)
                <span class="rounded-full px-2.5 py-0.5 text-[11px] font-bold {{ $isOverdue ? 'bg-rose-100 text-rose-800' : 'bg-white text-blue-700 border border-blue-200' }}">
                  {{ $isOverdue ? 'Overdue' : 'Due '.$report->due_at->format('M d') }}
                </span>
              @endif
            </div>
          </div>

          @if(in_array($report->status, ['routed', 'in_progress', 'needs_info'], true))
            @php
              $updateTypes = \App\Models\Report::UPDATE_TYPES;
              $defaultUpdateType = array_key_first($updateTypes);
              $selectedUpdateType = old('update_type', $defaultUpdateType);
              if (! isset($updateTypes[$selectedUpdateType])) {
                  $selectedUpdateType = $defaultUpdateType;
              }
              $selectedUpdateMeta = $updateTypes[$selectedUpdateType];
              $defaultUpdateMessage = old('message', $selectedUpdateMeta['template'] ?? '');
              $openResolutionForm = $errors->has('note') || $errors->has('proof_photos') || $errors->has('proof_photos.*');
            @endphp

            {{-- Log Progress Update --}}
            <form method="POST" action="{{ route('admin.reports.updates.store', $report) }}" enctype="multipart/form-data" class="rounded-xl border border-slate-200 p-4 space-y-3" data-prevent-double-submit>
              @csrf
              <div class="flex items-center justify-between gap-2 flex-wrap">
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-700 m-0">Log Progress Update</h3>
                <div class="flex flex-wrap gap-1.5">
                  @foreach(['Work started' => 'Field crew on-site; work has started.', 'Inspection scheduled' => 'Site inspection has been scheduled.', 'Waiting on materials' => 'Waiting on required materials for repair.'] as $chipLabel => $chipMsg)
                    <button type="button" class="btn-quick-fill text-[11px] font-semibold px-2.5 py-1 rounded-full bg-slate-100 hover:bg-blue-50 text-slate-700 border border-slate-200 transition"
                            data-chip-target="update-message" data-chip-text="{{ $chipMsg }}">
                      {{ $chipLabel }}
                    </button>
                  @endforeach
                </div>
              </div>

              <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                  <label for="update-type" class="block text-xs font-bold text-slate-700 mb-1">Update type</label>
                  <select id="update-type" name="update_type" required data-update-type-select class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                    @foreach($updateTypes as $typeKey => $typeMeta)
                      <option value="{{ $typeKey }}" data-template="{{ $typeMeta['template'] }}" data-message-required="{{ $typeMeta['message_required'] ? '1' : '0' }}" @selected($selectedUpdateType === $typeKey)>
                        {{ $typeMeta['label'] }} ({{ \App\Models\Report::STATUSES[$typeMeta['status']] ?? $typeMeta['status'] }})
                      </option>
                    @endforeach
                  </select>
                </div>
                <div>
                  <label for="expected-completion-date" class="block text-xs font-bold text-slate-700 mb-1">Expected completion date <span class="text-slate-400 font-normal">(optional)</span></label>
                  <input id="expected-completion-date" type="date" name="expected_completion_date" min="{{ now()->toDateString() }}" value="{{ old('expected_completion_date') }}" class="w-full h-10 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                </div>
              </div>

              <div>
                <label for="update-message" class="block text-xs font-bold text-slate-700 mb-1">
                  Message to resident <span class="text-slate-400 font-normal" data-update-message-optional>{{ $selectedUpdateMeta['message_required'] ? '(required)' : '(optional)' }}</span>
                </label>
                <textarea id="update-message" name="message" rows="2" maxlength="2000" data-update-message @if($selectedUpdateMeta['message_required']) required @endif
                          class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ $defaultUpdateMessage }}</textarea>
              </div>

              <div class="review-form-field">
                <label for="update-photos" class="block text-xs font-bold text-slate-700 mb-1">Progress photos <span class="text-slate-400 font-normal">(optional · max {{ $proofConfig['max_files'] }}, {{ round($proofConfig['max_size_kb'] / 1024) }} MB each)</span></label>
                <input id="update-photos" type="file" name="photos[]" multiple accept="{{ $proofConfig['accept'] }}"
                       data-proof-input data-max-files="{{ $proofConfig['max_files'] }}" data-max-size-kb="{{ $proofConfig['max_size_kb'] }}" class="text-xs">
                <ul class="review-proof-errors" data-proof-errors hidden></ul>
                <div class="review-proof-preview" data-proof-preview></div>
              </div>

              <div class="flex flex-wrap items-center justify-between gap-2 pt-1">
                <div>
                  <input type="hidden" name="visible_to_resident" value="0">
                  <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="visible_to_resident" value="1" @checked(old('visible_to_resident', '1') == '1')>
                    <span>Visible to resident</span>
                  </label>
                </div>
                <div class="flex items-center gap-2">
                  <button type="submit" class="rounded-xl bg-slate-800 hover:bg-slate-900 text-white text-xs font-bold px-3.5 py-2 transition" data-submit-once>Post update</button>
                  <button type="button" class="rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 text-xs font-bold px-3.5 py-2 transition"
                          data-toggle-resolution aria-expanded="{{ $openResolutionForm ? 'true' : 'false' }}" aria-controls="resolution-form-panel">
                    Mark resolved
                  </button>
                </div>
              </div>
            </form>

            {{-- Upload Proof Photos & Propose Resolution --}}
            <div id="resolution-form-panel" class="rounded-xl border border-emerald-200 bg-emerald-50/30 p-4 space-y-3" data-resolution-panel @if(! $openResolutionForm) hidden @endif>
              <form method="POST" action="{{ route('admin.reports.resolve', $report) }}" enctype="multipart/form-data" class="space-y-3" data-prevent-double-submit>
                @csrf
                <h3 class="text-xs font-extrabold uppercase tracking-wider text-emerald-900 m-0">Upload Proof Photos &amp; Propose Resolution</h3>
                <div>
                  <label for="resolution-note" class="block text-xs font-bold text-slate-700 mb-1">Resolution note <span class="text-rose-600">*</span></label>
                  <textarea id="resolution-note" name="note" rows="3" required maxlength="2000" class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs">{{ old('note') }}</textarea>
                  @error('note')<span class="review-field-error">{{ $message }}</span>@enderror
                </div>
                <div class="review-form-field">
                  <label for="proof-photos" class="block text-xs font-bold text-slate-700 mb-1">Proof photos <span class="text-slate-400 font-normal">(up to {{ $proofConfig['max_files'] }} images, {{ round($proofConfig['max_size_kb'] / 1024) }} MB each)</span></label>
                  <input id="proof-photos" type="file" name="proof_photos[]" multiple accept="{{ $proofConfig['accept'] }}"
                         data-proof-input data-max-files="{{ $proofConfig['max_files'] }}" data-max-size-kb="{{ $proofConfig['max_size_kb'] }}" class="text-xs">
                  <ul class="review-proof-errors" data-proof-errors hidden></ul>
                  <div class="review-proof-preview" data-proof-preview></div>
                </div>
                <div class="flex justify-end">
                  <button type="submit" class="rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold px-4 py-2.5 transition" data-submit-once>
                    <i class="fa-solid fa-check-circle mr-1" aria-hidden="true"></i> Submit resolution proof
                  </button>
                </div>
              </form>

              @if($report->proofPhotos->isNotEmpty())
                <div class="pt-3 border-t border-emerald-200">
                  <h4 class="text-xs font-bold text-slate-700 mb-2">Saved proof photos</h4>
                  <div class="review-proof-preview">
                    @foreach($report->proofPhotos as $proofPhoto)
                      <div class="review-proof-item">
                        <a href="{{ route('admin.reports.proof-photos.show', [$report, $proofPhoto]) }}" target="_blank" rel="noopener">
                          <img src="{{ route('admin.reports.proof-photos.show', [$report, $proofPhoto]) }}" alt="Saved proof photo">
                        </a>
                        <form method="POST" action="{{ route('admin.reports.proof-photos.destroy', [$report, $proofPhoto]) }}"
                              data-confirm-delete="Delete this proof photo? This cannot be undone.">
                          @csrf
                          @method('DELETE')
                          <button type="submit" class="review-proof-delete" aria-label="Delete proof photo">&times;</button>
                        </form>
                      </div>
                    @endforeach
                  </div>
                </div>
              @endif
            </div>

          @elseif($report->status === 'awaiting_confirmation')
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-900">
              Waiting for the resident to confirm the resolution on the tracking page.
            </div>
            @if($report->due_at && $report->due_at->copy()->addDays($autoCloseDays)->isPast() && auth()->user()?->role === 'admin')
              <form method="POST" action="{{ route('admin.reports.close', $report) }}" class="rounded-xl border border-slate-200 p-4 space-y-2" data-prevent-double-submit>
                @csrf
                <label for="close-justification" class="block text-xs font-bold text-slate-700">Manual Resolution Justification <span class="text-rose-600">*</span></label>
                <textarea id="close-justification" name="justification" rows="2" required minlength="10" maxlength="1000" class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ old('justification') }}</textarea>
                <button type="submit" class="rounded-xl bg-slate-800 text-white text-xs font-bold px-3.5 py-2" data-submit-once>Finalize resolution</button>
              </form>
            @endif

          @elseif($report->status === 'referred')
            @php $referral = $report->activeReferral; $referralOffice = $referral?->office; @endphp
            @if($referral)
              <div class="rounded-xl border border-indigo-200 bg-indigo-50/40 p-4 space-y-3" data-referral-panel data-letter="{{ $referral->referral_text }}">
                <div class="flex flex-wrap items-center justify-between gap-2">
                  <div class="flex items-center gap-2 flex-wrap">
                    <span class="font-mono text-xs font-bold text-indigo-800 bg-white border border-indigo-200 px-2 py-0.5 rounded">{{ $referral->reference_no }}</span>
                    <strong class="text-xs text-slate-900">{{ $referralOffice?->name ?? 'Unknown agency' }}</strong>
                  </div>
                  <a class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3 py-1.5" href="{{ route('admin.referrals.letter', $referral) }}" target="_blank" rel="noopener" data-turbo="false">
                    <i class="fa-solid fa-print mr-1" aria-hidden="true"></i> Print / Save as PDF
                  </a>
                </div>
                @if($referral->status === 'prepared')
                  <form method="POST" action="{{ route('admin.referrals.mark-sent', $referral) }}" class="space-y-2" data-prevent-double-submit>
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                      <input type="date" name="sent_at" value="{{ now()->toDateString() }}" class="h-9 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                      <select name="sent_via" class="h-9 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                        <option value="">Sent via (optional)</option>
                        @foreach(\App\Models\Referral::SENT_VIA as $sentViaKey => $sentViaLabel)
                          <option value="{{ $sentViaKey }}">{{ $sentViaLabel }}</option>
                        @endforeach
                      </select>
                    </div>
                    <button type="submit" class="rounded-lg bg-blue-600 text-white text-xs font-bold px-3.5 py-1.5" data-submit-once>Mark as sent</button>
                  </form>
                @elseif($referral->isOpen())
                  <form method="POST" action="{{ route('admin.referrals.updates.store', $referral) }}" enctype="multipart/form-data" class="space-y-2" data-prevent-double-submit>
                    @csrf
                    <textarea name="note" rows="2" required maxlength="2000" placeholder="What the agency reported..." class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs"></textarea>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                      <select name="channel" required class="h-9 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                        @foreach(\App\Models\Referral::CHANNELS as $channelKey => $channelLabel)
                          <option value="{{ $channelKey }}">{{ $channelLabel }}</option>
                        @endforeach
                      </select>
                      <select name="status" class="h-9 rounded-lg border border-slate-300 bg-white px-2.5 text-xs">
                        <option value="">Keep current status</option>
                        @foreach(\App\Models\Referral::UPDATE_TARGET_STATUSES as $updateStatus)
                          <option value="{{ $updateStatus }}">{{ \App\Models\Referral::STATUSES[$updateStatus] ?? $updateStatus }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="flex items-center justify-between gap-2">
                      <button type="submit" class="rounded-lg bg-blue-600 text-white text-xs font-bold px-3 py-1.5" data-submit-once>Log update</button>
                      <button type="button" class="rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold px-3 py-1.5" data-open-dialog="close-referral-modal">Close referral</button>
                    </div>
                  </form>

                  <dialog id="close-referral-modal" class="review-modal" @if($errors->has('outcome')) data-open-on-load @endif>
                    <form method="POST" action="{{ route('admin.referrals.close', $referral) }}" data-prevent-double-submit>
                      @csrf
                      <header class="review-modal-header">
                        <strong><i class="fa-solid fa-circle-check" aria-hidden="true"></i> Close referral</strong>
                        <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
                      </header>
                      <div class="review-modal-body space-y-2">
                        <select name="outcome" required class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs">
                          @foreach(\App\Models\Referral::OUTCOMES as $outcomeKey => $outcomeLabel)
                            <option value="{{ $outcomeKey }}">{{ $outcomeLabel }}</option>
                          @endforeach
                        </select>
                        <textarea name="note" rows="3" required maxlength="2000" placeholder="Summarize the final outcome..." class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></textarea>
                      </div>
                      <footer class="review-modal-footer">
                        <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
                        <button type="submit" class="review-button review-button-primary" data-submit-once>Close referral</button>
                      </footer>
                    </form>
                  </dialog>
                @endif
              </div>
            @endif

          @elseif(in_array($report->status, ['resolved', 'no_action'], true))
            @if($report->status === 'resolved')
              <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs text-emerald-900 space-y-1">
                <div class="font-bold">Resolved @if($report->resolved_at) on {{ $report->resolved_at->format('M j, Y g:i A') }} @endif</div>
                @if($report->satisfaction_rating)
                  <div>Resident rating: <strong>{{ $report->satisfaction_rating }} / 5</strong> @if($report->satisfaction_comment) — “{{ $report->satisfaction_comment }}” @endif</div>
                @endif
              </div>
            @endif
            <form method="POST" action="{{ route('admin.reports.reopen', $report) }}" class="rounded-xl border border-slate-200 p-4 space-y-2" data-prevent-double-submit>
              @csrf
              <label for="reopen-reason" class="block text-xs font-bold text-slate-700">Reason for reopening</label>
              <textarea id="reopen-reason" name="reason" rows="2" required minlength="5" maxlength="1000" placeholder="Explain why this report is being reopened..." class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs">{{ old('reason') }}</textarea>
              <button type="submit" class="rounded-xl border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3.5 py-2" data-submit-once>Reopen report</button>
            </form>
          @elseif($report->status === 'merged')
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-4 text-xs text-slate-700 space-y-2">
              <p class="m-0">This report was merged as a duplicate.</p>
              <button type="button" class="rounded-lg border border-slate-300 bg-white text-slate-700 text-xs font-bold px-3 py-1.5" data-open-dialog="unmerge-modal">Unmerge report</button>
            </div>
          @endif
        @endif
      </div>