@extends('layouts.admin')

@section('content')
<div class="mx-auto max-w-5xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">
    <div class="flex flex-col gap-3 border-b border-slate-200 pb-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Escalation Destinations</h1>
            <p class="mt-1 text-sm text-slate-600">Courts, PNP precincts, and government offices where unconciliated cases are escalated.</p>
        </div>
        <x-ui.button href="{{ route('admin.escalation-destinations.create') }}" variant="primary">
            <i class="fa-solid fa-plus mr-1.5"></i> Add Destination
        </x-ui.button>
    </div>

    @if($destinations->isEmpty())
        <div class="rounded-xl border border-dashed border-slate-300 p-12 text-center bg-white">
            <i class="fa-solid fa-building-columns text-4xl text-slate-400 mb-3"></i>
            <h3 class="text-base font-bold text-slate-800">No destinations configured</h3>
            <p class="mt-1 text-xs text-slate-500">Configure external courts, prosecutors, and agencies for case referral and CFA issuance.</p>
        </div>
    @else
        <div class="overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
            <table class="w-full text-left text-xs">
                <thead class="border-b border-slate-200 bg-slate-50 text-slate-600 font-semibold uppercase tracking-wider">
                    <tr>
                        <th class="py-3 px-4">Order</th>
                        <th class="py-3 px-4">Destination Name</th>
                        <th class="py-3 px-4">Linked Office</th>
                        <th class="py-3 px-4">Status</th>
                        <th class="py-3 px-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @foreach($destinations as $dest)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="py-3 px-4 font-mono text-slate-400">{{ $dest->sort_order }}</td>
                            <td class="py-3 px-4 font-bold text-slate-900">{{ $dest->name }}</td>
                            <td class="py-3 px-4 text-slate-600">{{ $dest->office?->name ?? 'None' }}</td>
                            <td class="py-3 px-4">
                                <span class="rounded px-2 py-0.5 text-[11px] font-semibold {{ $dest->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $dest->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-right whitespace-nowrap">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ route('admin.escalation-destinations.edit', $dest) }}" class="rounded border border-slate-300 bg-white px-2.5 py-1 font-semibold text-slate-700 hover:bg-slate-50 shadow-sm">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.escalation-destinations.destroy', $dest) }}" onsubmit="return confirm('Delete this destination?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded border border-red-200 bg-white px-2.5 py-1 font-semibold text-red-600 hover:bg-red-50 shadow-sm">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $destinations->links() }}
        </div>
    @endif
</div>
@endsection
