    <!DOCTYPE html>
    <html lang="en">

    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>@yield('title', 'Admin Panel') — ShopOwner</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>

    <body class="bg-slate-100 min-h-screen flex">

        {{-- SIDEBAR --}}
        <aside class="sidebar">

            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <div class="sidebar-logo-icon">
                        <svg width="18" height="18" fill="none" stroke="white" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z" />
                        </svg>
                    </div>
                    <div>
                        <p style="color:#fff; font-weight:700; font-size:13.5px; line-height:1; margin:0;">ShopAdmin</p>
                        <p style="color:#9ca3af; font-size:11px; margin:3px 0 0;">Owner Panel</p>
                    </div>
                </div>
            </div>



            <nav class="sidebar-nav">
                <p class="nav-section-label">Overview</p>
                <a href="{{ route('admin.dashboard') }}"
                    class="sidebar-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" style="flex-shrink:0;">
                        <rect x="3" y="3" width="7" height="7" rx="1" />
                        <rect x="14" y="3" width="7" height="7" rx="1" />
                        <rect x="3" y="14" width="7" height="7" rx="1" />
                        <rect x="14" y="14" width="7" height="7" rx="1" />
                    </svg>
                    Dashboard
                </a>

                <p class="nav-section-label">Catalogue</p>
                <a href="{{ route('admin.categories.index') }}"
                    class="sidebar-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                    Categories
                </a>
                <a href="{{ route('admin.products.index') }}"
                    class="sidebar-link {{ request()->routeIs('admin.products.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10" />
                    </svg>
                    Products
                </a>

                <p class="nav-section-label">Management</p>
                <a href="{{ route('admin.orders.index') }}"
                    class="sidebar-link {{ request()->routeIs('admin.orders.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Orders
                </a>
                <a href="{{ route('admin.users.index') }}"
                    class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    Users
                </a>
            </nav>

            <div class="sidebar-user">
                <div class="sidebar-avatar">
                    {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
                </div>
                <div style="overflow:hidden;">
                    <p
                        style="color:#e2e8f0; font-size:12.5px; font-weight:600; margin:0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ auth()->user()->name ?? 'Admin' }}
                    </p>
                    <p
                        style="color:#9ca3af; font-size:11px; margin:2px 0 0; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">
                        {{ auth()->user()->email ?? '' }}
                    </p>
                </div>
            </div>

            <div class="sidebar-footer">
                <button type="button" onclick="openLogoutModal()" class="sidebar-link logout"
                    style="width:100%; background:none; border:none; cursor:pointer;">
                    <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24" style="flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                    Logout
                </button>
            </div>
        </aside>

        {{-- MAIN --}}
        <div class="main-wrapper">

            <header class="topbar">
                <h1 style="font-size:15px; font-weight:600; color:#1e293b; margin:0;">@yield('title', 'Dashboard')</h1>
                <div style="display:flex; align-items:center; gap:16px;">
                    <div style="display:flex; align-items:center; gap:6px; font-size:12.5px; color:#94a3b8;">
                        <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        {{-- {{ now()->format('D, d M Y · H:i') }} --}}
                        {{-- {{ now()->timezone('Asia/Bangkok')->format('D, d M Y · H:i') }} --}}
                        {{ now()->timezone('Asia/Bangkok')->format('D, d M Y · h:i A') }}
                    </div>
                    <div class="topbar-avatar">
                        {{ strtoupper(substr(auth()->user()->name ?? 'A', 0, 2)) }}
                    </div>
                </div>
            </header>

            @if (session('success') || session('error'))
                <div style="padding:16px 32px 0;">
                    @if (session('success'))
                        <div class="flash-success">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"
                                style="flex-shrink:0;">
                                <path fill-rule="evenodd"
                                    d="M16.707 5.293a1 1 0 00-1.414 0L8 12.586 4.707 9.293a1 1 0 00-1.414 1.414l4 4a1 1 0 001.414 0l8-8a1 1 0 000-1.414z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ session('success') }}
                        </div>
                    @endif
                    @if (session('error'))
                        <div class="flash-error">
                            <svg width="16" height="16" fill="currentColor" viewBox="0 0 20 20"
                                style="flex-shrink:0;">
                                <path fill-rule="evenodd"
                                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z"
                                    clip-rule="evenodd" />
                            </svg>
                            {{ session('error') }}
                        </div>
                    @endif
                </div>
            @endif

            <main class="main-content">
                @yield('content')
            </main>

            <footer class="main-footer">
                ShopOwner Admin Panel &copy; {{ date('Y') }}
            </footer>
        </div>



        {{-- Logout Confirmation Modal --}}
        <div id="logoutModal" class="modal-overlay" onclick="closeLogoutModal()">
            <div class="modal-box" onclick="event.stopPropagation()">
                <div class="modal-icon">
                    <svg width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                    </svg>
                </div>
                <h3 class="modal-title">Sign out?</h3>
                <p class="modal-desc">Are you sure you want to logout from the admin panel?</p>
                <div class="modal-actions">
                    <button onclick="closeLogoutModal()" class="btn-cancel">Cancel</button>
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="btn-danger">Yes, Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </body>

    </html>
