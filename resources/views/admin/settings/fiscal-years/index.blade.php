@extends('layouts.admin')

@section('title', 'Fiscal Years Management')
@section('page-heading', 'Fiscal Years Management')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Fiscal Years Management"
        subtitle="Configure statutory operational years, active operational status, and record locks.">
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" onclick="document.getElementById('modal-add-fy').showModal()">
                <i class="fa-solid fa-plus text-xs mr-1.5"></i>
                <span>Add Fiscal Year</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
            <ul class="list-disc list-inside text-xs space-y-0.5">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <x-ui.card>
        <div class="overflow-hidden">
            <table class="w-full text-left border-collapse text-sm table-fixed responsive-card-table">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider bg-slate-50/50">
                        <th class="py-3 px-4 w-[12%]">Year</th>
                        <th class="py-3 px-4 w-[16%]">Start Date</th>
                        <th class="py-3 px-4 w-[16%]">End Date</th>
                        <th class="py-3 px-4 w-[14%]">Status</th>
                        <th class="py-3 px-4 w-[8%] text-center">Locked</th>
                        <th class="py-3 px-4 w-[22%]">Records Scoped</th>
                        <th class="py-3 px-4 w-[12%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($years as $fy)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td data-label="Year" class="py-3.5 px-4 font-bold text-slate-900">
                                {{ $fy->year }}
                            </td>
                            <td data-label="Start Date" class="py-3.5 px-4 text-slate-600 font-mono text-xs">
                                {{ $fy->starts_on?->format('Y-m-d') ?? "{$fy->year}-01-01" }}
                            </td>
                            <td data-label="End Date" class="py-3.5 px-4 text-slate-600 font-mono text-xs">
                                {{ $fy->ends_on?->format('Y-m-d') ?? "{$fy->year}-12-31" }}
                            </td>
                            <td data-label="Status" class="py-3.5 px-4">
                                @if($fy->status === 'active')
                                    <x-ui.badge variant="success" dot>Active</x-ui.badge>
                                @elseif($fy->status === 'closed')
                                    <x-ui.badge variant="slate">Closed</x-ui.badge>
                                @else
                                    <x-ui.badge variant="info">Inactive</x-ui.badge>
                                @endif
                            </td>
                            <td data-label="Locked" class="py-3.5 px-4 text-center">
                                @if($fy->is_locked)
                                    <span class="inline-flex text-rose-600" title="Locked: read-only">
                                        <i class="fa-solid fa-lock text-xs"></i>
                                    </span>
                                @else
                                    <span class="inline-flex text-slate-300" title="Unlocked: writable">
                                        <i class="fa-solid fa-lock-open text-xs"></i>
                                    </span>
                                @endif
                            </td>
                            <td data-label="Records Scoped" class="py-3.5 px-4 text-xs text-slate-600">
                                <span class="font-medium text-slate-800">{{ $fy->reports_count ?? 0 }}</span> rep,
                                <span class="font-medium text-slate-800">{{ $fy->cases_count ?? 0 }}</span> cases,
                                <span class="font-medium text-slate-800">{{ $fy->referrals_count ?? 0 }}</span> ref
                            </td>
                            <td data-label="Actions" class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    @if($fy->status !== 'active' && ! $fy->is_locked)
                                        <form method="POST" action="{{ route('admin.fiscal-years.activate', $fy) }}">
                                            @csrf
                                            <button type="submit" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                                Activate
                                            </button>
                                        </form>
                                    @endif

                                    @if($fy->status !== 'closed')
                                        <button type="button" onclick="openCloseModal({{ $fy->id }}, {{ $fy->year }})"
                                            class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline">
                                            Close
                                        </button>
                                    @else
                                        <button type="button" onclick="openReopenModal({{ $fy->id }}, {{ $fy->year }})"
                                            class="text-xs font-semibold text-amber-600 hover:text-amber-800 hover:underline">
                                            Reopen
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-slate-400 text-sm">
                                No fiscal years configured. Click "+ Add Fiscal Year" to initialize.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>

<!-- Add Modal -->
<dialog id="modal-add-fy" class="rounded-xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/40">
    <div class="p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Add Fiscal Year</h3>
            <button type="button" onclick="document.getElementById('modal-add-fy').close()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.fiscal-years.store') }}" class="mt-4 space-y-4">
            @csrf
            <x-ui.form-field label="Operational Year" name="year" required hint="e.g. {{ now()->addYear()->year }}">
                <input type="number" name="year" id="year" required min="2020" max="2050"
                    value="{{ old('year', now()->addYear()->year) }}"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500 font-bold" />
            </x-ui.form-field>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="activate" id="activate" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500" />
                <label for="activate" class="text-xs text-slate-700 font-medium">Activate immediately (deactivates current active year)</label>
            </div>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <x-ui.button type="button" variant="ghost" onclick="document.getElementById('modal-add-fy').close()">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">Create Year</x-ui.button>
            </div>
        </form>
    </div>
</dialog>

<!-- Close Modal -->
<dialog id="modal-close-fy" class="rounded-xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/40">
    <div class="p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Close Fiscal Year <span id="close-year-label"></span></h3>
            <button type="button" onclick="document.getElementById('modal-close-fy').close()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="form-close-fy" method="POST" action="" class="mt-4 space-y-4">
            @csrf
            <div class="p-3 bg-amber-50 rounded-lg text-xs text-amber-900">
                Closing this fiscal year marks it read-only and locks all associated complaint, blotter, and referral records from further editing.
            </div>

            <x-ui.form-field label="Closing Audit Note" name="note" hint="Optional note explaining annual closing.">
                <textarea name="note" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Concluded annual operational cycle and archived logs."></textarea>
            </x-ui.form-field>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <x-ui.button type="button" variant="ghost" onclick="document.getElementById('modal-close-fy').close()">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="danger">Confirm Close & Lock</x-ui.button>
            </div>
        </form>
    </div>
</dialog>

<!-- Reopen Modal -->
<dialog id="modal-reopen-fy" class="rounded-xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/40">
    <div class="p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Reopen Fiscal Year <span id="reopen-year-label"></span></h3>
            <button type="button" onclick="document.getElementById('modal-reopen-fy').close()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="form-reopen-fy" method="POST" action="" class="mt-4 space-y-4">
            @csrf
            <div class="p-3 bg-rose-50 rounded-lg text-xs text-rose-900">
                Reopening an archived fiscal year unlocks records for modifications and logs an audit entry. A mandatory justification note is required.
            </div>

            <x-ui.form-field label="Justification Note *" name="note" required hint="Required for audit compliance.">
                <textarea name="note" required rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" placeholder="e.g. Authorized correction of docketed blotter record per PB directive."></textarea>
            </x-ui.form-field>

            <div class="flex items-center justify-end gap-2 pt-4 border-t border-slate-100">
                <x-ui.button type="button" variant="ghost" onclick="document.getElementById('modal-reopen-fy').close()">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="amber">Confirm Reopen</x-ui.button>
            </div>
        </form>
    </div>
</dialog>

<script>
    function openCloseModal(id, year) {
        document.getElementById('close-year-label').textContent = year;
        document.getElementById('form-close-fy').action = `/admin/settings/fiscal-years/${id}/close`;
        document.getElementById('modal-close-fy').showModal();
    }

    function openReopenModal(id, year) {
        document.getElementById('reopen-year-label').textContent = year;
        document.getElementById('form-reopen-fy').action = `/admin/settings/fiscal-years/${id}/reopen`;
        document.getElementById('modal-reopen-fy').showModal();
    }
</script>
@endsection
