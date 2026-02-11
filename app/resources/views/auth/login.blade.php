<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="securecat">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureCAT — Sign in</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex flex-col">
    <header class="bg-base-100 border-b border-base-300 px-6 py-4">
        <nav class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-semibold text-base-content">SecureCAT</a>
            <a href="{{ url('/') }}" class="text-sm text-base-content/60 hover:text-base-content">Back to home</a>
        </nav>
    </header>

    <main class="flex-1 flex items-start justify-center px-6 py-12">
        <section class="w-full max-w-md card bg-base-100 shadow-sm">
            <div class="card-body">
                <h1 class="text-2xl font-bold text-base-content mb-4">Sign in</h1>

                @if ($errors->any())
                    <div class="alert alert-error mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span>{{ $errors->first() }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('login') }}" class="space-y-5">
                    @csrf

                    <fieldset class="fieldset">
                        <label class="label" for="email">Email</label>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="input w-full" required autofocus autocomplete="email"
                               placeholder="you@example.com">
                    </fieldset>

                    <fieldset class="fieldset">
                        <label class="label" for="password">Password</label>
                        <input type="password" id="password" name="password"
                               class="input w-full" required autocomplete="current-password"
                               placeholder="••••••••">
                    </fieldset>

                    <button type="submit" class="btn btn-primary w-full">Sign in</button>
                </form>
                <p class="mt-4 text-sm text-base-content/50">
                    <a href="{{ url('/') }}" class="link link-hover">Back to home</a>
                    <span class="mx-2">&middot;</span>
                    <a href="{{ route('register') }}" class="link link-hover">Register</a>
                </p>
            </div>
        </section>
    </main>

    <footer class="border-t border-base-300 bg-base-100 px-6 py-4 text-center text-xs text-base-content/40">
        SecureCAT &middot; Phase 1
    </footer>
</body>
</html>
