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
  @php
    $agencyUser = auth()->user();
    $agencyOffice = $agencyUser?->office;
    $isOfficeAdmin = (bool) ($agencyUser?->agencyRegistration?->is_office_admin ?? false);
    $agencyNav = config('navigation.agency', []);
    $positionTitle = $agencyUser?->agencyRegistration?->position_title ?? 'Representative';
  @endphp
  <div class="agency-shell flex min-h-screen">
    <!-- Agency Sidebar Rail -->
    <x-layout.sidebar
      :theme="'slate'"
      :brand-title="'Agency Portal'"
      :brand-subtitle="$agencyOffice?->name ?? 'External Agency'"
      :brand-route="route('agency.referrals.index')"
      :sections="$agencyNav"
      :current-user="$agencyUser"
      :user-role-label="$positionTitle"
      :user-role-class="'agency'"
      :is-office-admin="$isOfficeAdmin"
      :portal-type="'agency'"
    />

    <!-- Agency Main Content -->
    <div class="agency-main flex-1 flex flex-col min-w-0">
      <header class="bg-white border-b border-slate-200 py-3.5 px-4 md:px-6 flex flex-wrap items-center justify-between gap-3 sticky top-0 z-20 shadow-xs">
        <div class="flex items-center gap-3">
          <button type="button" class="staff-menu-toggle lg:hidden p-2 text-slate-600 hover:text-slate-900 rounded-lg hover:bg-slate-100"
            aria-label="Open agency navigation" aria-controls="staff-sidebar" aria-expanded="false">
            <i class="fa-solid fa-bars text-lg" aria-hidden="true"></i>
          </button>
          <div>
            <h1 class="text-xl font-bold text-slate-900">@yield('page-heading', 'Agency Portal')</h1>
            <p class="text-xs text-slate-500">Barangay Calarian · External Referral Management</p>
          </div>
        </div>
        <div class="flex items-center gap-3">
          <span class="px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
            {{ $agencyOffice?->name ?? 'External Partner' }}
          </span>
        </div>
      </header>

      <main class="flex-1 p-4 md:p-6 min-w-0">
        <x-ui.flash />
        @yield('content')
      </main>
    </div>
  </div>
  <button type="button" class="staff-drawer-overlay" aria-label="Close agency navigation" tabindex="-1"></button>
  @stack('scripts')
</body>
</html>
