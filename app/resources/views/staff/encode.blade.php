@extends('layouts.app')

@section('content')
<div class="p-6 max-w-xl" x-data="encodeForm(@js($courses->toArray()))" x-init="init()">
    <div class="mb-6">
        <a href="/staff/home" class="link link-hover text-sm">← Staff Home</a>
        <h1 class="text-2xl font-bold mt-2">Encode Applicant</h1>
        <p class="text-base-content/70 text-sm mt-1">Create applicant and application. First preferred course is required; second and third are optional (must be distinct).</p>
    </div>

    <form @submit.prevent="submit()" class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="form-control">
                    <label class="label" for="first_name"><span class="label-text">First name</span> <span class="label-text-alt text-error">required</span></label>
                    <input id="first_name" type="text" x-model="form.first_name" class="input input-bordered w-full" placeholder="Juan" maxlength="100" required>
                    <p class="text-error text-sm mt-1" x-show="errors.first_name" x-text="errors.first_name"></p>
                </div>
                <div class="form-control">
                    <label class="label" for="last_name"><span class="label-text">Last name</span> <span class="label-text-alt text-error">required</span></label>
                    <input id="last_name" type="text" x-model="form.last_name" class="input input-bordered w-full" placeholder="Dela Cruz" maxlength="100" required>
                    <p class="text-error text-sm mt-1" x-show="errors.last_name" x-text="errors.last_name"></p>
                </div>
            </div>
            <div class="form-control">
                <label class="label" for="email"><span class="label-text">Email</span> <span class="label-text-alt text-base-content/60">optional</span></label>
                <input id="email" type="email" x-model="form.email" class="input input-bordered w-full" placeholder="juan@email.com">
                <p class="text-error text-sm mt-1" x-show="errors.email" x-text="errors.email"></p>
            </div>
            <div class="form-control">
                <label class="label" for="contact_number"><span class="label-text">Contact number</span> <span class="label-text-alt text-base-content/60">optional</span></label>
                <input id="contact_number" type="text" x-model="form.contact_number" class="input input-bordered w-full" placeholder="09171234567" maxlength="20">
                <p class="text-error text-sm mt-1" x-show="errors.contact_number" x-text="errors.contact_number"></p>
            </div>
            <div class="form-control">
                <label class="label" for="date_of_birth"><span class="label-text">Date of birth</span> <span class="label-text-alt text-error">required (age ≥ 15)</span></label>
                <input id="date_of_birth" type="date" x-model="form.date_of_birth" class="input input-bordered w-full" required>
                <p class="text-error text-sm mt-1" x-show="errors.date_of_birth" x-text="errors.date_of_birth"></p>
            </div>
            <div class="form-control">
                <label class="label" for="address"><span class="label-text">Address</span> <span class="label-text-alt text-base-content/60">optional</span></label>
                <textarea id="address" x-model="form.address" class="textarea textarea-bordered w-full" rows="2" maxlength="500" placeholder="123 Main St"></textarea>
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
                    <span x-show="!saving">Submit</span>
                    <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                </button>
                <a href="/staff/home" class="btn btn-ghost">Cancel</a>
            </div>
            <p class="text-error" x-show="error" x-text="error"></p>
            <div x-show="success" class="alert alert-success shadow-lg">
                <span x-text="successMessage"></span>
                <a href="/staff/encode" class="btn btn-sm">Encode another</a>
                <a :href="'/staff/applications/' + lastApplicationId" class="btn btn-sm btn-ghost" x-show="lastApplicationId">View application</a>
            </div>
        </div>
    </form>

    <p x-show="courses.length === 0 && !loading" class="text-base-content/70 mt-4">No active courses. Contact admin to add courses to an active admission period.</p>
</div>

<script>
function encodeForm(initialCourses) {
    return {
        courses: initialCourses,
        loading: false,
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
        saving: false,
        success: false,
        successMessage: '',
        lastApplicationId: null,
        init() {
            this.loading = false;
        },
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
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
                first_course_id: this.form.first_course_id ? parseInt(this.form.first_course_id) : null,
            };
            if (this.form.email) payload.email = this.form.email;
            if (this.form.contact_number) payload.contact_number = this.form.contact_number;
            if (this.form.address) payload.address = this.form.address;
            if (this.form.second_course_id) payload.second_course_id = parseInt(this.form.second_course_id);
            if (this.form.third_course_id) payload.third_course_id = parseInt(this.form.third_course_id);
            try {
                const res = await fetch('/api/applicants', {
                    method: 'POST',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    this.successMessage = 'Applicant and application created. Status: ' + (data.status || 'pending_review');
                    this.lastApplicationId = data.application_id;
                    this.success = true;
                    this.form = { first_name: '', last_name: '', email: '', contact_number: '', date_of_birth: '', address: '', first_course_id: '', second_course_id: '', third_course_id: '' };
                    return;
                }
                if (res.status === 422 && data.errors) {
                    for (const [field, msgs] of Object.entries(data.errors)) {
                        this.errors[field] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                    return;
                }
                this.error = data.message || 'Could not create applicant.';
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
