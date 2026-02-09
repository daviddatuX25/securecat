@extends('layouts.app')

@section('content')
<div class="max-w-xl" x-data="roomForm()">
    <div class="mb-6">
        <a href="/admin/rooms" class="link link-hover text-sm">← Rooms</a>
        <h1 class="text-2xl font-bold mt-2">New room</h1>
    </div>

    <form @submit.prevent="submit()" class="card bg-base-100 shadow">
        <div class="card-body space-y-4">
            <div class="form-control">
                <label class="label" for="name"><span class="label-text">Name</span></label>
                <input id="name" type="text" x-model="form.name" class="input input-bordered w-full" placeholder="e.g. Room 101" maxlength="100" required>
                <p class="text-error text-sm mt-1" x-show="errors.name" x-text="errors.name"></p>
            </div>
            <div class="form-control">
                <label class="label" for="capacity"><span class="label-text">Capacity</span></label>
                <input id="capacity" type="number" x-model.number="form.capacity" class="input input-bordered w-full" min="1" placeholder="40" required>
                <p class="text-error text-sm mt-1" x-show="errors.capacity" x-text="errors.capacity"></p>
            </div>
            <div class="form-control">
                <label class="label" for="location_notes"><span class="label-text">Location notes (optional)</span></label>
                <textarea id="location_notes" x-model="form.location_notes" class="textarea textarea-bordered w-full" rows="2" maxlength="1000" placeholder="Building A, 1st floor"></textarea>
                <p class="text-error text-sm mt-1" x-show="errors.location_notes" x-text="errors.location_notes"></p>
            </div>
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
