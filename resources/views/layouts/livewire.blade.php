<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name', 'Inventory') }}</title>
    <link rel="icon" type="image/jpeg" href="{{ asset('logo.png') }}">
    @vite(['resources/css/livewire.css'])
    @livewireStyles
</head>

<body>
    @auth
        @if (request()->routeIs('pengpos'))
            <main class="pengpos-fullscreen">
                {{ $slot }}
            </main>
        @else
            <div class="app-shell">
                <aside class="sidebar">
                    <a class="brand" href="{{ route('dashboard') }}"><img class="brand-logo" src="{{ asset('logo.png') }}"
                            alt="Tinta Peng logo"><span>Tinta<span>peng</span></span></a>
                    <p class="eyebrow">Inventory workspace</p>
                    <nav class="nav-list" aria-label="Main navigation">
                        <a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}"
                            href="{{ route('dashboard') }}">Dashboard</a>
                        <a class="nav-link {{ request()->routeIs('pos') ? 'active' : '' }}" href="{{ route('pos') }}">Point
                            of
                            sale</a>
                        <a class="nav-link {{ request()->routeIs('pengpos') ? 'active' : '' }}"
                            href="{{ route('pengpos') }}">PengPOS POS</a>
                        <a class="nav-link {{ request()->routeIs('products') ? 'active' : '' }}"
                            href="{{ route('products') }}">Products</a>
                        <a class="nav-link {{ request()->routeIs('reports') ? 'active' : '' }}"
                            href="{{ route('reports') }}">Reports</a>
                        <a class="nav-link {{ request()->routeIs('transactions') ? 'active' : '' }}"
                            href="{{ route('transactions') }}">Transactions</a>
                        <a class="nav-link {{ request()->routeIs('settings') ? 'active' : '' }}"
                            href="{{ route('settings') }}">Settings</a>
                    </nav>
                    <div class="sidebar-footer">
                        <div class="user-chip">
                            <strong>{{ auth()->user()->name }}</strong><span>{{ ucfirst(auth()->user()->role) }}</span>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">@csrf<button class="button button-quiet"
                                type="submit">Sign out</button></form>
                    </div>
                </aside>
                <main class="main-content">
                    @if (session('status'))
                        <div class="flash">{{ session('status') }}</div>
                    @endif
                    {{ $slot }}
                </main>
            </div>
        @endif
    @else
        {{ $slot }}
    @endauth
    @livewireScripts
</body>

</html>
