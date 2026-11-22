@extends('layouts.admin')

@section('title', 'Master Party Directory')

@section('content')
<div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8 space-y-6">

    <!-- Page Header -->
    <x-ui.page-header
        title="Master Party Directory"
        subtitle="Reusable directory of complainants, respondents, and witnesses with residency verification"
    >
        <x-slot:actions>
            @can('cases.create')
                <a href="{{ route('admin.cases.create') }}" class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition-colors shadow-sm">
                    <i class="fa-solid fa-plus"></i>
                    <span>Add New Person</span>
                </a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    @if (session('success'))
        <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-lg text-sm text-emerald-800 font-medium">
            {{ session('success') }}
        </div>
    @endif

    <!-- Filters Card -->
    <x-ui.card class="p-4 bg-white border border-slate-200">
        <form method="GET" action="{{ route('admin.parties.index') }}" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3 text-sm">
            <!-- Search -->
            <div class="lg:col-span-2">
                <label for="search" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Search Directory</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <i class="fa-solid fa-magnifying-glass text-xs"></i>
                    </div>
                    <input
                        type="text"
                        name="search"
                        id="search"
                        value="{{ $search }}"
                        placeholder="Name, alias, phone, or ID number..."
                        class="block w-full pl-8 pr-3 py-1.5 text-xs border border-slate-300 rounded-md focus:ring-1 focus:ring-blue-500 text-slate-800"
                    />
                </div>
            </div>

            <!-- Residency Filter -->
            <div>
                <label for="residency" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Residency</label>
                <select name="residency" id="residency" class="block w-full py-1.5 px-2 text-xs border border-slate-300 rounded-md focus:ring-1 focus:ring-blue-500 text-slate-800">
                    <option value="all">All Residency</option>
                    <option value="resident" @selected($residency === 'resident')>Resident</option>
                    <option value="non_resident" @selected($residency === 'non_resident')>Non-Resident</option>
                    <option value="unverified" @selected($residency === 'unverified')>Unverified</option>
                </select>
            </div>

            <!-- Purok Filter -->
            <div>
                <label for="purok_id" class="block text-xs font-semibold text-slate-700 uppercase tracking-wider mb-1">Purok</label>
                <select name="purok_id" id="purok_id" class="block w-full py-1.5 px-2 text-xs border border-slate-300 rounded-md focus:ring-1 focus:ring-blue-500 text-slate-800">
                    <option value="">All Puroks</option>
                    @foreach($puroks as $pk)
                        <option value="{{ $pk->id }}" @selected($purokId == $pk->id)>{{ $pk->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Buttons -->
            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 py-1.5 px-3 text-xs font-semibold text-white bg-slate-800 rounded-md hover:bg-slate-900 transition-colors">
                    Filter
                </button>
                @if(request()->hasAny(['search', 'residency', 'purok_id']))
                    <a href="{{ route('admin.parties.index') }}" class="py-1.5 px-2 text-xs font-medium text-slate-500 hover:text-slate-800 border border-slate-300 rounded-md bg-white text-center">
                        Clear
                    </a>
                @endif
            </div>
        </form>
    </x-ui.card>

    <!-- Master Directory Table -->
    <x-ui.card class="overflow-hidden border border-slate-200">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200 text-left text-sm">
                <thead class="bg-slate-50 text-xs font-bold text-slate-500 uppercase tracking-wider">
                    <tr>
                        <th scope="col" class="py-3.5 pl-4 pr-3 sm:pl-6 w-[26%]">Name</th>
                        <th scope="col" class="px-3 py-3.5 w-[14%]">Residency</th>
                        <th scope="col" class="px-3 py-3.5 w-[14%]">Purok / Origin</th>
                        <th scope="col" class="px-3 py-3.5 w-[18%]">Contact</th>
                        <th scope="col" class="px-3 py-3.5 w-[10%] text-right">Cases</th>
                        <th scope="col" class="py-3.5 pl-3 pr-4 sm:pr-6 w-[18%] text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200 bg-white text-slate-700">
                    @forelse($parties as $party)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Name & Alias -->
                            <td class="py-3.5 pl-4 pr-3 sm:pl-6 font-medium text-slate-900 text-xs">
                                <a href="{{ route('admin.parties.show', $party) }}" class="font-bold text-blue-600 hover:underline">
                                    {{ $party->full_name }}
                                </a>
                                @if($party->alias)
                                    <span class="text-slate-500 italic block text-[11px]">aka "{{ $party->alias }}"</span>
                                @endif
                            </td>

                            <!-- Residency Badge -->
                            <td class="px-3 py-3.5 text-xs whitespace-nowrap">
                                @php
                                    $resColor = match($party->residency_status) {
                                        'resident' => 'green',
                                        'non_resident' => 'blue',
                                        default => 'amber',
                                    };
                                @endphp
                                <x-ui.badge :color="$resColor">
                                    {{ ucfirst(str_replace('_', ' ', $party->residency_status)) }}
                                </x-ui.badge>
                            </td>

                            <!-- Purok / Origin -->
                            <td class="px-3 py-3.5 text-xs text-slate-600">
                                @if($party->residency_status === 'resident')
                                    <span>{{ $party->purok?->name ?? 'Calarian' }}</span>
                                @else
                                    <span>{{ $party->home_barangay ? "{$party->home_barangay}, {$party->home_city}" : ($party->home_city ?? 'Non-Resident') }}</span>
                                @endif
                            </td>

                            <!-- Contact -->
                            <td class="px-3 py-3.5 text-xs text-slate-600">
                                <div>{{ $party->contact_number ?: 'None' }}</div>
                                @if($party->email)
                                    <div class="text-[11px] text-slate-400">{{ $party->email }}</div>
                                @endif
                            </td>

                            <!-- Cases Lifetime Count -->
                            <td class="px-3 py-3.5 text-xs text-right font-bold text-slate-800">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] bg-slate-100 font-mono">
                                    {{ $party->cases_count }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="py-3.5 pl-3 pr-4 sm:pr-6 text-right whitespace-nowrap text-xs">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('admin.parties.show', $party) }}" class="px-2.5 py-1 rounded bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium transition-colors">
                                        Profile
                                    </a>
                                    @can('cases.edit')
                                        <a href="{{ route('admin.parties.edit', $party) }}" class="px-2.5 py-1 rounded bg-blue-50 hover:bg-blue-100 text-blue-700 font-medium transition-colors">
                                            Edit
                                        </a>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12">
                                <x-ui.empty-state
                                    title="No Party Records Found"
                                    description="No individuals match the current search or residency filters."
                                />
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($parties->hasPages())
            <div class="p-4 border-t border-slate-200 bg-slate-50">
                {{ $parties->links() }}
            </div>
        @endif
    </x-ui.card>

</div>
@endsection
