@extends('setup.layout')

@section('title', 'Step 1: System Verification')

@section('step-content')
<div>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900 tracking-tight">Step 1: System Verification</h2>
        <p class="text-sm text-slate-500 mt-1">Confirm that the server and storage environment are ready for configuration.</p>
    </div>

    <div class="space-y-4 mb-8">
        @foreach($checks as $key => $check)
            <div class="flex items-start justify-between p-4 rounded-lg border {{ $check['passed'] ? 'border-emerald-200 bg-emerald-50/40' : 'border-rose-200 bg-rose-50/40' }}">
                <div class="flex items-start gap-3">
                    <div class="mt-0.5">
                        @if($check['passed'])
                            <i class="fa-solid fa-circle-check text-emerald-600 text-base"></i>
                        @else
                            <i class="fa-solid fa-circle-xmark text-rose-600 text-base"></i>
                        @endif
                    </div>
                    <div>
                        <h4 class="text-sm font-semibold text-slate-900">{{ $check['label'] }}</h4>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $check['message'] }}</p>
                    </div>
                </div>
                <span @class([
                    'inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold',
                    'bg-emerald-100 text-emerald-800' => $check['passed'],
                    'bg-rose-100 text-rose-800' => ! $check['passed'],
                ])>
                    {{ $check['passed'] ? 'Ready' : 'Issue Detected' }}
                </span>
            </div>
        @endforeach
    </div>

    <form method="POST" action="{{ route('setup.store', ['step' => 1]) }}" class="space-y-4 pt-4 border-t border-slate-100">
        @csrf

        @if(! empty($requiresSetupToken))
            <x-ui.form-field label="Installation Token" name="setup_token" required hint="Enter the single-use SETUP-XXXX-XXXX token printed in the server console after running php artisan app:empty.">
                <input type="text" name="setup_token" id="setup_token" required
                    value="{{ old('setup_token') }}"
                    placeholder="SETUP-XXXX-XXXX"
                    class="w-full rounded-lg border-slate-300 text-sm font-mono uppercase focus:border-blue-500 focus:ring-blue-500" />
            </x-ui.form-field>
        @endif

        <div class="flex items-center justify-end">
            <x-ui.button type="submit" variant="primary">
                <span>Start Setup</span>
                <i class="fa-solid fa-arrow-right text-xs ml-1.5"></i>
            </x-ui.button>
        </div>
    </form>
</div>
@endsection
