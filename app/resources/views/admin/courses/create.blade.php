@extends('layouts.app')

@section('content')
<div class="max-w-xl" x-data="courseForm()" x-init="fetchPeriods()">
    <div class="mb-6">
        <div class="breadcrumbs text-sm mb-2">
            <ul>
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/admin/courses">Courses</a></li>
                <li class="text-base-content/60">New</li>
            </ul>
        </div>
        <h1 class="text-2xl font-bold">New course</h1>
        <p class="text-base-content/70 text-sm mt-1">Add a new course program to an admission period.</p>
    </div>

    <template x-if="periodsLoading && periods.length === 0">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading periods...</span>
        </div>
    </template>

    <form x-show="periods.length > 0" @submit.prevent="submit()" class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <fieldset class="fieldset">
                <label class="label" for="admission_period_id">Admission period <span class="text-error">*</span></label>
                <select id="admission_period_id" x-model="form.admission_period_id" class="select w-full" required>
                    <option value="">Select period</option>
                    <template x-for="p in periods" :key="p.id">
                        <option :value="p.id" x-text="p.name"></option>
                    </template>
                </select>
                <p class="text-sm text-base-content/60 mt-1">Select the admission period this course belongs to.</p>
                <p class="text-error text-sm mt-1" x-show="errors.admission_period_id" x-text="errors.admission_period_id"></p>
            </fieldset>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <fieldset class="fieldset">
                    <label class="label" for="name">Name <span class="text-error">*</span></label>
                    <input id="name" type="text" x-model="form.name" class="input w-full" placeholder="e.g. BS Information Technology" maxlength="255" required>
                    <p class="text-sm text-base-content/60 mt-1">Full name of the course program.</p>
                    <p class="text-error text-sm mt-1" x-show="errors.name" x-text="errors.name"></p>
                </fieldset>
                <fieldset class="fieldset">
                    <label class="label" for="code">Code <span class="text-error">*</span></label>
                    <input id="code" type="text" x-model="form.code" class="input w-full" placeholder="e.g. BSIT" maxlength="20" required>
                    <p class="text-sm text-base-content/60 mt-1">Course code must be 2-20 characters, uppercase letters and numbers.</p>
                    <p class="text-error text-sm mt-1" x-show="errors.code" x-text="errors.code"></p>
                </fieldset>
            </div>
            <fieldset class="fieldset">
                <label class="label" for="description">Description</label>
                <textarea id="description" x-model="form.description" class="textarea w-full" rows="3" maxlength="2000" placeholder="Bachelor of Science in IT"></textarea>
                <p class="text-sm text-base-content/60 mt-1">Optional. Brief description of the course program.</p>
                <p class="text-error text-sm mt-1" x-show="errors.description" x-text="errors.description"></p>
            </fieldset>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="!saving">Create</span>
                    <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                </button>
                <a href="/admin/courses" class="btn btn-ghost">Cancel</a>
            </div>
            <p class="text-error" x-show="error" x-text="error"></p>
        </div>
    </form>

    <p x-show="!periodsLoading && periods.length === 0" class="text-base-content/70">No admission periods yet. <a href="/admin/periods/new" class="link">Create a period</a> first.</p>
</div>

<script>
function courseForm() {
    return {
        form: { admission_period_id: '', name: '', code: '', description: '' },
        periods: [],
        periodsLoading: true,
        errors: {},
        error: null,
        saving: false,
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
            } finally {
                this.periodsLoading = false;
            }
        },
        async submit() {
            this.errors = {}; this.error = null; this.saving = true;
            const payload = { ...this.form };
            if (!payload.description) payload.description = null;
            try {
                const res = await fetch('/api/courses', {
                    method: 'POST',
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
                this.error = data.message || 'Could not create course.';
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
