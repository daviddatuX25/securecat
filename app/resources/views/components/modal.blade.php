{{--
    Reusable modal wrapper (DaisyUI modal + native <dialog>).
    Open via: document.getElementById($id).showModal()
    Or use Alpine: x-ref="modal" then $refs.modal.showModal() / close()
--}}
@props([
    'id' => 'modal',
    'title' => null,
])

<dialog id="{{ $id }}" class="modal" {{ $attributes->except(['id', 'title']) }}>
    <div class="modal-box">
        @if($title)
            <h3 class="text-lg font-bold">{{ $title }}</h3>
        @endif
        <div class="py-4">
            {{ $slot }}
        </div>
        @if(isset($actions))
            <div class="modal-action">
                {{ $actions }}
            </div>
        @endif
    </div>
    <form method="dialog" class="modal-backdrop">
        <button type="submit" aria-label="Close">close</button>
    </form>
</dialog>
