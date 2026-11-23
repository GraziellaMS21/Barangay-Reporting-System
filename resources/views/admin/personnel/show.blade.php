@extends('layouts.admin')

@section('title', "Official: {$personnel->full_name}")
@section('page-heading', 'Personnel Profile')

@section('content')
<x-ui.page-header :title="$personnel->full_name" :subtitle="$personnel->position?->name ?? 'Barangay Official'">
    <x-slot:actions>
        <x-ui.button variant="secondary" :href="route('admin.personnel.index')">
            <i class="fa-solid fa-arrow-left"></i> Back to Directory
        </x-ui.button>
        <x-ui.button variant="primary" :href="route('admin.personnel.edit', $personnel)">
            <i class="fa-solid fa-pen"></i> Edit Official
        </x-ui.button>
    </x-slot:actions>
</x-ui.page-header>

<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <x-ui.card title="Official Details">
        <dl class="space-y-3 text-sm">
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Full Name</dt>
                <dd class="font-semibold text-slate-900">{{ $personnel->full_name }}</dd>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Position</dt>
                <dd class="font-medium text-slate-800">{{ $personnel->position?->name ?? 'Unassigned' }}</dd>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Status</dt>
                <dd>
                    <x-ui.badge :color="$personnel->is_active ? 'green' : 'slate'" :dot="true">
                        {{ $personnel->is_active ? 'Active' : 'Inactive' }}
                    </x-ui.badge>
                </dd>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Term of Office</dt>
                <dd class="text-slate-800">{{ $personnel->term_start?->format('M d, Y') ?? '—' }} to {{ $personnel->term_end?->format('M d, Y') ?? 'Present' }}</dd>
            </div>
            <div class="flex justify-between border-b border-slate-100 pb-2">
                <dt class="text-slate-500">Contact Number</dt>
                <dd class="text-slate-800">{{ $personnel->contact_number ?: '—' }}</dd>
            </div>
            <div class="flex justify-between pb-1">
                <dt class="text-slate-500">Email</dt>
                <dd class="text-slate-800">{{ $personnel->email ?: '—' }}</dd>
            </div>
        </dl>
    </x-ui.card>

    <x-ui.card title="Signatory & Account Status">
        <div class="space-y-4 text-sm">
            <div>
                <span class="text-slate-500 block mb-1">Signatory Authorization</span>
                @if($personnel->is_signatory)
                    <x-ui.badge color="green">Authorized Document Signer</x-ui.badge>
                @else
                    <x-ui.badge color="slate">Not a Document Signer</x-ui.badge>
                @endif
            </div>

            @if($signatureDataUri)
                <div>
                    <span class="text-slate-500 block mb-1">Stored Signature specimen</span>
                    <img src="{{ $signatureDataUri }}" alt="Signature specimen" class="max-h-16 border border-slate-200 rounded p-2 bg-slate-50">
                </div>
            @endif

            <div>
                <span class="text-slate-500 block mb-1">Linked User Account</span>
                @if($personnel->user)
                    <p class="font-semibold text-slate-900">{{ $personnel->user->email }} ({{ ucfirst($personnel->user->role) }})</p>
                @else
                    <p class="text-slate-500">No system login linked.</p>
                @endif
            </div>

            @if($personnel->notes)
                <div>
                    <span class="text-slate-500 block mb-1">Notes</span>
                    <p class="whitespace-pre-line text-slate-700 bg-slate-50 p-3 rounded-lg border border-slate-200">{{ $personnel->notes }}</p>
                </div>
            @endif
        </div>
    </x-ui.card>
</div>
@endsection
