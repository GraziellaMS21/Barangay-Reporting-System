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
      <a href="{{ route('admin.dashboard') }}" class="site-brand staff-sidebar-brand">
        <x-brand-seal />
        <span>
          <strong>Calarian Reports</strong>
          <small>{{ auth()->user()->isAdmin() ? 'Admin dashboard' : 'Staff dashboard' }}</small>
        </span>
      </a>
      <nav class="staff-nav" aria-label="Staff navigation">
        <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>
          <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
          {{ auth()->user()->isAdmin() ? 'Admin Dashboard' : 'Staff Dashboard' }}
        </a>
        <a href="{{ route('admin.reports.index') }}" @class(['active' => request()->routeIs('admin.reports.index')])>
          <i class="fa-solid fa-inbox" aria-hidden="true"></i> Report queue
        </a>
        <a href="{{ route('admin.reports.my-cases') }}" @class(['active' => request()->routeIs('admin.reports.my-cases')])>
          <i class="fa-solid fa-user-check" aria-hidden="true"></i> My cases
        </a>
        <a href="{{ route('admin.map') }}" @class(['active' => request()->routeIs('admin.map')])>
          <i class="fa-solid fa-map-location-dot" aria-hidden="true"></i> Staff map
        </a>
        <a href="{{ route('admin.referrals.index') }}" @class(['active' => request()->routeIs('admin.referrals.index')])>
          <i class="fa-solid fa-clipboard-list" aria-hidden="true"></i> Referrals
        </a>
        <a href="{{ route('reports.create') }}">
          <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Resident site
        </a>
        @if(auth()->user()->isAdmin())
          <div class="staff-nav-heading">Administration</div>
          <a href="{{ route('admin.settings.index') }}"
            @class(['active' => request()->routeIs(
              'admin.settings.index',
              'admin.settings.priority*',
              'admin.settings.emergency-alert-emails.*',
              'admin.settings.categories.*',
              'admin.settings.offices.*',
              'admin.settings.routing-rules.*',
              'admin.settings.sensitive-places.*',
              'admin.settings.users.*'
            )])>
            <i class="fa-solid fa-gear" aria-hidden="true"></i> Settings
          </a>
          <a href="{{ route('admin.settings.emergency-contacts.index') }}"
            @class(['active' => request()->routeIs('admin.settings.emergency-contacts.*')])>
            <i class="fa-solid fa-phone" aria-hidden="true"></i> Emergency Contacts
          </a>
          <a href="{{ route('admin.audit-log.index') }}"
            @class(['active' => request()->routeIs('admin.audit-log.*')])>
            <i class="fa-solid fa-clipboard-check" aria-hidden="true"></i> Audit log
          </a>
        @endif
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit">
            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Log out
          </button>
        </form>
      </nav>
      <footer class="staff-sidebar-footer">
        <strong>{{ auth()->user()->name }}</strong>
        <span class="role-badge {{ auth()->user()->isAdmin() ? 'role-admin' : 'role-staff' }}">
          {{ auth()->user()->isAdmin() ? 'Admin' : 'Staff' }}
        </span>
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
        <div class="staff-user">
          <span class="staff-avatar" aria-hidden="true">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
          <span class="staff-user-name">
            <strong>{{ auth()->user()->name }}</strong>
            <span class="role-badge {{ auth()->user()->isAdmin() ? 'role-admin' : 'role-staff' }}">
              {{ auth()->user()->isAdmin() ? 'Admin' : 'Staff' }}
            </span>
          </span>
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
