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

    /** @var string|null Preview container width (CSS value, e.g. '16rem', '256px'). Null = use config default. */
    protected ?string $previewWidth = null;

    /** @var string|null Preview container height (CSS value, e.g. '8rem', '128px'). Null = aspect-square. */
    protected ?string $previewHeight = null;

    /** @var string|null Chip size preset: 'xs', 'sm', 'md', 'lg', 'xl', '2xl'. Null = use config default. */
    protected ?string $chipSize = null;

    /** @var string|null Lightbox max width (CSS value, e.g. '800px', '50vw'). Null = use config default. */
    protected ?string $lightboxMaxWidth = null;

    /** @var string|null Lightbox max height (CSS value, e.g. '600px', '80vh'). Null = use config default. */
    protected ?string $lightboxMaxHeight = null;

    /** @var int|null Lightbox backdrop opacity (0-100). Null = use config default. */
    protected ?int $lightboxOpacity = null;

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

    /** @var string[] Valid chip size presets */
    protected const CHIP_SIZES = ['xs', 'sm', 'md', 'lg', 'xl', '2xl'];

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

    /**
     * Set the preview container width (CSS value, e.g. '16rem', '256px').
     * When only width is set, the container remains aspect-square.
     */
    public function previewWidth(string $value): static
    {
        $this->previewWidth = $value;

        return $this;
    }

    /**
     * Set the preview container height (CSS value, e.g. '8rem', '128px').
     * Setting a height removes the default aspect-square constraint.
     */
    public function previewHeight(string $value): static
    {
        $this->previewHeight = $value;

        return $this;
    }

    /**
     * Get the inline style string for the preview container dimensions.
     * Returns CSS properties like "width: 12rem" or "width: 16rem; height: 8rem".
     */
    public function getPreviewSizeStyle(): string
    {
        $width = $this->previewWidth ?? config('media.picker.preview_width', '12rem');
        $height = $this->previewHeight ?? config('media.picker.preview_height');

        $styles = [];

        if ($width) {
            $styles[] = "width: {$width}";
        }

        if ($height) {
            $styles[] = "height: {$height}";
        }

        return implode('; ', $styles);
    }

    /**
     * Whether the preview container should use aspect-square (true when no explicit height is set).
     */
    public function shouldUseAspectSquare(): bool
    {
        $height = $this->previewHeight ?? config('media.picker.preview_height');

        return $height === null;
    }

    /**
     * Get inline style for preview width only (used for matching button width in integratedDropdown).
     */
    public function getPreviewWidthStyle(): string
    {
        $width = $this->previewWidth ?? config('media.picker.preview_width', '12rem');

        return $width ? "width: {$width}" : '';
    }

    /**
     * Set the chip size preset for compact and dropdown display styles.
     *
     * Controls thumbnail size, text size, and spacing within the file chips.
     * - 'xs':  Tiny        — 20px thumbnails, 12px text
     * - 'sm':  Small       — 32px thumbnails, 14px text (default)
     * - 'md':  Medium      — 48px thumbnails, 14px text
     * - 'lg':  Large       — 64px thumbnails, 16px text
     * - 'xl':  Extra large — 80px thumbnails, 18px text
     * - '2xl': Huge        — 96px thumbnails, 20px text
     */
    public function chipSize(string $size): static
    {
        if (! in_array($size, self::CHIP_SIZES)) {
            throw new \InvalidArgumentException(
                "Invalid chip size '{$size}'. Use: " . implode(', ', array_map(fn ($s) => "'{$s}'", self::CHIP_SIZES)) . '.'
            );
        }

        $this->chipSize = $size;

        return $this;
    }

    public function getChipSize(): string
    {
        if ($this->chipSize !== null) {
            return $this->chipSize;
        }

        $configDefault = config('media.picker.chip_size', 'sm');

        return in_array($configDefault, self::CHIP_SIZES)
            ? $configDefault
            : 'sm';
    }

    /**
     * Get the CSS dimensions for chip elements based on the chip size preset.
     *
     * @return array{thumb: string, icon: string, fontSize: string, maxName: string}
     */
    public function getChipDimensions(): array
    {
        return match ($this->getChipSize()) {
            'xs' => ['thumb' => '1.25rem', 'icon' => '0.625rem', 'fontSize' => '0.75rem', 'maxName' => '150px'],
            'md' => ['thumb' => '3rem', 'icon' => '1.25rem', 'fontSize' => '0.875rem', 'maxName' => '200px'],
            'lg' => ['thumb' => '4rem', 'icon' => '1.5rem', 'fontSize' => '1rem', 'maxName' => '250px'],
            'xl' => ['thumb' => '5rem', 'icon' => '2rem', 'fontSize' => '1.125rem', 'maxName' => '300px'],
            '2xl' => ['thumb' => '6rem', 'icon' => '2.5rem', 'fontSize' => '1.25rem', 'maxName' => '350px'],
            default => ['thumb' => '2rem', 'icon' => '1rem', 'fontSize' => '0.875rem', 'maxName' => '200px'],
        };
    }

    /**
     * Set the lightbox max width (CSS value, e.g. '800px', '50vw').
     * When null, the image fills the available viewport width.
     */
    public function lightboxMaxWidth(string $value): static
    {
        $this->lightboxMaxWidth = $value;

        return $this;
    }

    /**
     * Set the lightbox max height (CSS value, e.g. '600px', '80vh').
     * When null, the image fills the available viewport height.
     */
    public function lightboxMaxHeight(string $value): static
    {
        $this->lightboxMaxHeight = $value;

        return $this;
    }

    /**
     * Get the inline style string for the lightbox image constraints.
     * Returns empty string when using defaults (full viewport).
     */
    public function getLightboxStyle(): string
    {
        $maxWidth = $this->lightboxMaxWidth ?? config('media.picker.lightbox_max_width');
        $maxHeight = $this->lightboxMaxHeight ?? config('media.picker.lightbox_max_height');

        $styles = [];

        if ($maxWidth) {
            $styles[] = "max-width: {$maxWidth}";
        }

        if ($maxHeight) {
            $styles[] = "max-height: {$maxHeight}";
        }

        return implode('; ', $styles);
    }

    /**
     * Set the lightbox backdrop opacity (0-100).
     * 0 = fully transparent, 100 = fully opaque. Default: 80.
     */
    public function lightboxOpacity(int $percent): static
    {
        $this->lightboxOpacity = max(0, min(100, $percent));

        return $this;
    }

    /**
     * Get the lightbox backdrop opacity as a decimal (0.0 - 1.0).
     */
    public function getLightboxOpacity(): float
    {
        $percent = $this->lightboxOpacity ?? config('media.picker.lightbox_opacity', 80);

        return max(0, min(100, (int) $percent)) / 100;
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
