<?php

namespace Codenzia\FilamentMedia\Forms;

use Closure;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Services\UploadService;
use Filament\Forms\Components\Field;

/**
 * Custom Filament form field that opens a media picker modal for selecting
 * files from the media library with support for type filtering and multiple selection.
 *
 * Use ->relationship() to auto-hydrate and auto-save via the model's HasMediaFiles morph
 * relationship. Supports both single and multiple file selection:
 *
 *     MediaPickerField::make('avatar')->relationship()->imageOnly()
 *     MediaPickerField::make('images')->relationship()->multiple()->maxFiles(10)
 *     MediaPickerField::make('docs')->relationship('documents')  // uses documents() scope
 */
class MediaPickerField extends Field
{
    protected string $view = 'filament-media::forms.media-picker-field';

    protected bool $isMultiple = false;

    protected array $acceptedFileTypes = [];

    protected int $maxFiles = 0;

    protected ?string $directory = null;

    protected ?string $collection = null;

    protected ?string $relationshipScope = null;

    protected ?bool $directUpload = null;

    /** @var string|null Display style. Null = use config default. */
    protected ?string $displayStyle = null;

    /** @var string[]|null Override: ONLY these extensions allowed (ignores global config) */
    protected ?array $allowedFileTypesOnly = null;

    /** @var string[]|null Merge: additional extensions added to global config */
    protected ?array $includedFileTypes = null;

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

    /**
     * Enable or disable direct file upload alongside the media browser button.
     * When enabled, the "Browse Media" button becomes a dropdown with "Browse Media"
     * and "Upload File" options. Pass null to use the global config default.
     */
    public function directUpload(bool $allow = true): static
    {
        $this->directUpload = $allow;

        return $this;
    }

    public function isDirectUploadEnabled(): bool
    {
        return $this->directUpload ?? config('media.picker.direct_upload', false);
    }

    public function getUploadUrl(): string
    {
        return route('media.files.upload');
    }

    public function getMaxUploadSize(): int
    {
        return app(UploadService::class)->getMaxSize();
    }

    /** @var string[] Valid display style values */
    protected const DISPLAY_STYLES = ['compact', 'dropdown', 'thumbnail', 'integratedLinks', 'integratedDropdown'];

    /**
     * Set the visual display style for the picker field.
     *
     * - 'compact':            Text links for browse/upload with chip-style file list (default)
     * - 'dropdown':           Button with dropdown menu for browse/upload options
     * - 'thumbnail':          Visual preview card, click to browse, drag & drop
     * - 'integratedLinks':    Thumbnail preview + text links below, drag & drop
     * - 'integratedDropdown': Thumbnail preview + dropdown button below, drag & drop
     */
    public function displayStyle(string $style): static
    {
        if (! in_array($style, self::DISPLAY_STYLES)) {
            throw new \InvalidArgumentException(
                "Invalid display style '{$style}'. Use: " . implode(', ', array_map(fn ($s) => "'{$s}'", self::DISPLAY_STYLES)) . '.'
            );
        }

        $this->displayStyle = $style;

        return $this;
    }

    public function getDisplayStyle(): string
    {
        if ($this->displayStyle !== null) {
            return $this->displayStyle;
        }

        $configDefault = config('media.picker.display_style', 'compact');

        return in_array($configDefault, self::DISPLAY_STYLES)
            ? $configDefault
            : 'compact';
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

    /**
     * Enable automatic morph relationship hydration and saving.
     *
     * When called, the field will:
     * - On load: populate from the model's morph relationship (files(), images(), etc.)
     * - On save: sync selected media file IDs via HasMediaFiles::syncMediaByIds()
     *
     * The model must use the HasMediaFiles trait.
     *
     * @param  string|null  $scope  The relationship method to use (e.g. 'images', 'videos', 'documents').
     *                              Defaults to 'images' for imageOnly, otherwise 'files'.
     */
    public function relationship(?string $scope = null): static
    {
        $this->relationshipScope = $scope ?? '';

        $this->dehydrated(false);

        $this->afterStateHydrated(function (MediaPickerField $component, $record): void {
            if (! $record) {
                return;
            }

            $scopeMethod = $this->resolveRelationshipScope();
            $query = $record->{$scopeMethod}();

            if ($this->isMultiple) {
                $component->state($query->pluck('id')->toArray());
            } else {
                $component->state($query->first()?->getKey());
            }
        });

        $this->saveRelationshipsUsing(function (MediaPickerField $component, $record, $state): void {
            if (! $record) {
                return;
            }

            $ids = $this->isMultiple
                ? (is_array($state) ? array_filter($state) : [])
                : ($state ? [$state] : []);

            $record->syncMediaByIds($ids);
        });

        return $this;
    }

    /**
     * Determine which relationship scope to use for hydration.
     */
    protected function resolveRelationshipScope(): string
    {
        if ($this->relationshipScope) {
            return $this->relationshipScope;
        }

        // Auto-detect from accepted file types
        if (in_array('image/*', $this->acceptedFileTypes)) {
            return 'images';
        }

        if (in_array('video/*', $this->acceptedFileTypes)) {
            return 'videos';
        }

        return 'files';
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

    /**
     * Restrict uploads to ONLY these file extensions (ignores global config).
     *
     * @param  string[]  $extensions  e.g. ['pdf', 'docx']
     */
    public function allowedFileTypesOnly(array $extensions): static
    {
        $this->allowedFileTypesOnly = array_map('strtolower', $extensions);

        return $this;
    }

    /**
     * Add extra file extensions to the global allowed list for this field.
     *
     * @param  string[]  $extensions  e.g. ['ico', 'svg']
     */
    public function includeFileTypes(array $extensions): static
    {
        $this->includedFileTypes = array_map('strtolower', $extensions);

        return $this;
    }

    /**
     * Compute the effective allowed extensions for this field.
     * Returns a comma-separated string, or null to use global default.
     */
    public function getEffectiveExtensions(): ?string
    {
        if ($this->allowedFileTypesOnly !== null) {
            return implode(',', $this->allowedFileTypesOnly);
        }

        if ($this->includedFileTypes !== null) {
            $global = app(UploadService::class)->getAllowedMimeTypes();
            $merged = array_unique(array_merge($global, $this->includedFileTypes));

            return implode(',', $merged);
        }

        return null;
    }

    /**
     * Generate an HMAC signature for the effective extensions to prevent client-side tampering.
     */
    public function getEffectiveExtensionsSignature(): ?string
    {
        $extensions = $this->getEffectiveExtensions();

        if ($extensions === null) {
            return null;
        }

        return hash_hmac('sha256', $extensions, config('app.key'));
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
