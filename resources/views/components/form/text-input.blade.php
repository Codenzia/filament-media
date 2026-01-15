@props([
    'name' => null,
    'type' => 'text',
    'label' => null,
    'value' => null,
    'help' => null,
    'error' => null,
])

<div class="mb-3">
    @if($label)
        <label class="form-label" for="{{ $attributes->get('id') ?? $name }}">{{ $label }}</label>
    @endif

    <input
        type="{{ $type }}"
        name="{{ $name }}"
        value="{{ old($name, $value) }}"
        {{ $attributes->class(['form-control', $error ? 'is-invalid' : null]) }}
    >

    @if($help)
        <div class="form-text">{{ $help }}</div>
    @endif

    @if($error)
        <div class="invalid-feedback">{{ $error }}</div>
    @endif
</div>
