@extends('layouts.admin')

@section('title', 'Puroks & Zones')
@section('page-heading', 'Puroks & Zones')

@section('content')
<div class="max-w-4xl space-y-6">
    <x-ui.page-header
        title="Puroks & Zones"
        subtitle="Manage the official barangay puroks, zones, or sitios selectable on community reports and case dockets.">
        <x-slot:actions>
            <x-ui.button type="button" variant="primary" onclick="document.getElementById('modal-add-purok').showModal()">
                <i class="fa-solid fa-plus text-xs mr-1.5"></i>
                <span>Add Purok / Zone</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation text-rose-600"></i>
            <span>{{ session('error') }}</span>
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
            <table class="w-full text-left border-collapse text-sm responsive-card-table">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider bg-slate-50/50">
                        <th class="py-3 px-4">Purok / Zone Name</th>
                        <th class="py-3 px-4 w-32">Linked Reports</th>
                        <th class="py-3 px-4 w-48 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($puroks as $purok)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td data-label="Purok / Zone Name" class="py-3.5 px-4 font-semibold text-slate-900">
                                {{ $purok->name }}
                            </td>
                            <td data-label="Linked Reports" class="py-3.5 px-4 text-xs text-slate-600">
                                {{ $purok->reports_count }}
                            </td>
                            <td data-label="Actions" class="py-3.5 px-4 text-right space-x-3">
                                <button type="button"
                                    onclick="openEditPurok({{ $purok->id }}, @js($purok->name))"
                                    class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                    Rename
                                </button>
                                @if($purok->reports_count === 0)
                                    <form method="POST" action="{{ route('admin.puroks.destroy', $purok) }}" class="inline" onsubmit="return confirm('Remove this purok?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline">
                                            Delete
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-8 text-center text-slate-400 text-sm">
                                No puroks or zones configured yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>

<!-- Add Modal -->
<dialog id="modal-add-purok" class="rounded-xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/40">
    <div class="p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Add Purok / Zone</h3>
            <button type="button" onclick="document.getElementById('modal-add-purok').close()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form method="POST" action="{{ route('admin.puroks.store') }}" class="mt-4 space-y-4">
            @csrf
            <x-ui.form-field label="Purok / Sitio Name" name="name" required>
                <input type="text" name="name" required placeholder="e.g. Purok 8 - Maharlika"
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>
            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="secondary" onclick="document.getElementById('modal-add-purok').close()">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">Save Purok</x-ui.button>
            </div>
        </form>
    </div>
</dialog>

<!-- Edit Modal -->
<dialog id="modal-edit-purok" class="rounded-xl shadow-2xl p-0 w-full max-w-md backdrop:bg-slate-900/40">
    <div class="p-6">
        <div class="flex items-center justify-between pb-4 border-b border-slate-100">
            <h3 class="text-lg font-bold text-slate-900">Rename Purok / Zone</h3>
            <button type="button" onclick="document.getElementById('modal-edit-purok').close()" class="text-slate-400 hover:text-slate-600">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <form id="form-edit-purok" method="POST" action="" class="mt-4 space-y-4">
            @csrf
            @method('PUT')
            <x-ui.form-field label="Purok / Sitio Name" name="name" required>
                <input type="text" name="name" id="edit-purok-name" required
                    class="w-full rounded-lg border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>
            <div class="flex justify-end gap-2 pt-2">
                <x-ui.button type="button" variant="secondary" onclick="document.getElementById('modal-edit-purok').close()">Cancel</x-ui.button>
                <x-ui.button type="submit" variant="primary">Update Name</x-ui.button>
            </div>
        </form>
    </div>
</dialog>

<script>
function openEditPurok(id, name) {
    const form = document.getElementById('form-edit-purok');
    form.action = "{{ url('/admin/settings/puroks') }}/" + id;
    document.getElementById('edit-purok-name').value = name;
    document.getElementById('modal-edit-purok').showModal();
}
</script>
@endsection
