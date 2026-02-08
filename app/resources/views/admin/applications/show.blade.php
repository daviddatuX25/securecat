@extends('layouts.app')

@section('content')
<div class="p-6 max-w-3xl" x-data="applicationShow(@js($applicationId))" x-init="fetchApplication(); fetchSessions();">
    <div class="mb-6">
        <a href="/admin/applications" class="link link-hover text-sm">← Applications</a>
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
            <a href="/admin/applications" class="link">Back to list</a>
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

            <div class="flex flex-wrap gap-2" x-show="app.status === 'approved' && !app.assignment">
                <button type="button" class="btn btn-primary" @click="openAssignModal()" :disabled="actioning">Assign to session</button>
            </div>

            <div class="flex flex-wrap gap-2" x-show="app.status === 'pending_review'">
                <button type="button" class="btn btn-success" @click="openApproveModal()" :disabled="actioning">Approve</button>
                <button type="button" class="btn btn-error" @click="openRejectModal()" :disabled="actioning">Reject</button>
                <button type="button" class="btn btn-warning" @click="openRevisionModal()" :disabled="actioning">Request revision</button>
            </div>
        </div>
    </template>

    <!-- Approve modal: optional assign to session -->
    <dialog id="approve-modal" class="modal" :class="{ 'modal-open': showApproveModal }" @close="showApproveModal = false; approveError = null">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Approve application</h3>
            <p class="py-2">You may optionally assign to an exam session now. Capacity is checked on submit.</p>
            <div class="form-control mt-4">
                <label class="label"><span class="label-text">Exam session (optional)</span></label>
                <select x-model="approveForm.exam_session_id" class="select select-bordered w-full">
                    <option value="">Approve only (assign later)</option>
                    <template x-for="s in sessions" :key="s.id">
                        <option :value="s.id" x-text="sessionOptionLabel(s)"></option>
                    </template>
                </select>
            </div>
            <p class="text-sm mt-2 text-base-content/80" x-show="selectedSessionCapacityText()" x-text="selectedSessionCapacityText()"></p>
            <p class="text-warning text-sm mt-1" x-show="selectedSessionAtCapacity()" x-text="'Room at capacity — assign to another session or approve without assignment.'"></p>
            <div class="form-control mt-2">
                <label class="label"><span class="label-text">Seat number (optional)</span></label>
                <input type="text" x-model="approveForm.seat_number" class="input input-bordered w-full" maxlength="10" placeholder="e.g. A-01">
            </div>
            <p class="text-error text-sm mt-2" x-show="approveError" x-text="approveError"></p>
            <div class="modal-action">
                <button type="button" class="btn" @click="closeApproveModal()">Cancel</button>
                <button type="button" class="btn btn-success" @click="doApprove()" :disabled="actioning">
                    <span x-show="!actioning">Approve</span>
                    <span class="loading loading-spinner loading-sm" x-show="actioning"></span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="closeApproveModal()">
            <button type="submit">close</button>
        </form>
    </dialog>

    <!-- Reject modal -->
    <dialog id="reject-modal" class="modal" :class="{ 'modal-open': showRejectModal }" @close="showRejectModal = false">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Reject application</h3>
            <div class="form-control mt-4">
                <label class="label"><span class="label-text">Notes (optional)</span></label>
                <textarea x-model="rejectForm.admin_notes" class="textarea textarea-bordered w-full" rows="3" maxlength="2000" placeholder="Reason for rejection..."></textarea>
            </div>
            <div class="modal-action">
                <button type="button" class="btn" @click="closeRejectModal()">Cancel</button>
                <button type="button" class="btn btn-error" @click="doReject()" :disabled="actioning">
                    <span x-show="!actioning">Reject</span>
                    <span class="loading loading-spinner loading-sm" x-show="actioning"></span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="closeRejectModal()">
            <button type="submit">close</button>
        </form>
    </dialog>

    <!-- Assign to session modal (for approved apps without assignment) -->
    <dialog id="assign-modal" class="modal" :class="{ 'modal-open': showAssignModal }" @close="showAssignModal = false; assignError = null">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Assign to exam session</h3>
            <p class="py-2">Select a session and optional seat. Capacity is checked on submit.</p>
            <div class="form-control mt-4">
                <label class="label"><span class="label-text">Exam session</span></label>
                <select x-model="assignForm.exam_session_id" class="select select-bordered w-full" required>
                    <option value="">Choose session</option>
                    <template x-for="s in sessions" :key="s.id">
                        <option :value="s.id" x-text="sessionOptionLabel(s)"></option>
                    </template>
                </select>
            </div>
            <p class="text-sm mt-2 text-base-content/80" x-show="assignSessionCapacityText()" x-text="assignSessionCapacityText()"></p>
            <p class="text-warning text-sm mt-1" x-show="assignSessionAtCapacity()" x-text="'Room at capacity — choose another session.'"></p>
            <div class="form-control mt-2">
                <label class="label"><span class="label-text">Seat number (optional)</span></label>
                <input type="text" x-model="assignForm.seat_number" class="input input-bordered w-full" maxlength="10" placeholder="e.g. A-01">
            </div>
            <p class="text-error text-sm mt-2" x-show="assignError" x-text="assignError"></p>
            <div class="modal-action">
                <button type="button" class="btn" @click="closeAssignModal()">Cancel</button>
                <button type="button" class="btn btn-primary" @click="doAssign()" :disabled="actioning || !assignForm.exam_session_id">
                    <span x-show="!actioning">Assign</span>
                    <span class="loading loading-spinner loading-sm" x-show="actioning"></span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="closeAssignModal()">
            <button type="submit">close</button>
        </form>
    </dialog>

    <!-- Request revision modal -->
    <dialog id="revision-modal" class="modal" :class="{ 'modal-open': showRevisionModal }" @close="showRevisionModal = false">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Request revision</h3>
            <div class="form-control mt-4">
                <label class="label"><span class="label-text">Notes (optional)</span></label>
                <textarea x-model="revisionForm.admin_notes" class="textarea textarea-bordered w-full" rows="3" maxlength="2000" placeholder="What needs to be revised..."></textarea>
            </div>
            <div class="modal-action">
                <button type="button" class="btn" @click="closeRevisionModal()">Cancel</button>
                <button type="button" class="btn btn-warning" @click="doRequestRevision()" :disabled="actioning">
                    <span x-show="!actioning">Request revision</span>
                    <span class="loading loading-spinner loading-sm" x-show="actioning"></span>
                </button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="closeRevisionModal()">
            <button type="submit">close</button>
        </form>
    </dialog>
