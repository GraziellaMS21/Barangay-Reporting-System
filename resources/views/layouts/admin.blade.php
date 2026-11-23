<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  @php
    $currentUser = auth()->user();
    $isAdmin = $currentUser?->isAdmin() ?? false;
    $isSecretary = $currentUser?->isSecretary() ?? false;

    $personnel = $currentUser?->relationLoaded('personnel')
        ? $currentUser->personnel
        : $currentUser?->personnel()->with('position')->first();
    $positionName = $personnel?->position?->name;

    if ($isAdmin) {
        $roleLabel = 'Admin';
        $roleClass = 'admin';
        $brandSubtitle = 'Admin Dashboard';
        $dashboardRoute = route('admin.dashboard');
    } elseif ($isSecretary) {
        $roleLabel = $positionName ?: 'Secretary';
        $roleClass = 'secretary';
        $brandSubtitle = 'Secretary Portal';
        $dashboardRoute = route('admin.secretary');
    } elseif ($positionName) {
        $roleLabel = $positionName ?: 'Personnel';
        $roleClass = 'personnel';
        $brandSubtitle = 'Personnel Portal';
        $dashboardRoute = route('admin.dashboard');
    } else {
        $roleLabel = 'Staff';
        $roleClass = 'staff';
        $brandSubtitle = 'Staff Dashboard';
        $dashboardRoute = route('admin.dashboard');
    }
  @endphp
  <title>@yield('title', $brandSubtitle) | Barangay Calarian</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('head')
</head>
<x-hall-background>
  <div class="staff-shell">
    <aside id="staff-sidebar" class="staff-sidebar" aria-label="Navigation">
      <header class="staff-sidebar-header">
        <a href="{{ $dashboardRoute }}" class="site-brand staff-sidebar-brand" title="Barangay Calarian Portal">
          <x-brand-seal />
          <span class="sidebar-brand-text">
            <strong>Calarian Reports</strong>
            <small>{{ $brandSubtitle }}</small>
          </span>
        </a>
      </header>

      <div class="staff-nav-container">
        <nav class="staff-nav" aria-label="Portal menu">
          @php
            $adminNav = config('navigation.admin', []);
            $unconfirmedCount = 0;
            try {
                $unconfirmedCount = app(\App\Services\SettingsService::class)->unconfirmed()->count();
            } catch (\Throwable) {}
          @endphp

          @foreach($adminNav as $section)
            @php
              $visibleItems = collect($section['items'] ?? [])->filter(function ($item) use ($isAdmin, $isSecretary) {
                // If secretary_only is true, only show if user is secretary
                if (!empty($item['secretary_only']) && !$isSecretary) {
                    return false;
                }
                // If admin_only is true, hide if user is secretary (secretary has their own dashboard)
                if (!empty($item['admin_only']) && $isSecretary) {
                    return false;
                }
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
        <div class="staff-sidebar-blur-fade" aria-hidden="true"></div>
      </div>

      <footer class="staff-sidebar-footer" title="{{ $currentUser?->name }} ({{ $roleLabel }})">
        <span class="sidebar-avatar" aria-hidden="true">{{ str($currentUser?->name ?? 'U')->substr(0, 1)->upper() }}</span>
        <div class="sidebar-user-details">
          <strong>{{ $currentUser?->name }}</strong>
          <span class="role-badge role-{{ $roleClass }}">
            {{ $roleLabel }}
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
          <h1>@yield('page-heading', $brandSubtitle)</h1>
          <p>Barangay Calarian · Operations</p>
        </div>
        <div class="staff-header-actions flex items-center gap-3">
          <x-ui.global-search />
          <x-year-switcher />
          @if($unconfirmedCount > 0)
            <x-ui.pending-badge :count="$unconfirmedCount" />
          @endif
          <div class="staff-user">
            <span class="staff-avatar" aria-hidden="true">{{ str($currentUser?->name ?? 'U')->substr(0, 1)->upper() }}</span>
            <span class="staff-user-name">
              <strong>{{ $currentUser?->name }}</strong>
              <span class="role-badge role-{{ $roleClass }}">
                {{ $roleLabel }}
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
