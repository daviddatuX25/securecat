@extends('layouts.app')

@section('content')
<div x-data="dashboardData()" x-init="fetchData()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-base-content">Dashboard</h1>
        <p class="text-base-content/70 mt-1">Overview and quick access to applications, scheduling, and reports.</p>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="!loading">
        <div class="space-y-6">
            {{-- Key metrics --}}
            <div class="stats stats-vertical sm:stats-horizontal shadow bg-base-100 w-full">
                <div class="stat">
                    <div class="stat-title text-xs">Pending Review</div>
                    <div class="stat-value text-primary text-2xl" x-text="pending"></div>
                    <a href="/admin/applications?status=pending_review" class="stat-desc link link-primary link-hover text-xs">Review applications →</a>
                </div>
                <div class="stat">
                    <div class="stat-title text-xs">Upcoming Sessions</div>
                    <div class="stat-value text-2xl" x-text="sessions.length"></div>
                    <a href="/admin/sessions" class="stat-desc link link-hover text-xs">View all sessions →</a>
                </div>
                <div class="stat">
                    <div class="stat-title text-xs">Today's Sessions</div>
                    <div class="stat-value text-2xl" x-text="todaySessions"></div>
                    <div class="stat-desc text-xs">Active today</div>
                </div>
            </div>

            {{-- Quick actions --}}
            <div class="card bg-base-100 shadow">
                <div class="card-body py-4 px-5">
                    <h2 class="card-title text-sm mb-3">Quick Actions</h2>
                    <div class="flex flex-wrap gap-2">
                        <a href="/admin/applications?status=pending_review" class="btn btn-primary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            Review Applications
                        </a>
                        <a href="/admin/sessions/new" class="btn btn-outline btn-primary btn-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            New Session
                        </a>
                        <a href="/admin/periods/new" class="btn btn-ghost btn-sm">New Period</a>
                        <a href="/admin/courses/new" class="btn btn-ghost btn-sm">New Course</a>
                    </div>
                </div>
            </div>

            {{-- Upcoming sessions --}}
            <div class="card bg-base-100 shadow">
                <div class="card-body py-4 px-5">
                    <h2 class="card-title text-sm">Upcoming Exam Sessions</h2>
                    <template x-if="sessions.length === 0">
                        <div class="mt-3">
                            <p class="text-base-content/70 text-sm">No upcoming sessions.</p>
                            <a href="/admin/sessions/new" class="btn btn-primary btn-sm mt-2">Create a session</a>
                        </div>
                    </template>
                    <ul class="list list-none p-0 space-y-2 mt-3" x-show="sessions.length > 0">
                        <template x-for="(session, i) in sessions.slice(0, 5)" :key="session.id || i">
                            <li>
                                <a :href="'/admin/sessions/' + session.id + '/edit'" class="link link-hover flex items-baseline gap-2 text-sm" x-text="session.label || (session.course_name + ' — ' + session.room_name + ' — ' + session.date)"></a>
                            </li>
                        </template>
                    </ul>
                    <p class="mt-3" x-show="sessions.length > 5">
                        <a href="/admin/sessions" class="link link-hover text-xs">View all <span x-text="sessions.length"></span> sessions →</a>
                    </p>
                    <p class="mt-2" x-show="sessions.length > 0 && sessions.length <= 5">
                        <a href="/admin/sessions" class="link link-hover text-xs">View all sessions →</a>
                    </p>
                </div>
            </div>

            {{-- Quick links --}}
            <div class="card bg-base-100 shadow">
                <div class="card-body py-4 px-5">
                    <h2 class="card-title text-sm mb-3">Navigation</h2>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-2">
                        <a href="/admin/applications" class="btn btn-ghost btn-sm justify-start">Applications</a>
                        <a href="/admin/periods" class="btn btn-ghost btn-sm justify-start">Periods</a>
                        <a href="/admin/courses" class="btn btn-ghost btn-sm justify-start">Courses</a>
                        <a href="/admin/rooms" class="btn btn-ghost btn-sm justify-start">Rooms</a>
                        <a href="/admin/sessions" class="btn btn-ghost btn-sm justify-start">Sessions</a>
                        <a href="/admin/reports/roster" class="btn btn-ghost btn-sm justify-start">Roster</a>
                        <a href="/admin/reports/attendance" class="btn btn-ghost btn-sm justify-start">Attendance</a>
                        <a href="/admin/audit-log" class="btn btn-ghost btn-sm justify-start">Audit Log</a>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>

<script>
function dashboardData() {
    return {
        pending: 0,
        sessions: [],
        loading: true,
        get todaySessions() {
            if (!this.sessions || this.sessions.length === 0) return 0;
            const today = new Date().toISOString().split('T')[0];
            return this.sessions.filter(s => s.date === today).length;
        },
        async fetchData() {
            try {
                const res = await fetch('/api/dashboard', { credentials: 'include' });
                if (res.ok) {
                    const data = await res.json();
                    this.pending = data.pending_applications_count ?? 0;
                    this.sessions = data.upcoming_sessions ?? [];
                }
            } catch (e) {
                console.error(e);
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
