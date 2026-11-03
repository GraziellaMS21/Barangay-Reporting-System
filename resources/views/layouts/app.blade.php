<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Barangay Calarian Reports')</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('head')
</head>

<body style="--hall-background: url('{{ asset('storage/Calarian_Barangay_Hall.jpg') }}')">
  <header class="site-header">
    <div class="site-header-inner">
      <a href="{{ route('reports.create') }}" class="site-brand">
        <span class="brand-seal" aria-hidden="true"><img src="{{ asset('storage/calarian-seal.png') }}" alt=""></span>
        <span class="brand-copy">
          Barangay Calarian Reports
          <small>Zamboanga City</small>
        </span>
      </a>
      <button type="button" class="site-menu-toggle" aria-label="Open main navigation"
        aria-controls="site-navigation" aria-expanded="false">
        <i class="fa-solid fa-bars" aria-hidden="true"></i>
      </button>
      <nav id="site-navigation" class="site-nav" aria-label="Main navigation">
        <a href="{{ route('reports.create') }}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Report</a>
        <a href="{{ route('track.index') }}"><i class="fa-solid fa-magnifying-glass" aria-hidden="true"></i> Track</a>
        <a href="{{ route('map.index') }}"><i class="fa-solid fa-map" aria-hidden="true"></i> Public map</a>
        @auth
          <a href="{{ route('admin.dashboard') }}">
            <i class="fa-solid fa-gauge-high" aria-hidden="true"></i>
            {{ auth()->user()->isAdmin() ? 'Admin Dashboard' : 'Staff Dashboard' }}
          </a>
          <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit"><i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Log out</button>
          </form>
        @else
          <a class="nav-cta" href="{{ route('login') }}"><i class="fa-solid fa-user-shield" aria-hidden="true"></i> Staff
            Login</a>
        @endauth
      </nav>
    </div>
  </header>

  @yield('hero')

  <main class="page-wrap @yield('page-wrap-class')">
    @if(session('success'))
      <div role="status" class="flash-success">{{ session('success') }}</div>
    @endif
    @yield('content')
  </main>

  <footer class="site-footer">
    <a class="btn btn-ghost btn-small" href="{{ route('login') }}">
      <i class="fa-solid fa-user-shield" aria-hidden="true"></i> Staff Login
    </a>
    <p>Barangay Calarian, Zamboanga City. Sama-sama tayo, mas maunlad na Calarian.</p>
  </footer>
  @stack('scripts')
</body>

</html>