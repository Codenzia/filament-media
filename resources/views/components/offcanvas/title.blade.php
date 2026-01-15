@props(['for' => null])

<h5 {{ $attributes->class('offcanvas-title') }} @if($for) id="{{ $for }}-label" @endif>
    {{ $slot }}
</h5>