</div>

<script>
function applicationShow(applicationId) {
    return {
        applicationId,
        app: null,
        sessions: [],
        loading: true,
        notFound: false,
        actioning: false,
        showApproveModal: false,
        showRejectModal: false,
        showRevisionModal: false,
        showAssignModal: false,
        approveForm: { exam_session_id: '', seat_number: '' },
        assignForm: { exam_session_id: '', seat_number: '' },
        rejectForm: { admin_notes: '' },
        revisionForm: { admin_notes: '' },
        approveError: null,
        assignError: null,
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
        openApproveModal() { this.showApproveModal = true; this.approveError = null; document.getElementById('approve-modal')?.showModal?.(); },
        closeApproveModal() { this.showApproveModal = false; this.approveError = null; document.getElementById('approve-modal')?.close?.(); },
        openRejectModal() { this.showRejectModal = true; document.getElementById('reject-modal')?.showModal?.(); },
        closeRejectModal() { this.showRejectModal = false; document.getElementById('reject-modal')?.close?.(); },
        openRevisionModal() { this.showRevisionModal = true; document.getElementById('revision-modal')?.showModal?.(); },
        closeRevisionModal() { this.showRevisionModal = false; document.getElementById('revision-modal')?.close?.(); },
        openAssignModal() { this.showAssignModal = true; this.assignError = null; this.assignForm = { exam_session_id: '', seat_number: '' }; document.getElementById('assign-modal')?.showModal?.(); },
        closeAssignModal() { this.showAssignModal = false; this.assignError = null; document.getElementById('assign-modal')?.close?.(); },
        assignSessionCapacityText() {
            if (!this.assignForm.exam_session_id) return '';
            const s = this.sessions.find(sess => String(sess.id) === String(this.assignForm.exam_session_id));
            if (!s || !s.room) return '';
            const assigned = s.assignments_count != null ? s.assignments_count : 0;
            return 'Capacity: ' + assigned + ' / ' + s.room.capacity + ' seats';
        },
        assignSessionAtCapacity() {
            if (!this.assignForm.exam_session_id) return false;
            const s = this.sessions.find(sess => String(sess.id) === String(this.assignForm.exam_session_id));
            return s && s.room && s.assignments_count != null && s.assignments_count >= s.room.capacity;
        },
        sessionOptionLabel(s) {
            const course = s.course ? s.course.code + ' — ' : '';
            const date = s.date || '';
            const time = (s.start_time || '') + '–' + (s.end_time || '');
            const room = s.room ? ' @ ' + s.room.name : '';
            const cap = (s.assignments_count != null && s.room) ? ' (' + s.assignments_count + '/' + s.room.capacity + ')' : '';
            return course + date + ' ' + time + room + cap;
        },
        selectedSessionCapacityText() {
            if (!this.approveForm.exam_session_id) return '';
            const s = this.sessions.find(sess => String(sess.id) === String(this.approveForm.exam_session_id));
            if (!s || !s.room) return '';
            const assigned = s.assignments_count != null ? s.assignments_count : 0;
            return 'Capacity: ' + assigned + ' / ' + s.room.capacity + ' seats';
        },
        selectedSessionAtCapacity() {
            if (!this.approveForm.exam_session_id) return false;
            const s = this.sessions.find(sess => String(sess.id) === String(this.approveForm.exam_session_id));
            if (!s || !s.room || s.assignments_count == null) return false;
            return s.assignments_count >= s.room.capacity;
        },
        async fetchApplication() {
            try {
                const res = await fetch('/api/applications/' + this.applicationId, { credentials: 'include', headers: this.getHeaders() });
                if (res.status === 404) { this.notFound = true; return; }
                if (!res.ok) throw new Error('Could not load application.');
                const json = await res.json();
                this.app = json.data;
            } catch (e) {
                this.notFound = true;
            } finally {
                this.loading = false;
            }
        },
        async fetchSessions() {
            try {
                const res = await fetch('/api/exam-sessions', { credentials: 'include', headers: this.getHeaders() });
                if (res.ok) {
                    const json = await res.json();
                    this.sessions = json.data || [];
                }
            } catch (e) {
                console.error(e);
            }
        },
        async doApprove() {
            this.actioning = true; this.approveError = null;
            try {
                const payload = {};
                if (this.approveForm.exam_session_id) payload.exam_session_id = this.approveForm.exam_session_id;
                if (this.approveForm.seat_number) payload.seat_number = this.approveForm.seat_number;
                const res = await fetch('/api/applications/' + this.applicationId + '/approve', {
                    method: 'POST',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.closeApproveModal();
                    await this.fetchApplication();
                    return;
                }
                if (res.status === 422 && data.errors) {
                    const msgs = data.errors.application || data.errors.exam_session_id || [];
                    this.approveError = Array.isArray(msgs) ? msgs[0] : msgs;
                    return;
                }
                this.approveError = data.message || 'Approve failed.';
            } catch (e) {
                this.approveError = e.message || 'Something went wrong.';
            } finally {
                this.actioning = false;
            }
        },
        async doReject() {
            this.actioning = true;
            try {
                const res = await fetch('/api/applications/' + this.applicationId + '/reject', {
                    method: 'POST',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(this.rejectForm),
                });
                if (res.ok) {
                    this.closeRejectModal();
                    await this.fetchApplication();
                } else {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Reject failed.');
                }
            } catch (e) {
                alert(e.message || 'Something went wrong.');
            } finally {
                this.actioning = false;
            }
        },
        async doRequestRevision() {
            this.actioning = true;
            try {
                const res = await fetch('/api/applications/' + this.applicationId + '/request-revision', {
                    method: 'POST',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(this.revisionForm),
                });
                if (res.ok) {
                    this.closeRevisionModal();
                    await this.fetchApplication();
                } else {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Request revision failed.');
                }
            } catch (e) {
                alert(e.message || 'Something went wrong.');
            } finally {
                this.actioning = false;
            }
        },
        async doAssign() {
            this.actioning = true; this.assignError = null;
            try {
                const payload = { exam_session_id: this.assignForm.exam_session_id };
                if (this.assignForm.seat_number) payload.seat_number = this.assignForm.seat_number;
                const res = await fetch('/api/applications/' + this.applicationId + '/assign', {
                    method: 'POST',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.closeAssignModal();
                    await this.fetchApplication();
                    return;
                }
                if (res.status === 422 && data.errors) {
                    const msgs = data.errors.application || data.errors.exam_session_id || [];
                    this.assignError = Array.isArray(msgs) ? msgs[0] : msgs;
                    return;
                }
                this.assignError = data.message || 'Assign failed.';
            } catch (e) {
                this.assignError = e.message || 'Something went wrong.';
            } finally {
                this.actioning = false;
            }
        },
    };
}
</script>
@endsection
