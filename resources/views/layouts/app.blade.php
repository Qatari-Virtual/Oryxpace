<!doctype html>
<html lang="en" data-theme="oryxops">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Oryxpace' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
    @php
        $pilot = auth()->user();
        $navigation = [
            ['route' => 'departments', 'label' => 'Departments', 'icon' => 'ri-dashboard-fill'],
            ['route' => 'schedules', 'label' => 'Schedules', 'icon' => 'ri-calendar-2-fill'],
            ['route' => 'notifications', 'label' => 'Notifications', 'icon' => 'ri-notification-3-fill'],
            ['route' => 'permissions', 'label' => 'Permissions', 'icon' => 'ri-shield-fill'],
        ];
    @endphp
    <body class="min-h-screen bg-layout text-main antialiased">
    <div class="app-shell">
        <div data-sidebar-backdrop class="sidebar-backdrop hidden"></div>

        <aside data-sidebar class="app-sidebar">
            <div class="sidebar-brand">
                <a href="{{ route('departments') }}" class="brand-lockup" aria-label="Oryxpace home">
                    <img data-sidebar-label src="{{ asset('oryxpace_logo_colored.webp') }}" alt="Oryxpace" class="h-9 w-auto object-contain">
                </a>
                <div class="flex items-center gap-1">
                    <button data-sidebar-toggle class="icon-button desktop-menu" type="button" aria-label="Collapse navigation"><i class="ri-menu-fold-fill" aria-hidden="true"></i></button>
                    <button data-mobile-menu class="icon-button mobile-menu" type="button" aria-label="Open navigation"><i class="ri-menu-fill" aria-hidden="true"></i></button>
                </div>
            </div>

            <nav class="sidebar-nav" aria-label="Main navigation">
                <p data-sidebar-label class="nav-caption">Workspace</p>
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}"
                       class="sidebar-link {{ request()->routeIs($item['route']) || ($item['route'] === 'departments' && request()->routeIs('projects.*')) ? 'is-active' : '' }}"
                       title="{{ $item['label'] }}">
                         <i class="sidebar-icon {{ $item['icon'] }}" aria-hidden="true"></i>
                        <span data-sidebar-label class="sidebar-link-label">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <div class="nav-divider"></div>
                <button data-theme-toggle class="sidebar-link" type="button" aria-label="Toggle theme" title="Toggle theme">
                    <i class="sidebar-icon ri-moon-fill" aria-hidden="true"></i>
                    <span data-sidebar-label class="sidebar-link-label">Theme</span>
                </button>
            </nav>

            <div class="sidebar-user">
                @if($pilot?->getAvatarUrl(128))
                    <img src="{{ $pilot->getAvatarUrl(128) }}" alt="{{ $pilot->name ?? 'Pilot' }}" class="avatar-placeholder avatar-img" onerror="this.style.display='none'; if (this.nextElementSibling) this.nextElementSibling.style.display='grid';">
                    <div class="avatar-placeholder" style="display: none;">{{ collect(explode(' ', $pilot->name ?? 'Pilot'))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('') }}</div>
                @else
                    <div class="avatar-placeholder">{{ collect(explode(' ', $pilot->name ?? 'Pilot'))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('') }}</div>
                @endif
                <div data-sidebar-label class="min-w-0">
                    <p class="user-name">{{ $pilot->name ?? 'Pilot' }}</p>
                    <p class="user-meta font-mono">{{ $pilot->callsign ?? 'UNKNOWN' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button data-sidebar-label class="icon-button" type="submit" aria-label="Sign out" title="Sign out"><i class="ri-logout-box-r-line" aria-hidden="true"></i></button>
                </form>
            </div>
        </aside>

        <div class="app-workspace">
            <main class="page-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
