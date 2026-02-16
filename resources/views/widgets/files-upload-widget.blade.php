<x-filament::widget>
    <x-filament::card>
        <form wire:submit="create">
            {{ $this->form }}

            <div
                class="mt-4 flex {{ match ($this->submitAlignment) {'center' => 'justify-center','end' => 'justify-end',default => 'justify-start'} }}">
                <x-filament::button type="submit" :color="$this->submitColor"
                    :disabled="! $this->hasUploadedFiles">
                    {{ $this->submitLabel ?: __('Save Changes') }}
                </x-filament::button>
            </div>
        </form>
    </x-filament::card>
</x-filament::widget>
