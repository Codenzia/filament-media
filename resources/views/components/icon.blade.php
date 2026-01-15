@props([
    'name' => '',
    'size' => null,
])

<i {{ $attributes->class([$name, $size ? "icon-{$size}" : null]) }}></i>
