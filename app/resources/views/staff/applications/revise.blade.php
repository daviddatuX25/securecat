@extends('layouts.app')

@section('content')
<div class="p-6 max-w-xl" x-data="reviseForm(@js($applicationId), @js($courses->toArray()))" x-init="init()">
    <div class="mb-6">
        <a :href="'/staff/applications/' + applicationId" class="link link-hover text-sm">← Back to application</a>
        <h1 class="text-2xl font-bold mt-2">Revise application</h1>
        <p class="text-base-content/70 text-sm mt-1">Update applicant and course choices, then resubmit for review. Admin notes are shown below.</p>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="notAllowed">
        <div class="alert alert-warning shadow-lg">
            <span>You can only revise applications that are in "Revision requested" and that you encoded.</span>
            <a href="/staff/applications" class="btn btn-sm">My applications</a>
        </div>
    </template>

    <template x-if="app && app.admin_notes" x-cloak>
        <div class="alert alert-info shadow-lg mb-4">
            <span class="font-medium">Admin notes:</span>
            <p x-text="app.admin_notes" class="mt-1"></p>
        </div>
    </template>

    <form x-show="app && app.status === 'revision_requested'" @submit.prevent="submit()" class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label" for="first_name"><span class="label-text">First name</span> <span class="label-text-alt text-error">required</span></label>
                    <input id="first_name" type="text" x-model="form.first_name" class="input input-bordered w-full" maxlength="100" required>
                    <p class="text-error text-sm mt-1" x-show="errors.first_name" x-text="errors.first_name"></p>
                </div>
                <div class="form-control">
                    <label class="label" for="last_name"><span class="label-text">Last name</span> <span class="label-text-alt text-error">required</span></label>
                    <input id="last_name" type="text" x-model="form.last_name" class="input input-bordered w-full" maxlength="100" required>
                    <p class="text-error text-sm mt-1" x-show="errors.last_name" x-text="errors.last_name"></p>
                </div>
            </div>
            <div class="form-control">
                <label class="label" for="email"><span class="label-text">Email</span> <span class="label-text-alt text-base-content/60">optional</span></label>
                <input id="email" type="email" x-model="form.email" class="input input-bordered w-full">
                <p class="text-error text-sm mt-1" x-show="errors.email" x-text="errors.email"></p>
            </div>
            <div class="form-control">
                <label class="label" for="contact_number"><span class="label-text">Contact number</span> <span class="label-text-alt text-base-content/60">optional</span></label>
                <input id="contact_number" type="text" x-model="form.contact_number" class="input input-bordered w-full" maxlength="20">
                <p class="text-error text-sm mt-1" x-show="errors.contact_number" x-text="errors.contact_number"></p>
            </div>
            <div class="form-control">
                <label class="label" for="date_of_birth"><span class="label-text">Date of birth</span> <span class="label-text-alt text-error">required (age ≥ 15)</span></label>
                <input id="date_of_birth" type="date" x-model="form.date_of_birth" class="input input-bordered w-full" required>
                <p class="text-error text-sm mt-1" x-show="errors.date_of_birth" x-text="errors.date_of_birth"></p>
            </div>
            <div class="form-control">
                <label class="label" for="address"><span class="label-text">Address</span> <span class="label-text-alt text-base-content/60">optional</span></label>
                <textarea id="address" x-model="form.address" class="textarea textarea-bordered w-full" rows="2" maxlength="500"></textarea>
                <p class="text-error text-sm mt-1" x-show="errors.address" x-text="errors.address"></p>
            </div>

            <div class="divider">Preferred courses</div>
            <div class="form-control">
                <label class="label" for="first_course_id"><span class="label-text">First preferred course</span> <span class="label-text-alt text-error">required</span></label>
                <select id="first_course_id" x-model="form.first_course_id" class="select select-bordered w-full" required>
                    <option value="">Select course</option>
                    <template x-for="c in courses" :key="c.id">
                        <option :value="c.id" x-text="c.name + ' (' + c.code + ')'"></option>
                    </template>
                </select>
                <p class="text-error text-sm mt-1" x-show="errors.first_course_id" x-text="errors.first_course_id"></p>
            </div>
            <div class="form-control">
                <label class="label" for="second_course_id"><span class="label-text">Second preferred course</span> <span class="label-text-alt text-base-content/60">optional</span></label>
                <select id="second_course_id" x-model="form.second_course_id" class="select select-bordered w-full">
                    <option value="">None</option>
                    <template x-for="c in courses" :key="'s-' + c.id">
                        <option :value="c.id" x-text="c.name + ' (' + c.code + ')'" :disabled="c.id == form.first_course_id || c.id == form.third_course_id"></option>
                    </template>
                </select>
                <p class="text-error text-sm mt-1" x-show="errors.second_course_id" x-text="errors.second_course_id"></p>
            </div>
            <div class="form-control">
                <label class="label" for="third_course_id"><span class="label-text">Third preferred course</span> <span class="label-text-alt text-base-content/60">optional</span></label>
                <select id="third_course_id" x-model="form.third_course_id" class="select select-bordered w-full">
                    <option value="">None</option>
                    <template x-for="c in courses" :key="'t-' + c.id">
                        <option :value="c.id" x-text="c.name + ' (' + c.code + ')'" :disabled="c.id == form.first_course_id || c.id == form.second_course_id"></option>
                    </template>
                </select>
                <p class="text-error text-sm mt-1" x-show="errors.third_course_id" x-text="errors.third_course_id"></p>
            </div>

            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="!saving">Resubmit for review</span>
                    <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                </button>
                <a :href="'/staff/applications/' + applicationId" class="btn btn-ghost">Cancel</a>
            </div>
            <p class="text-error" x-show="error" x-text="error"></p>
            <div x-show="success" class="alert alert-success shadow-lg">
                <span>Application resubmitted. Status is now pending review.</span>
                <a href="/staff/applications" class="btn btn-sm">My applications</a>
            </div>
        </div>
    </form>
