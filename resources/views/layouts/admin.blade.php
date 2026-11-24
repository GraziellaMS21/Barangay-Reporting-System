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
    @php
      $adminNav = config('navigation.admin', []);
      $unconfirmedCount = 0;
      try {
          $unconfirmedCount = app(\App\Services\SettingsService::class)->unconfirmed()->count();
      } catch (\Throwable) {}
    @endphp

    <x-layout.sidebar
      :brand-title="'Calarian Reports'"
      :brand-subtitle="$brandSubtitle"
      :brand-route="$dashboardRoute"
      :sections="$adminNav"
      :current-user="$currentUser"
      :user-role-label="$roleLabel"
      :user-role-class="$roleClass"
      :portal-type="'admin'"
    />

    <div class="staff-main">
      <header class="staff-topbar">
        <button type="button" class="staff-menu-toggle" aria-label="Open staff navigation"
          aria-controls="staff-sidebar" aria-expanded="false">
          <i class="fa-solid fa-bars" aria-hidden="true"></i>
        </button>
        <div class="min-w-0 flex-1">
          <h1>@yield('page-heading', $brandSubtitle)</h1>
          <p>Barangay Calarian · Operations</p>
        </div>
        <div class="staff-header-actions flex flex-wrap items-center gap-3">
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
