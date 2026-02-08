@extends('layouts.app')

@section('content')
<div class="p-6 max-w-xl" x-data="courseEdit(@js($courseId))" x-init="fetchCourse(); fetchPeriods();">
    <div class="mb-6">
        <a href="/admin/courses" class="link link-hover text-sm">← Courses</a>
        <h1 class="text-2xl font-bold mt-2">Edit course</h1>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="notFound">
        <div class="alert alert-warning">
            <span>Course not found.</span>
            <a href="/admin/courses" class="link">Back to list</a>
        </div>
    </template>

    <template x-if="!loading && !notFound && form">
        <form @submit.prevent="submit()" class="card bg-base-100 shadow">
            <div class="card-body space-y-4">
                <div class="form-control">
                    <label class="label" for="admission_period_id"><span class="label-text">Admission period</span></label>
                    <select id="admission_period_id" x-model="form.admission_period_id" class="select select-bordered w-full" required>
                        <option value="">Select period</option>
                        <template x-for="p in periods" :key="p.id">
                            <option :value="p.id" x-text="p.name"></option>
                        </template>
                    </select>
                    <p class="text-error text-sm mt-1" x-show="errors.admission_period_id" x-text="errors.admission_period_id"></p>
                </div>
                <div class="form-control">
                    <label class="label" for="name"><span class="label-text">Name</span></label>
                    <input id="name" type="text" x-model="form.name" class="input input-bordered w-full" maxlength="255" required>
                    <p class="text-error text-sm mt-1" x-show="errors.name" x-text="errors.name"></p>
                </div>
                <div class="form-control">
                    <label class="label" for="code"><span class="label-text">Code</span></label>
                    <input id="code" type="text" x-model="form.code" class="input input-bordered w-full" maxlength="20" required>
                    <p class="text-error text-sm mt-1" x-show="errors.code" x-text="errors.code"></p>
                </div>
                <div class="form-control">
                    <label class="label" for="description"><span class="label-text">Description (optional)</span></label>
                    <textarea id="description" x-model="form.description" class="textarea textarea-bordered w-full" rows="3" maxlength="2000"></textarea>
                    <p class="text-error text-sm mt-1" x-show="errors.description" x-text="errors.description"></p>
                </div>
                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving">Save</span>
                        <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                    </button>
                    <a href="/admin/courses" class="btn btn-ghost">Cancel</a>
                    <button type="button" class="btn btn-ghost text-error ml-auto" @click="confirmDelete()" :disabled="saving">
                        Delete course
                    </button>
                </div>
                <p class="text-error" x-show="error" x-text="error"></p>
            </div>
        </form>
    </template>

    <dialog id="delete-modal" class="modal" :class="{ 'modal-open': showDeleteModal }">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete this course?</h3>
            <p class="py-2">You cannot delete a course that has exam sessions or applications linked to it.</p>
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
function courseEdit(courseId) {
    return {
        courseId,
        form: null,
        periods: [],
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
        async fetchCourse() {
            try {
                const res = await fetch('/api/courses/' + this.courseId, { credentials: 'include', headers: this.getHeaders() });
                if (res.status === 404) { this.notFound = true; return; }
                if (!res.ok) throw new Error('Could not load course.');
                const json = await res.json();
                const d = json.data;
                this.form = {
                    admission_period_id: String(d.admission_period_id),
                    name: d.name,
                    code: d.code,
                    description: d.description || '',
                };
            } catch (e) {
                this.error = e.message || 'Could not load.';
            } finally {
                this.loading = false;
            }
        },
        async submit() {
            this.errors = {}; this.error = null; this.saving = true;
            const payload = { ...this.form };
            if (payload.description === '') payload.description = null;
            try {
                const res = await fetch('/api/courses/' + this.courseId, {
                    method: 'PATCH',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    window.location.href = '/admin/courses';
                    return;
                }
                if (res.status === 422 && data.errors) {
                    for (const [field, msgs] of Object.entries(data.errors)) {
                        this.errors[field] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                    return;
                }
                this.error = data.message || 'Could not update course.';
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
                const res = await fetch('/api/courses/' + this.courseId, { method: 'DELETE', credentials: 'include', headers: this.getHeaders() });
                if (res.status === 409) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Cannot delete: this course has exam sessions or applications linked to it.');
                    return;
                }
                if (!res.ok) throw new Error('Delete failed.');
                this.showDeleteModal = false;
                document.getElementById('delete-modal')?.close?.();
                window.location.href = '/admin/courses';
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
