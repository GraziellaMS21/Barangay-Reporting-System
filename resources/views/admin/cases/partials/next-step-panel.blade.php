@php
    $milestones = $nextStep['milestones'] ?? [];
    $clock = $nextStep['mediation_clock'] ?? null;
    $repudiation = $nextStep['repudiation_countdown'] ?? null;
    $reminderLine = $nextStep['reminder_line'] ?? null;
    $moreActions = $nextStep['more_actions'] ?? [];
    $primary = $nextStep['primary_action'] ?? null;
    $statusPill = $nextStep['status_pill'] ?? null;
    $nextAttemptNo = $nextStep['next_attempt_no'] ?? 1;
@endphp

<div class="space-y-4">
    <!-- Top-Level High-Level Stage Stepper (4 Stages) -->
    <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-xs">
        <div class="flex items-center justify-between gap-2 overflow-x-auto pb-1">
            @php
                $isResolved = in_array($case->status, [\App\Support\CaseStatus::SETTLED, \App\Support\CaseStatus::ESCALATED, \App\Support\CaseStatus::DISMISSED, \App\Support\CaseStatus::WITHDRAWN], true);
            @endphp

            <div class="flex items-center gap-2 shrink-0">
                <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold"><i class="fa-solid fa-check"></i></span>
                <span class="text-xs font-semibold text-emerald-800">1. Review</span>
            </div>
            <div class="h-px bg-emerald-200 flex-1 min-w-[20px]"></div>

            <div class="flex items-center gap-2 shrink-0">
                <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold"><i class="fa-solid fa-check"></i></span>
                <span class="text-xs font-semibold text-emerald-800">2. Docket</span>
            </div>
            <div class="h-px {{ $isResolved ? 'bg-emerald-200' : 'bg-blue-200' }} flex-1 min-w-[20px]"></div>

            <div class="flex items-center gap-2 shrink-0">
                @if($isResolved)
                    <span class="w-6 h-6 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold"><i class="fa-solid fa-check"></i></span>
                    <span class="text-xs font-semibold text-emerald-800">3. Mediation</span>
                @else
                    <span class="w-6 h-6 rounded-full bg-blue-600 text-white flex items-center justify-center text-xs font-bold">3</span>
                    <span class="text-xs font-bold text-blue-800">3. Mediation</span>
                @endif
            </div>
            <div class="h-px {{ $isResolved ? 'bg-emerald-200' : 'bg-slate-200' }} flex-1 min-w-[20px]"></div>

            <div class="flex items-center gap-2 shrink-0">
                @if($isResolved)
                    <span class="w-6 h-6 rounded-full bg-emerald-600 text-white flex items-center justify-center text-xs font-bold"><i class="fa-solid fa-flag-checkered text-[10px]"></i></span>
                    <span class="text-xs font-bold text-emerald-800">4. Resolve</span>
                @else
                    <span class="w-6 h-6 rounded-full bg-slate-100 text-slate-400 flex items-center justify-center text-xs font-bold">4</span>
                    <span class="text-xs font-medium text-slate-400">4. Resolve</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Next Step Card: Exactly ONE primary button per state -->
    <div class="bg-white rounded-xl border border-blue-200/90 shadow-sm overflow-visible">
        <div class="bg-gradient-to-r from-blue-50/90 via-indigo-50/50 to-white px-5 py-4 border-b border-blue-100 rounded-t-xl">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-1">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                        Action Required
                    </span>
                    <h3 class="text-base font-bold text-slate-900">{{ $nextStep['title'] }}</h3>
                </div>

                <!-- Right Controls: ONE Primary Button (or wait pill) + More Menu -->
                <div class="flex items-center gap-2 shrink-0">
                    @if(!empty($statusPill))
                        <span class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold bg-blue-50 text-blue-800 border border-blue-200">
                            <i class="fa-regular fa-clock"></i>
                            <span>{{ $statusPill }}</span>
                        </span>
                    @endif

                    @if(!empty($primary))
                        @if(!empty($primary['url']))
                            <a href="{{ $primary['url'] }}"
                               id="next-step-primary-btn"
                               @if(!empty($primary['target'])) target="{{ $primary['target'] }}" @endif
                               class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-xs transition-colors inline-flex items-center gap-1.5">
                                <i class="{{ $primary['icon'] }}"></i>
                                <span>{{ $primary['label'] }}</span>
                            </a>
                        @elseif(!empty($primary['tab']))
                            <button type="button"
                                    id="next-step-primary-btn"
                                    onclick="if(typeof switchCaseTab==='function') switchCaseTab('{{ $primary['tab'] }}')"
                                    class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                                <i class="{{ $primary['icon'] }}"></i>
                                <span>{{ $primary['label'] }}</span>
                            </button>
                        @elseif(!empty($primary['modal']))
                            <button type="button"
                                    id="next-step-primary-btn"
                                    data-modal="{{ $primary['modal'] }}"
                                    data-summons-id="{{ $primary['summons_id'] ?? '' }}"
                                    data-hearing-id="{{ $primary['hearing_id'] ?? '' }}"
                                    data-reschedule-hearing-id="{{ $primary['reschedule_hearing_id'] ?? '' }}"
                                    data-attempt-no="{{ $nextAttemptNo }}"
                                    onclick="openWorkflowActionFromButton(this)"
                                    class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-xs transition-colors inline-flex items-center gap-1.5 cursor-pointer">
                                <i class="{{ $primary['icon'] }}"></i>
                                <span>{{ $primary['label'] }}</span>
                            </button>
                        @endif
                    @endif

                    @if(!empty($moreActions))
                        <details class="relative">
                            <summary class="cursor-pointer list-none px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-xs inline-flex items-center gap-1.5">
                                <span>More</span>
                                <i class="fa-solid fa-chevron-down text-[10px] text-slate-500"></i>
                            </summary>
                            <div class="absolute right-0 mt-1.5 w-60 rounded-xl border border-slate-200 bg-white py-1.5 shadow-lg z-30">
                                @foreach($moreActions as $action)
                                    @if(!empty($action['url']))
                                        <a href="{{ $action['url'] }}"
                                           @if(!empty($action['target'])) target="{{ $action['target'] }}" @endif
                                           class="flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                            <i class="{{ $action['icon'] }} text-slate-500 w-4 text-center"></i>
                                            <span>{{ $action['label'] }}</span>
                                        </a>
                                    @elseif(!empty($action['tab']))
                                        <button type="button"
                                                onclick="this.closest('details').open = false; if(typeof switchCaseTab==='function') switchCaseTab('{{ $action['tab'] }}');"
                                                class="w-full text-left flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 cursor-pointer">
                                            <i class="{{ $action['icon'] }} text-slate-500 w-4 text-center"></i>
                                            <span>{{ $action['label'] }}</span>
                                        </button>
                                    @elseif(!empty($action['modal']))
                                        <button type="button"
                                                data-modal="{{ $action['modal'] }}"
                                                data-summons-id="{{ $action['summons_id'] ?? '' }}"
                                                data-hearing-id="{{ $action['hearing_id'] ?? '' }}"
                                                data-reschedule-hearing-id="{{ $action['reschedule_hearing_id'] ?? '' }}"
                                                data-attempt-no="{{ $nextAttemptNo }}"
                                                onclick="this.closest('details').open = false; openWorkflowActionFromButton(this);"
                                                class="w-full text-left flex items-center gap-2 px-3.5 py-2 text-xs font-medium text-slate-700 hover:bg-slate-50 cursor-pointer">
                                            <i class="{{ $action['icon'] }} text-slate-500 w-4 text-center"></i>
                                            <span>{{ $action['label'] }}</span>
                                        </button>
                                    @endif
                                @endforeach
                            </div>
                        </details>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-5 space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed">
                {{ $nextStep['description'] }}
            </p>

            @if($reminderLine)
                <div class="px-3.5 py-2 rounded-lg text-xs border flex items-center gap-2 {{ $reminderLine['is_overdue'] ? 'bg-amber-50 border-amber-200 text-amber-900 font-medium' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                    <i class="fa-solid {{ $reminderLine['is_overdue'] ? 'fa-triangle-exclamation text-amber-600' : 'fa-clock text-slate-500' }}"></i>
                    <span>{{ $reminderLine['text'] }}</span>
                </div>
            @endif

            @if($clock)
                <div class="px-3.5 py-2.5 rounded-lg text-xs border flex items-center justify-between gap-2 {{ $clock['is_overdue'] ? 'bg-red-50 border-red-200 text-red-900 font-semibold' : ($clock['is_warning'] ? 'bg-amber-50 border-amber-200 text-amber-900 font-medium' : 'bg-slate-50 border-slate-200 text-slate-700') }}">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left {{ $clock['is_overdue'] ? 'text-red-600' : ($clock['is_warning'] ? 'text-amber-600' : 'text-blue-600') }}"></i>
                        <span><strong>15-Working-Day Mediation Clock:</strong> {{ $clock['display'] }}</span>
                    </div>
                </div>
            @endif

            @if($repudiation)
                <div class="px-3.5 py-2 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-900 text-xs flex items-center gap-2">
                    <i class="fa-solid fa-hourglass-half text-emerald-600"></i>
                    <span><strong>10-Day Repudiation Window:</strong> {{ $repudiation['days_remaining'] }} days left (ends {{ $repudiation['ends_at'] }}).</span>
                </div>
            @endif

            <!-- 6-Item Milestone Progress Row (only ONE NEXT at a time, no truncation at 1280px) -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2.5">
                    <span class="text-[11px] font-bold uppercase tracking-wider text-slate-500">Milestone Progress</span>
                    <span class="text-[11px] text-slate-400">Sec. 410 Katarungang Pambarangay</span>
                </div>

                <div class="overflow-x-auto pb-1">
                    <div class="grid grid-cols-6 gap-2 min-w-[660px]">
                        @foreach($milestones as $m)
                            @php
                                $state = $m['state'] ?? 'locked';
                            @endphp
                            <div onclick="handleMilestoneClick('{{ $state }}')"
                                 title="{{ $m['full_title'] }}"
                                 class="rounded-lg border p-2.5 transition-all flex flex-col justify-between min-h-[76px] {{ $state === 'done' ? 'bg-emerald-50/60 border-emerald-200 cursor-pointer hover:bg-emerald-50' : ($state === 'next' ? 'bg-blue-50/80 border-blue-400 ring-1 ring-blue-300 cursor-pointer' : 'bg-slate-50/70 border-slate-200/80 opacity-65 cursor-default') }}">
                                <div>
                                    <div class="flex items-start justify-between gap-1">
                                        <span class="text-[11px] font-bold leading-tight {{ $state === 'done' ? 'text-emerald-900' : ($state === 'next' ? 'text-blue-900' : 'text-slate-500') }}">
                                            {{ $m['title'] }}
                                        </span>
                                        @if($state === 'done')
                                            <i class="fa-solid fa-circle-check text-emerald-600 text-xs shrink-0"></i>
                                        @elseif($state === 'next')
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase bg-blue-600 text-white shrink-0">Next</span>
                                        @else
                                            <i class="fa-solid fa-lock text-slate-400 text-[10px] shrink-0"></i>
                                        @endif
                                    </div>
                                    @if(!empty($m['hearing_label']))
                                        <div class="text-[9px] font-semibold {{ $state === 'done' ? 'text-emerald-700' : ($state === 'next' ? 'text-blue-700' : 'text-slate-400') }} mt-0.5">
                                            {{ $m['hearing_label'] }}
                                        </div>
                                    @endif
                                </div>

                                <div class="text-[10px] mt-1.5 pt-1 border-t {{ $state === 'done' ? 'border-emerald-200/60 text-emerald-800' : ($state === 'next' ? 'border-blue-200 text-blue-800 font-semibold' : 'border-slate-200/60 text-slate-400') }}">
                                    @if($state === 'done')
                                        <span>{{ $m['completed_at'] ?: 'Done' }}</span>
                                    @elseif($state === 'next')
                                        <span>Action Required</span>
                                    @else
                                        <span>Locked</span>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function openWorkflowActionFromButton(btn) {
        if (!btn) return;
        const modal = btn.dataset.modal;
        if (!modal) return;
        const attemptNo = parseInt(btn.dataset.attemptNo || '1', 10);
        const rescheduleHearingId = btn.dataset.rescheduleHearingId || null;
        const summonsId = btn.dataset.summonsId || '';
        const hearingId = btn.dataset.hearingId || '';

        if (modal === 'modal-schedule-and-issue-summons' && typeof openScheduleModal === 'function') {
            openScheduleModal({
                attemptNo: attemptNo,
                rescheduleHearingId: rescheduleHearingId
            });
            return;
        }
        if (modal === 'modal-record-summons-service' && summonsId) {
            const input = document.getElementById('service_modal_summons_id');
            if (input) input.value = summonsId;
        }
        if (modal === 'modal-record-hearing-outcome' && hearingId) {
            const input = document.getElementById('outcome_modal_hearing_id');
            if (input) input.value = hearingId;
        }
        if (typeof openWorkflowModal === 'function') {
            openWorkflowModal(modal);
        }
    }

    function handleMilestoneClick(state) {
        if (state === 'done') {
            if (typeof switchCaseTab === 'function') {
                switchCaseTab('timeline');
            }
            const timelinePane = document.getElementById('tab-pane-timeline');
            if (timelinePane) {
                timelinePane.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else if (state === 'next') {
            const primaryBtn = document.getElementById('next-step-primary-btn');
            if (primaryBtn) {
                primaryBtn.scrollIntoView({ behavior: 'smooth', block: 'center' });
                primaryBtn.classList.add('ring-4', 'ring-blue-300');
                setTimeout(() => primaryBtn.classList.remove('ring-4', 'ring-blue-300'), 1400);
                primaryBtn.focus();
            }
        }
    }
</script>
