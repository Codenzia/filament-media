@props([
    'label' => null,
    'icon' => null,
    'active' => false,
])

<li>
    <a {{ $attributes->class(['dropdown-item', $active ? 'active' : null]) }}>
        @if($icon)
            <x-core::icon :name="$icon" class="me-1" />
        @endif
        {{ $label ?? $slot }}
    </a>
</li>
