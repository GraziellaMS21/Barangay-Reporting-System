@extends('layouts.admin')

@section('title', 'Response Templates')
@section('page-heading', 'Response Templates')

@section('content')
<div class="space-y-6">
    <x-ui.page-header
        title="Response Templates"
        subtitle="Manage canned responses and official text templates for status updates, summons remarks, and referral transmittals.">
        <x-slot:actions>
            <x-ui.button href="{{ route('admin.templates.create') }}" variant="primary">
                <i class="fa-solid fa-plus text-xs mr-1.5"></i>
                <span>Create Template</span>
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    @if(session('success'))
        <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
            <i class="fa-solid fa-circle-check text-emerald-600"></i>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <x-ui.card>
        <div class="overflow-hidden">
            <table class="w-full text-left border-collapse text-sm table-fixed responsive-card-table">
                <thead>
                    <tr class="border-b border-slate-200 text-xs font-semibold text-slate-500 uppercase tracking-wider bg-slate-50/50">
                        <th class="py-3 px-4 w-[28%]">Title</th>
                        <th class="py-3 px-4 w-[18%]">Kind</th>
                        <th class="py-3 px-4 w-[34%]">Body Preview</th>
                        <th class="py-3 px-4 w-[10%] text-center">Status</th>
                        <th class="py-3 px-4 w-[10%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($templates as $t)
                        <tr class="hover:bg-slate-50/80">
                            <td data-label="Title" class="py-3.5 px-4 font-semibold text-slate-900 text-xs">
                                {{ $t->title }}
                            </td>
                            <td data-label="Kind" class="py-3.5 px-4 text-xs">
                                <span class="px-2 py-0.5 rounded bg-slate-100 text-slate-700 font-medium">
                                    {{ ucwords(str_replace('_', ' ', $t->kind)) }}
                                </span>
                            </td>
                            <td data-label="Body Preview" class="py-3.5 px-4 text-xs text-slate-600 truncate max-w-xs">
                                {{ Str::limit($t->body, 80) }}
                            </td>
                            <td data-label="Status" class="py-3.5 px-4 text-center">
                                @if($t->is_active)
                                    <x-ui.badge variant="success" dot>Active</x-ui.badge>
                                @else
                                    <x-ui.badge variant="slate">Inactive</x-ui.badge>
                                @endif
                            </td>
                            <td data-label="Actions" class="py-3.5 px-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.templates.edit', $t) }}" class="text-xs font-semibold text-blue-600 hover:text-blue-800 hover:underline">
                                        Edit
                                    </a>
                                    <form method="POST" action="{{ route('admin.templates.destroy', $t) }}" onsubmit="return confirm('Delete this template?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-rose-600 hover:text-rose-800 hover:underline">
                                            Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400 text-sm">
                                No response templates configured. Click "+ Create Template" to add one.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</div>
@endsection
