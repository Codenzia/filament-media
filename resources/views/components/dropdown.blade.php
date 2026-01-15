@props([
    'label' => null,
    'icon' => null,
    'color' => 'secondary',
    'wrapperClass' => null,
])

<div {{ $attributes->class(['dropdown', $wrapperClass]) }}>
    <button
        class="btn btn-{{ $color }} dropdown-toggle"
        type="button"
        data-bs-toggle="dropdown"
        aria-expanded="false"
    >
        @if($icon)
            <x-core::icon :name="$icon" class="me-1" />
        @endif
        {{ $label }}
    </button>

    <ul class="dropdown-menu">
        {{ $slot }}
    </ul>
</div>
