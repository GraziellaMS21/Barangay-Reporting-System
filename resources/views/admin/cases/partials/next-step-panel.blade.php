@props(['case', 'nextStep'])

@php
    $status = $case->status;
    $isRepudiation = !empty($case->repudiation_ends_at) && now()->lt($case->repudiation_ends_at) && empty($case->repudiated_at);
    
    // Stepper Progress: 1. Review -> 2. Docket -> 3. Mediation -> 4. Resolve
    // Step 3 (Mediation) is active from docketing until an outcome is recorded.
    $step1Done = true;
    $step2Done = true;
    $hearingCompleted = !empty($case->mediation_outcome);
    $step3Active = !$hearingCompleted && in_array($status, [\App\Support\CaseStatus::FILED, \App\Support\CaseStatus::IN_MEDIATION, \App\Support\CaseStatus::IN_CONCILIATION]);
    $step3Done = $hearingCompleted || in_array($status, [\App\Support\CaseStatus::SETTLED, \App\Support\CaseStatus::UNSETTLED, \App\Support\CaseStatus::ESCALATED, \App\Support\CaseStatus::DISMISSED, \App\Support\CaseStatus::WITHDRAWN]);
    $step4Active = $step3Done;
    $step4Done = ($status === \App\Support\CaseStatus::SETTLED && !$isRepudiation) || !empty($case->cfa_issued_at);
@endphp

