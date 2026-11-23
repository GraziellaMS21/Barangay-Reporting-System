@extends('layouts.admin')

@section('title', 'Case Classifications')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <x-ui.page-header
        title="Dispute Classifications"
        subtitle="Katarungang Pambarangay dispute categories and case type directory"
    >
        <x-slot:actions>
            <a href="{{ route('admin.case-types.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                <i class="fa-solid fa-plus"></i>
                <span>Add Case Type</span>
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-sm text-emerald-800 font-medium">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="p-4 bg-red-50 border-l-4 border-red-500 rounded-r-lg text-sm text-red-800 font-medium">
            {{ $errors->first() }}
        </div>
    @endif

    <x-ui.card class="overflow-hidden border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th class="py-3.5 pl-4 pr-3 sm:pl-6 w-[25%]">Classification Name</th>
                        <th class="px-3 py-3.5 w-[35%]">Description</th>
                        <th class="px-3 py-3.5 w-[12%] text-center">Order</th>
                        <th class="px-3 py-3.5 w-[12%] text-center">Status</th>
                        <th class="py-3.5 pl-3 pr-4 sm:pr-6 w-[16%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                    @forelse($caseTypes as $type)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <td class="py-3.5 pl-4 pr-3 sm:pl-6 font-semibold text-slate-900 text-xs">
                                <div class="flex items-center gap-2">
                                    <span>{{ $type->name }}</span>
                                    @if(!$type->is_confirmed)
                                        <x-ui.pending-badge />
                                    @endif
                                </div>
                                <span class="text-[11px] font-mono text-slate-400 block mt-0.5">{{ $type->slug }}</span>
                            </td>

                            <td class="px-3 py-3.5 text-xs text-slate-600">
                                {{ $type->description ?: 'No description provided' }}
                            </td>

                            <td class="px-3 py-3.5 text-xs text-center font-mono text-slate-500">
                                {{ $type->sort_order }}
                            </td>

                            <td class="px-3 py-3.5 text-xs text-center">
                                @if($type->is_active)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">Active</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-semibold bg-slate-100 text-slate-600">Inactive</span>
                                @endif
                            </td>

                            <td class="py-3.5 pl-3 pr-4 sm:pr-6 text-right whitespace-nowrap text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.case-types.edit', $type) }}" class="text-blue-600 hover:text-blue-800 font-medium">
                                        Edit
                                    </a>
                                    @if($type->cases_count === 0)
                                        <form action="{{ route('admin.case-types.destroy', $type) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this case type?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-500 hover:text-red-700 font-medium">
                                                Delete
                                            </button>
                                        </form>
                                    @else
                                        <span class="text-slate-300 font-medium cursor-not-allowed" title="In use by {{ $type->cases_count }} cases">Delete</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-12">
                                <x-ui.empty-state title="No Case Types Found" description="Click 'Add Case Type' to register dispute classifications." />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($caseTypes->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $caseTypes->links() }}
            </div>
        @endif
    </x-ui.card>
</div>
@endsection
