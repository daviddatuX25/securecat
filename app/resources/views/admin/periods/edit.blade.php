@extends('layouts.app')

@section('content')
<div class="p-6 max-w-xl" x-data="periodEdit(@js($periodId))" x-init="fetchPeriod()">
    <div class="mb-6">
        <a href="/admin/periods" class="link link-hover text-sm">← Admission periods</a>
        <h1 class="text-2xl font-bold mt-2">Edit admission period</h1>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="notFound">
        <div class="alert alert-warning">
            <span>Period not found.</span>
            <a href="/admin/periods" class="link">Back to list</a>
        </div>
    </template>

    <template x-if="!loading && !notFound && form">
        <form @submit.prevent="submit()" class="card bg-base-100 shadow">
            <div class="card-body space-y-4">
                <div class="form-control">
                    <label class="label" for="name"><span class="label-text">Name</span></label>
                    <input id="name" type="text" x-model="form.name" class="input input-bordered w-full" maxlength="255" required>
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
                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving">Save</span>
                        <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                    </button>
                    <a href="/admin/periods" class="btn btn-ghost">Cancel</a>
                    <button type="button" class="btn btn-ghost text-error ml-auto" @click="confirmDelete()" :disabled="saving">
                        Delete period
                    </button>
                </div>
                <p class="text-error" x-show="error" x-text="error"></p>
            </div>
        </form>
    </template>

    <!-- Delete confirmation modal -->
    <dialog id="delete-modal" class="modal" :class="{ 'modal-open': showDeleteModal }">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete this admission period?</h3>
            <p class="py-2">You cannot delete a period that has courses linked to it.</p>
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
function periodEdit(periodId) {
    return {
        periodId,
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
        async fetchPeriod() {
            try {
                const res = await fetch('/api/admission-periods/' + this.periodId, { credentials: 'include', headers: this.getHeaders() });
                if (res.status === 404) { this.notFound = true; return; }
                if (!res.ok) throw new Error('Could not load period.');
                const json = await res.json();
                const d = json.data;
                this.form = { name: d.name, start_date: d.start_date, end_date: d.end_date, status: d.status };
            } catch (e) {
                this.error = e.message || 'Could not load.';
            } finally {
                this.loading = false;
            }
        },
        async submit() {
            this.errors = {}; this.error = null; this.saving = true;
            try {
                const res = await fetch('/api/admission-periods/' + this.periodId, {
                    method: 'PATCH',
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
                this.error = data.message || 'Could not update period.';
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
                const res = await fetch('/api/admission-periods/' + this.periodId, { method: 'DELETE', credentials: 'include', headers: this.getHeaders() });
                if (res.status === 409) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Cannot delete: this period has courses linked to it.');
                    return;
                }
                if (!res.ok) throw new Error('Delete failed.');
                this.showDeleteModal = false;
                document.getElementById('delete-modal')?.close?.();
                window.location.href = '/admin/periods';
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
