@extends('layouts.app')

@section('content')
<div class="max-w-xl" x-data="sessionEdit(@js($sessionId), @js($proctors))" x-init="init()">
    <div class="mb-6">
        <div class="breadcrumbs text-sm mb-2">
            <ul>
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/admin/sessions">Exam Sessions</a></li>
                <li class="text-base-content/60">Edit</li>
            </ul>
        </div>
        <h1 class="text-2xl font-bold">Edit exam session</h1>
        <p class="text-base-content/70 text-sm mt-1">Update exam session details, assign proctor, or change schedule.</p>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-3 py-12 min-h-[8rem]" role="status" aria-live="polite">
            <span class="loading loading-spinner loading-lg text-primary"></span>
            <span class="text-base-content/80">Loading session and options...</span>
        </div>
    </template>

    <template x-if="notFound">
        <div class="alert alert-warning">
            <span>Exam session not found.</span>
            <a href="/admin/sessions" class="link">Back to list</a>
        </div>
    </template>

    <template x-if="!loading && !notFound && form">
        <form @submit.prevent="submit()" class="card bg-base-100 shadow">
            <div class="card-body space-y-4">
                <fieldset class="fieldset">
                    <label class="label" for="course_id">Course <span class="text-error">*</span></label>
                    <select id="course_id" x-model="form.course_id" class="select w-full" required>
                        <option value="">Select course</option>
                        <template x-for="c in courses" :key="c.id">
                            <option :value="String(c.id)" x-text="(c.code || '') + ' — ' + (c.name || '')"></option>
                        </template>
                    </select>
                    <p class="text-sm text-base-content/60 mt-1">Select the course for this exam session.</p>
                    <p class="text-error text-sm mt-1" x-show="errors.course_id" x-text="errors.course_id"></p>
                </fieldset>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <fieldset class="fieldset">
                        <label class="label" for="room_id">Room <span class="text-error">*</span></label>
                        <select id="room_id" x-model="form.room_id" class="select w-full" required>
                            <option value="">Select room</option>
                            <template x-for="r in rooms" :key="r.id">
                                <option :value="String(r.id)" x-text="r.name + ' (cap. ' + r.capacity + ')'"></option>
                            </template>
                        </select>
                        <p class="text-sm text-base-content/60 mt-1">Room where the exam will be held.</p>
                        <p class="text-error text-sm mt-1" x-show="errors.room_id" x-text="errors.room_id"></p>
                    </fieldset>
                    <fieldset class="fieldset">
                        <label class="label" for="proctor_id">Proctor <span class="text-error">*</span></label>
                        <select id="proctor_id" x-model="form.proctor_id" class="select w-full" required>
                            <option value="">Select proctor</option>
                            <template x-for="p in proctors" :key="p.id">
                                <option :value="String(p.id)" x-text="(p.first_name || '') + ' ' + (p.last_name || '') + ' (' + (p.email || '') + ')'"></option>
                            </template>
                        </select>
                        <p class="text-sm text-base-content/60 mt-1">Proctor assigned to supervise this session.</p>
                        <p class="text-error text-sm mt-1" x-show="errors.proctor_id" x-text="errors.proctor_id"></p>
                    </fieldset>
                </div>
                <fieldset class="fieldset">
                    <label class="label" for="date">Date <span class="text-error">*</span></label>
                    <input id="date" type="date" x-model="form.date" class="input w-full" required>
                    <p class="text-sm text-base-content/60 mt-1">Date when the exam session will be held.</p>
                    <p class="text-error text-sm mt-1" x-show="errors.date" x-text="errors.date"></p>
                </fieldset>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <fieldset class="fieldset">
                        <label class="label" for="start_time">Start time <span class="text-error">*</span></label>
                        <input id="start_time" type="time" x-model="form.start_time" class="input w-full" required>
                        <p class="text-error text-sm mt-1" x-show="errors.start_time" x-text="errors.start_time"></p>
                    </fieldset>
                    <fieldset class="fieldset">
                        <label class="label" for="end_time">End time <span class="text-error">*</span></label>
                        <input id="end_time" type="time" x-model="form.end_time" class="input w-full" required>
                        <p class="text-error text-sm mt-1" x-show="errors.end_time" x-text="errors.end_time"></p>
                    </fieldset>
                    <fieldset class="fieldset">
                        <label class="label" for="status">Status <span class="text-error">*</span></label>
                        <select id="status" x-model="form.status" class="select w-full" required>
                            <option value="scheduled">Scheduled</option>
                            <option value="in_progress">In Progress</option>
                            <option value="completed">Completed</option>
                        </select>
                        <p class="text-error text-sm mt-1" x-show="errors.status" x-text="errors.status"></p>
                    </fieldset>
                </div>
                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving">Save</span>
                        <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                    </button>
                    <a href="/admin/sessions" class="btn btn-ghost">Cancel</a>
                    <button type="button" class="btn btn-ghost text-error ml-auto" @click="confirmDelete()" :disabled="saving">
                        Delete session
                    </button>
                </div>
                <p class="text-error" x-show="error" x-text="error"></p>
            </div>
        </form>
    </template>

    <dialog id="delete-modal" class="modal" :class="{ 'modal-open': showDeleteModal }">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete this exam session?</h3>
            <p class="py-2">You cannot delete a session that has assignments linked to it.</p>
            <div class="modal-action">
                <button type="button" class="btn" @click="showDeleteModal = false">Cancel</button>
                <button type="button" class="btn btn-error" @click="doDelete()" :disabled="deleting">Delete</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="showDeleteModal = false">
            <button type="submit">close</button>
        </form>
    </dialog>
