@extends('layouts.app')

@section('content')
<div x-data="sessionsIndex()" x-init="init()">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold">Exam sessions</h1>
        <a href="/admin/sessions/new" class="btn btn-primary">New session</a>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="error">
        <div class="alert alert-warning shadow-lg">
            <span x-text="error"></span>
            <button type="button" class="btn btn-sm" @click="fetchSessions()">Retry</button>
        </div>
    </template>

    <template x-if="!loading && !error && sessions.length === 0">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-base-content/70">No items yet. Create one.</p>
                <a href="/admin/sessions/new" class="btn btn-primary w-fit">New session</a>
            </div>
        </div>
    </template>

    <template x-if="!loading && !error && sessions.length > 0">
        <div class="overflow-x-auto card bg-base-100 shadow">
            <table class="table">
                <thead>
                    <tr>
                        <th>Course</th>
                        <th>Room</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Capacity</th>
                        <th>Proctor</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="s in sessions" :key="s.id">
                        <tr>
                            <td>
                                <span x-text="s.course ? (s.course.code + ' — ' + s.course.name) : s.course_id"></span>
                            </td>
                            <td>
                                <span x-text="s.room ? s.room.name : s.room_id"></span>
                                <span x-show="s.room" class="text-base-content/60 text-sm ml-1" x-text="'(cap. ' + (s.room?.capacity ?? '—') + ')'"></span>
                            </td>
                            <td x-text="s.date"></td>
                            <td><span x-text="s.start_time + ' – ' + s.end_time"></span></td>
                            <td>
                                <span x-text="capacityText(s)"></span>
                                <a x-show="(s.assignments_count || 0) > 0" :href="'/admin/reports/roster?session_id=' + s.id" class="link link-hover text-sm ml-1">Roster</a>
                            </td>
                            <td x-text="proctorLabel(s.proctor)"></td>
                            <td><span class="badge badge-ghost" x-text="s.status"></span></td>
                            <td class="flex gap-2">
                                <a :href="'/admin/sessions/' + s.id + '/edit'" class="btn btn-ghost btn-sm">Edit</a>
                                <button type="button" class="btn btn-ghost btn-sm text-error" @click="confirmDelete(s)" :disabled="deleting === s.id">
                                    <span x-show="deleting !== s.id">Delete</span>
                                    <span class="loading loading-spinner loading-sm" x-show="deleting === s.id"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

    <dialog id="delete-modal" class="modal" :class="{ 'modal-open': deleteTarget }">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete exam session?</h3>
            <p class="py-2" x-show="deleteTarget" x-text="'This will remove this session. You cannot delete a session that has assignments linked to it.'"></p>
            <div class="modal-action">
                <form method="dialog">
                    <button type="button" class="btn" @click="deleteTarget = null">Cancel</button>
                </form>
                <button type="button" class="btn btn-error" @click="doDelete()" :disabled="deleting">Delete</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="deleteTarget = null">
            <button type="submit">close</button>
        </form>
    </dialog>
</div>

<script>
function sessionsIndex() {
    return {
        sessions: [],
        loading: true,
        error: null,
        deleting: null,
        deleteTarget: null,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        proctorLabel(proctor) {
            if (!proctor) return '—';
            return (proctor.first_name || '') + ' ' + (proctor.last_name || '').trim() || proctor.email || proctor.id;
        },
        capacityText(s) {
            const count = s.assignments_count != null ? s.assignments_count : 0;
            const cap = s.room?.capacity != null ? s.room.capacity : '—';
            return typeof cap === 'number' ? count + ' / ' + cap : count + ' assigned';
        },
        async init() {
            await this.fetchSessions();
        },
        async fetchSessions() {
            this.loading = true; this.error = null;
            try {
                const res = await fetch('/api/exam-sessions', { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load exam sessions.');
                const json = await res.json();
                this.sessions = json.data || [];
            } catch (e) {
                this.error = e.message || 'Could not load. Retry.';
            } finally {
                this.loading = false;
            }
        },
        confirmDelete(s) {
            this.deleteTarget = s;
            document.getElementById('delete-modal')?.showModal?.();
        },
        async doDelete() {
            if (!this.deleteTarget) return;
            const id = this.deleteTarget.id;
            this.deleting = id;
            try {
                const res = await fetch('/api/exam-sessions/' + id, { method: 'DELETE', credentials: 'include', headers: this.getHeaders() });
                if (res.status === 409) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Cannot delete: this session has assignments linked to it.');
                    return;
                }
                if (!res.ok) throw new Error('Delete failed.');
                this.deleteTarget = null;
                document.getElementById('delete-modal')?.close?.();
                await this.fetchSessions();
            } catch (e) {
                alert(e.message || 'Could not delete.');
            } finally {
                this.deleting = null;
            }
        },
    };
}
</script>
@endsection
