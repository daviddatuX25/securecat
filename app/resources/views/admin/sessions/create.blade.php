@extends('layouts.app')

@section('content')
<div class="max-w-xl" x-data="sessionForm(@js($proctors))" x-init="fetchCourses(); fetchRooms();">
    <div class="mb-6">
        <a href="/admin/sessions" class="link link-hover text-sm">← Exam sessions</a>
        <h1 class="text-2xl font-bold mt-2">New exam session</h1>
    </div>

    <template x-if="(coursesLoading || roomsLoading) && (courses.length === 0 || rooms.length === 0)">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading courses and rooms...</span>
        </div>
    </template>

    <form x-show="courses.length > 0 && rooms.length > 0" @submit.prevent="submit()" class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <div class="form-control">
                <label class="label" for="course_id"><span class="label-text">Course</span></label>
                <select id="course_id" x-model="form.course_id" class="select select-bordered w-full" required>
                    <option value="">Select course</option>
                    <template x-for="c in courses" :key="c.id">
                        <option :value="c.id" x-text="(c.code || '') + ' — ' + (c.name || '')"></option>
                    </template>
                </select>
                <p class="text-error text-sm mt-1" x-show="errors.course_id" x-text="errors.course_id"></p>
            </div>
            <div class="form-control">
                <label class="label" for="room_id"><span class="label-text">Room</span></label>
                <select id="room_id" x-model="form.room_id" class="select select-bordered w-full" required>
                    <option value="">Select room</option>
                    <template x-for="r in rooms" :key="r.id">
                        <option :value="r.id" x-text="r.name + ' (cap. ' + r.capacity + ')'"></option>
                    </template>
                </select>
                <p class="text-error text-sm mt-1" x-show="errors.room_id" x-text="errors.room_id"></p>
            </div>
            <div class="form-control">
                <label class="label" for="proctor_id"><span class="label-text">Proctor</span></label>
                <select id="proctor_id" x-model="form.proctor_id" class="select select-bordered w-full" required>
                    <option value="">Select proctor</option>
                    <template x-for="p in proctors" :key="p.id">
                        <option :value="p.id" x-text="(p.first_name || '') + ' ' + (p.last_name || '') + ' (' + (p.email || '') + ')'"></option>
                    </template>
                </select>
                <p class="text-error text-sm mt-1" x-show="errors.proctor_id" x-text="errors.proctor_id"></p>
            </div>
            <div class="form-control">
                <label class="label" for="date"><span class="label-text">Date</span></label>
                <input id="date" type="date" x-model="form.date" class="input input-bordered w-full" required>
                <p class="text-error text-sm mt-1" x-show="errors.date" x-text="errors.date"></p>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label" for="start_time"><span class="label-text">Start time</span></label>
                    <input id="start_time" type="time" x-model="form.start_time" class="input input-bordered w-full" required>
                    <p class="text-error text-sm mt-1" x-show="errors.start_time" x-text="errors.start_time"></p>
                </div>
                <div class="form-control">
                    <label class="label" for="end_time"><span class="label-text">End time</span></label>
                    <input id="end_time" type="time" x-model="form.end_time" class="input input-bordered w-full" required>
                    <p class="text-error text-sm mt-1" x-show="errors.end_time" x-text="errors.end_time"></p>
                </div>
            </div>
            <div class="form-control">
                <label class="label" for="status"><span class="label-text">Status</span></label>
                <select id="status" x-model="form.status" class="select select-bordered w-full" required>
                    <option value="scheduled">scheduled</option>
                    <option value="in_progress">in_progress</option>
                    <option value="completed">completed</option>
                </select>
                <p class="text-error text-sm mt-1" x-show="errors.status" x-text="errors.status"></p>
            </div>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="!saving">Create</span>
                    <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                </button>
                <a href="/admin/sessions" class="btn btn-ghost">Cancel</a>
            </div>
            <p class="text-error" x-show="error" x-text="error"></p>
        </div>
    </form>

    <p x-show="!coursesLoading && !roomsLoading && (courses.length === 0 || rooms.length === 0)" class="text-base-content/70">
        Add at least one <a href="/admin/courses/new" class="link">course</a> and one <a href="/admin/rooms/new" class="link">room</a> first.
        <span x-show="proctors.length === 0"> You also need at least one proctor user.</span>
    </p>
</div>

<script>
function sessionForm(proctors) {
    return {
        proctors: proctors || [],
        courses: [],
        rooms: [],
        coursesLoading: true,
        roomsLoading: true,
        form: {
            course_id: '',
            room_id: '',
            proctor_id: '',
            date: '',
            start_time: '08:00',
            end_time: '10:00',
            status: 'scheduled',
        },
        errors: {},
        error: null,
        saving: false,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
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
            } finally {
                this.coursesLoading = false;
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
            } finally {
                this.roomsLoading = false;
            }
        },
        async submit() {
            this.errors = {}; this.error = null; this.saving = true;
            const payload = {
                course_id: this.form.course_id,
                room_id: this.form.room_id,
                proctor_id: this.form.proctor_id,
                date: this.form.date,
                start_time: this.form.start_time,
                end_time: this.form.end_time,
                status: this.form.status,
            };
            try {
                const res = await fetch('/api/exam-sessions', {
                    method: 'POST',
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
                this.error = data.message || 'Could not create exam session.';
            } catch (e) {
                this.error = e.message || 'Something went wrong.';
            } finally {
                this.saving = false;
            }
        },
    };
}
</script>
@endsection
