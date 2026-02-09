@extends('layouts.app')

@section('content')
<div x-data="auditLogIndex()" x-init="fetchData()">
    <div class="mb-6">
        <a href="/admin/dashboard" class="link link-hover text-sm">← Dashboard</a>
        <h1 class="text-2xl font-bold mt-2">Audit log</h1>
    </div>

    <div class="flex flex-wrap gap-4 mb-4">
        <div class="form-control">
            <label class="label text-sm py-0"><span class="label-text">Action</span></label>
            <select x-model="filters.action" @change="fetchData()" class="select select-bordered select-sm w-48">
                <option value="">All</option>
                <option value="admission_period.create">admission_period.create</option>
                <option value="admission_period.update">admission_period.update</option>
                <option value="admission_period.delete">admission_period.delete</option>
                <option value="course.create">course.create</option>
                <option value="course.update">course.update</option>
                <option value="course.delete">course.delete</option>
                <option value="room.create">room.create</option>
                <option value="room.update">room.update</option>
                <option value="room.delete">room.delete</option>
                <option value="exam_session.create">exam_session.create</option>
                <option value="exam_session.update">exam_session.update</option>
                <option value="exam_session.delete">exam_session.delete</option>
                <option value="application.create">application.create</option>
                <option value="application.approve">application.approve</option>
                <option value="application.reject">application.reject</option>
                <option value="application.revision_request">application.revision_request</option>
                <option value="assignment.create">assignment.create</option>
            </select>
        </div>
        <div class="form-control">
            <label class="label text-sm py-0"><span class="label-text">Entity type</span></label>
            <input type="text" x-model="filters.entity_type" @change.debounce.300ms="fetchData()" class="input input-bordered input-sm w-40" placeholder="e.g. Application">
        </div>
        <div class="form-control">
            <label class="label text-sm py-0"><span class="label-text">Date from</span></label>
            <input type="date" x-model="filters.date_from" @change="fetchData()" class="input input-bordered input-sm w-40">
        </div>
        <div class="form-control">
            <label class="label text-sm py-0"><span class="label-text">Date to</span></label>
            <input type="date" x-model="filters.date_to" @change="fetchData()" class="input input-bordered input-sm w-40">
        </div>
        <div class="form-control justify-end">
            <label class="label text-sm py-0"><span class="label-text">&nbsp;</span></label>
            <button type="button" class="btn btn-sm btn-primary" @click="fetchData()">Apply</button>
        </div>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2"><span class="loading loading-spinner loading-md"></span><span>Loading...</span></div>
    </template>
    <template x-if="error">
        <div class="alert alert-warning"><span x-text="error"></span> <button type="button" class="btn btn-sm" @click="fetchData()">Retry</button></div>
    </template>
    <template x-if="!loading && !error && records.length === 0">
        <div class="card bg-base-100 shadow"><div class="card-body"><p class="text-base-content/70">No records match filters.</p></div></div>
    </template>
    <template x-if="!loading && !error && records.length > 0">
        <div class="overflow-x-auto card bg-base-100 shadow">
            <table class="table table-sm">
                <thead>
                    <tr><th>Time</th><th>User</th><th>Role</th><th>Action</th><th>Entity</th><th>IP</th><th>Details</th></tr>
                </thead>
                <tbody>
                    <template x-for="r in records" :key="r.id">
                        <tr>
                            <td class="whitespace-nowrap" x-text="r.timestamp ? new Date(r.timestamp).toLocaleString() : '—'"></td>
                            <td x-text="r.user ? (r.user.email || r.user.first_name + ' ' + r.user.last_name) : (r.user_id || '—')"></td>
                            <td x-text="r.role"></td>
                            <td x-text="r.action"></td>
                            <td><span x-text="r.entity_type"></span> #<span x-text="r.entity_id"></span></td>
                            <td x-text="r.ip_address || '—'"></td>
                            <td class="max-w-xs truncate" x-text="r.details && Object.keys(r.details).length ? JSON.stringify(r.details) : '—'"></td>
                        </tr>
                    </template>
                </tbody>
            </table>
            <div class="p-2 flex justify-between items-center border-t">
                <span class="text-sm text-base-content/70" x-text="'Page ' + meta.current_page + ' of ' + meta.last_page + ' (' + meta.total + ' total)'"></span>
                <div class="join">
                    <button type="button" class="join-item btn btn-sm" :disabled="meta.current_page <= 1" @click="goPage(meta.current_page - 1)">Prev</button>
                    <button type="button" class="join-item btn btn-sm" :disabled="meta.current_page >= meta.last_page" @click="goPage(meta.current_page + 1)">Next</button>
                </div>
            </div>
        </div>
    </template>
</div>
<script>
function auditLogIndex() {
    return {
        records: [],
        meta: { current_page: 1, last_page: 1, per_page: 15, total: 0 },
        loading: true,
        error: null,
        filters: { action: '', entity_type: '', date_from: '', date_to: '' },
        getHeaders() { return { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '', 'Accept': 'application/json' }; },
        buildQuery() {
            const q = new URLSearchParams();
            q.set('page', this.meta.current_page);
            q.set('limit', this.meta.per_page || 15);
            if (this.filters.action) q.set('action', this.filters.action);
            if (this.filters.entity_type) q.set('entity_type', this.filters.entity_type);
            if (this.filters.date_from) q.set('date_from', this.filters.date_from);
            if (this.filters.date_to) q.set('date_to', this.filters.date_to);
            return q.toString();
        },
        async fetchData() {
            this.loading = true; this.error = null;
            try {
                const res = await fetch('/api/audit-log?' + this.buildQuery(), { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load audit log.');
                const json = await res.json();
                this.records = json.data || [];
                this.meta = json.meta || this.meta;
            } catch (e) { this.error = e.message; this.records = []; } finally { this.loading = false; }
        },
        goPage(p) { this.meta.current_page = p; this.fetchData(); }
    };
}
</script>
@endsection
