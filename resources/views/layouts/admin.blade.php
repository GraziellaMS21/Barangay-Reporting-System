<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>@yield('title', 'Staff Dashboard') | Barangay Calarian</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @stack('head')
</head>
<body style="--hall-background: url('{{ asset('storage/Calarian_Barangay_Hall.jpg') }}')">
  <div class="staff-shell">
    <aside class="staff-sidebar">
      <a href="{{ route('admin.reports.index') }}" class="site-brand staff-sidebar-brand">
        <span class="brand-seal" aria-hidden="true"><img src="{{ asset('storage/calarian-seal.png') }}" alt=""></span>
        <span>
          <strong>Calarian Reports</strong>
          <small>Staff dashboard</small>
        </span>
      </a>
      <nav class="staff-nav" aria-label="Staff navigation">
        <a href="{{ route('admin.reports.index') }}" @class(['active' => request()->routeIs('admin.reports.index')])>
          <i class="fa-solid fa-inbox" aria-hidden="true"></i> Report queue
        </a>
        <a href="{{ route('reports.create') }}">
          <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i> Resident site
        </a>
        <form method="POST" action="{{ route('logout') }}">
          @csrf
          <button type="submit">
            <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i> Log out
          </button>
        </form>
      </nav>
    </aside>

    <div class="staff-main">
      <header class="staff-topbar">
        <div>
          <h1>@yield('page-heading', 'Staff dashboard')</h1>
          <p>Barangay Calarian · Report management</p>
        </div>
        <div class="staff-user">
          <span class="staff-avatar" aria-hidden="true">{{ str(auth()->user()->name)->substr(0, 1)->upper() }}</span>
          <span class="staff-user-name">
            <strong>{{ auth()->user()->name }}</strong>
            <small>{{ auth()->user()->role }}</small>
          </span>
        </div>
      </header>

      <main class="staff-content">
        @if(session('success'))
          <div role="status" class="flash-success">{{ session('success') }}</div>
        @endif
        @yield('content')
      </main>
    </div>
  </div>
  @stack('scripts')
</body>
</html>
