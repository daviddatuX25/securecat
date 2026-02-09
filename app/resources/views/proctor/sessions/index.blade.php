@extends('layouts.app')

@section('content')
<div x-data="proctorSessions()" x-init="fetchSessions()">
    <h1 class="text-2xl font-bold">My sessions</h1>
    <p class="text-sm text-base-content/70 mt-1">Sessions assigned to you. Open Scan to check in applicants, or Attendance to view who has been scanned.</p>

    <template x-if="loading">
        <div class="flex items-center gap-2 mt-4"><span class="loading loading-spinner loading-md"></span><span>Loading...</span></div>
    </template>
    <template x-if="error">
        <div class="alert alert-warning mt-4"><span x-text="error"></span> <button type="button" class="btn btn-sm" @click="fetchSessions()">Retry</button></div>
    </template>
    <template x-if="!loading && !error && sessions.length === 0">
        <div class="card bg-base-100 shadow mt-4"><div class="card-body"><p class="text-base-content/70">No sessions assigned to you.</p></div></div>
    </template>
    <template x-if="!loading && !error && sessions.length > 0">
        <div class="overflow-x-auto card bg-base-100 shadow mt-4">
            <table class="table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Course</th>
                        <th>Room</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="s in sessions" :key="s.id">
                        <tr>
                            <td x-text="s.date || '—'"></td>
                            <td x-text="(s.start_time || '') + '–' + (s.end_time || '')"></td>
                            <td x-text="s.course ? (s.course.code + ' ' + (s.course.name || '')) : '—'"></td>
                            <td x-text="s.room ? s.room.name : '—'"></td>
                            <td class="flex gap-2">
                                <a :href="'/proctor/scan/' + s.id" class="btn btn-primary btn-sm">Scan</a>
                                <a :href="'/proctor/attendance/' + s.id" class="btn btn-ghost btn-sm">Attendance</a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>
</div>
<script>
function proctorSessions() {
    return {
        sessions: [],
        loading: true,
        error: null,
        getHeaders() {
            return { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '', 'Accept': 'application/json' };
        },
        async fetchSessions() {
            this.loading = true; this.error = null;
            try {
                const res = await fetch('/api/exam-sessions', { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load sessions.');
                const j = await res.json();
                this.sessions = j.data || [];
            } catch (e) {
                this.error = e.message;
                this.sessions = [];
            } finally {
                this.loading = false;
            }
        }
    };
}
</script>
@endsection
