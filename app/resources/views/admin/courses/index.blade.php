@extends('layouts.app')

@section('content')
<div x-data="coursesIndex()" x-init="init()">
    <div class="mb-6">
        <div class="breadcrumbs text-sm mb-2">
            <ul>
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li class="text-base-content/60">Courses</li>
            </ul>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Courses</h1>
                <p class="text-base-content/70 text-sm mt-1">Manage course programs available for each admission period.</p>
            </div>
            <a href="/admin/courses/new" class="btn btn-primary">New course</a>
        </div>
    </div>

    <div class="mb-4" x-show="periods.length > 0">
        <fieldset class="fieldset">
            <label class="label text-xs py-0">Filter by period</label>
            <select x-model="filterPeriodId" @change="fetchCourses()" class="select select-sm w-64">
                <option value="">All periods</option>
                <template x-for="p in periods" :key="p.id">
                    <option :value="p.id" x-text="p.name"></option>
                </template>
            </select>
        </fieldset>
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
        <x-empty-state 
            title="No courses yet"
            description="Create your first course to make it available for applicants to choose from."
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5zm0 7l-9-5 9-5 9 5-9 5z"/>
            </svg>
            <a href="/admin/courses/new" class="btn btn-primary">Create first course</a>
        </x-empty-state>
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
