@props([
    'icon',
    'label',
    'badgeColor' => 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300',
    'placement' => 'bottom-start',
])

<div class="fm-toolbar-dropdown">
    <x-filament::dropdown :placement="$placement">
        <x-slot name="trigger">
            <button type="button" class="fm-dropdown-trigger">
                <x-filament::icon :icon="$icon" class="fm-dropdown-icon" />
                <span class="fm-dropdown-badge {{ $badgeColor }}">{{ $label }}</span>
                <x-filament::icon icon="heroicon-m-chevron-down" class="fm-dropdown-chevron" />
            </button>
        </x-slot>

        <x-filament::dropdown.list>
            {{ $slot }}
        </x-filament::dropdown.list>
    </x-filament::dropdown>
</div>
