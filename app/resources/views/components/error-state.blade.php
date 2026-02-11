{{--
    Consistent error display (e.g. failed fetch, validation summary).
    Message via prop or default slot. Optional retry button.
--}}
@props([
    'message' => null,
    'variant' => 'warning', // info | success | warning | error
    'showRetry' => false,
    'retryLabel' => 'Retry',
])

@php
    $alertClass = 'alert-' . $variant;
@endphp

<div {{ $attributes->merge(['class' => "alert {$alertClass} shadow-lg"]) }}>
    <span>
        @if(isset($slot) && trim($slot) !== '')
            {{ $slot }}
        @else
            {{ $message ?? 'Something went wrong.' }}
        @endif
    </span>
    @if($showRetry)
        @if(isset($retryAction))
            <div>{{ $retryAction }}</div>
        @else
            <button type="button" class="btn btn-sm" onclick="window.location.reload()">{{ $retryLabel }}</button>
        @endif
    @endif
</div>