<div class="space-y-4">
    <!-- Stepper Tracker -->
    <div class="bg-white rounded-xl border border-slate-200/90 p-3.5 shadow-xs">
        <div class="grid grid-cols-4 gap-2 text-center text-xs">
            <!-- Step 1: Review -->
            <div class="flex items-center gap-2 text-emerald-700">
                <span class="w-6 h-6 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center font-bold text-xs shrink-0">
                    <i class="fa-solid fa-check text-[11px] text-emerald-600"></i>
                </span>
                <span class="font-bold truncate hidden sm:inline">1. Review</span>
            </div>

            <!-- Step 2: Docket -->
            <div class="flex items-center gap-2 text-emerald-700">
                <span class="w-6 h-6 rounded-full bg-emerald-50 border border-emerald-200 flex items-center justify-center font-bold text-xs shrink-0">
                    <i class="fa-solid fa-check text-[11px] text-emerald-600"></i>
                </span>
                <span class="font-bold truncate hidden sm:inline">2. Docket</span>
            </div>

            <!-- Step 3: Mediation (Active from docketing until outcome) -->
            <div class="flex items-center gap-2 {{ $step3Done ? 'text-emerald-700' : ($step3Active ? 'text-blue-700' : 'text-slate-400') }}">
                <span class="w-6 h-6 rounded-full {{ $step3Done ? 'bg-emerald-50 border border-emerald-200 text-emerald-600' : ($step3Active ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-500') }} flex items-center justify-center font-bold text-xs shrink-0">
                    @if($step3Done)
                        <i class="fa-solid fa-check text-[11px]"></i>
                    @else
                        3
                    @endif
                </span>
                <span class="font-bold truncate hidden sm:inline">3. Mediation</span>
            </div>

            <!-- Step 4: Resolve -->
            <div class="flex items-center gap-2 {{ $step4Done ? 'text-emerald-700' : ($step4Active ? 'text-blue-700' : 'text-slate-400') }}">
                <span class="w-6 h-6 rounded-full {{ $step4Done ? 'bg-emerald-50 border border-emerald-200 text-emerald-600' : ($step4Active ? 'bg-blue-600 text-white shadow-xs' : 'bg-slate-100 text-slate-500') }} flex items-center justify-center font-bold text-xs shrink-0">
                    @if($step4Done)
                        <i class="fa-solid fa-check text-[11px]"></i>
                    @else
                        4
                    @endif
                </span>
                <span class="font-bold truncate hidden sm:inline">4. Resolve</span>
            </div>
        </div>
    </div>

    <!-- Cohesive "NEXT STEP" Workflow Card -->
    <div class="bg-white rounded-xl border border-blue-200/90 shadow-sm overflow-hidden">
        <!-- Top Banner / Header -->
        <div class="bg-gradient-to-r from-blue-50/90 via-indigo-50/50 to-white px-5 py-4 border-b border-blue-100">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider bg-blue-100 text-blue-800 border border-blue-200">
                            <span class="w-1.5 h-1.5 rounded-full bg-blue-600 animate-pulse"></span>
                            Action Required · Step {{ $nextStep['step_number'] ?? 1 }}
                        </span>
                    </div>
                    <h3 class="text-base font-bold text-slate-900">{{ $nextStep['title'] }}</h3>
                </div>

                <!-- Primary & Secondary Action Buttons -->
                <div class="flex items-center gap-2 flex-wrap">
                    @if(!empty($nextStep['secondary_action']))
                        @if(!empty($nextStep['secondary_action']['url']))
                            <a href="{{ $nextStep['secondary_action']['url'] }}" 
                               @if(!empty($nextStep['secondary_action']['target'])) target="{{ $nextStep['secondary_action']['target'] }}" @endif
                               class="px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-xs transition-colors flex items-center gap-1.5">
                                <i class="{{ $nextStep['secondary_action']['icon'] }} text-slate-500"></i>
                                <span>{{ $nextStep['secondary_action']['label'] }}</span>
                            </a>
                        @elseif(!empty($nextStep['secondary_action']['modal']))
                            <button type="button" 
                                    onclick="openWorkflowModal('{{ $nextStep['secondary_action']['modal'] }}')" 
                                    class="px-3.5 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                                <i class="{{ $nextStep['secondary_action']['icon'] }} text-slate-500"></i>
                                <span>{{ $nextStep['secondary_action']['label'] }}</span>
                            </button>
                        @endif
                    @endif

                    @if(!empty($nextStep['primary_action']))
                        @if(!empty($nextStep['primary_action']['url']))
                            <a href="{{ $nextStep['primary_action']['url'] }}" 
                               id="next-step-primary-btn"
                               @if(!empty($nextStep['primary_action']['target'])) target="{{ $nextStep['primary_action']['target'] }}" @endif
                               class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                                <i class="{{ $nextStep['primary_action']['icon'] }}"></i>
                                <span>{{ $nextStep['primary_action']['label'] }}</span>
                            </a>
                        @elseif(!empty($nextStep['primary_action']['modal']))
                            <button type="button" 
                                    id="next-step-primary-btn"
                                    onclick="openWorkflowModal('{{ $nextStep['primary_action']['modal'] }}')" 
                                    class="px-4 py-2 rounded-lg text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                                <i class="{{ $nextStep['primary_action']['icon'] }}"></i>
                                <span>{{ $nextStep['primary_action']['label'] }}</span>
                            </button>
                        @endif
                    @endif

                    @if(!empty($nextStep['extra_actions']))
                        @foreach($nextStep['extra_actions'] as $extra)
                            @if(!empty($extra['url']))
                                <a href="{{ $extra['url'] }}" 
                                   @if(!empty($extra['target'])) target="{{ $extra['target'] }}" @endif
                                   class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-xs transition-colors flex items-center gap-1.5">
                                    <i class="{{ $extra['icon'] }} text-slate-500"></i>
                                    <span>{{ $extra['label'] }}</span>
                                </a>
                            @elseif(!empty($extra['modal']))
                                <button type="button" 
                                        onclick="openWorkflowModal('{{ $extra['modal'] }}')" 
                                        class="px-3 py-2 rounded-lg text-xs font-semibold text-slate-700 bg-white hover:bg-slate-50 border border-slate-300 shadow-xs transition-colors flex items-center gap-1.5 cursor-pointer">
                                    <i class="{{ $extra['icon'] }} text-slate-500"></i>
                                    <span>{{ $extra['label'] }}</span>
                                </button>
                            @endif
                        @endforeach
                    @endif
                </div>
            </div>
        </div>

        <!-- Body: Description & Details -->
        <div class="p-5 space-y-4">
            <p class="text-xs text-slate-600 leading-relaxed font-sans">{{ $nextStep['description'] }}</p>

            <!-- 1 Working Day Summons Reminder Line (Step 2) -->
            @if(!empty($nextStep['reminder_line']))
                <div class="p-2.5 rounded-lg border text-xs flex items-center gap-2 {{ $nextStep['reminder_line']['is_overdue'] ? 'bg-amber-50 border-amber-300 text-amber-900 font-semibold' : 'bg-slate-50 border-slate-200 text-slate-700' }}">
                    <i class="fa-solid fa-clock {{ $nextStep['reminder_line']['is_overdue'] ? 'text-amber-600' : 'text-slate-400' }}"></i>
                    <span>{{ $nextStep['reminder_line']['text'] }}</span>
                </div>
            @endif

            <!-- 15-Day Mediation Period Notice (Sec. 410b RA 7160) -->
            @if(!empty($nextStep['mediation_clock']))
                <div class="p-3 rounded-lg border flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs {{ $nextStep['mediation_clock']['is_overdue'] ? 'bg-red-50 border-red-200 text-red-900' : ($nextStep['mediation_clock']['is_warning'] ? 'bg-amber-50 border-amber-200 text-amber-900' : 'bg-blue-50/70 border-blue-200/80 text-slate-800') }}">
                    <div class="flex items-center gap-2">
                        <i class="fa-solid fa-clock-rotate-left {{ $nextStep['mediation_clock']['is_overdue'] ? 'text-red-600' : ($nextStep['mediation_clock']['is_warning'] ? 'text-amber-600' : 'text-blue-600') }}"></i>
                        <span>
                            <strong class="font-bold">15-Day Mediation Clock:</strong>
                            {{ $nextStep['mediation_clock']['display'] }}
                        </span>
                    </div>
                    @if($nextStep['mediation_clock']['is_started'])
                        <div>
                            @if($nextStep['mediation_clock']['is_overdue'])
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-red-100 text-red-800 border border-red-200">Overdue</span>
                            @elseif($nextStep['mediation_clock']['is_warning'])
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                                    {{ $nextStep['mediation_clock']['days_remaining'] }} days left
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-blue-100 text-blue-800 border border-blue-200">
                                    Active (Day {{ config('katarungang_pambarangay.mediation_period_days', 15) - $nextStep['mediation_clock']['days_remaining'] }})
                                </span>
                            @endif
                        </div>
                    @endif
                </div>
            @endif

            @if(!empty($nextStep['repudiation_countdown']))
                <div class="p-3 rounded-lg bg-amber-50 border border-amber-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2 text-xs">
                    <div class="flex items-center gap-2 text-amber-900">
                        <i class="fa-solid fa-shield-halved text-amber-600"></i>
                        <span><strong class="text-amber-950">10-Day Repudiation Period Active:</strong> Challenge window ends {{ $nextStep['repudiation_countdown']['ends_at'] }}</span>
                    </div>
                    <span class="px-2 py-0.5 rounded text-[11px] font-bold bg-amber-100 text-amber-800 border border-amber-200">
                        {{ $nextStep['repudiation_countdown']['days_remaining'] }} days left
                    </span>
                </div>
            @endif

            <!-- Clean 6-Item Milestone Progress Row (No truncation, wrapping, min-width) -->
            <div class="pt-3 border-t border-slate-100">
                <div class="flex items-center justify-between mb-2.5">
                    <h4 class="text-[10px] font-bold uppercase tracking-wider text-slate-500">Milestone Progress</h4>
                    <span class="text-[10px] text-slate-400">Sec. 410 Katarungang Pambarangay</span>
                </div>
                
                <div class="overflow-x-auto pb-1">
                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-2.5 min-w-[640px] text-xs">
                        @foreach($nextStep['milestones'] as $m)
                            @php
                                $state = $m['state']; // 'done', 'next', 'in_progress', 'locked'
                                $cardClasses = match($state) {
                                    'done' => 'bg-emerald-50/70 border-emerald-200 text-emerald-950 cursor-pointer hover:bg-emerald-100/60',
                                    'next' => 'bg-blue-50/80 border-blue-400 text-blue-950 ring-1 ring-blue-300 cursor-pointer hover:bg-blue-100/70',
                                    'in_progress' => 'bg-blue-50 border-blue-300 text-blue-950 ring-1 ring-blue-300',
                                    default => 'bg-slate-50/70 border-slate-200/70 text-slate-400 cursor-not-allowed',
                                };
                            @endphp

                            <div onclick="handleMilestoneClick('{{ $state }}')"
                                 title="{{ $m['full_title'] }}"
                                 class="p-2.5 rounded-lg border flex flex-col justify-between transition-all {{ $cardClasses }}">
                                <div>
                                    <div class="flex items-center justify-between gap-1 mb-1">
                                        <span class="font-bold text-xs leading-tight break-words">
                                            {{ $m['title'] }}
                                        </span>
                                        @if($state === 'done')
                                            <i class="fa-solid fa-circle-check text-emerald-600 text-xs shrink-0"></i>
                                        @elseif($state === 'next')
                                            <span class="px-1.5 py-0.2 rounded text-[9px] font-extrabold uppercase bg-blue-600 text-white shrink-0">Next</span>
                                        @elseif($state === 'in_progress')
                                            <span class="w-2 h-2 rounded-full bg-blue-600 animate-ping shrink-0"></span>
                                        @else
                                            <i class="fa-solid fa-lock text-slate-400 text-[10px] shrink-0"></i>
                                        @endif
                                    </div>
                                </div>

                                <div class="text-[10px] mt-1.5 pt-1 border-t {{ $state === 'done' ? 'border-emerald-200/60 text-emerald-800' : ($state === 'next' ? 'border-blue-200 text-blue-800 font-semibold' : 'border-slate-200/60 text-slate-400') }}">
                                    @if($state === 'done')
                                        <span>{{ $m['completed_at'] ?: 'Completed' }}</span>
                                        @if(!empty($m['subtext']))
                                            <div class="text-[9px] text-emerald-700/80">{{ $m['subtext'] }}</div>
                                        @endif
                                    @elseif($state === 'next')
                                        <span>Action Required</span>
                                    @elseif($state === 'in_progress')
                                        <span>In Progress</span>
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
    function handleMilestoneClick(state) {
        if (state === 'done') {
            // Scroll to timeline & logs tab
            if (typeof switchCaseTab === 'function') {
                switchCaseTab('timeline');
            }
            const timelinePane = document.getElementById('tab-pane-timeline');
            if (timelinePane) {
                timelinePane.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        } else if (state === 'next') {
            const btn = document.getElementById('next-step-primary-btn');
            if (btn) {
                btn.focus();
                btn.classList.add('ring-2', 'ring-offset-2', 'ring-blue-500');
                setTimeout(() => btn.classList.remove('ring-2', 'ring-offset-2', 'ring-blue-500'), 1500);
            }
        }
    }
</script>
