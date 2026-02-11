@extends('layouts.app')

@section('content')
<div class="max-w-xl" x-data="roomForm()">
    <div class="mb-6">
        <div class="breadcrumbs text-sm mb-2">
            <ul>
                <li><a href="/admin/dashboard">Dashboard</a></li>
                <li><a href="/admin/rooms">Rooms</a></li>
                <li class="text-base-content/60">New</li>
            </ul>
        </div>
        <h1 class="text-2xl font-bold">New room</h1>
        <p class="text-base-content/70 text-sm mt-1">Add a new exam room with capacity and location details.</p>
    </div>

    <form @submit.prevent="submit()" class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <fieldset class="fieldset">
                    <label class="label" for="name">Name <span class="text-error">*</span></label>
                    <input id="name" type="text" x-model="form.name" class="input w-full" placeholder="e.g. Room 101" maxlength="100" required>
                    <p class="text-sm text-base-content/60 mt-1">Room identifier (e.g., "Room 101" or "Lab 3").</p>
                    <p class="text-error text-sm mt-1" x-show="errors.name" x-text="errors.name"></p>
                </fieldset>
                <fieldset class="fieldset">
                    <label class="label" for="capacity">Capacity <span class="text-error">*</span></label>
                    <input id="capacity" type="number" x-model.number="form.capacity" class="input w-full" min="1" placeholder="40" required>
                    <p class="text-sm text-base-content/60 mt-1">Maximum number of examinees this room can accommodate.</p>
                    <p class="text-error text-sm mt-1" x-show="errors.capacity" x-text="errors.capacity"></p>
                </fieldset>
            </div>
            <fieldset class="fieldset">
                <label class="label" for="location_notes">Location notes</label>
                <textarea id="location_notes" x-model="form.location_notes" class="textarea w-full" rows="2" maxlength="1000" placeholder="Building A, 1st floor"></textarea>
                <p class="text-sm text-base-content/60 mt-1">Optional. Additional location details to help examinees find the room.</p>
                <p class="text-error text-sm mt-1" x-show="errors.location_notes" x-text="errors.location_notes"></p>
            </fieldset>
            <div class="flex gap-2 pt-2">
                <button type="submit" class="btn btn-primary" :disabled="saving">
                    <span x-show="!saving">Create</span>
                    <span class="loading loading-spinner loading-sm" x-show="saving"></span>
                </button>
                <a href="/admin/rooms" class="btn btn-ghost">Cancel</a>
            </div>
            <p class="text-error" x-show="error" x-text="error"></p>
        </div>
    </form>
</div>

<script>
function roomForm() {
    return {
        form: { name: '', capacity: null, location_notes: '' },
        errors: {},
        error: null,
        saving: false,
        getHeaders() {
            const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
            return { 'X-CSRF-TOKEN': token, 'Accept': 'application/json', 'Content-Type': 'application/json' };
        },
        async submit() {
            this.errors = {}; this.error = null; this.saving = true;
            const payload = {
                name: this.form.name,
                capacity: Number(this.form.capacity) || 1,
                location_notes: this.form.location_notes || null,
            };
            try {
                const res = await fetch('/api/rooms', {
                    method: 'POST',
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
                this.error = data.message || 'Could not create room.';
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
