<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="securecat">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureCAT – Secure College Admission Testing</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-base-200 flex flex-col">
    <header class="bg-base-100 border-b border-base-300 px-6 py-4">
        <nav class="max-w-4xl mx-auto flex items-center justify-between">
            <a href="{{ url('/') }}" class="text-xl font-semibold text-base-content">SecureCAT</a>
            <div class="flex items-center gap-4">
                <a href="{{ route('login') }}" class="text-sm text-base-content/60 hover:text-base-content">Sign in</a>
                <a href="{{ route('register') }}" class="btn btn-primary btn-sm">Register</a>
            </div>
        </nav>
    </header>

    <main class="flex-1 max-w-4xl mx-auto w-full px-6 py-16">

        {{-- Hero --}}
        <section class="text-center">
            <p class="text-base-content/50 text-sm uppercase tracking-wide mb-2">Secure College Admission Testing</p>
            <h1 class="text-4xl font-bold text-base-content mb-4">A secure, role-based platform for admission testing</h1>
            <p class="text-lg text-base-content/60 mb-10 max-w-2xl mx-auto">Manage applications, schedules, and results with confidentiality, integrity, and accountability built in.</p>
            <a href="{{ route('login') }}" class="btn btn-primary">Sign in</a>
            <a href="{{ route('register') }}" class="btn btn-outline btn-primary ml-2">Register</a>
        </section>

        {{-- System Flow --}}
        <section class="mt-20">
            <h2 class="text-lg font-semibold text-base-content mb-2 text-center">System flow</h2>
            <p class="text-base-content/50 text-sm text-center mb-8">Application to result release — each step is logged for accountability.</p>
            <div class="card bg-base-100 shadow-sm">
                <div class="card-body">
                    <ol class="space-y-0">
                        @php
                            $steps = [
                                'Applicant Registration',
                                'Document Upload & Verification',
                                'Admin Review & Approval',
                                'Schedule Assignment & QR Generation',
                                'Exam Day QR Validation',
                                'Score Import & Calculation',
                                'Admin Result Review',
                                'Official Result Release',
                                'Examinee Result Access',
                            ];
                        @endphp
                        @foreach($steps as $i => $step)
                            <li class="flex items-center gap-4 py-3 {{ !$loop->last ? 'border-b border-base-200' : '' }}">
                                <span class="flex-shrink-0 w-8 h-8 bg-primary text-primary-content rounded-full flex items-center justify-center text-sm font-semibold">{{ $i + 1 }}</span>
                                <span class="text-base-content">{{ $step }}</span>
                            </li>
                            @if(!$loop->last)
                                <li class="flex justify-center"><span class="text-base-300">&darr;</span></li>
                            @endif
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        {{-- Security Features --}}
        <section class="mt-20">
            <h2 class="text-lg font-semibold text-base-content mb-4 text-center">Security features</h2>
            <p class="text-base-content/60 text-sm text-center mb-6">This system is designed with information assurance and security in mind.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @php
                    $features = [
                        ['Secure password hashing', 'Passwords are hashed with bcrypt; never stored in plain text.'],
                        ['Session management', 'Secure sessions with timeout and regeneration on login.'],
                        ['Role-based access', 'Access limited by role: Administrator, Staff, Proctor, Examinee.'],
                        ['Audit logging', 'Logins and key actions are logged for accountability.'],
                    ];
                @endphp
                @foreach($features as $i => $feature)
                    <div class="card bg-base-100 shadow-sm card-sm">
                        <div class="card-body flex-row gap-4">
                            <div class="flex-shrink-0 w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center text-primary font-bold">{{ $i + 1 }}</div>
                            <div>
                                <h3 class="font-medium text-base-content">{{ $feature[0] }}</h3>
                                <p class="text-sm text-base-content/60">{{ $feature[1] }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Role Cards --}}
        <section class="mt-20">
            <h2 class="text-lg font-semibold text-base-content mb-4 text-center">View role dashboards</h2>
            <p class="text-base-content/50 text-sm text-center mb-6">Sign in to access your role-specific dashboard.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @php
                    $roles = [
                        ['Administrator', 'Full system access'],
                        ['Staff', 'Walk-in & support'],
                        ['Proctor', 'Exam-day verification'],
                        ['Examinee', 'Applications & results'],
                    ];
                @endphp
                @foreach($roles as $role)
                    <a href="{{ route('login') }}" class="card bg-base-100 card-border card-sm hover:shadow transition text-center">
                        <div class="card-body items-center">
                            <span class="font-medium text-base-content">{{ $role[0] }}</span>
                            <p class="text-sm text-base-content/50">{{ $role[1] }}</p>
                        </div>
                    </a>
                @endforeach
            </div>
        </section>
    </main>

    <footer class="border-t border-base-300 bg-base-100 px-6 py-4 text-center text-xs text-base-content/40">
        SecureCAT &middot; Phase 1
    </footer>
</body>
</html>
