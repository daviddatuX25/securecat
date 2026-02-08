@extends('layouts.app')

@section('content')
<div class="p-6 max-w-xl" x-data="periodForm()">
    <div class="mb-6">
        <a href="/admin/periods" class="link link-hover text-sm">← Admission periods</a>
        <h1 class="text-2xl font-bold mt-2">New admission period</h1>
    </div>

    <form @submit.prevent="submit()" class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <div class="form-control">
                <label class="label" for="name"><span class="label-text">Name</span></label>
                <input id="name" type="text" x-model="form.name" class="input input-bordered w-full" placeholder="e.g. 2nd Semester AY 2026-2027" maxlength="255" required>
                <p class="text-error text-sm mt-1" x-show="errors.name" x-text="errors.name"></p>
            </div>
            <div class="form-control">
                <label class="label" for="start_date"><span class="label-text">Start date</span></label>
                <input id="start_date" type="date" x-model="form.start_date" class="input input-bordered w-full" required>
                <p class="text-error text-sm mt-1" x-show="errors.start_date" x-text="errors.start_date"></p>
            </div>
            <div class="form-control">
                <label class="label" for="end_date"><span class="label-text">End date</span></label>
                <input id="end_date" type="date" x-model="form.end_date" class="input input-bordered w-full" required>
                <p class="text-error text-sm mt-1" x-show="errors.end_date" x-text="errors.end_date"></p>
            </div>
            <div class="form-control">
                <label class="label" for="status"><span class="label-text">Status</span></label>
                <select id="status" x-model="form.status" class="select select-bordered w-full">
                    <option value="draft">draft</option>
                    <option value="active">active</option>
                    <option value="closed">closed</option>
                </select>
                <p class="text-error text-sm mt-1" x-show="errors.status" x-text="errors.status"></p>
            </div>
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
