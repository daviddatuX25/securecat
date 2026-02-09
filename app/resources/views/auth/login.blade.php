<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Sign in</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 flex flex-col">
    <header class="bg-white border-b border-slate-200 px-6 py-4">
        <nav class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-semibold text-slate-800">{{ config('app.name') }}</a>
            <a href="{{ url('/') }}" class="text-slate-600 hover:text-slate-900">Back to home</a>
        </nav>
    </header>

    <main class="flex-1 max-w-4xl mx-auto w-full px-6 py-12 flex items-start justify-center">
        <section class="w-full max-w-md bg-white border border-slate-200 rounded-xl p-8 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Sign in</h1>

            @if ($errors->any())
                <div class="alert alert-error mb-4">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('login') }}" class="space-y-5">
                @csrf

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="input input-bordered w-full" required autofocus autocomplete="email"
                           placeholder="you@example.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" name="password"
                           class="input input-bordered w-full" required autocomplete="current-password"
                           placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary w-full">Sign in</button>
            </form>
            <p class="mt-4 text-sm text-slate-500">
                <a href="{{ url('/') }}" class="text-slate-600 hover:underline">Back to home</a>
                <span class="mx-2">·</span>
                <a href="{{ route('register') }}" class="text-slate-600 hover:underline">Register</a>
            </p>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-4 text-center text-sm text-slate-500">
        Phase 1
    </footer>
</body>
</html>
