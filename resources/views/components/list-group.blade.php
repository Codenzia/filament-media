@props([
    'flush' => false,
])

<div {{ $attributes->class(['list-group', $flush ? 'list-group-flush' : null]) }}>
    {{ $slot }}
</div>
