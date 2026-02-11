@extends('layouts.app')

@section('content')
<div x-data="roomsIndex()" x-init="init()">
    <div class="mb-6">
        <div class="breadcrumbs text-sm mb-2">
            <ul>
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li class="text-base-content/60">Rooms</li>
            </ul>
        </div>
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold">Rooms</h1>
                <p class="text-base-content/70 text-sm mt-1">Manage exam rooms and their capacity for scheduling exam sessions.</p>
            </div>
            <a href="/admin/rooms/new" class="btn btn-primary">New room</a>
        </div>
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
            <button type="button" class="btn btn-sm" @click="fetchRooms()">Retry</button>
        </div>
    </template>

    <template x-if="!loading && !error && rooms.length === 0">
        <x-empty-state 
            title="No rooms yet"
            description="Create your first exam room to start scheduling exam sessions."
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mx-auto text-base-content/30 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
            </svg>
            <a href="/admin/rooms/new" class="btn btn-primary">Create first room</a>
        </x-empty-state>
    </template>

    <template x-if="!loading && !error && rooms.length > 0">
        <div class="overflow-x-auto card bg-base-100 shadow">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Capacity</th>
                        <th>Location notes</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="r in rooms" :key="r.id">
                        <tr>
                            <td x-text="r.name"></td>
                            <td x-text="r.capacity"></td>
                            <td class="max-w-xs truncate" x-text="r.location_notes || '—'"></td>
                            <td class="flex gap-2">
                                <a :href="'/admin/rooms/' + r.id + '/edit'" class="btn btn-ghost btn-sm">Edit</a>
                                <button type="button" class="btn btn-ghost btn-sm text-error" @click="confirmDelete(r)" :disabled="deleting === r.id">
                                    <span x-show="deleting !== r.id">Delete</span>
                                    <span class="loading loading-spinner loading-sm" x-show="deleting === r.id"></span>
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
            <h3 class="font-bold text-lg">Delete room?</h3>
            <p class="py-2" x-show="deleteTarget" x-text="'This will remove «' + (deleteTarget?.name || '') + '». You cannot delete a room that has exam sessions linked to it.'"></p>
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
function roomsIndex() {
    return {
        rooms: [],
        loading: true,
        error: null,
        deleting: null,
        deleteTarget: null,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        async init() {
            await this.fetchRooms();
        },
        async fetchRooms() {
            this.loading = true; this.error = null;
            try {
                const res = await fetch('/api/rooms', { credentials: 'include', headers: this.getHeaders() });
                if (!res.ok) throw new Error('Could not load rooms.');
                const json = await res.json();
                this.rooms = json.data || [];
            } catch (e) {
                this.error = e.message || 'Could not load. Retry.';
            } finally {
                this.loading = false;
            }
        },
        confirmDelete(r) {
            this.deleteTarget = r;
            document.getElementById('delete-modal')?.showModal?.();
        },
        async doDelete() {
            if (!this.deleteTarget) return;
            const id = this.deleteTarget.id;
            this.deleting = id;
            try {
                const res = await fetch('/api/rooms/' + id, { method: 'DELETE', credentials: 'include', headers: this.getHeaders() });
                if (res.status === 409) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Cannot delete: this room has exam sessions linked to it.');
                    return;
                }
                if (!res.ok) throw new Error('Delete failed.');
                this.deleteTarget = null;
                document.getElementById('delete-modal')?.close?.();
                await this.fetchRooms();
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
