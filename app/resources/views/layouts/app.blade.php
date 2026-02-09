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
    <body class="min-h-screen bg-slate-50 text-slate-800 flex flex-col">
        @auth
            <header class="bg-white border-b border-slate-200 px-6 py-4">
                <nav class="max-w-4xl mx-auto flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <a href="{{ url('/') }}" class="text-xl font-semibold text-slate-800">{{ config('app.name') }}</a>
                        <span class="font-medium text-slate-600">{{ auth()->user()->first_name }} {{ auth()->user()->last_name }}</span>
                        <span class="badge badge-primary badge-sm">{{ auth()->user()->role }}</span>
                        @if(auth()->user()->role === 'admin')
                            <a href="/admin/dashboard" class="link link-hover text-sm text-slate-600 hover:text-slate-900">Dashboard</a>
                            <a href="/admin/periods" class="link link-hover text-sm text-slate-600 hover:text-slate-900">Periods</a>
                            <a href="/admin/courses" class="link link-hover text-sm text-slate-600 hover:text-slate-900">Courses</a>
                            <a href="/admin/rooms" class="link link-hover text-sm text-slate-600 hover:text-slate-900">Rooms</a>
                            <a href="/admin/sessions" class="link link-hover text-sm text-slate-600 hover:text-slate-900">Sessions</a>
                            <a href="/admin/applications" class="link link-hover text-sm text-slate-600 hover:text-slate-900">Applications</a>
                        @endif
                        @if(auth()->user()->role === 'staff')
                            <a href="/staff/home" class="link link-hover text-sm text-slate-600 hover:text-slate-900">Home</a>
                            <a href="/staff/encode" class="link link-hover text-sm text-slate-600 hover:text-slate-900">Encode Applicant</a>
                        @endif
                        @if(auth()->user()->role === 'proctor')
                            <a href="/proctor/sessions" class="link link-hover text-sm text-slate-600 hover:text-slate-900">My sessions</a>
                        @endif
                    </div>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm text-slate-600 hover:text-slate-900">Log out</button>
                    </form>
                </nav>
            </header>
        @endauth

        @if (session('error'))
            <div class="max-w-4xl mx-auto w-full px-6 py-2">
                <div class="alert alert-warning shadow-lg">
                    <span>{{ session('error') }}</span>
                </div>
            </div>
        @endif
        @if (session('success'))
            <div class="max-w-4xl mx-auto w-full px-6 py-2">
                <div class="alert alert-success shadow-lg">
                    <span>{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <main class="flex-1 max-w-4xl mx-auto w-full px-6 py-12">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>

        @auth
            <footer class="border-t border-slate-200 bg-white px-6 py-4 text-center text-sm text-slate-500">
                Phase 1
            </footer>
        @endauth

        @livewireScripts
    </body>
</html>
