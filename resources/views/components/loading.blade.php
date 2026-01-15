@props([
    'size' => 'md', // sm|md|lg
    'text' => null,
])

@php
    $sizes = [
        'sm' => 'spinner-border-sm',
        'md' => null,
        'lg' => 'spinner-border-lg',
    ];
@endphp

<div class="d-inline-flex align-items-center gap-2">
    <div class="spinner-border text-primary {{ $sizes[$size] ?? null }}" role="status">
        <span class="visually-hidden">Loading...</span>
    </div>
    @if($text)
        <span>{{ $text }}</span>
    @endif
</div>
