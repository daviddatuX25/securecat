{{--
    Consistent empty state when there is no data to show.
    Pass title/description as props or use the default slot for custom content.
    Optional: icon, action (CTA button/link)
--}}
@props([
    'title' => null,
    'description' => null,
    'icon' => null,
    'action' => null,
])

<div {{ $attributes->merge(['class' => 'card bg-base-100 shadow']) }}>
    <div class="card-body text-center py-8">
        @if(isset($slot) && trim($slot) !== '')
            {{ $slot }}
        @else
            @if($icon)
                <div class="mb-3">{!! $icon !!}</div>
            @endif
            @if($title)
                <p class="font-medium text-base-content mb-1">{{ $title }}</p>
            @endif
            @if($description)
                <p class="text-sm text-base-content/60 mb-4">{{ $description }}</p>
            @endif
            @if(!$title && !$description)
                <p class="text-base-content/70 mb-4">No items to show.</p>
            @endif
            @if($action)
                <div class="mt-4">{!! $action !!}</div>
            @endif
        @endif
    </div>
</div>
