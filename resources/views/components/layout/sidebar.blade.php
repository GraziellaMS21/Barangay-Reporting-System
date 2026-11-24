@props([
    'theme' => 'navy', // 'navy' or 'slate'
    'brandTitle' => 'Calarian Reports',
    'brandSubtitle' => 'Portal',
    'brandRoute' => '#',
    'sections' => [],
    'currentUser' => null,
    'userRoleLabel' => 'Staff',
    'userRoleClass' => 'staff',
    'isOfficeAdmin' => false,
    'portalType' => 'admin',
])

@php
    $currentUser = $currentUser ?? auth()->user();
    $isAdmin = $currentUser?->isAdmin() ?? false;
    $isSecretary = $currentUser?->isSecretary() ?? false;
    $initial = str($currentUser?->name ?? 'U')->substr(0, 1)->upper();
@endphp

<aside id="staff-sidebar" class="staff-sidebar {{ $theme === 'slate' ? 'staff-sidebar--slate' : '' }}" aria-label="Navigation">
    <!-- Brand Header -->
    <header class="staff-sidebar-header">
        <a href="{{ $brandRoute }}" class="site-brand staff-sidebar-brand" title="{{ $brandTitle }} · {{ $brandSubtitle }}">
            @if($theme === 'slate')
                <div class="brand-seal bg-blue-600 text-white flex items-center justify-center font-bold text-base shadow-sm">
                    <i class="fa-solid fa-building-shield" aria-hidden="true"></i>
                </div>
            @else
                <x-brand-seal />
            @endif
            <span class="sidebar-brand-text">
                <strong>{{ $brandTitle }}</strong>
                <small>{{ $brandSubtitle }}</small>
            </span>
        </a>
    </header>

    <!-- Nav Container (Scrollable) -->
    <div class="staff-nav-container">
        <nav class="staff-nav" aria-label="Portal menu">
            @foreach($sections as $section)
                @php
                    $visibleItems = collect($section['items'] ?? [])->filter(function ($item) use ($isAdmin, $isSecretary, $isOfficeAdmin, $currentUser) {
                        if (!empty($item['office_admin_only']) && !$isOfficeAdmin) {
                            return false;
                        }
                        if (!empty($item['secretary_only']) && !$isSecretary) {
                            return false;
                        }
                        if (!empty($item['admin_only']) && $isSecretary) {
                            return false;
                        }
                        if (!empty($item['permission'])) {
                            return $currentUser && $currentUser->hasPermission($item['permission']);
                        }
                        return true;
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
                        <a href="{{ route($item['route']) }}"
                           @class(['active' => $isActive])
                           title="{{ $item['label'] }}"
                           aria-current="{{ $isActive ? 'page' : 'false' }}">
                            <i class="{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span class="nav-label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach
                @endif
            @endforeach

            @if($portalType === 'admin')
                <div class="staff-nav-heading">
                    <span class="nav-heading-text">External</span>
                </div>
                <a href="{{ route('reports.create') }}" title="Resident site" target="_blank" data-turbo="false">
                    <i class="fa-solid fa-arrow-up-right-from-square" aria-hidden="true"></i>
                    <span class="nav-label">Resident site</span>
                </a>
            @endif

            <div class="staff-nav-heading">
                <span class="nav-heading-text">Account</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" data-turbo="false">
                @csrf
                <button type="submit" title="Log out">
                    <i class="fa-solid fa-arrow-right-from-bracket" aria-hidden="true"></i>
                    <span class="nav-label">Log out</span>
                </button>
            </form>
        </nav>
    </div>

    <!-- User Footer -->
    <footer class="staff-sidebar-footer" title="{{ $currentUser?->name }} ({{ $userRoleLabel }})">
        <span class="sidebar-avatar" aria-hidden="true">{{ $initial }}</span>
        <div class="sidebar-user-details">
            <strong>{{ $currentUser?->name }}</strong>
            <span class="role-badge role-{{ $userRoleClass }}">
                {{ $userRoleLabel }}
            </span>
        </div>
    </footer>
</aside>
