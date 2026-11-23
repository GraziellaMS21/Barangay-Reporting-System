@extends('layouts.admin')

@section('title', 'Signatories Matrix Configuration')
@section('page-heading', 'Signatories Matrix')

@section('content')
<form method="POST" action="{{ route('admin.signatories.update') }}">
    @csrf

    <x-ui.page-header title="Signatories Matrix Configuration" subtitle="Assign which barangay position signs Prepared by, Noted by, and Approved by blocks on each official document.">
        <x-slot:actions>
            @if($unconfirmedCount > 0)
                <x-ui.pending-badge :count="$unconfirmedCount" />
            @endif
            <x-ui.button variant="secondary" :href="route('admin.personnel.index')">
                <i class="fa-solid fa-users"></i> Personnel
            </x-ui.button>
            <x-ui.button type="submit" variant="primary">
                <i class="fa-solid fa-floppy-disk"></i> Save Signatories Matrix
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <x-ui.table>
        <x-slot:header>
            <tr>
                <th class="px-4 py-3 w-[28%]">Document Type</th>
                <th class="px-4 py-3 w-[18%]">Prepared by</th>
                <th class="px-4 py-3 w-[18%]">Noted by</th>
                <th class="px-4 py-3 w-[18%]">Approved by</th>
                <th class="px-4 py-3 w-[18%]">Currently Resolves To</th>
            </tr>
        </x-slot:header>

        @foreach($matrixRows as $docType => $row)
            <tr class="hover:bg-slate-50 align-top">
                <td class="px-4 py-3">
                    <div class="font-semibold text-slate-900">{{ $row['label'] }}</div>
                    <div class="text-xs font-mono text-slate-500">{{ $docType }}</div>
                    @if(! $row['is_confirmed'])
                        <span class="mt-1 inline-flex items-center px-2 py-0.5 rounded text-[11px] font-medium bg-amber-50 text-amber-800 border border-dashed border-amber-300">
                            Pending confirmation
                        </span>
                    @endif
                </td>

                @foreach(['prepared_by', 'noted_by', 'approved_by'] as $roleKey)
                    @php
                        $currentAssignment = $row['assignments'][$roleKey] ?? null;
                    @endphp
                    <td class="px-4 py-3">
                        <select name="assignments[{{ $docType }}][{{ $roleKey }}]"
                                aria-label="{{ $row['label'] }} {{ $roleKey }}"
                                class="w-full rounded-lg border border-slate-300 px-2.5 py-1.5 text-sm bg-white">
                            <option value="">— None —</option>
                            @foreach($positions as $pos)
                                <option value="{{ $pos->id }}" @selected($currentAssignment && (int) $currentAssignment->position_id === (int) $pos->id)>
                                    {{ $pos->name }}
                                </option>
                            @endforeach
                        </select>
                    </td>
                @endforeach

                <td class="px-4 py-3 text-xs text-slate-700">
                    @php
                        $resolved = $resolvedPreviews[$docType] ?? [];
                    @endphp
                    @forelse($resolved as $sig)
                        <div class="mb-1">
                            <span class="text-slate-500">{{ $sig['label'] }}:</span>
                            <strong class="text-slate-900">{{ $sig['name'] }}</strong>
                            <span class="text-slate-400">({{ $sig['position'] }})</span>
                        </div>
                    @empty
                        <span class="text-slate-400 italic">No active official assigned</span>
                    @endforelse
                </td>
            </tr>
        @endforeach
    </x-ui.table>

    <div class="mt-4 flex items-center justify-between flex-wrap gap-4">
        <label class="inline-flex items-center gap-2 text-sm font-medium text-slate-700">
            <input type="checkbox" name="confirm_all" value="1">
            <span>Mark updated signatory assignments as confirmed by the Barangay</span>
        </label>
        <x-ui.button type="submit" variant="primary">
            <i class="fa-solid fa-floppy-disk"></i> Save Signatories Matrix
        </x-ui.button>
    </div>
</form>
@endsection
