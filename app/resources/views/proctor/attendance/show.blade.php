@extends('layouts.app')

@section('content')
@php
    $dateStr = $session->date instanceof \DateTimeInterface ? $session->date->format('Y-m-d') : ($session->date ?? '—');
    $timeStr = ($session->start_time ?? '') . ' – ' . ($session->end_time ?? '');
    $courseLabel = $session->course ? ($session->course->code . ' — ' . $session->course->name) : '—';
@endphp
<div class="p-6" x-data="proctorAttendance(@js($sessionId), @js($dateStr), @js($timeStr), @js($courseLabel), @js($session->room?->name ?? '—'))" x-init="fetchAttendance()">
    <div class="mb-4">
        <a href="/proctor/sessions" class="link link-hover text-sm">← My sessions</a>
        <h1 class="text-2xl font-bold mt-2">Attendance</h1>
        <p class="text-sm text-base-content/70 mt-1" x-text="'Session: ' + courseLabel + ' · ' + dateStr + ' · ' + timeStr + ' · ' + roomName"></p>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2"><span class="loading loading-spinner loading-md"></span><span>Loading...</span></div>
    </template>
    <template x-if="error">
        <div class="alert alert-warning"><span x-text="error"></span> <button type="button" class="btn btn-sm" @click="fetchAttendance()">Retry</button></div>
    </template>
    <template x-if="!loading && !error && list.length === 0">
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
    <div class="mt-4">
        <a :href="'/proctor/scan/' + sessionId" class="btn btn-primary btn-sm">Scan check-in</a>
    </div>
</div>
<script>
function proctorAttendance(sessionId, dateStr, timeStr, courseLabel, roomName) {
    return {
        sessionId,
        dateStr,
        timeStr,
        courseLabel,
        roomName,
        list: [],
        loading: true,
        error: null,
        getHeaders() {
            return { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '', 'Accept': 'application/json' };
        },
        async fetchAttendance() {
            this.loading = true; this.error = null;
            try {
                const res = await fetch('/api/reports/attendance/' + this.sessionId, { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load attendance.');
                const j = await res.json();
                this.list = j.data || [];
            } catch (e) {
                this.error = e.message;
                this.list = [];
            } finally {
                this.loading = false;
            }
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
