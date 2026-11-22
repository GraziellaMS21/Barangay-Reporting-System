@extends('layouts.admin')

@section('title', 'Review Agency: ' . $registration->agency_name_claimed)
@section('page-heading', 'Review Agency Access Request')

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
  <div class="flex items-center justify-between">
    <a href="{{ route('admin.agencies.index') }}" class="text-xs font-semibold text-slate-500 hover:text-slate-900 inline-flex items-center">
      <i class="fa-solid fa-arrow-left mr-1.5"></i> Back to Partner Agencies
    </a>

    <div>
      @if($registration->status === 'pending')
        <x-ui.badge variant="warning">Pending Review</x-ui.badge>
      @elseif($registration->status === 'approved')
        <x-ui.badge variant="success">Active Partner</x-ui.badge>
      @elseif($registration->status === 'rejected')
        <x-ui.badge variant="danger">Rejected</x-ui.badge>
      @else
        <x-ui.badge variant="neutral">{{ ucfirst($registration->status) }}</x-ui.badge>
      @endif
    </div>
  </div>

  <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    {{-- Left 2 cols: Details & Document --}}
    <div class="md:col-span-2 space-y-6">
      <x-ui.card>
        <div class="border-b border-slate-200 pb-4 mb-4">
          <h2 class="text-base font-bold text-slate-900">Agency & Representative Information</h2>
        </div>

        <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-4 gap-y-4 text-sm">
          <div>
            <dt class="text-xs font-medium text-slate-400">Claimed Agency</dt>
            <dd class="mt-0.5 font-semibold text-slate-900">{{ $registration->agency_name_claimed }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-slate-400">Linked Office</dt>
            <dd class="mt-0.5 text-slate-900">{{ $registration->office?->name ?? 'None selected' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-slate-400">Representative Name</dt>
            <dd class="mt-0.5 font-semibold text-slate-900">{{ $registration->user?->name ?? 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-slate-400">Official Position</dt>
            <dd class="mt-0.5 text-slate-900">{{ $registration->position_title }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-slate-400">Email Address</dt>
            <dd class="mt-0.5 text-slate-900">{{ $registration->user?->email ?? 'N/A' }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-slate-400">Contact Number</dt>
            <dd class="mt-0.5 text-slate-900">{{ $registration->contact_number }}</dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-slate-400">Office Admin Designation</dt>
            <dd class="mt-0.5 text-slate-900">
              {{ $registration->is_office_admin ? 'Yes (Can invite team members)' : 'No (Standard representative)' }}
            </dd>
          </div>
          <div>
            <dt class="text-xs font-medium text-slate-400">Requested On</dt>
            <dd class="mt-0.5 text-slate-900">{{ $registration->created_at->format('M d, Y h:i A') }}</dd>
          </div>
        </dl>
      </x-ui.card>

      {{-- Verification Document Viewer --}}
      <x-ui.card>
        <div class="flex items-center justify-between border-b border-slate-200 pb-4 mb-4">
          <div>
            <h2 class="text-base font-bold text-slate-900">Uploaded Verification Document</h2>
            <p class="text-xs text-slate-500">Official agency ID card or endorsement letter.</p>
          </div>
          <a href="{{ route('admin.agencies.document', $registration) }}" target="_blank"
             class="inline-flex items-center px-3 py-1.5 text-xs font-medium rounded-lg bg-blue-50 text-blue-700 hover:bg-blue-100">
            <i class="fa-solid fa-arrow-up-right-from-square mr-1.5"></i> Open File
          </a>
        </div>

        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <i class="fa-solid fa-file-shield text-2xl text-blue-600"></i>
            <div>
              <p class="text-sm font-semibold text-slate-900">Verification Document</p>
              <p class="text-xs text-slate-500">Stored privately in secure agency documents disk.</p>
            </div>
          </div>
          <a href="{{ route('admin.agencies.document', $registration) }}" target="_blank" class="text-xs font-semibold text-blue-600 hover:text-blue-800">
            View / Download
          </a>
        </div>
      </x-ui.card>
    </div>

    {{-- Right 1 col: Actions / Decisions --}}
    <div class="space-y-6">
      <x-ui.card>
        <h2 class="text-base font-bold text-slate-900 mb-4">Decision & Actions</h2>

        @if($registration->status === 'pending')
          <form method="POST" action="{{ route('admin.agencies.approve', $registration) }}" class="space-y-4 mb-6">
            @csrf
            <div>
              <label for="office_id" class="block text-xs font-semibold text-slate-700 mb-1">Assign Official Office</label>
              <select id="office_id" name="office_id" class="w-full rounded-lg border-slate-300 shadow-sm text-sm" required>
                <option value="">Choose partner office...</option>
                @foreach($offices as $office)
                  <option value="{{ $office->id }}" @selected(old('office_id', $registration->office_id) == $office->id)>
                    {{ $office->name }}
                  </option>
                @endforeach
              </select>
            </div>

            <label class="flex items-center gap-2 cursor-pointer">
              <input type="checkbox" name="is_office_admin" value="1" class="rounded border-slate-300 text-blue-600" @checked($registration->is_office_admin)>
              <span class="text-xs text-slate-700">Designate as Agency Office Admin</span>
            </label>

            <x-ui.button type="submit" variant="primary" class="w-full justify-center">
              <i class="fa-solid fa-check mr-1.5"></i> Approve Access Request
            </x-ui.button>
          </form>

          <div class="border-t border-slate-200 pt-4">
            <form method="POST" action="{{ route('admin.agencies.reject', $registration) }}" class="space-y-3">
              @csrf
              <x-ui.form-field name="decision_note" label="Rejection Reason" :required="true">
                <textarea name="decision_note" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Provide reason for rejecting..." required></textarea>
              </x-ui.form-field>
              <x-ui.button type="submit" variant="danger" class="w-full justify-center" onclick="return confirm('Are you sure you want to reject this request?')">
                <i class="fa-solid fa-xmark mr-1.5"></i> Reject Request
              </x-ui.button>
            </form>
          </div>

        @elseif($registration->status === 'approved')
          <div class="space-y-4">
            <div class="p-3 bg-emerald-50 border border-emerald-200 rounded-lg text-xs text-emerald-800">
              <i class="fa-solid fa-circle-check mr-1"></i> Active and approved partner account.
            </div>

            <form method="POST" action="{{ route('admin.agencies.suspend', $registration) }}">
              @csrf
              <x-ui.form-field name="decision_note" label="Suspension Reason" :required="true">
                <textarea name="decision_note" rows="2" class="w-full rounded-lg border-slate-300 text-sm" placeholder="Reason for suspending access..." required></textarea>
              </x-ui.form-field>
              <x-ui.button type="submit" variant="danger" class="w-full justify-center" onclick="return confirm('Suspend this partner agency account?')">
                <i class="fa-solid fa-ban mr-1.5"></i> Suspend Account
              </x-ui.button>
            </form>
          </div>

        @elseif($registration->status === 'suspended')
          <div class="space-y-4">
            <div class="p-3 bg-red-50 border border-red-200 rounded-lg text-xs text-red-800">
              <i class="fa-solid fa-triangle-exclamation mr-1"></i> Account is currently suspended.
              @if($registration->decision_note)
                <p class="mt-1 font-semibold">Reason: {{ $registration->decision_note }}</p>
              @endif
            </div>

            <form method="POST" action="{{ route('admin.agencies.reinstate', $registration) }}">
              @csrf
              <x-ui.button type="submit" variant="primary" class="w-full justify-center">
                <i class="fa-solid fa-rotate-left mr-1.5"></i> Reinstate Account Access
              </x-ui.button>
            </form>
          </div>
        @else
          <div class="p-3 bg-slate-100 rounded-lg text-xs text-slate-600">
            This request was rejected.
            @if($registration->decision_note)
              <p class="mt-1 font-semibold text-slate-800">Note: {{ $registration->decision_note }}</p>
            @endif
          </div>
        @endif
      </x-ui.card>
    </div>
  </div>
</div>
@endsection
