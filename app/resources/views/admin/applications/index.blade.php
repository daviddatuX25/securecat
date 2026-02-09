@extends('layouts.app')

@section('content')
<div x-data="applicationsIndex()" x-init="init()">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold">Applications</h1>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-4">
        <div class="flex items-center gap-2">
            <label class="label text-sm">Status</label>
            <select x-model="filterStatus" @change="fetchApplications()" class="select select-bordered select-sm w-48">
                <option value="">All</option>
                <option value="pending_review">Pending review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="revision_requested">Revision requested</option>
            </select>
        </div>
        <div class="flex items-center gap-2" x-show="periods.length > 0">
            <label class="label text-sm">Period</label>
            <select x-model="filterPeriodId" @change="fetchApplications()" class="select select-bordered select-sm w-64">
                <option value="">All periods</option>
                <template x-for="p in periods" :key="p.id">
                    <option :value="p.id" x-text="p.name"></option>
                </template>
            </select>
        </div>
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
            <button type="button" class="btn btn-sm" @click="fetchApplications()">Retry</button>
        </div>
    </template>

    <template x-if="!loading && !error && applications.length === 0">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-base-content/70">No pending applications.</p>
                <p class="text-sm text-base-content/60">Applications encoded by staff will appear here when their status is pending review.</p>
            </div>
        </div>
    </template>

    <template x-if="!loading && !error && applications.length > 0">
        <div class="overflow-x-auto card bg-base-100 shadow">
            <table class="table">
                <thead>
                    <tr>
                        <th>Applicant</th>
                        <th>First choice</th>
                        <th>Period</th>
                        <th>Status</th>
                        <th>Reviewed</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="a in applications" :key="a.id">
                        <tr>
                            <td>
                                <span x-text="applicantName(a.applicant)"></span>
                            </td>
                            <td>
                                <span x-text="a.course ? (a.course.code + ' — ' + a.course.name) : '—'"></span>
                            </td>
                            <td x-text="a.admission_period ? a.admission_period.name : '—'"></td>
                            <td>
                                <span class="badge" :class="statusBadgeClass(a.status)" x-text="formatStatus(a.status)"></span>
                            </td>
                            <td x-text="a.reviewed_at ? new Date(a.reviewed_at).toLocaleDateString() : '—'"></td>
                            <td>
                                <a :href="'/admin/applications/' + a.id" class="btn btn-ghost btn-sm">View</a>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

    <div class="mt-4" x-show="!loading && !error && meta && meta.total > meta.per_page">
        <p class="text-sm text-base-content/70" x-text="'Page ' + meta.current_page + ' of ' + meta.last_page + ' (' + meta.total + ' total)'"></p>
    </div>
</div>

<script>
function applicationsIndex() {
    return {
        applications: [],
        periods: [],
        filterStatus: 'pending_review',
        filterPeriodId: '',
        loading: true,
        error: null,
        meta: null,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        applicantName(applicant) {
            if (!applicant) return '—';
            return ((applicant.first_name || '') + ' ' + (applicant.last_name || '')).trim() || '—';
        },
        formatStatus(s) {
            if (!s) return '—';
            return s.replace(/_/g, ' ');
        },
        statusBadgeClass(status) {
            const m = { pending_review: 'badge-warning', approved: 'badge-success', rejected: 'badge-error', revision_requested: 'badge-info' };
            return m[status] || 'badge-ghost';
        },
        async init() {
            await this.fetchPeriods();
            await this.fetchApplications();
        },
        async fetchPeriods() {
            try {
                const res = await fetch('/api/admission-periods', { credentials: 'include', headers: this.getHeaders() });
                if (res.ok) {
                    const json = await res.json();
                    this.periods = json.data || [];
                }
            } catch (e) {
                console.error(e);
            }
        },
        async fetchApplications() {
            this.loading = true; this.error = null;
            try {
                const q = new URLSearchParams();
                if (this.filterStatus) q.set('status', this.filterStatus);
                if (this.filterPeriodId) q.set('admission_period_id', this.filterPeriodId);
                q.set('limit', '50');
                const res = await fetch('/api/applications?' + q, { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load applications.');
                const json = await res.json();
                this.applications = json.data || [];
                this.meta = json.meta || null;
            } catch (e) {
                this.error = e.message || 'Could not load. Retry.';
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
