<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Agency Portal') | Barangay Calarian</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('head')
</head>
<body class="bg-slate-50 text-slate-900 min-h-screen">
  <div class="flex min-h-screen">
    <!-- Agency Sidebar -->
    <aside class="w-64 bg-slate-900 text-white flex flex-col shrink-0">
      <div class="p-6 border-b border-slate-800">
        <a href="{{ route('agency.referrals.index') }}" class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-xl bg-blue-600 flex items-center justify-center text-white font-bold text-lg">
            <i class="fa-solid fa-building-shield"></i>
          </div>
          <div>
            <strong class="block text-sm font-bold tracking-tight">Agency Portal</strong>
            <small class="text-xs text-slate-400">{{ auth()->user()->office?->name ?? 'External Agency' }}</small>
          </div>
        </a>
      </div>

      <nav class="flex-1 p-4 space-y-1">
        @php
          $navSections = config('navigation.agency', []);
          $isOfficeAdmin = (bool) (auth()->user()->agencyRegistration?->is_office_admin ?? false);
        @endphp

        @foreach($navSections as $section)
          @if(!empty($section['section']))
            <div class="px-3 pt-4 pb-2 text-xs font-semibold text-slate-400 uppercase tracking-wider">
              {{ $section['section'] }}
            </div>
          @endif
          @foreach($section['items'] as $item)
            @if(empty($item['office_admin_only']) || $isOfficeAdmin)
              @php
                $isActive = !empty($item['active_pattern']) && request()->routeIs($item['active_pattern']);
              @endphp
              <a href="{{ route($item['route']) }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-colors {{ $isActive ? 'bg-blue-600 text-white font-semibold' : 'text-slate-300 hover:bg-slate-800 hover:text-white' }}">
                <i class="{{ $item['icon'] }} w-5 text-center"></i>
                <span>{{ $item['label'] }}</span>
              </a>
            @endif
          @endforeach
        @endforeach

        <div class="pt-4 border-t border-slate-800 mt-4">
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-slate-400 hover:bg-slate-800 hover:text-white w-full text-left">
              <i class="fa-solid fa-arrow-right-from-bracket w-5 text-center"></i>
              <span>Log out</span>
            </button>
          </form>
        </div>
      </nav>

      <footer class="p-4 border-t border-slate-800 text-xs text-slate-400">
        <div class="flex items-center gap-3">
          <span class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-white">
            {{ str(auth()->user()->name)->substr(0, 1)->upper() }}
          </span>
          <div class="truncate">
            <p class="font-semibold text-white truncate">{{ auth()->user()->name }}</p>
            <p class="truncate">{{ auth()->user()->agencyRegistration?->position_title ?? 'Representative' }}</p>
          </div>
        </div>
      </footer>
    </aside>

    <!-- Agency Main Content -->
    <div class="flex-1 flex flex-col min-w-0">
      <header class="bg-white border-b border-slate-200 py-4 px-6 sm:px-8 flex items-center justify-between">
        <div>
          <h1 class="text-xl font-bold text-slate-900">@yield('page-heading', 'Agency Portal')</h1>
          <p class="text-xs text-slate-500">Barangay Calarian · External Referral Management</p>
        </div>
        <div class="flex items-center gap-4">
          <span class="px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
            {{ auth()->user()->office?->name ?? 'External Partner' }}
          </span>
        </div>
      </header>

      <main class="flex-1 p-6 sm:p-8">
        <x-ui.flash />
        @yield('content')
      </main>
    </div>
  </div>
  @stack('scripts')
</body>
</html>
