@props([
    'active' => false,
])

<div {{ $attributes->class(['list-group-item', $active ? 'active' : null]) }}>
    {{ $slot }}
</div>
