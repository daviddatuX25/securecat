@extends('layouts.app')

@section('content')
<div x-data="applicationsIndex()" x-init="init()">
    <div class="mb-6">
        <h1 class="text-2xl font-bold">Applications</h1>
        <p class="text-base-content/70 text-sm mt-1">Review and manage applicant applications. Filter by status or admission period.</p>
    </div>

    <div class="mb-4 flex flex-wrap items-start gap-3">
        <fieldset class="fieldset">
            <label class="label text-xs py-0">Status</label>
            <select x-model="filterStatus" @change="fetchApplications()" class="select select-sm w-44">
                <option value="">All</option>
                <option value="pending_review">Pending review</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
                <option value="revision_requested">Revision requested</option>
            </select>
        </fieldset>
        <fieldset class="fieldset" x-show="periods.length > 0">
            <label class="label text-xs py-0">Period</label>
            <select x-model="filterPeriodId" @change="fetchApplications()" class="select select-sm w-52">
                <option value="">All periods</option>
                <template x-for="p in periods" :key="p.id">
                    <option :value="p.id" x-text="p.name"></option>
                </template>
            </select>
        </fieldset>
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
            <div class="card-body text-center py-8">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="font-medium text-base-content mb-1">No applications found</p>
                <p class="text-sm text-base-content/60 mb-4">Applications encoded by staff will appear here when their status matches your filters.</p>
                <template x-if="filterStatus || filterPeriodId">
                    <button type="button" class="btn btn-sm btn-ghost" @click="filterStatus = ''; filterPeriodId = ''; fetchApplications();">Clear filters</button>
                </template>
            </div>
        </div>
    </template>

    <template x-if="!loading && !error && applications.length > 0">
        <div class="space-y-4">
            {{-- Mobile: cards (one per application) --}}
            <div class="block md:hidden space-y-3">
                <template x-for="a in applications" :key="'card-' + a.id">
                    <a :href="'/admin/applications/' + a.id" class="card bg-base-100 shadow-sm card-border hover:shadow transition block">
                        <div class="card-body py-4 px-4">
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <p class="font-medium text-base-content truncate" x-text="applicantName(a.applicant)"></p>
                                    <p class="text-sm text-base-content/60 truncate" x-text="a.course ? (a.course.code + ' — ' + a.course.name) : '—'"></p>
                                    <p class="text-xs text-base-content/50 mt-0.5" x-text="a.admission_period ? a.admission_period.name : '—'"></p>
                                </div>
                                <span class="badge badge-sm shrink-0" :class="statusBadgeClass(a.status)" x-text="formatStatus(a.status)"></span>
                            </div>
                            <p class="text-xs text-base-content/40 mt-2" x-text="'Reviewed: ' + (a.reviewed_at ? new Date(a.reviewed_at).toLocaleDateString() : '—')"></p>
                        </div>
                    </a>
                </template>
            </div>

            {{-- Desktop: table --}}
            <div class="hidden md:block overflow-x-auto card bg-base-100 shadow-sm">
                <table class="table table-zebra">
                    <thead>
                        <tr>
                            <th>Applicant</th>
                            <th class="min-w-[12rem]">First choice</th>
                            <th class="min-w-[10rem]">Period</th>
                            <th>Status</th>
                            <th>Reviewed</th>
                            <th class="w-20"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="a in applications" :key="a.id">
                            <tr class="hover:bg-base-200/50">
                                <td class="font-medium">
                                    <span x-text="applicantName(a.applicant)"></span>
                                </td>
                                <td>
                                    <span class="text-sm" x-text="a.course ? (a.course.code + ' — ' + a.course.name) : '—'"></span>
                                </td>
                                <td class="text-sm" x-text="a.admission_period ? a.admission_period.name : '—'"></td>
                                <td>
                                    <span class="badge badge-sm" :class="statusBadgeClass(a.status)" x-text="formatStatus(a.status)"></span>
                                </td>
                                <td class="text-sm text-base-content/70" x-text="a.reviewed_at ? new Date(a.reviewed_at).toLocaleDateString() : '—'"></td>
                                <td>
                                    <a :href="'/admin/applications/' + a.id" class="btn btn-ghost btn-sm">View</a>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>
    </template>

    <div class="mt-4 flex items-center justify-between" x-show="!loading && !error && meta && meta.total > meta.per_page">
        <p class="text-xs text-base-content/50" x-text="'Page ' + meta.current_page + ' of ' + meta.last_page + ' (' + meta.total + ' total)'"></p>
        <div class="join">
            <button class="join-item btn btn-xs" :disabled="meta.current_page === 1" @click="changePage(meta.current_page - 1)">Previous</button>
            <button class="join-item btn btn-xs btn-active" x-text="meta.current_page"></button>
            <template x-if="meta.last_page > meta.current_page">
                <button class="join-item btn btn-xs" @click="changePage(meta.current_page + 1)">Next</button>
            </template>
        </div>
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
        async fetchApplications(page = 1) {
            this.loading = true; this.error = null;
            try {
                const q = new URLSearchParams();
                if (this.filterStatus) q.set('status', this.filterStatus);
                if (this.filterPeriodId) q.set('admission_period_id', this.filterPeriodId);
                q.set('page', String(page));
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
        changePage(page) {
            if (page >= 1 && this.meta && page <= this.meta.last_page) {
                this.fetchApplications(page);
            }
        },
    };
}
</script>
@endsection
