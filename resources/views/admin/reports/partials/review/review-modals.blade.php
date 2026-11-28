  {{-- Auxiliary action forms outside #review-form --}}
  @if(auth()->user()?->hasPermission('cases.create'))
    <form id="kebab-convert-case-form" method="POST" action="{{ route('admin.reports.convert-to-case', $report) }}" class="hidden">@csrf</form>
  @endif
  @if(!$report->is_emergency && !in_array($report->status, \App\Models\Report::CLOSED_STATUSES, true))
    <form id="kebab-emergency-form" method="POST" action="{{ route('admin.reports.emergency', $report) }}" data-confirm="Flag this report as an emergency concern?" class="hidden">
      @csrf
      @method('PATCH')
      <input type="hidden" name="confirm" value="1">
    </form>
  @endif
  @if($aiUnavailable && $report->ai_status === 'failed' && ! $emergencyActionOnly)
    <form id="review-retry-ai-form" method="POST" action="{{ route('admin.reports.retry-ai', $report) }}" class="hidden">@csrf</form>
  @endif
  @if($aiPending && ! $emergencyActionOnly)
    <form id="review-stop-ai-form" method="POST" action="{{ route('admin.reports.cancel-ai', $report) }}" class="hidden">@csrf</form>
  @endif
  @if($isOverdue && ! $report->escalated_at)
    <form id="review-escalate-form" method="POST" action="{{ route('admin.reports.escalate', $report) }}" class="hidden">@csrf</form>
  @endif

  {{-- Modals --}}
  @if($canMergeDuplicate)
    <dialog id="duplicate-modal" class="review-modal">
      <form method="POST" action="{{ route('admin.reports.duplicate', $report) }}">
        @csrf
        @method('PATCH')
        <header class="review-modal-header">
          <strong><i class="fa-solid fa-copy" aria-hidden="true"></i> Mark duplicate</strong>
          <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <div class="review-modal-body">
          @php
            $mergeSelectableCandidates = $actionableDuplicates->pluck('candidate')
                ->filter(fn ($c) => $c !== null && ! $c->is_emergency && $c->status !== 'emergency_flagged')
                ->concat($duplicateCandidates)->unique('id')->values();
          @endphp
          <label for="duplicate-candidate">Duplicate of</label>
          <select id="duplicate-candidate" name="candidate_id" required>
            <option value="">Select a report</option>
            @foreach($mergeSelectableCandidates as $candidate)
              <option value="{{ $candidate->id }}" @selected($pendingDuplicate && ! $pendingDuplicate->candidate->is_emergency && $pendingDuplicate->candidate->status !== 'emergency_flagged' && (string) $pendingDuplicate->candidate_id === (string) $candidate->id)>
                {{ $candidate->tracking_code }} — {{ \Illuminate\Support\Str::limit($candidate->description, 70) }}
              </option>
            @endforeach
          </select>
          <label for="duplicate-note">Merge note</label>
          <textarea id="duplicate-note" name="note" rows="2" required maxlength="500" placeholder="Why these are the same report."></textarea>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary" @disabled($mergeSelectableCandidates->isEmpty())>Mark duplicate</button>
        </footer>
      </form>
    </dialog>
  @endif

  @if($canTakeNoAction)
    <dialog id="no-action-modal" class="review-modal">
      <form method="POST" action="{{ route('admin.reports.status', $report) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="no_action">
        <header class="review-modal-header">
          <strong><i class="fa-solid fa-circle-xmark" aria-hidden="true"></i> Take no action</strong>
          <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <div class="review-modal-body space-y-3">
          <div>
            <label for="no-action-reason" class="block text-xs font-bold mb-1">Reason code</label>
            <select id="no-action-reason" name="no_action_reason" required class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs">
              <option value="">Select a reason code...</option>
              @foreach(\App\Models\Report::NO_ACTION_REASONS as $code => $label)
                <option value="{{ $code }}">{{ $label }}</option>
              @endforeach
            </select>
          </div>
          <div>
            <label for="no-action-note" class="block text-xs font-bold mb-1">Explanation shown to resident</label>
            <textarea id="no-action-note" name="note" rows="3" required maxlength="500" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></textarea>
          </div>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary">Confirm No Action</button>
        </footer>
      </form>
    </dialog>
  @endif

  @if($report->status === 'merged')
    <dialog id="unmerge-modal" class="review-modal">
      <form method="POST" action="{{ route('admin.reports.unmerge', $report) }}">
        @csrf
        @method('PATCH')
        <header class="review-modal-header">
          <strong><i class="fa-solid fa-code-merge" aria-hidden="true"></i> Unmerge report</strong>
          <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <div class="review-modal-body">
          <label for="unmerge-note" class="block text-xs font-bold mb-1">Reason for unmerging</label>
          <textarea id="unmerge-note" name="note" rows="3" required minlength="5" maxlength="500" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></textarea>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary">Confirm Unmerge</button>
        </footer>
      </form>
    </dialog>
  @endif

  @if($report->is_emergency)
    <dialog id="emergency-handled-modal" class="review-modal">
      <form method="POST" action="{{ route('admin.reports.status', $report) }}">
        @csrf
        @method('PATCH')
        <input type="hidden" name="status" value="in_progress">
        <header class="review-modal-header">
          <strong><i class="fa-solid fa-shield-halved" aria-hidden="true"></i> Emergency Handled by Barangay</strong>
          <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
        </header>
        <div class="review-modal-body">
          <label for="emergency-handled-note" class="block text-xs font-bold mb-1">Action taken note</label>
          <textarea id="emergency-handled-note" name="note" rows="3" required minlength="5" maxlength="500" class="w-full rounded-lg border border-slate-300 px-2.5 py-2 text-xs"></textarea>
        </div>
        <footer class="review-modal-footer">
          <button type="button" class="review-button review-button-outline" data-close-modal>Cancel</button>
          <button type="submit" class="review-button review-button-primary">Move to In Progress</button>
        </footer>
      </form>
    </dialog>
  @endif

  <dialog id="photo-lightbox-modal" class="review-modal">
    <div class="bg-white rounded-2xl max-w-2xl w-full overflow-hidden">
      <header class="review-modal-header">
        <strong>Incident Photo — {{ $report->tracking_code }}</strong>
        <button type="button" class="review-modal-close" data-close-modal aria-label="Close">&times;</button>
      </header>
      <div class="p-3 bg-slate-100 flex justify-center">
        <img id="photo-lightbox-img" src="" alt="Enlarged resident photo" class="max-h-[75vh] rounded-xl object-contain">
      </div>
    </div>
  </dialog>
