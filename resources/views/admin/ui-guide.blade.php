@extends('layouts.admin')

@section('content')
<div class="max-w-6xl mx-auto py-6 px-4 space-y-8">
    <x-ui.page-header title="Phase 0 UI Component Library" subtitle="Living design system style guide for all workstreams">
        <x-slot:actions>
            <x-ui.button variant="secondary" size="sm">
                <i class="fa-solid fa-code"></i> Docs
            </x-ui.button>
            <x-ui.button variant="amber" size="sm">
                <i class="fa-solid fa-plus"></i> New Example
            </x-ui.button>
        </x-slot:actions>
    </x-ui.page-header>

    <!-- Watermark / Draft status -->
    <x-ui.card title="Confirmation States">
        <div class="space-y-4">
            <div>
                <p class="text-xs text-slate-500 mb-1">Draft watermark (used on unconfirmed records):</p>
                <x-ui.draft-watermark :confirmed="false" />
            </div>
            <div>
                <p class="text-xs text-slate-500 mb-1">Pending badge (used in headers):</p>
                <x-ui.pending-badge :count="5" />
            </div>
        </div>
    </x-ui.card>

    <!-- Buttons -->
    <x-ui.card title="Buttons (<x-ui.button>)">
        <div class="space-y-4">
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button variant="primary">Primary Button</x-ui.button>
                <x-ui.button variant="amber">Amber / Action Button</x-ui.button>
                <x-ui.button variant="secondary">Secondary Button</x-ui.button>
                <x-ui.button variant="danger">Danger Button</x-ui.button>
                <x-ui.button variant="ghost">Ghost Button</x-ui.button>
            </div>
            <div class="flex flex-wrap items-center gap-3">
                <x-ui.button size="sm" variant="primary">Small</x-ui.button>
                <x-ui.button size="md" variant="primary">Medium</x-ui.button>
                <x-ui.button size="lg" variant="primary">Large</x-ui.button>
            </div>
        </div>
    </x-ui.card>

    <!-- Badges -->
    <x-ui.card title="Badges (<x-ui.badge>)">
        <div class="flex flex-wrap items-center gap-3">
            <x-ui.badge color="blue" dot>Blue / Filed</x-ui.badge>
            <x-ui.badge color="green" dot>Green / Settled</x-ui.badge>
            <x-ui.badge color="amber" dot>Amber / In Mediation</x-ui.badge>
            <x-ui.badge color="red" dot>Red / Critical / Overdue</x-ui.badge>
            <x-ui.badge color="purple" dot>Purple / Escalated</x-ui.badge>
            <x-ui.badge color="slate" dot>Slate / Dismissed</x-ui.badge>
        </div>
    </x-ui.card>

    <!-- Form Fields -->
    <x-ui.card title="Form Fields (<x-ui.form-field>)">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <x-ui.form-field label="Full Name" name="name" required hint="Enter complainant or respondent full name.">
                <input type="text" id="name" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:outline-none" placeholder="Juan dela Cruz">
            </x-ui.form-field>

            <x-ui.form-field label="Case Category" name="category" required>
                <select id="category" class="w-full px-3 py-2 text-sm border border-slate-300 rounded-lg focus:ring-1 focus:ring-blue-500 focus:outline-none">
                    <option>Boundary Dispute</option>
                    <option>Neighborhood Noise</option>
                </select>
            </x-ui.form-field>
        </div>
    </x-ui.card>

    <!-- Table -->
    <x-ui.card title="Data Table (<x-ui.table>)">
        <x-ui.table>
            <x-slot:header>
                <tr>
                    <th class="px-4 py-3">Case Number</th>
                    <th class="px-4 py-3">Title</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Action</th>
                </tr>
            </x-slot:header>
            <tr>
                <td class="px-4 py-3 font-mono font-bold text-blue-600">CASE-2026-00001</td>
                <td class="px-4 py-3">Neighborhood property boundary altercation</td>
                <td class="px-4 py-3"><x-ui.badge color="amber" dot>In Mediation</x-ui.badge></td>
                <td class="px-4 py-3 text-right">
                    <x-ui.button variant="secondary" size="sm">View</x-ui.button>
                </td>
            </tr>
            <tr>
                <td class="px-4 py-3 font-mono font-bold text-blue-600">CASE-2026-00002</td>
                <td class="px-4 py-3">Dispute regarding drainage line runoff</td>
                <td class="px-4 py-3"><x-ui.badge color="green" dot>Settled</x-ui.badge></td>
                <td class="px-4 py-3 text-right">
                    <x-ui.button variant="secondary" size="sm">View</x-ui.button>
                </td>
            </tr>
        </x-ui.table>
    </x-ui.card>

    <!-- Empty State -->
    <x-ui.card title="Empty State (<x-ui.empty-state>)">
        <x-ui.empty-state title="No cases recorded for this period" description="Cases filed for the active fiscal year will appear here.">
            <x-slot:action>
                <x-ui.button variant="primary" size="sm">File New Case</x-ui.button>
            </x-slot:action>
        </x-ui.empty-state>
    </x-ui.card>
</div>
@endsection
