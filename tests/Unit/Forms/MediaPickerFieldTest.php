<?php

use Codenzia\FilamentMedia\Forms\MediaPickerField;

describe('MediaPickerField', function () {
    it('can be created', function () {
        $field = MediaPickerField::make('media');

        expect($field)->toBeInstanceOf(MediaPickerField::class);
    });

    it('defaults to single selection', function () {
        $field = MediaPickerField::make('media');

        expect($field->isMultiple())->toBeFalse();
    });

    it('defaults to no file type restrictions', function () {
        $field = MediaPickerField::make('media');

        expect($field->getAcceptedFileTypes())->toBe([]);
    });

    it('defaults to no max files limit', function () {
        $field = MediaPickerField::make('media');

        expect($field->getMaxFiles())->toBe(0);
    });
});

describe('MediaPickerField multiple()', function () {
    it('sets isMultiple to true', function () {
        $field = MediaPickerField::make('media')->multiple();

        expect($field->isMultiple())->toBeTrue();
    });

    it('can be toggled back to false', function () {
        $field = MediaPickerField::make('media')->multiple()->multiple(false);

        expect($field->isMultiple())->toBeFalse();
    });
});

describe('MediaPickerField file type filters', function () {
    it('imageOnly sets accepted file types to image wildcard', function () {
        $field = MediaPickerField::make('media')->imageOnly();

        expect($field->getAcceptedFileTypes())->toBe(['image/*']);
    });

    it('videoOnly sets accepted file types to video wildcard', function () {
        $field = MediaPickerField::make('media')->videoOnly();

        expect($field->getAcceptedFileTypes())->toBe(['video/*']);
    });

    it('documentOnly sets accepted file types to document mime types', function () {
        $field = MediaPickerField::make('media')->documentOnly();

        $types = $field->getAcceptedFileTypes();

        expect($types)->toBeArray()
            ->and($types)->toContain('application/pdf')
            ->and($types)->toContain('application/msword')
            ->and($types)->toContain('application/vnd.openxmlformats-officedocument.wordprocessingml.document')
            ->and($types)->toContain('application/vnd.ms-excel')
            ->and($types)->toContain('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->and($types)->toContain('text/plain')
            ->and($types)->toContain('text/csv');
    });

    it('acceptedFileTypes sets custom types', function () {
        $customTypes = ['image/svg+xml', 'image/webp'];
        $field = MediaPickerField::make('media')->acceptedFileTypes($customTypes);

        expect($field->getAcceptedFileTypes())->toBe($customTypes);
    });
});

describe('MediaPickerField maxFiles()', function () {
    it('sets max files limit', function () {
        $field = MediaPickerField::make('media')->maxFiles(5);

        expect($field->getMaxFiles())->toBe(5);
    });

    it('can set max files to 1', function () {
        $field = MediaPickerField::make('media')->maxFiles(1);

        expect($field->getMaxFiles())->toBe(1);
    });
});

describe('MediaPickerField directory and collection', function () {
    it('sets directory', function () {
        $field = MediaPickerField::make('media')->directory('photos');

        expect($field->getDirectory())->toBe('photos');
    });

    it('sets collection', function () {
        $field = MediaPickerField::make('media')->collection('hero-banners');

        expect($field->getCollection())->toBe('hero-banners');
    });

    it('defaults directory to null', function () {
        $field = MediaPickerField::make('media');

        expect($field->getDirectory())->toBeNull();
    });

    it('defaults collection to null', function () {
        $field = MediaPickerField::make('media');

        expect($field->getCollection())->toBeNull();
    });
});

describe('MediaPickerField per-field file type overrides', function () {
    it('allowedFileTypesOnly returns only specified extensions', function () {
        $field = MediaPickerField::make('media')
            ->allowedFileTypesOnly(['pdf', 'docx']);

        expect($field->getEffectiveExtensions())->toBe('pdf,docx');
    });

    it('allowedFileTypesOnly normalizes extensions to lowercase', function () {
        $field = MediaPickerField::make('media')
            ->allowedFileTypesOnly(['PDF', 'DOCX', 'Ico']);

        expect($field->getEffectiveExtensions())->toBe('pdf,docx,ico');
    });

    it('includeFileTypes merges with global config', function () {
        $field = MediaPickerField::make('media')
            ->includeFileTypes(['ico', 'svg']);

        $effective = $field->getEffectiveExtensions();

        // Should contain the global types plus 'ico' and 'svg'
        expect($effective)->toContain('ico')
            ->and($effective)->toContain('svg')
            ->and($effective)->toContain('jpg');
    });

    it('includeFileTypes does not duplicate existing extensions', function () {
        $field = MediaPickerField::make('media')
            ->includeFileTypes(['jpg', 'ico']);

        $extensions = explode(',', $field->getEffectiveExtensions());

        // 'jpg' should appear only once
        $jpgCount = count(array_filter($extensions, fn ($ext) => $ext === 'jpg'));
        expect($jpgCount)->toBe(1);
    });

    it('returns null for effective extensions when no override is set', function () {
        $field = MediaPickerField::make('media');

        expect($field->getEffectiveExtensions())->toBeNull();
    });

    it('generates HMAC signature for effective extensions', function () {
        $field = MediaPickerField::make('media')
            ->allowedFileTypesOnly(['pdf', 'docx']);

        $sig = $field->getEffectiveExtensionsSignature();
        $expected = hash_hmac('sha256', 'pdf,docx', config('app.key'));

        expect($sig)->toBe($expected);
    });

    it('returns null signature when no override is set', function () {
        $field = MediaPickerField::make('media');

        expect($field->getEffectiveExtensionsSignature())->toBeNull();
    });

    it('allowedFileTypesOnly takes priority over includeFileTypes', function () {
        $field = MediaPickerField::make('media')
            ->includeFileTypes(['ico'])
            ->allowedFileTypesOnly(['pdf', 'docx']);

        // allowedFileTypesOnly should win (it's checked first)
        expect($field->getEffectiveExtensions())->toBe('pdf,docx');
    });

    it('supports chaining with other methods', function () {
        $field = MediaPickerField::make('media')
            ->imageOnly()
            ->includeFileTypes(['ico'])
            ->directUpload()
            ->maxFiles(5);

        expect($field->getAcceptedFileTypes())->toBe(['image/*'])
            ->and($field->getEffectiveExtensions())->toContain('ico')
            ->and($field->isDirectUploadEnabled())->toBeTrue()
            ->and($field->getMaxFiles())->toBe(5);
    });
});

describe('MediaPickerField displayStyle()', function () {
    it('defaults to compact from config', function () {
        config()->set('media.picker.display_style', 'compact');

        $field = MediaPickerField::make('media');

        expect($field->getDisplayStyle())->toBe('compact');
    });

    it('respects config default when not explicitly set', function () {
        config()->set('media.picker.display_style', 'thumbnail');

        $field = MediaPickerField::make('media');

        expect($field->getDisplayStyle())->toBe('thumbnail');
    });

    it('respects integratedLinks config default', function () {
        config()->set('media.picker.display_style', 'integratedLinks');

        $field = MediaPickerField::make('media');

        expect($field->getDisplayStyle())->toBe('integratedLinks');
    });

    it('respects integratedDropdown config default', function () {
        config()->set('media.picker.display_style', 'integratedDropdown');

        $field = MediaPickerField::make('media');

        expect($field->getDisplayStyle())->toBe('integratedDropdown');
    });

    it('can be set to compact', function () {
        $field = MediaPickerField::make('media')->displayStyle('compact');

        expect($field->getDisplayStyle())->toBe('compact');
    });

    it('can be set to thumbnail', function () {
        $field = MediaPickerField::make('media')->displayStyle('thumbnail');

        expect($field->getDisplayStyle())->toBe('thumbnail');
    });

    it('can be set to integratedLinks', function () {
        $field = MediaPickerField::make('media')->displayStyle('integratedLinks');

        expect($field->getDisplayStyle())->toBe('integratedLinks');
    });

    it('can be set to integratedDropdown', function () {
        $field = MediaPickerField::make('media')->displayStyle('integratedDropdown');

        expect($field->getDisplayStyle())->toBe('integratedDropdown');
    });

    it('can be set to dropdown', function () {
        $field = MediaPickerField::make('media')->displayStyle('dropdown');

        expect($field->getDisplayStyle())->toBe('dropdown');
    });

    it('respects dropdown config default', function () {
        config()->set('media.picker.display_style', 'dropdown');

        $field = MediaPickerField::make('media');

        expect($field->getDisplayStyle())->toBe('dropdown');
    });

    it('explicit value overrides config default', function () {
        config()->set('media.picker.display_style', 'thumbnail');

        $field = MediaPickerField::make('media')->displayStyle('compact');

        expect($field->getDisplayStyle())->toBe('compact');
    });

    it('falls back to compact for invalid config value', function () {
        config()->set('media.picker.display_style', 'invalid');

        $field = MediaPickerField::make('media');

        expect($field->getDisplayStyle())->toBe('compact');
    });

    it('throws exception for invalid displayStyle argument', function () {
        MediaPickerField::make('media')->displayStyle('invalid');
    })->throws(\InvalidArgumentException::class);

    it('throws exception for old integrated value', function () {
        MediaPickerField::make('media')->displayStyle('integrated');
    })->throws(\InvalidArgumentException::class);

    it('supports chaining with other methods', function () {
        $field = MediaPickerField::make('media')
            ->displayStyle('thumbnail')
            ->imageOnly()
            ->multiple()
            ->maxFiles(5);

        expect($field->getDisplayStyle())->toBe('thumbnail')
            ->and($field->getAcceptedFileTypes())->toBe(['image/*'])
            ->and($field->isMultiple())->toBeTrue()
            ->and($field->getMaxFiles())->toBe(5);
    });

    it('falls back to compact when config key is missing', function () {
        config()->set('media.picker.display_style', null);

        $field = MediaPickerField::make('media');

        expect($field->getDisplayStyle())->toBe('compact');
    });
});

describe('MediaPickerField previewWidth() and previewHeight()', function () {
    it('defaults to width 12rem with aspect-square', function () {
        $field = MediaPickerField::make('media');

        expect($field->getPreviewSizeStyle())->toBe('width: 12rem')
            ->and($field->shouldUseAspectSquare())->toBeTrue()
            ->and($field->getPreviewWidthStyle())->toBe('width: 12rem');
    });

    it('width only keeps aspect-square', function () {
        $field = MediaPickerField::make('media')->previewWidth('16rem');

        expect($field->getPreviewSizeStyle())->toBe('width: 16rem')
            ->and($field->shouldUseAspectSquare())->toBeTrue()
            ->and($field->getPreviewWidthStyle())->toBe('width: 16rem');
    });

    it('height only removes aspect-square and uses default width', function () {
        $field = MediaPickerField::make('media')->previewHeight('8rem');

        expect($field->getPreviewSizeStyle())->toBe('width: 12rem; height: 8rem')
            ->and($field->shouldUseAspectSquare())->toBeFalse()
            ->and($field->getPreviewWidthStyle())->toBe('width: 12rem');
    });

    it('null config width with height returns height only', function () {
        config()->set('media.picker.preview_width', null);
        config()->set('media.picker.preview_height', '8rem');

        $field = MediaPickerField::make('media');

        expect($field->getPreviewSizeStyle())->toBe('height: 8rem')
            ->and($field->shouldUseAspectSquare())->toBeFalse()
            ->and($field->getPreviewWidthStyle())->toBe('');
    });

    it('both width and height removes aspect-square', function () {
        $field = MediaPickerField::make('media')
            ->previewWidth('16rem')
            ->previewHeight('8rem');

        expect($field->getPreviewSizeStyle())->toBe('width: 16rem; height: 8rem')
            ->and($field->shouldUseAspectSquare())->toBeFalse()
            ->and($field->getPreviewWidthStyle())->toBe('width: 16rem');
    });

    it('respects config defaults', function () {
        config()->set('media.picker.preview_width', '16rem');
        config()->set('media.picker.preview_height', '6rem');

        $field = MediaPickerField::make('media');

        expect($field->getPreviewSizeStyle())->toBe('width: 16rem; height: 6rem')
            ->and($field->shouldUseAspectSquare())->toBeFalse()
            ->and($field->getPreviewWidthStyle())->toBe('width: 16rem');
    });

    it('explicit values override config', function () {
        config()->set('media.picker.preview_width', '16rem');
        config()->set('media.picker.preview_height', '6rem');

        $field = MediaPickerField::make('media')
            ->previewWidth('8rem')
            ->previewHeight('12rem');

        expect($field->getPreviewSizeStyle())->toBe('width: 8rem; height: 12rem')
            ->and($field->getPreviewWidthStyle())->toBe('width: 8rem');
    });

    it('supports chaining with other methods', function () {
        $field = MediaPickerField::make('media')
            ->displayStyle('integratedDropdown')
            ->previewWidth('16rem')
            ->previewHeight('8rem')
            ->imageOnly();

        expect($field->getPreviewSizeStyle())->toBe('width: 16rem; height: 8rem')
            ->and($field->getDisplayStyle())->toBe('integratedDropdown')
            ->and($field->getAcceptedFileTypes())->toBe(['image/*']);
    });

    it('accepts pixel values', function () {
        $field = MediaPickerField::make('media')
            ->previewWidth('256px')
            ->previewHeight('128px');

        expect($field->getPreviewSizeStyle())->toBe('width: 256px; height: 128px');
    });
});

describe('MediaPickerField chipSize()', function () {
    it('defaults to sm', function () {
        $field = MediaPickerField::make('media');

        expect($field->getChipSize())->toBe('sm');
    });

    it('can be set to xs', function () {
        $field = MediaPickerField::make('media')->chipSize('xs');

        expect($field->getChipSize())->toBe('xs');
    });

    it('can be set to md', function () {
        $field = MediaPickerField::make('media')->chipSize('md');

        expect($field->getChipSize())->toBe('md');
    });

    it('can be set to lg', function () {
        $field = MediaPickerField::make('media')->chipSize('lg');

        expect($field->getChipSize())->toBe('lg');
    });

    it('throws exception for invalid chipSize', function () {
        MediaPickerField::make('media')->chipSize('invalid');
    })->throws(\InvalidArgumentException::class);

    it('respects config default', function () {
        config()->set('media.picker.chip_size', 'lg');

        $field = MediaPickerField::make('media');

        expect($field->getChipSize())->toBe('lg');
    });

    it('explicit value overrides config', function () {
        config()->set('media.picker.chip_size', 'lg');

        $field = MediaPickerField::make('media')->chipSize('xs');

        expect($field->getChipSize())->toBe('xs');
    });

    it('falls back to sm for invalid config value', function () {
        config()->set('media.picker.chip_size', 'invalid');

        $field = MediaPickerField::make('media');

        expect($field->getChipSize())->toBe('sm');
    });

    it('can be set to xl', function () {
        $field = MediaPickerField::make('media')->chipSize('xl');

        expect($field->getChipSize())->toBe('xl');
    });

    it('can be set to 2xl', function () {
        $field = MediaPickerField::make('media')->chipSize('2xl');

        expect($field->getChipSize())->toBe('2xl');
    });

    it('returns correct dimensions for each size', function () {
        $xs = MediaPickerField::make('media')->chipSize('xs')->getChipDimensions();
        $sm = MediaPickerField::make('media')->chipSize('sm')->getChipDimensions();
        $md = MediaPickerField::make('media')->chipSize('md')->getChipDimensions();
        $lg = MediaPickerField::make('media')->chipSize('lg')->getChipDimensions();
        $xl = MediaPickerField::make('media')->chipSize('xl')->getChipDimensions();
        $xxl = MediaPickerField::make('media')->chipSize('2xl')->getChipDimensions();

        expect($xs['thumb'])->toBe('1.25rem')
            ->and($sm['thumb'])->toBe('2rem')
            ->and($md['thumb'])->toBe('3rem')
            ->and($lg['thumb'])->toBe('4rem')
            ->and($xl['thumb'])->toBe('5rem')
            ->and($xxl['thumb'])->toBe('6rem');
    });

    it('supports chaining with other methods', function () {
        $field = MediaPickerField::make('media')
            ->displayStyle('dropdown')
            ->chipSize('lg')
            ->imageOnly();

        expect($field->getChipSize())->toBe('lg')
            ->and($field->getDisplayStyle())->toBe('dropdown')
            ->and($field->getAcceptedFileTypes())->toBe(['image/*']);
    });
});

describe('MediaPickerField lightboxMaxWidth() and lightboxMaxHeight()', function () {
    it('defaults to empty string (full viewport)', function () {
        $field = MediaPickerField::make('media');

        expect($field->getLightboxStyle())->toBe('');
    });

    it('sets max width only', function () {
        $field = MediaPickerField::make('media')->lightboxMaxWidth('800px');

        expect($field->getLightboxStyle())->toBe('max-width: 800px');
    });

    it('sets max height only', function () {
        $field = MediaPickerField::make('media')->lightboxMaxHeight('80vh');

        expect($field->getLightboxStyle())->toBe('max-height: 80vh');
    });

    it('sets both max width and height', function () {
        $field = MediaPickerField::make('media')
            ->lightboxMaxWidth('800px')
            ->lightboxMaxHeight('600px');

        expect($field->getLightboxStyle())->toBe('max-width: 800px; max-height: 600px');
    });

    it('respects config defaults', function () {
        config()->set('media.picker.lightbox_max_width', '1024px');
        config()->set('media.picker.lightbox_max_height', '80vh');

        $field = MediaPickerField::make('media');

        expect($field->getLightboxStyle())->toBe('max-width: 1024px; max-height: 80vh');
    });

    it('explicit values override config', function () {
        config()->set('media.picker.lightbox_max_width', '1024px');
        config()->set('media.picker.lightbox_max_height', '80vh');

        $field = MediaPickerField::make('media')
            ->lightboxMaxWidth('500px')
            ->lightboxMaxHeight('50vh');

        expect($field->getLightboxStyle())->toBe('max-width: 500px; max-height: 50vh');
    });

    it('supports chaining with other methods', function () {
        $field = MediaPickerField::make('media')
            ->displayStyle('thumbnail')
            ->lightboxMaxWidth('800px')
            ->imageOnly();

        expect($field->getLightboxStyle())->toBe('max-width: 800px')
            ->and($field->getDisplayStyle())->toBe('thumbnail')
            ->and($field->getAcceptedFileTypes())->toBe(['image/*']);
    });
});

describe('MediaPickerField lightboxOpacity()', function () {
    it('defaults to 0.8', function () {
        $field = MediaPickerField::make('media');

        expect($field->getLightboxOpacity())->toBe(0.8);
    });

    it('can be set to a custom value', function () {
        $field = MediaPickerField::make('media')->lightboxOpacity(50);

        expect($field->getLightboxOpacity())->toBe(0.5);
    });

    it('clamps to 0-100 range', function () {
        $low = MediaPickerField::make('media')->lightboxOpacity(-10);
        $high = MediaPickerField::make('media')->lightboxOpacity(150);

        expect($low->getLightboxOpacity())->toBe(0.0)
            ->and($high->getLightboxOpacity())->toBe(1.0);
    });

    it('respects config default', function () {
        config()->set('media.picker.lightbox_opacity', 60);

        $field = MediaPickerField::make('media');

        expect($field->getLightboxOpacity())->toBe(0.6);
    });

    it('explicit value overrides config', function () {
        config()->set('media.picker.lightbox_opacity', 60);

        $field = MediaPickerField::make('media')->lightboxOpacity(90);

        expect($field->getLightboxOpacity())->toBe(0.9);
    });
});

describe('MediaPickerField fluent interface', function () {
    it('supports method chaining', function () {
        $field = MediaPickerField::make('media')
            ->multiple()
            ->imageOnly()
            ->maxFiles(10)
            ->directory('images')
            ->collection('gallery');

        expect($field)->toBeInstanceOf(MediaPickerField::class)
            ->and($field->isMultiple())->toBeTrue()
            ->and($field->getAcceptedFileTypes())->toBe(['image/*'])
            ->and($field->getMaxFiles())->toBe(10)
            ->and($field->getDirectory())->toBe('images')
            ->and($field->getCollection())->toBe('gallery');
    });
});
