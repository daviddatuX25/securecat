@extends('layouts.app')

@section('content')
<div x-data="dashboardData()" x-init="fetchData()">
    <h1 class="text-2xl font-bold mb-6">Admin Dashboard</h1>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="!loading">
        <div class="space-y-6">
            <div class="flex flex-wrap gap-4 mb-4">
                <a href="/admin/applications" class="link link-hover font-medium">Applications</a>
                <a href="/admin/periods" class="link link-hover font-medium">Admission periods</a>
                <a href="/admin/courses" class="link link-hover font-medium">Courses</a>
                <a href="/admin/rooms" class="link link-hover font-medium">Rooms</a>
                <a href="/admin/sessions" class="link link-hover font-medium">Exam sessions</a>
                <a href="/admin/reports/roster" class="link link-hover font-medium">Reports (roster)</a>
                <a href="/admin/reports/attendance" class="link link-hover font-medium">Reports (attendance)</a>
                <a href="/admin/audit-log" class="link link-hover font-medium">Audit log</a>
            </div>

            <div class="stats stats-vertical lg:stats-horizontal shadow bg-base-100">
                <div class="stat">
                    <div class="stat-title">Pending applications</div>
                    <div class="stat-value" x-text="pending"></div>
                    <a href="/admin/applications?status=pending_review" class="link link-primary stat-desc">View pending applications →</a>
                </div>
            </div>

            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title">Upcoming sessions</h2>
                    <template x-if="sessions.length === 0">
                        <p class="text-base-content/70">No upcoming sessions. <a href="/admin/sessions/new" class="link link-hover">Create a session</a>.</p>
                    </template>
                    <ul class="list-disc list-inside" x-show="sessions.length > 0">
                        <template x-for="(session, i) in sessions" :key="session.id || i">
                            <li>
                                <a :href="'/admin/sessions/' + session.id + '/edit'" class="link link-hover" x-text="session.label || (session.course_name + ' — ' + session.room_name + ' — ' + session.date)"></a>
                            </li>
                        </template>
                    </ul>
                    <p class="mt-2" x-show="sessions.length > 0"><a href="/admin/sessions" class="link link-hover text-sm">View all sessions →</a></p>
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
