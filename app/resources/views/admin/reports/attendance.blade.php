@extends('layouts.app')

@section('content')
<div x-data="attendanceReport()" x-init="fetchSessions()">
    <div class="mb-6">
        <a href="/admin/dashboard" class="link link-hover text-sm">← Dashboard</a>
        <h1 class="text-2xl font-bold mt-2">Attendance report</h1>
    </div>

    <div class="form-control max-w-md mb-4">
        <label class="label"><span class="label-text">Select session</span></label>
        <select x-model="sessionId" @change="fetchAttendance()" class="select select-bordered w-full">
            <option value="">— Select a session —</option>
            <template x-for="s in sessions" :key="s.id">
                <option :value="s.id" x-text="(s.course ? s.course.code + ' — ' : '') + (s.date || '') + ' ' + (s.start_time || '') + '–' + (s.end_time || '') + (s.room ? ' @ ' + s.room.name : '')"></option>
            </template>
        </select>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2"><span class="loading loading-spinner loading-md"></span><span>Loading...</span></div>
    </template>
    <template x-if="error">
        <div class="alert alert-warning"><span x-text="error"></span> <button type="button" class="btn btn-sm" @click="fetchAttendance()">Retry</button></div>
    </template>
    <template x-if="!loading && !error && sessionId && list.length === 0">
        <div class="card bg-base-100 shadow"><div class="card-body"><p class="text-base-content/70">No assignments for this session.</p></div></div>
    </template>
    <template x-if="!loading && !error && list.length > 0">
        <div class="overflow-x-auto card bg-base-100 shadow">
            <table class="table">
                <thead>
                    <tr>
                        <th>Seat</th>
                        <th>Applicant</th>
                        <th>Contact</th>
                        <th>Scanned</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in list" :key="r.assignment_id">
                        <tr>
                            <td x-text="r.seat_number || '—'"></td>
                            <td x-text="r.applicant ? (r.applicant.first_name + ' ' + r.applicant.last_name) : '—'"></td>
                            <td x-text="r.applicant ? (r.applicant.email || r.applicant.contact_number || '—') : '—'"></td>
                            <td>
                                <span x-show="r.scanned_at" class="badge badge-success">✓ <span x-text="formatTime(r.scanned_at)"></span></span>
                                <span x-show="!r.scanned_at" class="text-base-content/50">—</span>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>
</div>
<script>
function attendanceReport() {
    return {
        sessions: [],
        sessionId: '',
        list: [],
        loading: false,
        error: null,
        getHeaders() { return { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '', 'Accept': 'application/json' }; },
        async fetchSessions() {
            try {
                const res = await fetch('/api/exam-sessions', { credentials: 'include', headers: this.getHeaders() });
                if (res.ok) { const j = await res.json(); this.sessions = j.data || []; }
            } catch (e) { console.error(e); }
        },
        async fetchAttendance() {
            if (!this.sessionId) { this.list = []; return; }
            this.loading = true; this.error = null;
            try {
                const res = await fetch('/api/reports/attendance/' + this.sessionId, { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load attendance.');
                const j = await res.json();
                this.list = j.data || [];
            } catch (e) { this.error = e.message; this.list = []; } finally { this.loading = false; }
        },
        formatTime(iso) {
            if (!iso) return '—';
            try {
                const d = new Date(iso);
                return d.toLocaleTimeString(undefined, { hour: '2-digit', minute: '2-digit' });
            } catch (e) { return iso; }
        }
    };
}
</script>
@endsection
