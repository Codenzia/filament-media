@props([
    'id' => null,
    'placement' => 'start', // start|end|top|bottom
    'title' => null,
])

<div
    {{ $attributes->class(["offcanvas offcanvas-{$placement}"]) }}
    tabindex="-1"
    @if($id) id="{{ $id }}" @endif
    aria-labelledby="{{ $id ? "{$id}-label" : null }}"
>
    {{ $slot }}
</div>
