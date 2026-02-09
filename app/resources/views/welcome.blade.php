<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ config('app.name') }} – Secure College Admission Testing</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-800 flex flex-col">
    <header class="bg-white border-b border-slate-200 px-6 py-4">
        <nav class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-semibold text-slate-800">{{ config('app.name') }}</a>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-slate-600 hover:text-slate-900">Sign in</a>
                <a href="{{ route('register') }}" class="text-slate-600 hover:text-slate-900">Register</a>
            </div>
        </nav>
    </header>

    <main class="flex-1 max-w-4xl mx-auto w-full px-6 py-16">
        <section class="text-center">
            <p class="text-slate-500 text-sm uppercase tracking-wide mb-2">Secure College Admission Testing</p>
            <h1 class="text-4xl font-bold text-slate-900 mb-4">A secure, role-based platform for admission testing</h1>
            <p class="text-lg text-slate-600 mb-10 max-w-2xl mx-auto">Manage applications, schedules, and results with confidentiality, integrity, and accountability built in.</p>
            <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
            <a href="{{ route('register') }}" class="btn btn-outline btn-primary ml-2">Register</a>
        </section>

        <section class="mt-20">
            <h2 class="text-lg font-semibold text-slate-700 mb-2 text-center">System flow</h2>
            <p class="text-slate-500 text-sm text-center mb-8">Application to result release — each step is logged for accountability.</p>
            <div class="bg-white border border-slate-200 rounded-xl p-6 sm:p-8 shadow-sm">
                <ol class="space-y-0">
                    <li class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">1</span>
                        <span class="text-slate-800">Applicant Registration</span>
                    </li>
                    <li class="flex justify-center"><span class="text-slate-300">↓</span></li>
                    <li class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">2</span>
                        <span class="text-slate-800">Document Upload & Verification</span>
                    </li>
                    <li class="flex justify-center"><span class="text-slate-300">↓</span></li>
                    <li class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">3</span>
                        <span class="text-slate-800">Admin Review & Approval</span>
                    </li>
                    <li class="flex justify-center"><span class="text-slate-300">↓</span></li>
                    <li class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">4</span>
                        <span class="text-slate-800">Schedule Assignment & QR Generation</span>
                    </li>
                    <li class="flex justify-center"><span class="text-slate-300">↓</span></li>
                    <li class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">5</span>
                        <span class="text-slate-800">Exam Day QR Validation</span>
                    </li>
                    <li class="flex justify-center"><span class="text-slate-300">↓</span></li>
                    <li class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">6</span>
                        <span class="text-slate-800">Score Import & Calculation</span>
                    </li>
                    <li class="flex justify-center"><span class="text-slate-300">↓</span></li>
                    <li class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">7</span>
                        <span class="text-slate-800">Admin Result Review</span>
                    </li>
                    <li class="flex justify-center"><span class="text-slate-300">↓</span></li>
                    <li class="flex items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">8</span>
                        <span class="text-slate-800">Official Result Release</span>
                    </li>
                    <li class="flex justify-center"><span class="text-slate-300">↓</span></li>
                    <li class="flex items-center gap-4 py-3">
                        <span class="flex-shrink-0 w-8 h-8 bg-slate-800 text-white rounded-full flex items-center justify-center text-sm font-semibold">9</span>
                        <span class="text-slate-800">Examinee Result Access</span>
                    </li>
                </ol>
            </div>
        </section>

        <section class="mt-20">
            <h2 class="text-lg font-semibold text-slate-800 mb-4 text-center">Security features</h2>
            <p class="text-slate-600 text-sm text-center mb-6">This system is designed with information assurance and security in mind.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div class="flex gap-4 p-4 bg-white border border-slate-200 rounded-lg shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 font-bold">1</div>
                    <div>
                        <h3 class="font-medium text-slate-800">Secure password hashing</h3>
                        <p class="text-sm text-slate-600">Passwords are hashed with bcrypt; never stored in plain text.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 bg-white border border-slate-200 rounded-lg shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 font-bold">2</div>
                    <div>
                        <h3 class="font-medium text-slate-800">Session management</h3>
                        <p class="text-sm text-slate-600">Secure sessions with timeout and regeneration on login.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 bg-white border border-slate-200 rounded-lg shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 font-bold">3</div>
                    <div>
                        <h3 class="font-medium text-slate-800">Role-based access</h3>
                        <p class="text-sm text-slate-600">Access limited by role: Administrator, Staff, Proctor, Examinee.</p>
                    </div>
                </div>
                <div class="flex gap-4 p-4 bg-white border border-slate-200 rounded-lg shadow-sm">
                    <div class="flex-shrink-0 w-10 h-10 bg-slate-100 rounded-lg flex items-center justify-center text-slate-600 font-bold">4</div>
                    <div>
                        <h3 class="font-medium text-slate-800">Audit logging</h3>
                        <p class="text-sm text-slate-600">Logins and key actions are logged for accountability.</p>
                    </div>
                </div>
            </div>
        </section>

        <section class="mt-20">
            <h2 class="text-lg font-semibold text-slate-700 mb-4 text-center">View role dashboards</h2>
            <p class="text-slate-500 text-sm text-center mb-6">Sign in to access your role-specific dashboard.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <a href="{{ route('login') }}" class="block p-5 bg-white border border-slate-200 rounded-lg hover:border-slate-300 hover:shadow transition text-center">
                    <span class="font-medium text-slate-800">Administrator</span>
                    <p class="text-sm text-slate-500 mt-1">Full system access</p>
                </a>
                <a href="{{ route('login') }}" class="block p-5 bg-white border border-slate-200 rounded-lg hover:border-slate-300 hover:shadow transition text-center">
                    <span class="font-medium text-slate-800">Staff</span>
                    <p class="text-sm text-slate-500 mt-1">Walk-in & support</p>
                </a>
                <a href="{{ route('login') }}" class="block p-5 bg-white border border-slate-200 rounded-lg hover:border-slate-300 hover:shadow transition text-center">
                    <span class="font-medium text-slate-800">Proctor</span>
                    <p class="text-sm text-slate-500 mt-1">Exam-day verification</p>
                </a>
                <a href="{{ route('login') }}" class="block p-5 bg-white border border-slate-200 rounded-lg hover:border-slate-300 hover:shadow transition text-center">
                    <span class="font-medium text-slate-800">Examinee</span>
                    <p class="text-sm text-slate-500 mt-1">Applications & results</p>
                </a>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-200 bg-white px-6 py-4 text-center text-sm text-slate-500">
        Phase 1
    </footer>
</body>
</html>
