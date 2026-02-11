@extends('layouts.app')

@section('content')
<div class="max-w-xl" x-data="periodForm()">
    <div class="mb-6">
        <div class="breadcrumbs text-sm mb-2">
            <ul>
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/admin/periods">Admission Periods</a></li>
                <li class="text-base-content/60">New</li>
            </ul>
        </div>
        <h1 class="text-2xl font-bold">New admission period</h1>
        <p class="text-base-content/70 text-sm mt-1">Create a new admission period to organize courses and applications by academic term.</p>
    </div>

    <form @submit.prevent="submit()" class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <fieldset class="fieldset">
                <label class="label" for="name">Name <span class="text-error">*</span></label>
                <input id="name" type="text" x-model="form.name" class="input w-full" placeholder="e.g. 2nd Semester AY 2026-2027" maxlength="255" required>
                <p class="text-sm text-base-content/60 mt-1">Enter a descriptive name for this admission period (e.g., "1st Semester 2026" or "2nd Semester AY 2026-2027").</p>
                <p class="text-error text-sm mt-1" x-show="errors.name" x-text="errors.name"></p>
            </fieldset>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <fieldset class="fieldset">
                    <label class="label" for="start_date">Start date <span class="text-error">*</span></label>
                    <input id="start_date" type="date" x-model="form.start_date" class="input w-full" required>
                    <p class="text-sm text-base-content/60 mt-1">First day applications are accepted for this period.</p>
                    <p class="text-error text-sm mt-1" x-show="errors.start_date" x-text="errors.start_date"></p>
                </fieldset>
                <fieldset class="fieldset">
                    <label class="label" for="end_date">End date <span class="text-error">*</span></label>
                    <input id="end_date" type="date" x-model="form.end_date" class="input w-full" required>
                    <p class="text-sm text-base-content/60 mt-1">Last day applications are accepted for this period.</p>
                    <p class="text-error text-sm mt-1" x-show="errors.end_date" x-text="errors.end_date"></p>
                </fieldset>
            </div>
            <fieldset class="fieldset">
                <label class="label" for="status">Status</label>
                <select id="status" x-model="form.status" class="select w-full">
                    <option value="draft">Draft</option>
                    <option value="active">Active</option>
                    <option value="closed">Closed</option>
                </select>
                <p class="text-sm text-base-content/60 mt-1">Draft: not yet accepting applications. Active: accepting applications. Closed: period has ended.</p>
                <p class="text-error text-sm mt-1" x-show="errors.status" x-text="errors.status"></p>
            </fieldset>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="!saving">Create</span>
                    <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                </button>
                <a href="/admin/periods" class="btn btn-ghost">Cancel</a>
            </div>
            <p class="text-error" x-show="error" x-text="error"></p>
        </div>
    </form>
</div>

<script>
function periodForm() {
    return {
        form: { name: '', start_date: '', end_date: '', status: 'draft' },
        errors: {},
        error: null,
        saving: false,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        async submit() {
            this.errors = {}; this.error = null; this.saving = true;
            try {
                const res = await fetch('/api/admission-periods', {
                    method: 'POST',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(this.form),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    window.location.href = '/admin/periods';
                    return;
                }
                if (res.status === 422 && data.errors) {
                    for (const [field, msgs] of Object.entries(data.errors)) {
                        this.errors[field] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                    return;
                }
                this.error = data.message || 'Could not create period.';
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
