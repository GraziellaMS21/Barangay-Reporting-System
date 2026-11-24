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

<x-hall-background class="public-site-background">
  <header class="site-header">
    <div class="site-header-inner">
      <a href="{{ route('home') }}" class="site-brand">
        <x-brand-seal />
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
        <a href="{{ route('home') }}"><i class="fa-solid fa-house" aria-hidden="true"></i> Home</a>
        <a href="{{ route('reports.create') }}"><i class="fa-solid fa-pen-to-square" aria-hidden="true"></i> Report</a>
        <a href="{{ route('complaint.create') }}"><i class="fa-solid fa-scale-balanced" aria-hidden="true"></i> Complaint</a>
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
      <div role="status" class="flash-success" data-toast>{{ session('success') }}</div>
    @endif
    @yield('content')
  </main>

  <x-site-footer />
  <script>document.querySelectorAll('[data-toast]').forEach(el=>{setTimeout(()=>{el.style.opacity='0';el.style.transition='opacity .3s';setTimeout(()=>el.remove(),300);},3000);});</script>
  @stack('scripts')
</x-hall-background>

</html>