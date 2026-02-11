<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="securecat">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? 'SecureCAT' }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="min-h-screen bg-base-200">

        @auth
        {{-- ═══════════════════════════════════════════
             DRAWER LAYOUT — sidebar on lg, toggle on mobile
             ═══════════════════════════════════════════ --}}
        <div class="drawer lg:drawer-open">
            <input id="main-drawer" type="checkbox" class="drawer-toggle" />

            {{-- ─── PAGE CONTENT ─── --}}
            <div class="drawer-content flex flex-col min-h-screen">

                {{-- Mobile top bar: hamburger + brand only (role shown in sidebar to avoid duplicate) --}}
                <header class="navbar bg-base-100 border-b border-base-300 px-3 lg:hidden sticky top-0 z-30">
                    <div class="navbar-start">
                        <label for="main-drawer" class="btn btn-ghost btn-sm btn-square" aria-label="Open navigation">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                        </label>
                    </div>
                    <div class="navbar-center">
                        <a href="{{ url('/') }}" class="text-base font-semibold text-base-content">SecureCAT</a>
                    </div>
                    <div class="navbar-end"></div>
                </header>

                {{-- Flash messages --}}
                @if (session('error'))
                    <div class="px-6 pt-4">
                        <div class="alert alert-warning shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                            <span>{{ session('error') }}</span>
                        </div>
                    </div>
                @endif
                @if (session('success'))
                    <div class="px-6 pt-4">
                        <div class="alert alert-success shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>{{ session('success') }}</span>
                        </div>
                    </div>
                @endif

                {{-- Main content area --}}
                <main class="flex-1 p-6">
                    @hasSection('content')
                        @yield('content')
                    @else
                        {{ $slot ?? '' }}
                    @endif
                </main>

                {{-- Footer --}}
                <footer class="px-6 py-4 text-center text-xs text-base-content/40">
                    SecureCAT &middot; Phase 1
                </footer>
            </div>

            {{-- ─── SIDEBAR ─── --}}
            <div class="drawer-side z-40">
                <label for="main-drawer" aria-label="Close navigation" class="drawer-overlay"></label>
                <aside class="bg-neutral text-neutral-content w-64 min-h-full flex flex-col">

                    {{-- Brand (logo placeholder; logo coming soon) --}}
                    <div class="p-5 border-b border-neutral-content/10">
                        <a href="{{ url('/') }}" class="text-lg font-bold tracking-tight">SecureCAT</a>
                        <p class="text-[0.65rem] text-neutral-content/50 mt-0.5">Secure College Admission Testing</p>
                    </div>

                    {{-- Navigation --}}
                    <nav class="flex-1 overflow-y-auto p-3">
                        <ul class="menu menu-sm gap-0.5">

                            {{-- ── Admin Navigation ── --}}
                            @if(auth()->user()->role === 'admin')
                                <li class="menu-title text-neutral-content/40 text-[0.6rem] uppercase tracking-wider pt-2">Overview</li>
                                <li>
                                    <a href="/admin/dashboard" class="{{ request()->is('admin/dashboard') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v5a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM14 5a1 1 0 011-1h4a1 1 0 011 1v2a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zM14 12a1 1 0 011-1h4a1 1 0 011 1v7a1 1 0 01-1 1h-4a1 1 0 01-1-1v-7z"/></svg>
                                        Dashboard
                                    </a>
                                </li>

                                <li class="menu-title text-neutral-content/40 text-[0.6rem] uppercase tracking-wider pt-4">Admissions</li>
                                <li>
                                    <a href="/admin/applications" class="{{ request()->is('admin/applications*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Applications
                                    </a>
                                </li>
                                <li>
                                    <a href="/admin/periods" class="{{ request()->is('admin/periods*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                        Periods
                                    </a>
                                </li>
                                <li>
                                    <a href="/admin/courses" class="{{ request()->is('admin/courses*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 7l-9-5 9-5 9 5-9 5z"/></svg>
                                        Courses
                                    </a>
                                </li>

                                <li class="menu-title text-neutral-content/40 text-[0.6rem] uppercase tracking-wider pt-4">Exam Management</li>
                                <li>
                                    <a href="/admin/rooms" class="{{ request()->is('admin/rooms*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                                        Rooms
                                    </a>
                                </li>
                                <li>
                                    <a href="/admin/sessions" class="{{ request()->is('admin/sessions*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        Sessions
                                    </a>
                                </li>

                                <li class="menu-title text-neutral-content/40 text-[0.6rem] uppercase tracking-wider pt-4">Reporting</li>
                                <li>
                                    <a href="/admin/reports/roster" class="{{ request()->is('admin/reports/roster*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Roster Report
                                    </a>
                                </li>
                                <li>
                                    <a href="/admin/reports/attendance" class="{{ request()->is('admin/reports/attendance*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                        Attendance Report
                                    </a>
                                </li>
                                <li>
                                    <a href="/admin/audit-log" class="{{ request()->is('admin/audit-log*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                                        Audit Log
                                    </a>
                                </li>
                            @endif

                            {{-- ── Staff Navigation ── --}}
                            @if(auth()->user()->role === 'staff')
                                <li class="menu-title text-neutral-content/40 text-[0.6rem] uppercase tracking-wider pt-2">Staff</li>
                                <li>
                                    <a href="/staff/home" class="{{ request()->is('staff/home') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0a1 1 0 01-1-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 01-1 1m-4 0h4"/></svg>
                                        Home
                                    </a>
                                </li>
                                <li>
                                    <a href="/staff/encode" class="{{ request()->is('staff/encode*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                                        Encode Applicant
                                    </a>
                                </li>
                                <li>
                                    <a href="/staff/applications" class="{{ request()->is('staff/applications*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                        Applications
                                    </a>
                                </li>
                            @endif

                            {{-- ── Proctor Navigation ── --}}
                            @if(auth()->user()->role === 'proctor')
                                <li class="menu-title text-neutral-content/40 text-[0.6rem] uppercase tracking-wider pt-2">Proctor</li>
                                <li>
                                    <a href="/proctor/sessions" class="{{ request()->is('proctor/sessions*') || request()->is('proctor/home') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                                        My Sessions
                                    </a>
                                </li>
                                <li>
                                    <a href="/proctor/roster" class="{{ request()->is('proctor/roster*') ? 'menu-active' : '' }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                        Roster
                                    </a>
                                </li>
                            @endif

                        </ul>
                    </nav>

                    {{-- User info + Logout --}}
                    <div class="p-4 border-t border-neutral-content/10">
                        <div class="flex items-center gap-3 mb-3">
                            <div class="w-9 h-9 rounded-full bg-neutral-content/15 flex items-center justify-center text-sm font-bold shrink-0">
                                {{ strtoupper(substr(auth()->user()->first_name, 0, 1)) }}{{ strtoupper(substr(auth()->user()->last_name, 0, 1)) }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-medium truncate">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</p>
                                <span class="badge badge-primary badge-xs capitalize">{{ auth()->user()->role }}</span>
                            </div>
                        </div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost btn-sm w-full justify-start text-neutral-content/70 hover:text-neutral-content">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                                Log out
                            </button>
                        </form>
                    </div>
                </aside>
            </div>
        </div>
        @endauth

        {{-- Guest fallback (unauthenticated pages using this layout) --}}
        @guest
            <main class="flex-1">
                @hasSection('content')
                    @yield('content')
                @else
                    {{ $slot ?? '' }}
                @endif
            </main>
        @endguest

        @livewireScripts
    </body>
</html>
