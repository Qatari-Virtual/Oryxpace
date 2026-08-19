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
            ['route' => 'departments', 'label' => 'Departments', 'icon' => '▦'],
            ['route' => 'schedules', 'label' => 'Schedules', 'icon' => '◷'],
            ['route' => 'notifications', 'label' => 'Notifications', 'icon' => '◌'],
        ];
    @endphp
    <body class="min-h-screen bg-layout text-main antialiased">
    <div class="app-shell">
        <div data-sidebar-backdrop class="sidebar-backdrop hidden"></div>

        <aside data-sidebar class="app-sidebar">
            <div class="sidebar-brand">
                <a href="{{ route('departments') }}" class="brand-lockup" aria-label="Oryxpace home">
                    <span class="brand-mark">O</span>
                    <span data-sidebar-label class="brand-wordmark">ORYXPACE</span>
                </a>
            </div>

            <nav class="sidebar-nav" aria-label="Main navigation">
                <p data-sidebar-label class="nav-caption">Workspace</p>
                @foreach ($navigation as $item)
                    <a href="{{ route($item['route']) }}"
                       class="sidebar-link {{ request()->routeIs($item['route']) || ($item['route'] === 'departments' && request()->routeIs('projects.*')) ? 'is-active' : '' }}"
                       title="{{ $item['label'] }}">
                        <span class="sidebar-icon" aria-hidden="true">{{ $item['icon'] }}</span>
                        <span data-sidebar-label class="sidebar-link-label">{{ $item['label'] }}</span>
                    </a>
                @endforeach

                <div class="nav-divider"></div>
                <p data-sidebar-label class="nav-caption">Operations</p>
                <div class="ops-callout">
                    <span class="status-dot"></span>
                    <span data-sidebar-label><strong>Systems nominal</strong><small>All services operational</small></span>
                </div>
            </nav>

            <div class="sidebar-user">
                <div class="avatar-placeholder">{{ collect(explode(' ', $pilot->name ?? 'Pilot'))->map(fn ($part) => substr($part, 0, 1))->take(2)->join('') }}</div>
                <div data-sidebar-label class="min-w-0">
                    <p class="user-name">{{ $pilot->name ?? 'Pilot' }}</p>
                    <p class="user-meta font-mono">{{ $pilot->callsign ?? 'UNKNOWN' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}" class="ml-auto">
                    @csrf
                    <button data-sidebar-label class="icon-button" type="submit" aria-label="Sign out" title="Sign out">↪</button>
                </form>
            </div>
        </aside>

        <div class="app-workspace">
            <header class="topbar">
                <button data-mobile-menu class="icon-button mobile-menu" type="button" aria-label="Open navigation">☰</button>
                <button data-sidebar-toggle class="icon-button desktop-menu" type="button" aria-label="Collapse navigation">☰</button>
                <div class="topbar-context">
                    <span class="eyebrow">Operations workspace</span>
                    <span class="topbar-page">{{ $title ?? 'Oryxpace' }}</span>
                </div>
                <div class="topbar-actions">
                    <span class="live-pill"><span class="status-dot"></span><span class="hidden sm:inline">LIVE</span></span>
                    <button data-theme-toggle class="icon-button" type="button" aria-label="Toggle theme">◐</button>
                    <div class="operator-chip"><span class="operator-avatar">{{ substr($pilot->callsign ?? 'OP', 0, 2) }}</span><span class="hidden sm:inline font-mono">{{ $pilot->callsign ?? 'OPS' }}</span></div>
                </div>
            </header>

            <main class="page-content">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
</body>
</html>
