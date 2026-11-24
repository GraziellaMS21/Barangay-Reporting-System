@extends('layouts.admin')

@section('title', 'Pending Confirmations Register')
@section('page-heading', 'Pending Confirmations Register')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Pending Confirmations Register"
        subtitle="Review baseline assumptions and unconfirmed placeholder configurations awaiting official verification from the Barangay.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.pending.export') }}" variant="secondary" data-turbo="false">
                <i class="fa-solid fa-file-csv text-xs mr-1.5"></i>
                <span>Export Register (CSV)</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Alert Banner -->
    @if($totalPending > 0)
        <div class="p-4 rounded-xl bg-amber-50 border border-amber-200 text-amber-900 flex items-start gap-3">
            <i class="fa-solid fa-triangle-exclamation text-amber-600 mt-0.5 text-base"></i>
            <div class="text-xs leading-relaxed">
                <strong class="font-semibold text-sm block mb-0.5">{{ $totalPending }} items currently use placeholder assumptions.</strong>
                Placeholder values are functional defaults. Once verified by the Punong Barangay or Barangay Secretary, confirm them individually or by section to remove the pending badges and unconfirmed watermarks.
            </div>
        </div>
    @else
        <div class="p-6 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-900 text-center">
            <i class="fa-solid fa-circle-check text-emerald-600 text-3xl mb-2"></i>
            <h3 class="text-base font-bold text-emerald-900">All System Configurations Confirmed</h3>
            <p class="text-xs text-emerald-700 mt-1">Zero unconfirmed placeholders remain. The barangay operational profile is officially signed off.</p>
        </div>
    @endif

    <!-- 1. Settings Section -->
    @if($pendingSettings->isNotEmpty())
        <x-ui.card>
            <x-slot:headerActions>
                <div class="flex items-center justify-between w-full">
                    <span class="font-bold text-slate-900 text-sm">General & Numbering Settings ({{ $pendingSettings->count() }})</span>
                    <form method="POST" action="{{ route('admin.pending.confirm-group') }}">
                        @csrf
                        <input type="hidden" name="group" value="settings">
                        <x-ui.button type="submit" variant="ghost" size="sm">
                            <i class="fa-solid fa-check-double text-xs mr-1"></i>
                            <span>Confirm All Settings</span>
                        </x-ui.button>
                    </form>
                </div>
            </x-slot:headerActions>

            <div class="overflow-hidden">
                <table class="w-full text-left border-collapse text-sm table-fixed responsive-card-table">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase bg-slate-50/50">
                            <th class="py-2.5 px-4 w-[28%]">Setting</th>
                            <th class="py-2.5 px-4 w-[32%]">Current Value</th>
                            <th class="py-2.5 px-4 w-[22%]">Question Reference</th>
                            <th class="py-2.5 px-4 w-[18%] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($pendingSettings as $s)
                            <tr class="hover:bg-slate-50/80">
                                <td data-label="Setting" class="py-3 px-4 font-medium text-slate-900 text-xs">
                                    {{ $s->label ?? $s->key }}
                                </td>
                                <td data-label="Current Value" class="py-3 px-4 font-mono text-xs text-blue-700 break-all">
                                    {{ $s->value }}
                                </td>
                                <td data-label="Question Reference" class="py-3 px-4 text-xs text-slate-500">
                                    {{ $s->source_question ?? 'N/A' }}
                                </td>
                                <td data-label="Action" class="py-3 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.pending.confirm') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="type" value="setting">
                                        <input type="hidden" name="id" value="{{ $s->id }}">
                                        <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 hover:underline">
                                            Confirm
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <!-- 2. Positions & Signatories -->
    @if($pendingPositions->isNotEmpty() || $pendingSignatories->isNotEmpty())
        <x-ui.card>
            <x-slot:headerActions>
                <div class="flex items-center justify-between w-full">
                    <span class="font-bold text-slate-900 text-sm">Positions & Signatory Roles</span>
                    <div class="flex items-center gap-2">
                        @if($pendingPositions->isNotEmpty())
                            <form method="POST" action="{{ route('admin.pending.confirm-group') }}">
                                @csrf
                                <input type="hidden" name="group" value="positions">
                                <x-ui.button type="submit" variant="ghost" size="sm">Confirm Positions</x-ui.button>
                            </form>
                        @endif
                        @if($pendingSignatories->isNotEmpty())
                            <form method="POST" action="{{ route('admin.pending.confirm-group') }}">
                                @csrf
                                <input type="hidden" name="group" value="signatories">
                                <x-ui.button type="submit" variant="ghost" size="sm">Confirm Signatories</x-ui.button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-slot:headerActions>

            <div class="overflow-hidden">
                <table class="w-full text-left border-collapse text-sm table-fixed responsive-card-table">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase bg-slate-50/50">
                            <th class="py-2.5 px-4 w-[28%]">Role / Assignment</th>
                            <th class="py-2.5 px-4 w-[32%]">Assigned Position / Code</th>
                            <th class="py-2.5 px-4 w-[22%]">Scope</th>
                            <th class="py-2.5 px-4 w-[18%] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($pendingPositions as $p)
                            <tr class="hover:bg-slate-50/80">
                                <td data-label="Role / Assignment" class="py-3 px-4 font-medium text-slate-900 text-xs">
                                    Position: {{ $p->name }}
                                </td>
                                <td data-label="Assigned Position / Code" class="py-3 px-4 font-mono text-xs text-slate-700">
                                    {{ $p->code }}
                                </td>
                                <td data-label="Scope" class="py-3 px-4 text-xs text-slate-500">
                                    Official Position
                                </td>
                                <td data-label="Action" class="py-3 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.pending.confirm') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="type" value="position">
                                        <input type="hidden" name="id" value="{{ $p->id }}">
                                        <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 hover:underline">
                                            Confirm
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach

                        @foreach($pendingSignatories as $sa)
                            <tr class="hover:bg-slate-50/80">
                                <td data-label="Role / Assignment" class="py-3 px-4 font-medium text-slate-900 text-xs">
                                    Signatory: {{ ucwords(str_replace('_', ' ', $sa->document_type)) }} ({{ $sa->role_label }})
                                </td>
                                <td data-label="Assigned Position / Code" class="py-3 px-4 text-xs text-blue-700 font-semibold">
                                    {{ $sa->position?->name ?? 'Default PB / Secretary' }}
                                </td>
                                <td data-label="Scope" class="py-3 px-4 text-xs text-slate-500">
                                    Legal Transmittal Signer
                                </td>
                                <td data-label="Action" class="py-3 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.pending.confirm') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="type" value="signatory">
                                        <input type="hidden" name="id" value="{{ $sa->id }}">
                                        <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 hover:underline">
                                            Confirm
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif

    <!-- 3. Case Types & Destinations -->
    @if($pendingCaseTypes->isNotEmpty() || $pendingDestinations->isNotEmpty())
        <x-ui.card>
            <x-slot:headerActions>
                <div class="flex items-center justify-between w-full">
                    <span class="font-bold text-slate-900 text-sm">Dispute Categories & Escalation Venues</span>
                    <div class="flex items-center gap-2">
                        @if($pendingCaseTypes->isNotEmpty())
                            <form method="POST" action="{{ route('admin.pending.confirm-group') }}">
                                @csrf
                                <input type="hidden" name="group" value="case_types">
                                <x-ui.button type="submit" variant="ghost" size="sm">Confirm Case Types</x-ui.button>
                            </form>
                        @endif
                        @if($pendingDestinations->isNotEmpty())
                            <form method="POST" action="{{ route('admin.pending.confirm-group') }}">
                                @csrf
                                <input type="hidden" name="group" value="destinations">
                                <x-ui.button type="submit" variant="ghost" size="sm">Confirm Venues</x-ui.button>
                            </form>
                        @endif
                    </div>
                </div>
            </x-slot:headerActions>

            <div class="overflow-hidden">
                <table class="w-full text-left border-collapse text-sm table-fixed responsive-card-table">
                    <thead>
                        <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase bg-slate-50/50">
                            <th class="py-2.5 px-4 w-[28%]">Entity</th>
                            <th class="py-2.5 px-4 w-[32%]">Name / System Key</th>
                            <th class="py-2.5 px-4 w-[22%]">Scope</th>
                            <th class="py-2.5 px-4 w-[18%] text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($pendingCaseTypes as $ct)
                            <tr class="hover:bg-slate-50/80">
                                <td data-label="Entity" class="py-3 px-4 font-medium text-slate-900 text-xs">
                                    Dispute: {{ $ct->name }}
                                </td>
                                <td data-label="Name / System Key" class="py-3 px-4 font-mono text-xs text-slate-600">
                                    {{ $ct->slug }}
                                </td>
                                <td data-label="Scope" class="py-3 px-4 text-xs text-slate-500">
                                    Katarungang Pambarangay
                                </td>
                                <td data-label="Action" class="py-3 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.pending.confirm') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="type" value="case_type">
                                        <input type="hidden" name="id" value="{{ $ct->id }}">
                                        <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 hover:underline">
                                            Confirm
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach

                        @foreach($pendingDestinations as $dest)
                            <tr class="hover:bg-slate-50/80">
                                <td data-label="Entity" class="py-3 px-4 font-medium text-slate-900 text-xs">
                                    Escalation: {{ $dest->name }}
                                </td>
                                <td data-label="Name / System Key" class="py-3 px-4 text-xs text-slate-600">
                                    Court / Prosecutor / Police
                                </td>
                                <td data-label="Scope" class="py-3 px-4 text-xs text-slate-500">
                                    CFA Referral Destination
                                </td>
                                <td data-label="Action" class="py-3 px-4 text-right">
                                    <form method="POST" action="{{ route('admin.pending.confirm') }}" class="inline">
                                        @csrf
                                        <input type="hidden" name="type" value="destination">
                                        <input type="hidden" name="id" value="{{ $dest->id }}">
                                        <button type="submit" class="text-xs font-semibold text-emerald-600 hover:text-emerald-800 hover:underline">
                                            Confirm
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    @endif
</div>
@endsection
