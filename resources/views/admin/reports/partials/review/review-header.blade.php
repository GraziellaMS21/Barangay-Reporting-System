  {{-- 1A. Sticky Report Sub-Header --}}
  <header class="bg-white/95 backdrop-blur border border-slate-200 rounded-2xl px-4 sm:px-5 py-3 shadow-xs">
    <div class="flex flex-wrap items-center justify-between gap-3">
      <div class="min-w-0">
        <div class="flex flex-wrap items-center gap-2">
          <a href="{{ route('admin.reports.index') }}" class="inline-flex items-center gap-1.5 text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
            <i class="fa-solid fa-arrow-left text-[11px]" aria-hidden="true"></i>
            <span>Back to report queue</span>
          </a>
          <span class="text-slate-300">|</span>
          <h1 class="font-mono text-base sm:text-lg font-extrabold text-slate-900 tracking-tight m-0">{{ $report->tracking_code }}</h1>
          <x-status-badge :status="$report->status" />
          @if($report->is_emergency)
            <span class="inline-flex items-center gap-1 rounded-full bg-rose-100 text-rose-800 border border-rose-200 px-2.5 py-0.5 text-[11px] font-bold">
              <i class="fa-solid fa-triangle-exclamation text-[10px]" aria-hidden="true"></i> Emergency
            </span>
          @endif
          @if($report->source === \App\Models\Report::SOURCE_VOICE)
            <span class="inline-flex items-center gap-1 rounded-full bg-blue-50 text-blue-700 border border-blue-200 px-2.5 py-0.5 text-[11px] font-bold" title="Voice report">
              <i class="fa-solid fa-microphone text-[10px]" aria-hidden="true"></i> Voice report
            </span>
          @elseif($report->source === \App\Models\Report::SOURCE_PHONE_LOG)
            <span class="inline-flex items-center gap-1 rounded-full bg-teal-50 text-teal-800 border border-teal-200 px-2.5 py-0.5 text-[11px] font-bold" title="Phone hotline report">
              <i class="fa-solid fa-phone text-[10px]" aria-hidden="true"></i> Phone intake
            </span>
          @endif
          @if($isOverdue)
            <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 text-rose-700 border border-rose-200 px-2.5 py-0.5 text-[11px] font-bold" title="SLA due {{ $report->due_at->format('M d, Y h:i A') }}">
              <i class="fa-solid fa-clock-rotate-left text-[10px]" aria-hidden="true"></i> Overdue
            </span>
          @endif
          @if($report->escalated_at)
            <span class="inline-flex items-center gap-1 rounded-full bg-rose-600 text-white px-2.5 py-0.5 text-[11px] font-bold">
              <i class="fa-solid fa-fire text-[10px]" aria-hidden="true"></i> Escalated
            </span>
          @endif
        </div>
        <p class="text-xs text-slate-500 mt-1 mb-0">
          Submitted {{ $report->created_at->format('M j, Y \a\t g:i A') }} ({{ $report->created_at->diffForHumans() }})
          @if($report->due_at)
            <span aria-hidden="true">·</span>
            SLA due {{ $report->due_at->format('M j, Y g:i A') }}
          @endif
        </p>
      </div>

      {{-- + Other actions dropdown --}}
      <div class="flex items-center gap-2">
        @if($isOverdue && ! $report->escalated_at)
          <button type="submit" form="review-escalate-form" class="inline-flex items-center gap-1.5 rounded-lg border border-rose-300 bg-rose-50 hover:bg-rose-100 text-rose-800 text-xs font-bold px-3 py-1.5 transition">
            <i class="fa-solid fa-fire text-[10px]" aria-hidden="true"></i> Escalate overdue
          </button>
        @endif

        <div class="kebab relative">
          <button type="button" class="inline-flex items-center gap-1.5 bg-white hover:bg-slate-50 text-slate-700 border border-slate-300 text-xs font-bold px-3 py-1.5 rounded-lg shadow-xs transition"
                  data-kebab-toggle aria-haspopup="true" aria-expanded="false" aria-label="More actions">
            <i class="fa-solid fa-plus text-[10px]" aria-hidden="true"></i>
            <span>Other actions</span>
            @if($unreadReplies > 0 || ($hasResidentReply && !session('info_thread_seen.'.$report->id)))
              <span class="chat-notif-circle" data-chat-notif-circle title="New reply from resident">{{ $unreadReplies > 0 ? $unreadReplies : '!' }}</span>
            @endif
            <i class="fa-solid fa-chevron-down text-[10px] text-slate-400" aria-hidden="true"></i>
          </button>
          <div class="kebab-menu absolute right-0 mt-1.5 w-60 bg-white rounded-xl border border-slate-200 shadow-xl py-1.5 z-40 text-xs" hidden>
            @if($canOpenInfoChat)
              <button type="button" class="w-full px-3.5 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 font-medium"
                      aria-label="Ask Reporter for Info"
                      data-chat-open data-report-id="{{ $report->id }}"
                      data-chat-tracking="{{ $report->tracking_code }}"
                      data-chat-category="{{ $report->category?->name ?? 'Category not assigned' }}"
                      data-chat-status="{{ \App\Models\Report::STATUSES[$report->status] ?? $report->status }}"
                      data-chat-summary="{{ $report->description }}{{ $report->purok ? ' — '.$report->purok->name : '' }}">
                <i class="fa-solid fa-comment-dots text-blue-600 w-4 text-center" aria-hidden="true"></i>
                <span>Ask Reporter for More Info</span>
              </button>
            @endif
            @if(filter_var(\App\Models\Setting::value('cases.enabled') ?? config('cases.enabled', true), FILTER_VALIDATE_BOOLEAN) && auth()->user()?->hasPermission('cases.create'))
              @includeIf('admin.cases.partials.convert-button', ['report' => $report, 'formId' => 'kebab-convert-case-form'])
            @endif
            @if($canMergeDuplicate)
              <button type="button" class="w-full px-3.5 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 font-medium"
                      data-open-dialog="duplicate-modal" title="Mark as duplicate">
                <i class="fa-solid fa-code-merge text-amber-600 w-4 text-center" aria-hidden="true"></i>
                <span>Manual Duplicate Merge</span>
              </button>
            @endif
            @if($report->status === 'merged')
              <button type="button" class="w-full px-3.5 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 font-medium" data-open-dialog="unmerge-modal">
                <i class="fa-solid fa-code-branch text-blue-600 w-4 text-center" aria-hidden="true"></i>
                <span>Unmerge Report</span>
              </button>
            @endif
            @if(!$emergencyActionOnly && $isRouteable)
              <button type="submit" form="review-form" name="intent" value="save" class="w-full px-3.5 py-2 text-left text-slate-700 hover:bg-slate-50 flex items-center gap-2.5 font-medium">
                <i class="fa-regular fa-floppy-disk text-slate-500 w-4 text-center" aria-hidden="true"></i>
                <span>Save review without routing</span>
              </button>
            @endif
            @if(!$report->is_emergency && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true))
              <button type="submit" form="kebab-emergency-form" class="w-full px-3.5 py-2 text-left text-rose-700 hover:bg-rose-50 flex items-center gap-2.5 font-medium">
                <i class="fa-solid fa-triangle-exclamation text-rose-600 w-4 text-center" aria-hidden="true"></i>
                <span>Flag as emergency</span>
              </button>
            @endif
            @if($canTakeNoAction)
              <div class="my-1 border-t border-slate-100"></div>
              <button type="button" class="w-full px-3.5 py-2 text-left text-rose-600 hover:bg-rose-50 flex items-center gap-2.5 font-semibold" data-open-dialog="no-action-modal">
                <i class="fa-solid fa-ban w-4 text-center" aria-hidden="true"></i>
                <span>Take No Action</span>
              </button>
            @endif
          </div>
        </div>
      </div>
    </div>
  </header>

  {{-- 1B. Full-Width Conditional Alert Banners --}}
  @if($hasEmergencyFlag)
    <section class="rounded-xl border border-rose-300 bg-rose-50 p-4 flex flex-wrap items-center justify-between gap-3" aria-label="Emergency review required">
      <div class="min-w-0">
        <strong class="text-sm font-extrabold text-rose-900 flex items-center gap-2">
          <i class="fa-solid fa-triangle-exclamation text-rose-600" aria-hidden="true"></i>
          <span>Possible emergency</span>
        </strong>
        <p class="text-xs text-rose-800 mt-1 mb-0">{{ $report->is_emergency ? 'This report is currently flagged as an emergency.' : 'The AI or report details indicate a possible emergency. Confirm before continuing.' }}</p>
      </div>
      <div class="flex flex-wrap items-center gap-2">
        @if(! $report->is_emergency && $canResolveEmergency)
          <form method="POST" action="{{ route('admin.reports.emergency', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="confirm" value="1">
            <button type="submit" class="rounded-lg border border-rose-300 bg-white hover:bg-rose-100 text-rose-800 text-xs font-bold px-3 py-1.5 transition">Confirm emergency</button>
          </form>
        @endif
        @if($report->is_emergency && $report->status === 'emergency_flagged')
          <button type="button" class="rounded-lg bg-blue-600 hover:bg-blue-700 text-white text-xs font-bold px-3 py-1.5 transition" data-open-dialog="emergency-handled-modal">
            <i class="fa-solid fa-person-digging mr-1" aria-hidden="true"></i> Handle Locally (Tanod Response)
          </button>
        @endif
        @if($canResolveEmergency)
          <form method="POST" action="{{ route('admin.reports.emergency', $report) }}">
            @csrf
            @method('PATCH')
            <input type="hidden" name="confirm" value="0">
            <button type="submit" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3 py-1.5 transition">Not an emergency</button>
          </form>
        @else
          <span class="text-xs text-slate-600">This report is in a final status; its emergency state cannot be changed here.</span>
        @endif
      </div>
    </section>
  @endif

  @if($report->hasUnverifiedAudio())
    <section id="voice-audio-verification-banner" class="bg-amber-50 border border-amber-300 rounded-xl p-4 space-y-3" aria-label="Voice audio verification required">
      <div class="flex flex-wrap items-start justify-between gap-2">
        <div class="flex items-start gap-3">
          <div class="w-9 h-9 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-microphone-slash" aria-hidden="true"></i>
          </div>
          <div>
            <div class="flex items-center gap-2 flex-wrap">
              <strong class="text-sm font-extrabold text-amber-900">Voice Audio Needs Verification</strong>
              <span class="inline-flex items-center rounded-full bg-amber-200/80 text-amber-900 px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide">Unverified draft</span>
            </div>
            <p class="text-xs text-amber-800 mt-0.5 mb-0">
              AI transcription is unclear or unverified. Listen to the recording and confirm or update the report description to unblock routing.
            </p>
          </div>
        </div>
      </div>
      <audio controls src="{{ route('admin.reports.audio', $report) }}" preload="metadata" class="w-full max-w-lg h-10"></audio>
      <form method="POST" action="{{ route('admin.reports.audio.verify', $report) }}" class="space-y-2 pt-1">
        @csrf
        <label for="verified_description" class="block text-xs font-bold text-amber-900">
          Staff Verified Description <span class="text-rose-600">*</span>
        </label>
        <textarea id="verified_description" name="description" rows="2" required minlength="5"
                  class="w-full rounded-lg border border-amber-400 bg-white px-3 py-2 text-xs text-slate-900 focus:outline-none focus:ring-2 focus:ring-amber-500/30">{{ old('description', $report->description) }}</textarea>
        <div class="flex justify-end">
          <button type="submit" class="inline-flex items-center gap-1.5 rounded-lg bg-amber-600 hover:bg-amber-700 text-white text-xs font-bold px-3.5 py-2 transition">
            <i class="fa-solid fa-check-double" aria-hidden="true"></i> Verify and Update Description
          </button>
        </div>
      </form>
    </section>
  @endif

  @if($hasPhotoMismatch)
    <section class="rounded-xl border border-amber-300 bg-amber-50 px-4 py-3 flex flex-wrap items-center justify-between gap-3" aria-label="Possible photo mismatch">
      <div class="flex items-center gap-2.5 text-xs text-amber-900">
        <i class="fa-solid fa-image text-amber-600 text-sm" aria-hidden="true"></i>
        <span><strong>Possible photo mismatch:</strong> Check whether the attached photo matches the resident's description.</span>
      </div>
      <div class="flex items-center gap-2">
        <form method="POST" action="{{ route('admin.reports.photo-mismatch', $report) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="confirm" value="1">
          <button type="submit" class="rounded-lg border border-amber-300 bg-white hover:bg-amber-100 text-amber-900 text-xs font-bold px-3 py-1.5 transition">Confirm mismatch</button>
        </form>
        <form method="POST" action="{{ route('admin.reports.photo-mismatch', $report) }}">
          @csrf
          @method('PATCH')
          <input type="hidden" name="confirm" value="0">
          <button type="submit" class="rounded-lg border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 text-xs font-bold px-3 py-1.5 transition">Not a mismatch</button>
        </form>
      </div>
    </section>
  @endif
