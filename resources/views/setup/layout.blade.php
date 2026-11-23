<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'System Setup') | Barangay Calarian</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col justify-between antialiased">
    <!-- Header -->
    <header class="py-6 px-4 sm:px-6 bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-3xl mx-auto flex items-center justify-between">
            <div class="flex items-center gap-3">
                <x-brand-seal />
                <div>
                    <h1 class="text-lg font-bold text-slate-900 tracking-tight">Barangay Calarian</h1>
                    <p class="text-xs text-slate-500 font-medium">System Onboarding & Configuration</p>
                </div>
            </div>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                Setup Wizard
            </span>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 py-8 px-4 sm:px-6">
        <div class="max-w-3xl mx-auto">
            @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm flex items-center gap-2">
                    <i class="fa-solid fa-circle-check text-emerald-600"></i>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-rose-50 border border-rose-200 text-rose-800 text-sm">
                    <div class="flex items-center gap-2 font-semibold mb-1">
                        <i class="fa-solid fa-triangle-exclamation text-rose-600"></i>
                        <span>Please correct the errors below:</span>
                    </div>
                    <ul class="list-disc list-inside space-y-0.5 text-xs text-rose-700">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <x-ui.stepper :steps="$steps ?? []" :current="$currentStep ?? 1" />

            <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 sm:p-8">
                    @yield('step-content')
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <footer class="py-4 text-center text-xs text-slate-400 border-t border-slate-200 bg-white">
        Barangay Calarian Executive Management & Reporting Platform &copy; {{ date('Y') }}
    </footer>
</body>
</html>
