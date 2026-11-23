@extends('layouts.admin')

@section('title', 'Review Agency: ' . $registration->agency_name_claimed)
@section('page-heading', 'Review Agency Request')

@section('content')
<div class="max-w-5xl space-y-6">

  {{-- Top Navigation & Status --}}
  <div class="flex items-center justify-between flex-wrap gap-3">
    <a href="{{ route('admin.agencies.index') }}" class="text-xs font-semibold text-slate-500 hover:text-blue-600 inline-flex items-center gap-1.5 transition-colors">
      <i class="fa-solid fa-arrow-left text-[11px]" aria-hidden="true"></i> Back to Partner Agencies
    </a>
    <div>
      @if($registration->status === 'pending')
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200">
          <i class="fa-solid fa-clock text-[10px]" aria-hidden="true"></i> Pending Review
        </span>
      @elseif($registration->status === 'approved')
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
          <i class="fa-solid fa-circle-check text-[10px]" aria-hidden="true"></i> Active Partner
        </span>
      @elseif($registration->status === 'rejected')
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-red-50 text-red-700 border border-red-200">
          <i class="fa-solid fa-xmark text-[10px]" aria-hidden="true"></i> Rejected
        </span>
      @else
        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
          <i class="fa-solid fa-ban text-[10px]" aria-hidden="true"></i> {{ ucfirst($registration->status) }}
        </span>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

    {{-- Left 2 cols: Agency Information & Document --}}
    <div class="lg:col-span-2 space-y-6">

      {{-- Details Card --}}
      <section class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
          <div class="w-8 h-8 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center" aria-hidden="true">
            <i class="fa-solid fa-building-shield text-sm"></i>
          </div>
          <div>
            <h2 class="text-base font-bold text-slate-900 m-0">Agency & Representative Information</h2>
            <p class="text-xs text-slate-500 m-0">Official application data submitted for account verification.</p>
          </div>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-5 text-sm">
          <div>
            <dt class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Claimed Agency</dt>
            <dd class="mt-1 font-bold text-slate-900">{{ $registration->agency_name_claimed }}</dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Linked Partner Office</dt>
            <dd class="mt-1 text-slate-800 font-medium">{{ $registration->office?->name ?? 'None selected' }}</dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Representative Name</dt>
            <dd class="mt-1 font-bold text-slate-900 flex items-center gap-2">
              <span class="w-7 h-7 rounded-full bg-blue-50 text-blue-700 inline-flex items-center justify-center text-xs font-bold flex-shrink-0" aria-hidden="true">
                {{ str($registration->user?->name ?? 'N')->substr(0, 1)->upper() }}
              </span>
              {{ $registration->user?->name ?? 'N/A' }}
            </dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Official Position</dt>
            <dd class="mt-1 text-slate-800">{{ $registration->position_title }}</dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Official Email</dt>
            <dd class="mt-1 text-slate-800 flex items-center gap-1.5 font-medium">
              <i class="fa-solid fa-envelope text-slate-400 text-[11px]" aria-hidden="true"></i>
              {{ $registration->user?->email ?? 'N/A' }}
            </dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Contact Number</dt>
            <dd class="mt-1 text-slate-800 flex items-center gap-1.5">
              <i class="fa-solid fa-phone text-slate-400 text-[11px]" aria-hidden="true"></i>
              {{ $registration->contact_number }}
            </dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Office Admin Role</dt>
            <dd class="mt-1">
              @if($registration->is_office_admin)
                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                  <i class="fa-solid fa-crown text-[10px]" aria-hidden="true"></i> Yes — Can invite agency team members
                </span>
              @else
                <span class="text-xs text-slate-500">Standard representative</span>
              @endif
            </dd>
          </div>
          <div>
            <dt class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider">Application Date</dt>
            <dd class="mt-1 text-slate-800 flex items-center gap-1.5">
              <i class="fa-solid fa-calendar text-slate-400 text-[11px]" aria-hidden="true"></i>
              {{ $registration->created_at->format('M d, Y · h:i A') }}
            </dd>
          </div>
        </dl>
      </section>

      {{-- Verification Document Card --}}
      <section class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
        <div class="flex items-center justify-between pb-4 mb-4 border-b border-slate-100 flex-wrap gap-2">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center" aria-hidden="true">
              <i class="fa-solid fa-file-shield text-sm"></i>
            </div>
            <div>
              <h2 class="text-base font-bold text-slate-900 m-0">Uploaded Verification Document</h2>
              <p class="text-xs text-slate-500 m-0">Official government agency ID card or endorsement letter.</p>
            </div>
          </div>
          <a href="{{ route('admin.agencies.document', $registration) }}" target="_blank"
             class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition-colors inline-flex items-center gap-1">
            Open full document <i class="fa-solid fa-arrow-up-right-from-square text-[10px]" aria-hidden="true"></i>
          </a>
        </div>

        <div class="p-4 bg-slate-50 rounded-xl border border-slate-100 flex items-center justify-between gap-3">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-blue-50 text-blue-600 flex items-center justify-center flex-shrink-0" aria-hidden="true">
              <i class="fa-solid fa-file-shield text-lg"></i>
            </div>
            <div>
              <p class="text-sm font-semibold text-slate-900 m-0">Verification Credential</p>
              <p class="text-xs text-slate-500 m-0">Protected file stored privately on server disk.</p>
            </div>
          </div>
          <a href="{{ route('admin.agencies.document', $registration) }}" target="_blank"
             class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-semibold bg-blue-50 text-blue-700 hover:bg-blue-100 transition-colors">
            <i class="fa-solid fa-eye text-[11px]" aria-hidden="true"></i> View File
          </a>
        </div>
      </section>
    </div>

    {{-- Right 1 col: Review Decision & Actions --}}
    <div class="space-y-6">
      <section class="card rounded-2xl bg-white p-6 shadow-sm border border-slate-100">
        <div class="flex items-center gap-2.5 pb-4 mb-4 border-b border-slate-100">
          <div class="w-8 h-8 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center" aria-hidden="true">
            <i class="fa-solid fa-gavel text-sm"></i>
          </div>
          <h2 class="text-base font-bold text-slate-900 m-0">Secretary Decision</h2>
        </div>

        @if($registration->status === 'pending')
          {{-- Approve Form --}}
          <form method="POST" action="{{ route('admin.agencies.approve', $registration) }}" class="space-y-4 mb-5">
            @csrf
            <div>
              <label for="office_id" class="block text-xs font-bold text-slate-700 mb-1.5">Assign Partner Office</label>
              <select id="office_id" name="office_id" class="w-full rounded-xl border-slate-200 shadow-sm text-sm py-2.5 px-3 focus:border-blue-500 focus:ring-blue-200" required>
                <option value="">Select official office...</option>
                @foreach($offices as $office)
                  <option value="{{ $office->id }}" @selected(old('office_id', $registration->office_id) == $office->id)>
                    {{ $office->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <label class="flex items-center gap-2.5 cursor-pointer p-2.5 rounded-lg hover:bg-slate-50 transition-colors">
              <input type="checkbox" name="is_office_admin" value="1"
                class="rounded border-slate-300 text-blue-600 w-4 h-4"
                @checked($registration->is_office_admin)>
              <span class="text-xs font-medium text-slate-700">Designate as Agency Office Admin</span>
            </label>

            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold bg-emerald-600 hover:bg-emerald-700 text-white transition-colors shadow-sm cursor-pointer">
              <i class="fa-solid fa-check" aria-hidden="true"></i> Approve Agency Access
            </button>
          </form>

          {{-- Reject Form --}}
          <div class="border-t border-slate-100 pt-4">
            <form method="POST" action="{{ route('admin.agencies.reject', $registration) }}" class="space-y-3">
              @csrf
              <div>
                <label for="decision_note" class="block text-xs font-bold text-slate-700 mb-1.5">Rejection Reason</label>
                <textarea name="decision_note" id="decision_note" rows="2"
                  class="w-full rounded-xl border-slate-200 shadow-sm text-sm py-2 px-3 focus:border-red-400 focus:ring-red-200"
                  placeholder="Provide reason for declining this request..." required></textarea>
              </div>
              <button type="submit"
                class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white transition-colors shadow-sm cursor-pointer"
                onclick="return confirm('Reject this agency access request?')">
                <i class="fa-solid fa-xmark" aria-hidden="true"></i> Reject Request
              </button>
            </form>
          </div>

        @elseif($registration->status === 'approved')
          <div class="p-3.5 bg-emerald-50 border border-emerald-200 rounded-xl text-xs text-emerald-800 font-medium flex items-center gap-2 mb-4">
            <i class="fa-solid fa-circle-check text-emerald-600 text-sm" aria-hidden="true"></i>
            Approved partner agency account.
          </div>

          <form method="POST" action="{{ route('admin.agencies.suspend', $registration) }}" class="space-y-3">
            @csrf
            <div>
              <label for="suspend_note" class="block text-xs font-bold text-slate-700 mb-1.5">Suspension Reason</label>
              <textarea name="decision_note" id="suspend_note" rows="2"
                class="w-full rounded-xl border-slate-200 shadow-sm text-sm py-2 px-3 focus:border-red-400 focus:ring-red-200"
                placeholder="Reason for suspending access..." required></textarea>
            </div>
            <button type="submit"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold bg-red-600 hover:bg-red-700 text-white transition-colors shadow-sm cursor-pointer"
              onclick="return confirm('Suspend this partner agency account?')">
              <i class="fa-solid fa-ban" aria-hidden="true"></i> Suspend Account
            </button>
          </form>

        @elseif($registration->status === 'suspended')
          <div class="p-3.5 bg-red-50 border border-red-200 rounded-xl text-xs text-red-800 font-medium mb-4">
            <div class="flex items-center gap-2">
              <i class="fa-solid fa-triangle-exclamation text-red-600" aria-hidden="true"></i>
              Account is currently suspended.
            </div>
            @if($registration->decision_note)
              <p class="mt-1.5 font-bold text-red-900">Reason: {{ $registration->decision_note }}</p>
            @endif
          </div>

          <form method="POST" action="{{ route('admin.agencies.reinstate', $registration) }}">
            @csrf
            <button type="submit"
              class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-bold bg-blue-600 hover:bg-blue-700 text-white transition-colors shadow-sm cursor-pointer">
              <i class="fa-solid fa-rotate-left" aria-hidden="true"></i> Reinstate Account Access
            </button>
          </form>

        @else
          <div class="p-3.5 bg-slate-50 border border-slate-200 rounded-xl text-xs text-slate-600 font-medium">
            <div class="flex items-center gap-2">
              <i class="fa-solid fa-circle-xmark text-slate-400" aria-hidden="true"></i>
              This request was rejected.
            </div>
            @if($registration->decision_note)
              <p class="mt-1.5 font-bold text-slate-800">Note: {{ $registration->decision_note }}</p>
            @endif
          </div>
        @endif
      </section>
    </div>
  </div>
</div>
@endsection
