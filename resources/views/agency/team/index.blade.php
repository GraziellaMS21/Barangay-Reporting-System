@extends('layouts.agency')

@section('title', 'Agency Team Management')
@section('page-heading', 'Agency Team Members')

@section('content')
<div class="space-y-6">
  <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
    <div>
      <h2 class="text-lg font-bold text-slate-900">{{ auth()->user()->office?->name }} Portal Team</h2>
      <p class="text-sm text-slate-500">Manage user access and colleague invitations for your department.</p>
    </div>
  </div>

  <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    {{-- Left 2 cols: Active Team Members & Pending Invites --}}
    <div class="lg:col-span-2 space-y-6">
      <x-ui.card>
        <div class="border-b border-slate-200 pb-3 mb-4">
          <h3 class="text-sm font-bold text-slate-900">Current Team Members</h3>
          <p class="text-xs text-slate-500">Colleagues with active access to {{ auth()->user()->office?->name }} referrals.</p>
        </div>

        <x-ui.table>
          <x-slot:header>
            <tr>
              <th scope="col" class="px-4 py-3 text-left w-[40%] sm:w-[30%]">Name</th>
              <th scope="col" class="hidden sm:table-cell px-4 py-3 text-left w-[25%]">Position</th>
              <th scope="col" class="px-4 py-3 text-left w-[25%] sm:w-[15%]">Role</th>
              <th scope="col" class="hidden md:table-cell px-4 py-3 text-left w-[15%]">Status</th>
              <th scope="col" class="px-4 py-3 text-right w-[35%] sm:w-[15%]">Actions</th>
            </tr>
          </x-slot:header>
          @foreach($members as $member)
            <tr class="hover:bg-slate-50/75">
              <td class="px-4 py-3 font-semibold text-slate-900" data-label="Name">
                <div class="truncate max-w-[160px]">{{ $member->name }}</div>
                <div class="text-[11px] font-normal text-slate-400 truncate max-w-[160px]">{{ $member->email }}</div>
                <div class="sm:hidden text-[10px] text-slate-500 mt-0.5">{{ $member->agencyRegistration?->position_title ?? 'Representative' }}</div>
              </td>
              <td class="hidden sm:table-cell px-4 py-3 text-slate-600 truncate max-w-[140px]" data-label="Position">
                {{ $member->agencyRegistration?->position_title ?? 'Representative' }}
              </td>
              <td class="px-4 py-3" data-label="Role">
                @if($member->agencyRegistration?->is_office_admin)
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-purple-50 text-purple-700">
                    Admin
                  </span>
                @else
                  <span class="text-slate-500 text-xs">Staff</span>
                @endif
              </td>
              <td class="hidden md:table-cell px-4 py-3" data-label="Status">
                @if($member->is_active)
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-emerald-50 text-emerald-700">Active</span>
                @else
                  <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-slate-100 text-slate-500">Inactive</span>
                @endif
              </td>
              <td class="px-4 py-3 text-right" data-label="Actions">
                @if($member->id !== auth()->id())
                  <form method="POST" action="{{ route('agency.team.deactivate') }}" class="inline">
                    @csrf
                    <input type="hidden" name="user_id" value="{{ $member->id }}">
                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800 transition-colors" onclick="return confirm('Deactivate access for {{ $member->name }}?')">
                      Deactivate
                    </button>
                  </form>
                @else
                  <span class="text-slate-400 text-xs italic">You</span>
                @endif
              </td>
            </tr>
          @endforeach
        </x-ui.table>
      </x-ui.card>

      {{-- Pending Invitations --}}
      <x-ui.card>
        <div class="border-b border-slate-200 pb-3 mb-4">
          <h3 class="text-sm font-bold text-slate-900">Pending Invitations</h3>
          <p class="text-xs text-slate-500">Invitations sent to colleagues awaiting registration (valid 7 days).</p>
        </div>

        <x-ui.table>
          <thead class="bg-slate-50 border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider">
            <tr>
              <th class="px-4 py-3 text-left">Email Address</th>
              <th class="px-4 py-3 text-left">Expires</th>
              <th class="px-4 py-3 text-right">Action</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200 text-xs">
            @forelse($invitations as $invite)
              <tr>
                <td class="px-4 py-3 font-medium text-slate-900">{{ $invite->email }}</td>
                <td class="px-4 py-3 text-slate-500">{{ $invite->expires_at->diffForHumans() }}</td>
                <td class="px-4 py-3 text-right">
                  <form method="POST" action="{{ route('agency.team.revoke') }}" class="inline">
                    @csrf
                    <input type="hidden" name="invitation_id" value="{{ $invite->id }}">
                    <button type="submit" class="text-xs font-semibold text-red-600 hover:text-red-800" onclick="return confirm('Revoke invitation?')">
                      Revoke
                    </button>
                  </form>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="3" class="px-4 py-6 text-center text-slate-400 italic">No pending invitations.</td>
              </tr>
            @endforelse
          </tbody>
        </x-ui.table>
      </x-ui.card>
    </div>

    {{-- Right 1 col: Invite Colleague Form --}}
    <div>
      <x-ui.card>
        <h3 class="text-base font-bold text-slate-900 mb-2">Invite Colleague</h3>
        <p class="text-xs text-slate-500 mb-4">Send a 7-day portal registration invite to an authorized member of your office.</p>

        <form method="POST" action="{{ route('agency.team.invite') }}" class="space-y-4">
          @csrf
          <x-ui.form-field name="email" label="Official Agency Email" :required="true">
            <input type="email" name="email" class="w-full rounded-lg border-slate-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500" placeholder="colleague@agency.gov.ph" required>
          </x-ui.form-field>

          <x-ui.button type="submit" variant="primary" class="w-full justify-center">
            <i class="fa-solid fa-envelope mr-1.5"></i> Send Invitation
          </x-ui.button>
        </form>
      </x-ui.card>
    </div>
  </div>
</div>
@endsection
