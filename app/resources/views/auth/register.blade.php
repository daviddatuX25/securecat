<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} — Register</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 flex flex-col">
    <header class="bg-white border-b border-slate-200 px-6 py-4">
        <nav class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-semibold text-slate-800">{{ config('app.name') }}</a>
            <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Sign in</a>
        </nav>
    </header>

    <main class="flex-1 max-w-4xl mx-auto w-full px-6 py-12 flex items-start justify-center">
        <section class="w-full max-w-md bg-white border border-slate-200 rounded-xl p-8 shadow-sm" id="register-form-container">
            <h1 class="text-2xl font-bold text-slate-900 mb-6">Register</h1>

            @if ($errors->any())
                <div class="alert alert-error mb-4">
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form method="POST" action="{{ route('register') }}" class="space-y-5" id="register-form">
                @csrf

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-slate-700 mb-1">First name</label>
                        <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}"
                               class="input input-bordered w-full" required autofocus autocomplete="given-name"
                               maxlength="100">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-slate-700 mb-1">Last name</label>
                        <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}"
                               class="input input-bordered w-full" required autocomplete="family-name"
                               maxlength="100">
                    </div>
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}"
                           class="input input-bordered w-full" required autocomplete="email"
                           placeholder="you@example.com">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                    <input type="password" id="password" name="password"
                           class="input input-bordered w-full" required autocomplete="new-password"
                           placeholder="••••••••"
                           aria-describedby="password-strength-desc password-strength">
                    <p id="password-strength-desc" class="sr-only">Password strength is shown below as you type; use a strong password.</p>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700 mb-1">Confirm password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation"
                           class="input input-bordered w-full" required autocomplete="new-password"
                           placeholder="••••••••">
                </div>

                <button type="submit" class="btn btn-primary w-full">Register</button>
            </form>
            <p class="mt-4 text-sm text-slate-500">
                <a href="{{ route('login') }}" class="text-slate-600 hover:underline">Already have an account? Sign in</a>
            </p>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-4 text-center text-sm text-slate-500">
        Phase 1
    </footer>
</body>
</html>
