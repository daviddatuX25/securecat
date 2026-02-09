@extends('layouts.app')

@section('content')
<div class="max-w-3xl" x-data="staffApplicationShow(@js($applicationId))" x-init="fetchApplication()">
    <div class="mb-6">
        <a href="/staff/applications" class="link link-hover text-sm">← My applications</a>
        <h1 class="text-2xl font-bold mt-2">Application detail</h1>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="notFound">
        <div class="alert alert-warning">
            <span>Application not found.</span>
            <a href="/staff/applications" class="link">Back to list</a>
        </div>
    </template>

    <template x-if="!loading && !notFound && app">
        <div class="space-y-6">
            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title">Applicant</h2>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                        <dt class="font-medium text-base-content/70">Name</dt>
                        <dd x-text="applicantName(app.applicant)"></dd>
                        <dt class="font-medium text-base-content/70">Email</dt>
                        <dd x-text="app.applicant?.email || '—'"></dd>
                        <dt class="font-medium text-base-content/70">Contact</dt>
                        <dd x-text="app.applicant?.contact_number || '—'"></dd>
                        <dt class="font-medium text-base-content/70">Date of birth</dt>
                        <dd x-text="app.applicant?.date_of_birth || '—'"></dd>
                        <dt class="font-medium text-base-content/70">Address</dt>
                        <dd x-text="app.applicant?.address || '—'" class="col-span-2"></dd>
                    </dl>
                </div>
            </div>

            <div class="card bg-base-100 shadow">
                <div class="card-body">
                    <h2 class="card-title">Course choices</h2>
                    <dl class="space-y-1">
                        <dt class="font-medium text-base-content/70">First choice</dt>
                        <dd x-text="app.course ? (app.course.code + ' — ' + app.course.name) : '—'"></dd>
                        <template x-if="app.second_course">
                            <div>
                                <dt class="font-medium text-base-content/70 mt-2">Second choice</dt>
                                <dd x-text="app.second_course ? (app.second_course.code + ' — ' + app.second_course.name) : '—'"></dd>
                            </div>
                        </template>
                        <template x-if="app.third_course">
                            <div>
                                <dt class="font-medium text-base-content/70 mt-2">Third choice</dt>
                                <dd x-text="app.third_course ? (app.third_course.code + ' — ' + app.third_course.name) : '—'"></dd>
                            </div>
                        </template>
                    </dl>
                    <p class="mt-2"><span class="badge" :class="statusBadgeClass(app.status)" x-text="formatStatus(app.status)"></span></p>
                    <template x-if="app.admin_notes">
                        <div class="mt-2 p-2 bg-base-200 rounded">
                            <dt class="font-medium text-base-content/70 text-sm">Admin notes</dt>
                            <dd class="text-sm" x-text="app.admin_notes"></dd>
                        </div>
                    </template>
                </div>
            </div>

            <template x-if="app.assignment">
                <div class="card bg-base-100 shadow">
                    <div class="card-body">
                        <h2 class="card-title">Assignment</h2>
                        <p x-show="app.assignment?.exam_session" x-text="'Session: ' + (app.assignment.exam_session ? (app.assignment.exam_session.date + ' ' + app.assignment.exam_session.start_time + '–' + app.assignment.exam_session.end_time + (app.assignment.exam_session.room ? ' @ ' + app.assignment.exam_session.room.name : '')) : '')"></p>
                        <p x-show="app.assignment?.seat_number">Seat: <span x-text="app.assignment.seat_number"></span></p>
                        <a :href="'/print/admission-slip/' + app.assignment.id" target="_blank" class="btn btn-primary btn-sm w-fit mt-2">Print admission slip</a>
                    </div>
                </div>
            </template>
        </div>
    </template>
</div>

<script>
function staffApplicationShow(applicationId) {
    return {
        applicationId,
        app: null,
        loading: true,
        notFound: false,
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
        async fetchApplication() {
            try {
                const res = await fetch('/api/applications/' + this.applicationId, { credentials: 'include', headers: this.getHeaders() });
                if (res.status === 404 || res.status === 403) {
                    this.notFound = true;
                    return;
                }
                if (!res.ok) throw new Error('Could not load application.');
                const json = await res.json();
                this.app = json.data;
            } catch (e) {
                this.notFound = true;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection
