{{--
    Consistent empty state when there is no data to show.
    Pass title/description as props or use the default slot for custom content.
--}}
@props([
    'title' => null,
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'card bg-base-100 shadow']) }}>
    <div class="card-body">
        @if(isset($slot) && trim($slot) !== '')
            {{ $slot }}
        @else
            @if($title)
                <p class="font-medium text-base-content">{{ $title }}</p>
            @endif
            @if($description)
                <p class="text-sm text-base-content/70">{{ $description }}</p>
            @endif
            @if(!$title && !$description)
                <p class="text-base-content/70">No items to show.</p>
            @endif
        @endif
    </div>
</div>
