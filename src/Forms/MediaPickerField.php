<?php

namespace Codenzia\FilamentMedia\Forms;

use Closure;
use Filament\Forms\Components\Field;

class MediaPickerField extends Field
{
    protected string $view = 'filament-media::forms.media-picker-field';

    protected bool $isMultiple = false;

    protected array $acceptedFileTypes = [];

    protected int $maxFiles = 0;

    protected ?string $directory = null;

    protected ?string $collection = null;

    public function multiple(bool $multiple = true): static
    {
        $this->isMultiple = $multiple;

        return $this;
    }

    public function acceptedFileTypes(array $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    public function maxFiles(int $max): static
    {
        $this->maxFiles = $max;

        return $this;
    }

    public function directory(string $directory): static
    {
        $this->directory = $directory;

        return $this;
    }

    public function collection(string $collection): static
    {
        $this->collection = $collection;

        return $this;
    }

    public function imageOnly(): static
    {
        $this->acceptedFileTypes = ['image/*'];

        return $this;
    }

    public function videoOnly(): static
    {
        $this->acceptedFileTypes = ['video/*'];

        return $this;
    }

    public function documentOnly(): static
    {
        $this->acceptedFileTypes = [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'text/plain',
            'text/csv',
        ];

        return $this;
    }

    public function isMultiple(): bool
    {
        return $this->isMultiple;
    }

    public function getAcceptedFileTypes(): array
    {
        return $this->acceptedFileTypes;
    }

    public function getMaxFiles(): int
    {
        return $this->maxFiles;
    }

    public function getDirectory(): ?string
    {
        return $this->directory;
    }

    public function getCollection(): ?string
    {
        return $this->collection;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->afterStateHydrated(function (MediaPickerField $component, $state) {
            if (is_string($state) && str_contains($state, ',')) {
                $component->state(explode(',', $state));
            }
        });

        $this->dehydrateStateUsing(function ($state) {
            if (is_array($state) && ! $this->isMultiple) {
                return $state[0] ?? null;
            }

            return $state;
        });
    }
}
