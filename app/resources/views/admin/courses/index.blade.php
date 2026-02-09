@extends('layouts.app')

@section('content')
<div x-data="coursesIndex()" x-init="init()">
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <h1 class="text-2xl font-bold">Courses</h1>
        <a href="/admin/courses/new" class="btn btn-primary">New course</a>
    </div>

    <div class="mb-4 flex flex-wrap items-center gap-2" x-show="periods.length > 0">
        <label class="label text-sm">Filter by period</label>
        <select x-model="filterPeriodId" @change="fetchCourses()" class="select select-bordered select-sm w-64">
            <option value="">All periods</option>
            <template x-for="p in periods" :key="p.id">
                <option :value="p.id" x-text="p.name"></option>
            </template>
        </select>
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
            <button type="button" class="btn btn-sm" @click="fetchCourses()">Retry</button>
        </div>
    </template>

    <template x-if="!loading && !error && courses.length === 0">
        <div class="card bg-base-100 shadow">
            <div class="card-body">
                <p class="text-base-content/70">No items yet. Create one.</p>
                <a href="/admin/courses/new" class="btn btn-primary w-fit">New course</a>
            </div>
        </div>
    </template>

    <template x-if="!loading && !error && courses.length > 0">
        <div class="overflow-x-auto card bg-base-100 shadow">
            <table class="table">
                <thead>
                    <tr>
                        <th>Period</th>
                        <th>Name</th>
                        <th>Code</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="c in courses" :key="c.id">
                        <tr>
                            <td x-text="periodName(c.admission_period_id)"></td>
                            <td x-text="c.name"></td>
                            <td><span class="badge badge-ghost" x-text="c.code"></span></td>
                            <td class="flex gap-2">
                                <a :href="'/admin/courses/' + c.id + '/edit'" class="btn btn-ghost btn-sm">Edit</a>
                                <button type="button" class="btn btn-ghost btn-sm text-error" @click="confirmDelete(c)" :disabled="deleting === c.id">
                                    <span x-show="deleting !== c.id">Delete</span>
                                    <span class="loading loading-spinner loading-sm" x-show="deleting === c.id"></span>
                                </button>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </template>

    <dialog id="delete-modal" class="modal" :class="{ 'modal-open': deleteTarget }">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete course?</h3>
            <p class="py-2" x-show="deleteTarget" x-text="'This will remove «' + (deleteTarget?.name || '') + '» (' + (deleteTarget?.code || '') + '). You cannot delete a course that has exam sessions or applications linked to it.'"></p>
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
function coursesIndex() {
    return {
        courses: [],
        periods: [],
        filterPeriodId: '',
        loading: true,
        error: null,
        deleting: null,
        deleteTarget: null,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        periodName(periodId) {
            const p = this.periods.find(x => String(x.id) === String(periodId));
            return p ? p.name : periodId;
        },
        async init() {
            await this.fetchPeriods();
            await this.fetchCourses();
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
        async fetchCourses() {
            this.loading = true; this.error = null;
            try {
                const q = new URLSearchParams();
                if (this.filterPeriodId) q.set('admission_period_id', this.filterPeriodId);
                const res = await fetch('/api/courses?' + q, { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load courses.');
                const json = await res.json();
                this.courses = json.data || [];
            } catch (e) {
                this.error = e.message || 'Could not load. Retry.';
            } finally {
                this.loading = false;
            }
        },
        confirmDelete(c) {
            this.deleteTarget = c;
            document.getElementById('delete-modal')?.showModal?.();
        },
        async doDelete() {
            if (!this.deleteTarget) return;
            const id = this.deleteTarget.id;
            this.deleting = id;
            try {
                const res = await fetch('/api/courses/' + id, { method: 'DELETE', credentials: 'include', headers: this.getHeaders() });
                if (res.status === 409) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Cannot delete: this course has exam sessions or applications linked to it.');
                    return;
                }
                if (!res.ok) throw new Error('Delete failed.');
                this.deleteTarget = null;
                document.getElementById('delete-modal')?.close?.();
                await this.fetchCourses();
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