</div>

<script>
function reviseForm(applicationId, initialCourses) {
    return {
        applicationId,
        courses: initialCourses,
        app: null,
        form: {
            first_name: '',
            last_name: '',
            email: '',
            contact_number: '',
            date_of_birth: '',
            address: '',
            first_course_id: '',
            second_course_id: '',
            third_course_id: '',
        },
        errors: {},
        error: null,
        loading: true,
        notAllowed: false,
        saving: false,
        success: false,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        async init() {
            try {
                const res = await fetch('/api/applications/' + this.applicationId, { credentials: 'include', headers: this.getHeaders() });
                if (res.status === 403 || res.status === 404) { this.notAllowed = true; return; }
                if (!res.ok) throw new Error('Could not load application.');
                const json = await res.json();
                this.app = json.data;
                if (this.app && this.app.status === 'revision_requested') {
                    const a = this.app.applicant;
                    this.form = {
                        first_name: a?.first_name || '',
                        last_name: a?.last_name || '',
                        email: a?.email || '',
                        contact_number: a?.contact_number || '',
                        date_of_birth: (a?.date_of_birth || '').toString().substring(0, 10),
                        address: a?.address || '',
                        first_course_id: this.app.course_id != null ? String(this.app.course_id) : '',
                        second_course_id: (this.app.second_course && this.app.second_course.id) ? String(this.app.second_course.id) : '',
                        third_course_id: (this.app.third_course && this.app.third_course.id) ? String(this.app.third_course.id) : '',
                    };
                } else {
                    this.notAllowed = true;
                }
            } catch (e) {
                this.notAllowed = true;
            } finally {
                this.loading = false;
            }
        },
        async submit() {
            this.errors = {};
            this.error = null;
            this.success = false;
            this.saving = true;
            const payload = {
                first_name: this.form.first_name,
                last_name: this.form.last_name,
                date_of_birth: this.form.date_of_birth,
                first_course_id: this.form.first_course_id ? parseInt(this.form.first_course_id, 10) : null,
            };
            if (this.form.email) payload.email = this.form.email;
            if (this.form.contact_number) payload.contact_number = this.form.contact_number;
            if (this.form.address) payload.address = this.form.address;
            if (this.form.second_course_id) payload.second_course_id = parseInt(this.form.second_course_id, 10);
            if (this.form.third_course_id) payload.third_course_id = parseInt(this.form.third_course_id, 10);
            try {
                const res = await fetch('/api/applications/' + this.applicationId, {
                    method: 'PATCH',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.success = true;
                    return;
                }
                if (res.status === 422 && data.errors) {
                    for (const [field, msgs] of Object.entries(data.errors)) {
                        this.errors[field] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                    return;
                }
                this.error = data.message || 'Could not resubmit.';
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
