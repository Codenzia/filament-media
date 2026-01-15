@props([
    'type' => 'button',
    'color' => 'secondary',
    'icon' => null,
    'iconOnly' => false,
])

@php
    $isLink = $attributes->has('href');
    $classes = [
        'btn',
        "btn-{$color}",
        $iconOnly ? 'btn-icon' : null,
    ];
@endphp

<{{ $isLink ? 'a' : 'button' }}
    @unless($isLink)
        type="{{ $type }}"
    @endunless
    {{ $attributes->class($classes) }}
    @if($isLink)
        role="button"
    @endif
>
    @if($icon)
        <x-core::icon :name="$icon" class="{{ $iconOnly ? '' : 'me-1' }}" />
    @endif

    @unless($iconOnly)
        {{ $slot }}
    @endunless
</{{ $isLink ? 'a' : 'button' }}>
