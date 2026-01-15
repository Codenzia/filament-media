@props([
    'name' => null,
    'value' => '1',
    'checked' => false,
    'label' => null,
])

<div class="form-check">
    <input
        type="checkbox"
        name="{{ $name }}"
        value="{{ $value }}"
        {{ $checked ? 'checked' : '' }}
        {{ $attributes->class('form-check-input') }}
    >

    @if($label ?? $slot)
        <label class="form-check-label" for="{{ $attributes->get('id') }}">
            {{ $label ?? $slot }}
        </label>
    @endif
</div>
