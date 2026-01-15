@props([
    'hover' => true,
    'striped' => false,
])

<table {{ $attributes->class([
    'table',
    $hover ? 'table-hover' : null,
    $striped ? 'table-striped' : null,
]) }}>
    {{ $slot }}
</table>
