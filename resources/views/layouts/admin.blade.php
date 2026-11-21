<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Staff Dashboard') | Barangay Calarian</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('head')
</head>
<x-hall-background>
  <div class="staff-shell">
    <aside id="staff-sidebar" class="staff-sidebar" aria-label="Staff navigation">
      <a href="{{ route('admin.dashboard') }}" class="site-brand staff-sidebar-brand" title="Calarian Reports">
        <x-brand-seal />
        <span class="sidebar-brand-text">
          <strong>Calarian Reports</strong>
          <small>{{ auth()->user()->isAdmin() ? 'Admin Dashboard' : 'Staff Dashboard' }}</small>
        </span>
      </a>
      <nav class="staff-nav" aria-label="Staff navigation">
        @php
          $adminNav = config('navigation.admin', []);
          $unconfirmedCount = 0;
          try {
              $unconfirmedCount = app(\App\Services\SettingsService::class)->unconfirmed()->count();
          } catch (\Throwable) {}
        @endphp

        @foreach($adminNav as $section)
          @php
            $visibleItems = collect($section['items'] ?? [])->filter(function ($item) {
              return empty($item['permission']) || auth()->user()->hasPermission($item['permission']);
            });
          @endphp

          @if($visibleItems->isNotEmpty())
            @if(!empty($section['section']))
              <div class="staff-nav-heading">
                <span class="nav-heading-text">{{ $section['section'] }}</span>
              </div>
            @endif

            @foreach($visibleItems as $item)
              @php
                $isActive = !empty($item['active_pattern']) && request()->routeIs($item['active_pattern']);
              @endphp
              <a href="{{ route($item['route']) }}" @class(['active' => $isActive]) title="{{ $item['label'] }}">
                <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                <span class="nav-label">{{ $item['label'] }}</span>
              </a>
            @endforeach
          @endif
        @endforeach

        <div class="staff-nav-heading">
          <span class="nav-heading-text">External</span>
        </div>
        <a href="{{ route('reports.create') }}" title="Resident site" target="_blank">
          <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
          <span class="nav-label">Resident site</span>
        </a>

        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit" title="Log out">
            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
            <span class="nav-label">Log out</span>
          </button>
        </form>
      </nav>
      <footer class="staff-sidebar-footer" title="{{ auth()->user()->name }} ({{ auth()->user()->isAdmin() ? 'Admin' : 'Staff' }})">
        <span class="sidebar-avatar" aria-hidden="true">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
        <div class="sidebar-user-details">
          <strong>{{ auth()->user()->name }}</strong>
          <span class="role-badge {{ auth()->user()->isAdmin() ? 'role-admin' : 'role-staff' }}">
            {{ auth()->user()->isAdmin() ? 'Admin' : 'Staff' }}
          </span>
        </div>
      </footer>
    </aside>

    <div class="staff-main">
      <header class="staff-topbar">
        <button type="button" class="staff-menu-toggle" aria-label="Open staff navigation"
          aria-controls="staff-sidebar" aria-expanded="false">
          <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div>
          <h1>@yield('page-heading', 'Staff dashboard')</h1>
          <p>Barangay Calarian · Report management</p>
        </div>
        <div class="staff-header-actions flex items-center gap-3">
          <x-ui.global-search />
          <x-year-switcher />
          @if($unconfirmedCount > 0)
            <x-ui.pending-badge :count="$unconfirmedCount" />
          @endif
          <div class="staff-user">
            <span class="staff-avatar" aria-hidden="true">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
            <span class="staff-user-name">
              <strong>{{ auth()->user()->name }}</strong>
              <span class="role-badge {{ auth()->user()->isAdmin() ? 'role-admin' : 'role-staff' }}">
                {{ auth()->user()->isAdmin() ? 'Admin' : 'Staff' }}
              </span>
            </span>
          </div>
        </div>
      </header>

      <main class="staff-content">
        @if(session('success'))
          <div role="status" class="flash-success" data-toast>{{ session('success') }}</div>
        @endif
        @if(session('error'))
          <div role="alert" class="form-errors">{{ session('error') }}</div>
        @endif
        @yield('content')
      </main>
    </div>
  </div>
  <button type="button" class="staff-drawer-overlay" aria-label="Close staff navigation" tabindex="-1"></button>
  <script>document.querySelectorAll('[data-toast]').forEach(el=>{setTimeout(()=>{el.style.opacity='0';el.style.transition='opacity .3s';setTimeout(()=>el.remove(),300);},3000);});</script>
  @stack('scripts')
</x-hall-background>
</html>
