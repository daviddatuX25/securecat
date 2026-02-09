@extends('layouts.app')

@section('content')
<div x-data="periodsIndex()" x-init="fetchPeriods()">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold">Admission Periods</h1>
        <a href="/admin/periods/new" class="btn btn-primary">New period</a>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="error">
        <div class="alert alert-warning shadow-lg">
            <span x-text="error"></span>
            <button type="button" class="btn btn-sm" @click="fetchPeriods()">Retry</button>
        </div>
    </template>

    <template x-if="!loading && !error && periods.length === 0">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-base-content/70">No items yet. Create one.</p>
                <a href="/admin/periods/new" class="btn btn-primary w-fit">New period</a>
            </div>
        </div>
    </template>

    <template x-if="!loading && !error && periods.length > 0">
        <div class="overflow-x-auto card bg-base-100 shadow">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="p in periods" :key="p.id">
                        <tr>
                            <td x-text="p.name"></td>
                            <td x-text="p.start_date"></td>
                            <td x-text="p.end_date"></td>
                            <td><span class="badge" :class="statusBadgeClass(p.status)" x-text="p.status"></span></td>
                            <td class="flex gap-2">
                                <a :href="'/admin/periods/' + p.id + '/edit'" class="btn btn-ghost btn-sm">Edit</a>
                                <button type="button" class="btn btn-ghost btn-sm text-error" @click="confirmDelete(p)" :disabled="deleting === p.id">
                                    <span x-show="deleting !== p.id">Delete</span>
                                    <span class="loading loading-spinner loading-sm" x-show="deleting === p.id"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

    <!-- Delete confirmation modal -->
    <dialog id="delete-modal" class="modal" :class="{ 'modal-open': deleteTarget }">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete admission period?</h3>
            <p class="py-2" x-show="deleteTarget" x-text="'This will remove «' + (deleteTarget?.name || '') + '». You cannot delete a period that has courses linked to it.'"></p>
            <div class="modal-action">
                <form method="dialog">
                    <button type="button" class="btn" @click="deleteTarget = null">Cancel</button>
                </form>
                <button type="button" class="btn btn-error" @click="doDelete()" :disabled="deleting">Delete</button>
            </div>
        </div>
        <form method="dialog" class="modal-backdrop" @click="deleteTarget = null">
            <button type="submit">close</button>
        </form>
    </dialog>
</div>

<script>
function periodsIndex() {
    return {
        periods: [],
        loading: true,
        error: null,
        deleting: null,
        deleteTarget: null,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        statusBadgeClass(s) {
            if (s === 'active') return 'badge-success';
            if (s === 'closed') return 'badge-ghost';
            return 'badge-neutral';
        },
        async fetchPeriods() {
            this.loading = true; this.error = null;
            try {
                const q = new URLSearchParams();
                const res = await fetch('/api/admission-periods?' + q, { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load periods.');
                const json = await res.json();
                this.periods = json.data || [];
            } catch (e) {
                this.error = e.message || 'Could not load. Retry.';
            } finally {
                this.loading = false;
            }
        },
        confirmDelete(p) {
            this.deleteTarget = p;
            document.getElementById('delete-modal')?.showModal?.();
        },
        async doDelete() {
            if (!this.deleteTarget) return;
            const id = this.deleteTarget.id;
            this.deleting = id;
            try {
                const res = await fetch('/api/admission-periods/' + id, { method: 'DELETE', credentials: 'include', headers: this.getHeaders() });
                if (res.status === 409) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Cannot delete: this period has courses linked to it.');
                    return;
                }
                if (!res.ok) throw new Error('Delete failed.');
                this.deleteTarget = null;
                document.getElementById('delete-modal')?.close?.();
                await this.fetchPeriods();
            } catch (e) {
                alert(e.message || 'Could not delete.');
            } finally {
                this.deleting = null;
            }
        },
    };
}
</script>
@endsection
