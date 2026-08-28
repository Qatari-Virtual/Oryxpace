@props([
    'message' => null,
    'type' => 'success',
    'timeout' => 5000,
])

@php
    $styles = [
        'success' => ['alert-success', '✓'],
        'error' => ['alert-error', '!'],
        'warning' => ['alert-warning', '!'],
        'info' => ['alert-info', 'i'],
    ];
    [$alertClass, $icon] = $styles[$type] ?? $styles['info'];
@endphp

@if($message)
    <div class="toast-stack" aria-live="polite" aria-atomic="true">
        <div
            {{ $attributes->merge(['class' => "toast-item alert {$alertClass}"]) }}
            data-toast
            data-toast-timeout="{{ $timeout }}"
            role="status"
        >
            <span class="toast-icon" aria-hidden="true">{{ $icon }}</span>
            <span class="flex-1">{{ $message }}</span>
            <button type="button" class="toast-close" data-toast-close aria-label="Dismiss notification">×</button>
        </div>
    </div>
@endif
