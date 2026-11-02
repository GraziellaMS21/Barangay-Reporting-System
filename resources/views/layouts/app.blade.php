<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>@yield('title', 'Barangay Reports')</title>
  <script src="https://cdn.tailwindcss.com/3.4.17"></script>
  @stack('head')
</head>
<body class="bg-gray-50 text-gray-800">
  <header class="bg-green-800 text-white">
    <div class="max-w-3xl mx-auto px-4 py-3 flex justify-between items-center">
      <a href="{{ route('reports.create') }}" class="font-bold">Barangay Calarian Reports</a>
      <nav class="space-x-4 text-sm">
        <a href="{{ route('reports.create') }}">Report a Problem</a>
        <a href="{{ route('track.index') }}">Track a Report</a>
        @auth
          <a href="{{ route('admin.reports.index') }}">Staff Dashboard</a>
          <form method="POST" action="{{ route('logout') }}" class="inline">
            @csrf
            <button type="submit">Log out</button>
          </form>
        @else
          <a href="{{ route('login') }}">Staff Login</a>
        @endauth
      </nav>
    </div>
  </header>
  <main class="max-w-3xl mx-auto px-4 py-6">
    @yield('content')
  </main>
  @stack('scripts')
</body>
</html>