</div>

<script>
function sessionEdit(sessionId, proctors) {
    return {
        sessionId,
        proctors: proctors || [],
        courses: [],
        rooms: [],
        form: null,
        loading: true,
        notFound: false,
        errors: {},
        error: null,
        saving: false,
        showDeleteModal: false,
        deleting: false,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        async init() {
            this.loading = true;
            this.notFound = false;
            this.error = null;
            try {
                await Promise.all([this.fetchCourses(), this.fetchRooms()]);
                await this.fetchSession();
            } finally {
                this.loading = false;
            }
        },
        async fetchSession() {
            try {
                const res = await fetch('/api/exam-sessions/' + this.sessionId, { credentials: 'include', headers: this.getHeaders() });
                if (res.status === 404) { this.notFound = true; return; }
                if (!res.ok) throw new Error('Could not load session.');
                const json = await res.json();
                const d = json.data;
                const startTime = typeof d.start_time === 'string' ? d.start_time.substring(0, 5) : (d.start_time || '08:00');
                const endTime = typeof d.end_time === 'string' ? d.end_time.substring(0, 5) : (d.end_time || '10:00');
                this.form = {
                    course_id: d.course_id != null ? String(d.course_id) : '',
                    room_id: d.room_id != null ? String(d.room_id) : '',
                    proctor_id: d.proctor_id != null ? String(d.proctor_id) : '',
                    date: typeof d.date === 'string' ? d.date.substring(0, 10) : d.date,
                    start_time: startTime,
                    end_time: endTime,
                    status: d.status || 'scheduled',
                };
            } catch (e) {
                this.error = e.message || 'Could not load.';
            }
        },
        async fetchCourses() {
            try {
                const res = await fetch('/api/courses', { credentials: 'include', headers: this.getHeaders() });
                if (res.ok) {
                    const json = await res.json();
                    this.courses = json.data || [];
                }
            } catch (e) {
                console.error(e);
            }
        },
        async fetchRooms() {
            try {
                const res = await fetch('/api/rooms', { credentials: 'include', headers: this.getHeaders() });
                if (res.ok) {
                    const json = await res.json();
                    this.rooms = json.data || [];
                }
            } catch (e) {
                console.error(e);
            }
        },
        async submit() {
            this.errors = {}; this.error = null; this.saving = true;
            const payload = {
                course_id: this.form.course_id ? parseInt(this.form.course_id, 10) : null,
                room_id: this.form.room_id ? parseInt(this.form.room_id, 10) : null,
                proctor_id: this.form.proctor_id ? parseInt(this.form.proctor_id, 10) : null,
                date: this.form.date,
                start_time: this.form.start_time,
                end_time: this.form.end_time,
                status: this.form.status,
            };
            try {
                const res = await fetch('/api/exam-sessions/' + this.sessionId, {
                    method: 'PATCH',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    window.location.href = '/admin/sessions';
                    return;
                }
                if (res.status === 422 && data.errors) {
                    for (const [field, msgs] of Object.entries(data.errors)) {
                        this.errors[field] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                    return;
                }
                this.error = data.message || 'Could not update session.';
            } catch (e) {
                this.error = e.message || 'Something went wrong.';
            } finally {
                this.saving = false;
            }
        },
        confirmDelete() { this.showDeleteModal = true; document.getElementById('delete-modal')?.showModal?.(); },
        async doDelete() {
            this.deleting = true;
            try {
                const res = await fetch('/api/exam-sessions/' + this.sessionId, { method: 'DELETE', credentials: 'include', headers: this.getHeaders() });
                if (res.status === 409) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Cannot delete: this session has assignments linked to it.');
                    return;
                }
                if (!res.ok) throw new Error('Delete failed.');
                this.showDeleteModal = false;
                document.getElementById('delete-modal')?.close?.();
                window.location.href = '/admin/sessions';
            } catch (e) {
                alert(e.message || 'Could not delete.');
            } finally {
                this.deleting = false;
            }
        },
    };
}
</script>
@endsection
