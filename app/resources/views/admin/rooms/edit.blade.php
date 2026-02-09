@extends('layouts.app')

@section('content')
<div class="max-w-xl" x-data="roomEdit(@js($roomId))" x-init="fetchRoom()">
    <div class="mb-6">
        <a href="/admin/rooms" class="link link-hover text-sm">← Rooms</a>
        <h1 class="text-2xl font-bold mt-2">Edit room</h1>
    </div>

    <template x-if="loading">
        <div class="flex items-center gap-2">
            <span class="loading loading-spinner loading-md"></span>
            <span>Loading...</span>
        </div>
    </template>

    <template x-if="notFound">
        <div class="alert alert-warning">
            <span>Room not found.</span>
            <a href="/admin/rooms" class="link">Back to list</a>
        </div>
    </template>

    <template x-if="!loading && !notFound && form">
        <form @submit.prevent="submit()" class="card bg-base-100 shadow">
            <div class="card-body space-y-4">
                <div class="form-control">
                    <label class="label" for="name"><span class="label-text">Name</span></label>
                    <input id="name" type="text" x-model="form.name" class="input input-bordered w-full" maxlength="100" required>
                    <p class="text-error text-sm mt-1" x-show="errors.name" x-text="errors.name"></p>
                </div>
                <div class="form-control">
                    <label class="label" for="capacity"><span class="label-text">Capacity</span></label>
                    <input id="capacity" type="number" x-model.number="form.capacity" class="input input-bordered w-full" min="1" required>
                    <p class="text-error text-sm mt-1" x-show="errors.capacity" x-text="errors.capacity"></p>
                </div>
                <div class="form-control">
                    <label class="label" for="location_notes"><span class="label-text">Location notes (optional)</span></label>
                    <textarea id="location_notes" x-model="form.location_notes" class="textarea textarea-bordered w-full" rows="2" maxlength="1000"></textarea>
                    <p class="text-error text-sm mt-1" x-show="errors.location_notes" x-text="errors.location_notes"></p>
                </div>
                <div class="flex flex-wrap gap-2 pt-2">
                    <button type="submit" class="btn btn-primary" :disabled="saving">
                        <span x-show="!saving">Save</span>
                        <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                    </button>
                    <a href="/admin/rooms" class="btn btn-ghost">Cancel</a>
                    <button type="button" class="btn btn-ghost text-error ml-auto" @click="confirmDelete()" :disabled="saving">
                        Delete room
                    </button>
                </div>
                <p class="text-error" x-show="error" x-text="error"></p>
            </div>
        </form>
    </template>

    <dialog id="delete-modal" class="modal" :class="{ 'modal-open': showDeleteModal }">
        <div class="modal-box">
            <h3 class="font-bold text-lg">Delete this room?</h3>
            <p class="py-2">You cannot delete a room that has exam sessions linked to it.</p>
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
function roomEdit(roomId) {
    return {
        roomId,
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
        async fetchRoom() {
            try {
                const res = await fetch('/api/rooms/' + this.roomId, { credentials: 'include', headers: this.getHeaders() });
                if (res.status === 404) { this.notFound = true; return; }
                if (!res.ok) throw new Error('Could not load room.');
                const json = await res.json();
                const d = json.data;
                this.form = {
                    name: d.name,
                    capacity: d.capacity,
                    location_notes: d.location_notes || '',
                };
            } catch (e) {
                this.error = e.message || 'Could not load.';
            } finally {
                this.loading = false;
            }
        },
        async submit() {
            this.errors = {}; this.error = null; this.saving = true;
            const payload = {
                name: this.form.name,
                capacity: Number(this.form.capacity) || 1,
                location_notes: this.form.location_notes || null,
            };
            try {
                const res = await fetch('/api/rooms/' + this.roomId, {
                    method: 'PATCH',
                    credentials: 'include',
                    headers: this.getHeaders(),
                    body: JSON.stringify(payload),
                });
                const data = await res.json().catch(() => ({}));
                if (res.ok) {
                    window.location.href = '/admin/rooms';
                    return;
                }
                if (res.status === 422 && data.errors) {
                    for (const [field, msgs] of Object.entries(data.errors)) {
                        this.errors[field] = Array.isArray(msgs) ? msgs[0] : msgs;
                    }
                    return;
                }
                this.error = data.message || 'Could not update room.';
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
                const res = await fetch('/api/rooms/' + this.roomId, { method: 'DELETE', credentials: 'include', headers: this.getHeaders() });
                if (res.status === 409) {
                    const data = await res.json().catch(() => ({}));
                    alert(data.message || 'Cannot delete: this room has exam sessions linked to it.');
                    return;
                }
                if (!res.ok) throw new Error('Delete failed.');
                this.showDeleteModal = false;
                document.getElementById('delete-modal')?.close?.();
                window.location.href = '/admin/rooms';
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
