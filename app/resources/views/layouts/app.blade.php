<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $title ?? config('app.name') }}</title>

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @livewireStyles
    </head>
    <body class="min-h-screen bg-base-200">
        @auth
            <div class="navbar bg-base-100 shadow-lg">
                <div class="navbar-start">
                    <span class="font-semibold">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                    <span class="badge badge-primary badge-sm ml-2">{{ auth()->user()->role }}</span>
                    @if(auth()->user()->role === 'admin')
                        <nav class="ml-6 flex gap-4">
                            <a href="/admin/dashboard" class="link link-hover text-sm">Dashboard</a>
                            <a href="/admin/periods" class="link link-hover text-sm">Periods</a>
                            <a href="/admin/courses" class="link link-hover text-sm">Courses</a>
                            <a href="/admin/rooms" class="link link-hover text-sm">Rooms</a>
                            <a href="/admin/sessions" class="link link-hover text-sm">Sessions</a>
                            <a href="/admin/applications" class="link link-hover text-sm">Applications</a>
                        </nav>
                    @endif
                    @if(auth()->user()->role === 'staff')
                        <nav class="ml-6 flex gap-4">
                            <a href="/staff/home" class="link link-hover text-sm">Home</a>
                            <a href="/staff/encode" class="link link-hover text-sm">Encode Applicant</a>
                        </nav>
                    @endif
                    @if(auth()->user()->role === 'proctor')
                        <nav class="ml-6 flex gap-4">
                            <a href="/proctor/sessions" class="link link-hover text-sm">My sessions</a>
                        </nav>
                    @endif
                </div>
                <div class="navbar-end">
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">Log out</button>
                    </form>
                </div>
            </div>
        @endauth

        @if (session('error'))
            <div class="container mx-auto px-4 py-2">
                <div class="alert alert-warning shadow-lg">
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <main class="container mx-auto">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        @livewireScripts
    </body>
</html>
