@props([
    'id' => null,
    'title' => null,
    'size' => null, // sm, lg, xl, fullscreen, etc.
    'centered' => false,
    'scrollable' => false,
    'hasForm' => false,
    'formAction' => null,
    'formMethod' => 'POST',
    'formAttrs' => [],
])

@php
    $dialogClasses = [
        'modal-dialog',
        $centered ? 'modal-dialog-centered' : null,
        $scrollable ? 'modal-dialog-scrollable' : null,
        $size ? 'modal-' . $size : null,
    ];

    $method = strtoupper($formMethod ?? 'POST');
    $spoofMethod = ! in_array($method, ['GET', 'POST'], true);
    $submitMethod = $spoofMethod ? 'POST' : $method;

    $formAttributes = '';
    foreach ($formAttrs ?? [] as $attrKey => $attrValue) {
        $formAttributes .= ' ' . $attrKey . '="' . e($attrValue) . '"';
    }
@endphp

<div
    {{ $attributes->class('modal fade') }}
    @if($id) id="{{ $id }}" @endif
    tabindex="-1"
    aria-hidden="true"
>
    <div class="{{ implode(' ', array_filter($dialogClasses)) }}">
        <div class="modal-content">
            @if($hasForm)
                <form action="{{ $formAction }}" method="{{ strtolower($submitMethod) }}"{!! $formAttributes !!}>
                    @csrf
                    @if($spoofMethod)
                        @method($method)
                    @endif
            @endif

            <div class="modal-header">
                @if($title)
                    <h5 class="modal-title">{{ $title }}</h5>
                @endif
                <x-core::modal.close-button />
            </div>

            <div class="modal-body">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="modal-footer">
                    {{ $footer }}
                </div>
            @endisset

            @if($hasForm)
                </form>
            @endif
        </div>
    </div>
</div>
