@props([
    'name' => null,
    'label' => null,
    'choices' => [],
    'selected' => null,
])

<div class="mb-3">
    @if($label)
        <label class="form-label">{{ $label }}</label>
    @endif

    <div class="d-flex flex-wrap gap-2">
        @foreach($choices as $value)
            <label class="d-inline-flex align-items-center gap-2">
                <input
                    type="radio"
                    name="{{ $name }}"
                    value="{{ $value }}"
                    class="form-check-input"
                    {{ (string) $selected === (string) $value ? 'checked' : '' }}
                >
                <span class="d-inline-block rounded" style="width: 24px; height: 24px; background: {{ $value }};"></span>
                <span class="small text-muted">{{ $value }}</span>
            </label>
        @endforeach
    </div>
</div>
