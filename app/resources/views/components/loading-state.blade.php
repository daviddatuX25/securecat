{{--
    Consistent loading spinner/placeholder.
    Use for async content (e.g. fetch) or Livewire (add wire:loading elsewhere and use this inside).
--}}
@props([
    'size' => 'md', // sm | md | lg
    'variant' => 'spinner', // spinner | dots | ring
    'text' => 'Loading...',
])

@php
    $sizeClass = match($size) {
        'sm' => 'loading-sm',
        'lg' => 'loading-lg',
        default => 'loading-md',
    };
    $variantClass = match($variant) {
        'dots' => 'loading-dots',
        'ring' => 'loading-ring',
        default => 'loading-spinner',
    };
@endphp

<div {{ $attributes->merge(['class' => 'flex items-center gap-2']) }}>
    <span class="loading {{ $variantClass }} {{ $sizeClass }}" aria-hidden="true"></span>
    @if($text)
        <span class="text-base-content/80">{{ $text }}</span>
    @endif
</div>
