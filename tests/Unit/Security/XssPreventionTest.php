<?php

use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    MediaFile::withoutGlobalScopes();
    MediaFolder::withoutGlobalScopes();
});

describe('XSS Prevention in File Names', function () {
    it('escapes script tags in file names via stringify', function () {
        $helper = new BaseHelper();
        $malicious = '<script>alert("xss")</script>file.jpg';

        $result = $helper->stringify($malicious);

        // htmlspecialchars encodes < and > to prevent XSS
        expect($result)->not->toContain('<script>')
            ->and($result)->toContain('&lt;script&gt;');
    });

    it('escapes event handlers in file names', function () {
        $helper = new BaseHelper();
        $malicious = 'file<img onerror=alert(1)>.jpg';

        $result = $helper->stringify($malicious);

        // The < and > are encoded, making onerror= harmless
        expect($result)->not->toContain('<img')
            ->and($result)->toContain('&lt;img');
    });

    it('escapes HTML entities in file names', function () {
        $helper = new BaseHelper();
        $malicious = '<div onclick="evil()">file</div>.jpg';

        $result = $helper->stringify($malicious);

        // < and > are encoded
        expect($result)->not->toContain('<div')
            ->and($result)->toContain('&lt;div');
    });
});

describe('XSS Prevention in Folder Names', function () {
    it('stores folder names as provided', function () {
        // Note: Model does not use SafeContentService for name field
        // XSS prevention happens at output time via stringify()
        $folder = MediaFolder::factory()->create([
            'name' => 'My Folder',
        ]);

        expect($folder->name)->toBe('My Folder');
    });

    it('folder names should be escaped when displayed', function () {
        $helper = new BaseHelper();
        $folderName = '<script>alert(1)</script>folder';

        $result = $helper->stringify($folderName);

        expect($result)->not->toContain('<script>');
    });
});

describe('XSS Prevention in Alt Text', function () {
    it('escapes script tags in alt text', function () {
        $helper = new BaseHelper();
        $malicious = '<script>steal(document.cookie)</script>';

        $result = $helper->stringify($malicious);

        expect($result)->not->toContain('<script>');
    });

    it('escapes quotes in alt text', function () {
        $helper = new BaseHelper();
        $malicious = '" onload="alert(1)" alt="';

        $result = $helper->stringify($malicious);

        expect($result)->toContain('&quot;');
    });
});

describe('XSS Prevention Common Attack Vectors', function () {
    // htmlspecialchars encodes: < > " ' &
    // This makes HTML injection impossible as tags can't be created

    it('prevents basic script attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<script>alert(1)</script>');

        expect($result)->not->toContain('<script');
    });

    it('prevents img onerror attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<img src=x onerror=alert(1)>');

        // < and > are encoded, tag cannot be created
        expect($result)->not->toContain('<img');
    });

    it('prevents svg onload attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<svg onload=alert(1)>');

        expect($result)->not->toContain('<svg');
    });

    it('prevents body onload attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<body onload=alert(1)>');

        expect($result)->not->toContain('<body');
    });

    it('prevents iframe javascript attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<iframe src="javascript:alert(1)">');

        expect($result)->not->toContain('<iframe');
    });

    it('prevents anchor javascript attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<a href="javascript:alert(1)">click</a>');

        expect($result)->not->toContain('<a ');
    });

    it('prevents attribute breakout attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('"><script>alert(1)</script>');

        // Quotes are encoded, preventing attribute breakout
        expect($result)->toContain('&quot;')
            ->and($result)->not->toContain('<script');
    });

    it('prevents single quote breakout attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify("'-alert(1)-'");

        // Single quotes are encoded with ENT_HTML5 as &apos;
        expect($result)->toContain('&apos;');
    });

    it('prevents math tag attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<math><maction actiontype="statusline#http://evil.com">click</maction></math>');

        expect($result)->not->toContain('<math');
    });

    it('prevents autofocus input attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<input onfocus=alert(1) autofocus>');

        expect($result)->not->toContain('<input');
    });

    it('prevents marquee attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<marquee onstart=alert(1)>');

        expect($result)->not->toContain('<marquee');
    });

    it('prevents video source error attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<video><source onerror="alert(1)">');

        expect($result)->not->toContain('<video');
    });

    it('prevents audio error attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<audio src=x onerror=alert(1)>');

        expect($result)->not->toContain('<audio');
    });

    it('prevents details toggle attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('<details open ontoggle=alert(1)>');

        expect($result)->not->toContain('<details');
    });

    it('prevents template literal attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('${alert(1)}');

        // Template literals are safe in HTML context (they're JS-specific)
        expect($result)->toBe('${alert(1)}');
    });

    it('prevents prototype pollution attempt attack', function () {
        $helper = new BaseHelper();
        $result = $helper->stringify('{{constructor.constructor("alert(1)")()}}');

        // This is a template injection, safe in HTML-escaped context
        expect($result)->toBe('{{constructor.constructor(&quot;alert(1)&quot;)()}}');
    });
});

describe('Clean Function XSS Prevention', function () {
    it('escapes script tags', function () {
        $result = clean('<script>alert(1)</script>safe');

        // clean() uses htmlspecialchars to encode < and >
        expect($result)->not->toContain('<script>')
            ->and($result)->toContain('&lt;script&gt;');
    });

    it('escapes event handlers', function () {
        $result = clean('<div onclick="evil()">text</div>');

        // clean() encodes < and > making the event handler harmless
        expect($result)->not->toContain('<div')
            ->and($result)->toContain('&lt;div');
    });

    it('handles nested malicious content', function () {
        $result = clean('<scr<script>ipt>alert(1)</scr</script>ipt>');

        // All < and > are encoded
        expect($result)->not->toContain('<script>');
    });

    it('handles encoded attacks', function () {
        $result = clean('&#60;script&#62;alert(1)&#60;/script&#62;');

        // Double encoding - the & becomes &amp;
        expect($result)->not->toMatch('/<script>/i');
    });
});
