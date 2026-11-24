@extends('layouts.admin')

@section('title', 'Complaint Intake #' . $intake->id)

@section('content')
<div class="max-w-4xl mx-auto space-y-6">
    <div class="flex items-center justify-between border-b border-slate-200 pb-4">
        <div>
            <nav class="flex text-xs text-slate-500 mb-1" aria-label="Breadcrumb">
                <ol class="flex items-center space-x-2">
                    <li><a href="{{ route('admin.cases.index', ['tab' => 'pending_intake']) }}" class="hover:text-slate-800">Pending Intakes</a></li>
                    <li><span class="text-slate-400">/</span></li>
                    <li class="font-semibold text-slate-800">Intake #{{ $intake->id }}</li>
                </ol>
            </nav>
            <h1 class="text-2xl font-bold text-slate-900">Citizen Complaint Intake Details</h1>
            <p class="text-xs text-slate-600 mt-0.5">Submitted {{ $intake->submitted_at?->format('F j, Y g:i A') }} ({{ $intake->submitted_at?->diffForHumans() }})</p>
        </div>
        <div class="flex items-center gap-2">
            @can('cases.create')
                <a href="{{ route('admin.cases.create', ['intake_id' => $intake->id]) }}" class="px-4 py-2 text-xs font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 shadow-sm transition-colors">
                    <i class="fa-solid fa-plus-circle mr-1"></i> Docket this Case
                </a>
            @endcan
        </div>
    </div>

    <!-- Details Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Complainant Card -->
        <x-ui.card class="p-5 border border-slate-200 space-y-3">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-2">
                <i class="fa-solid fa-user-tag text-blue-600"></i> Complainant Information
            </h2>
            <div class="text-xs space-y-1.5 text-slate-700">
                <div><span class="font-semibold text-slate-500">Full Name:</span> {{ $intake->complainant_name }}</div>
                @if($intake->complainant_alias)
                    <div><span class="font-semibold text-slate-500">Alias:</span> {{ $intake->complainant_alias }}</div>
                @endif
                <div><span class="font-semibold text-slate-500">Contact:</span> {{ $intake->complainant_contact }}</div>
                <div><span class="font-semibold text-slate-500">Address:</span> {{ $intake->complainant_address }}</div>
                <div><span class="font-semibold text-slate-500">Residency Claim:</span> <span class="font-bold">{{ ucfirst($intake->complainant_residency_claim) }}</span></div>
            </div>
        </x-ui.card>

        <!-- Respondent Card -->
        <x-ui.card class="p-5 border border-slate-200 space-y-3">
            <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-2">
                <i class="fa-solid fa-user-xmark text-slate-700"></i> Respondent Information
            </h2>
            <div class="text-xs space-y-1.5 text-slate-700">
                <div><span class="font-semibold text-slate-500">Full Name:</span> {{ $intake->respondent_name }}</div>
                @if($intake->respondent_alias)
                    <div><span class="font-semibold text-slate-500">Alias:</span> {{ $intake->respondent_alias }}</div>
                @endif
                <div><span class="font-semibold text-slate-500">Contact:</span> {{ $intake->respondent_contact ?? 'Not provided' }}</div>
                <div><span class="font-semibold text-slate-500">Address:</span> {{ $intake->respondent_address }}</div>
            </div>
        </x-ui.card>
    </div>

    <!-- Narrative & Legal Grounds -->
    <x-ui.card class="p-5 border border-slate-200 space-y-4">
        <h2 class="text-sm font-bold text-slate-800 flex items-center gap-2 border-b border-slate-100 pb-2">
            <i class="fa-solid fa-clipboard-question text-indigo-600"></i> Incident Narrative & Legal Relief
        </h2>
        <div class="text-xs space-y-3 text-slate-700">
            <div>
                <p class="font-semibold text-slate-500 mb-1">What Happened:</p>
                <p class="bg-slate-50 p-3 rounded-lg border border-slate-200 text-slate-800 leading-relaxed">{{ $intake->what_happened }}</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <span class="font-semibold text-slate-500">Who Was Involved:</span>
                    <p class="mt-0.5">{{ $intake->who_involved }}</p>
                </div>
                <div>
                    <span class="font-semibold text-slate-500">Date & Time:</span>
                    <p class="mt-0.5">{{ $intake->when_happened?->format('F j, Y g:i A') }}</p>
                </div>
            </div>
            <div>
                <span class="font-semibold text-slate-500">Location:</span>
                <p class="mt-0.5">{{ $intake->where_happened }}</p>
            </div>
            <div>
                <p class="font-semibold text-slate-500 mb-1">Why / How Happened:</p>
                <p class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-slate-800">{{ $intake->why_how_happened }}</p>
            </div>
            <div>
                <p class="font-semibold text-slate-500 mb-1">Rights Violated:</p>
                <p class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-slate-800">{{ $intake->rights_violated }}</p>
            </div>
            <div>
                <p class="font-semibold text-slate-500 mb-1">Relief Requested:</p>
                <p class="bg-slate-50 p-2.5 rounded-lg border border-slate-200 text-slate-800">{{ $intake->relief_requested }}</p>
            </div>
        </div>
    </x-ui.card>
</div>
@endsection
