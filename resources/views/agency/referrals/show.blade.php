@extends('layouts.agency')

@section('title', 'Referral: ' . $referral->reference_no)
@section('page-heading', 'Referral ' . $referral->reference_no)

@section('content')
<div class="space-y-6">
  <div class="flex items-center justify-between">
    <a href="{{ route('agency.referrals.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 inline-flex items-center">
      <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Referrals
    </a>

    <div>
      @if($referral->status === 'sent')
        <x-ui.badge variant="warning">Awaiting Acknowledgment</x-ui.badge>
      @elseif($referral->status === 'acknowledged')
        <x-ui.badge variant="info">Acknowledged</x-ui.badge>
      @elseif($referral->status === 'action_taken')
        <x-ui.badge variant="success">Action In Progress</x-ui.badge>
      @elseif($referral->status === 'closed')
        <x-ui.badge variant="neutral">Closed ({{ ucfirst($referral->outcome ?? 'Resolved') }})</x-ui.badge>
      @else
        <x-ui.badge variant="neutral">{{ ucfirst($referral->status) }}</x-ui.badge>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left 2 cols: Referral Details & Thread --}}
    <div class="lg:col-span-2 space-y-6">
      <x-ui.card>
        <div class="border-b border-slate-200 pb-4 mb-4">
          <span class="text-xs font-semibold uppercase tracking-wider text-blue-600">Official Referral Matter</span>
          <h2 class="text-lg font-bold text-slate-900 mt-1">
            {{ $referral->report?->category?->name ?? 'Community Concern' }}
          </h2>
          <p class="text-xs text-slate-500 mt-0.5">
            Forwarded by {{ setting('barangay.name', 'Barangay Calarian') }} · Location: {{ $referral->report?->purok?->name ?? 'Calarian' }}
          </p>
        </div>

        <div class="text-sm text-slate-800 leading-relaxed bg-slate-50 p-4 rounded-xl border border-slate-200">
          <h3 class="text-xs font-semibold text-slate-500 uppercase tracking-wider mb-2">Referral Endorsement Text</h3>
          <p class="whitespace-pre-line">{{ $referral->referral_text ?: 'Please review and take appropriate official action regarding this community matter.' }}</p>
        </div>

        {{-- Meta Grid --}}
        <dl class="grid grid-cols-1 sm:grid-cols-3 gap-4 mt-6 pt-4 border-t border-slate-200 text-xs">
          <div>
            <dt class="font-medium text-slate-400">Date Dispatched</dt>
            <dd class="mt-0.5 font-semibold text-slate-900">
              {{ $referral->sent_at ? \Carbon\Carbon::parse($referral->sent_at)->format('M d, Y h:i A') : '—' }}
            </dd>
          </div>
          <div>
            <dt class="font-medium text-slate-400">Agency Case / Docket No.</dt>
            <dd class="mt-0.5 font-semibold text-slate-900">
              {{ $referral->agency_reference_no ?: 'None recorded' }}
            </dd>
          </div>
          <div>
            <dt class="font-medium text-slate-400">Follow-up Target</dt>
            <dd class="mt-0.5 font-semibold text-slate-900">
              {{ $referral->follow_up_at ? \Carbon\Carbon::parse($referral->follow_up_at)->format('M d, Y') : '—' }}
            </dd>
          </div>
        </dl>
      </x-ui.card>

      {{-- Progress & Messages Timeline --}}
      <x-ui.card>
        <div class="border-b border-slate-200 pb-3 mb-4">
          <h3 class="text-sm font-bold text-slate-900">Updates & Communication Log</h3>
          <p class="text-xs text-slate-500">Record of agency updates and correspondence with barangay staff.</p>
        </div>

        <div class="space-y-4">
          @forelse($referral->updates as $update)
            <div class="p-3.5 rounded-xl border border-slate-200 bg-white text-xs space-y-1.5 shadow-sm">
              <div class="flex items-center justify-between text-slate-500">
                <span class="font-semibold text-slate-800">
                  <i class="fa-solid fa-user-circle mr-1 text-slate-400"></i>
                  {{ $update->user?->name ?? 'Agency Representative' }}
                </span>
                <span>{{ $update->created_at->format('M d, Y h:i A') }}</span>
              </div>
              @if($update->channel)
                <div>
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-blue-700">
                    Channel: {{ ucfirst(str_replace('_', ' ', $update->channel)) }}
                  </span>
                </div>
              @endif
              <p class="text-slate-700 whitespace-pre-line">{{ $update->note }}</p>
            </div>
          @empty
            <p class="text-xs text-slate-400 italic py-4 text-center">No updates recorded yet.</p>
          @endforelse
        </div>

        {{-- Send Message Form --}}
        @if($referral->status !== 'closed')
          <form method="POST" action="{{ route('agency.referrals.message', $referral) }}" class="mt-6 pt-4 border-t border-slate-200 space-y-3">
            @csrf
            <label for="message_body" class="block text-xs font-semibold text-slate-700">Send Note / Message to Barangay Staff</label>
            <textarea id="message_body" name="body" rows="2" class="w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="Type a message or inquiry..." required></textarea>
            <div class="text-right">
              <x-ui.button type="submit" variant="secondary" size="sm">
                <i class="fa-solid fa-paper-plane mr-1"></i> Send Note
              </x-ui.button>
            </div>
          </form>
        @endif
      </x-ui.card>
    </div>

    {{-- Right 1 col: Actions Card --}}
    <div class="space-y-6">
      <x-ui.card>
        <h3 class="text-base font-bold text-slate-900 mb-3">Agency Actions</h3>

        @if($referral->status === 'sent')
          {{-- Step 1: Acknowledge Receipt --}}
          <form method="POST" action="{{ route('agency.referrals.update', $referral) }}" class="space-y-3 mb-6">
            @csrf
            <input type="hidden" name="action_type" value="acknowledge">
            <p class="text-xs text-slate-600">Acknowledge that your department has received this referral.</p>
            
            <x-ui.form-field name="agency_reference_no" label="Your Agency Case / Blotter No. (Optional)" :required="false">
              <input type="text" name="agency_reference_no" class="w-full rounded-lg border-slate-300 text-xs shadow-sm" placeholder="e.g. CCPO-2026-102">
            </x-ui.form-field>

            <x-ui.button type="submit" variant="primary" class="w-full justify-center">
              <i class="fa-solid fa-check-double mr-1.5"></i> Acknowledge Receipt
            </x-ui.button>
          </form>

        @elseif($referral->status === 'acknowledged' || $referral->status === 'action_taken')
          {{-- Step 2: Post Action Taken --}}
          <form method="POST" action="{{ route('agency.referrals.update', $referral) }}" class="space-y-3 mb-6">
            @csrf
            <input type="hidden" name="action_type" value="action_taken">
            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Post Action Taken</h4>

            <x-ui.form-field name="note" label="Action Taken Summary" :required="true">
              <textarea name="note" rows="3" class="w-full rounded-lg border-slate-300 text-xs shadow-sm" placeholder="Details of inspection, response team dispatch, or preliminary findings..." required></textarea>
            </x-ui.form-field>

            <x-ui.button type="submit" variant="primary" class="w-full justify-center">
              <i class="fa-solid fa-shield-halved mr-1.5"></i> Record Action Taken
            </x-ui.button>
          </form>

          {{-- Step 3: Close or Finalize --}}
          <div class="border-t border-slate-200 pt-4 space-y-3">
            <form method="POST" action="{{ route('agency.referrals.update', $referral) }}" class="space-y-3">
              @csrf
              <input type="hidden" name="action_type" value="close">
              <h4 class="text-xs font-bold uppercase tracking-wider text-slate-700">Close / Finalize Referral</h4>

              <x-ui.form-field name="outcome" label="Resolution Outcome" :required="true">
                <select name="outcome" class="w-full rounded-lg border-slate-300 text-xs shadow-sm" required>
                  <option value="resolved">Resolved / Matter Handled</option>
                  <option value="declined">Declined / Out of Agency Jurisdiction</option>
                </select>
              </x-ui.form-field>

              <x-ui.form-field name="note" label="Final Disposition Note" :required="true">
                <textarea name="note" rows="2" class="w-full rounded-lg border-slate-300 text-xs shadow-sm" placeholder="Final resolution note..." required></textarea>
              </x-ui.form-field>

              <x-ui.button type="submit" variant="secondary" class="w-full justify-center" onclick="return confirm('Close this referral?')">
                <i class="fa-solid fa-lock mr-1.5"></i> Close Referral
              </x-ui.button>
            </form>
          </div>

        @else
          <div class="p-4 bg-slate-50 rounded-xl text-center text-xs text-slate-500">
            <i class="fa-solid fa-circle-check text-emerald-500 text-2xl mb-2 block"></i>
            This referral is marked as <strong>Closed ({{ ucfirst($referral->outcome) }})</strong>.
          </div>
        @endif
      </x-ui.card>
    </div>
  </div>
</div>
@endsection
