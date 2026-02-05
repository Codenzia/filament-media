<?php

use Codenzia\FilamentMedia\Services\SafeContentService;

describe('SafeContentService', function () {
    beforeEach(function () {
        $this->service = new SafeContentService();
        $this->model = new stdClass(); // Fake model for testing
    });

    describe('set method', function () {
        it('sanitizes HTML content on set', function () {
            $dirty = '<script>alert("xss")</script>Hello';
            $result = $this->service->set($this->model, 'name', $dirty, []);

            expect($result)->not->toContain('<script>');
        });

        it('handles null value', function () {
            $result = $this->service->set($this->model, 'name', null, []);

            expect($result)->toBe('');
        });

        it('handles empty string', function () {
            $result = $this->service->set($this->model, 'name', '', []);

            expect($result)->toBe('');
        });

        it('preserves safe content', function () {
            $safe = 'This is safe content';
            $result = $this->service->set($this->model, 'name', $safe, []);

            expect($result)->toBe($safe);
        });

        it('cleans HTML content', function () {
            // clean() uses HTMLPurifier which allows certain safe HTML
            $html = '<div>Test & "quotes"</div>';
            $result = $this->service->set($this->model, 'name', $html, []);

            // HTMLPurifier allows safe HTML like <div> but sanitizes dangerous content
            expect($result)->toContain('Test');
        });
    });

    describe('get method', function () {
        it('returns null for null value', function () {
            $result = $this->service->get($this->model, 'name', null, []);

            expect($result)->toBeNull();
        });

        it('returns empty string for empty value', function () {
            $result = $this->service->get($this->model, 'name', '', []);

            expect($result)->toBe('');
        });

        it('decodes HTML entities on get', function () {
            // Value stored as escaped
            $stored = '&lt;p&gt;Hello&lt;/p&gt;';
            $result = $this->service->get($this->model, 'name', $stored, []);

            // Should decode entities but still be sanitized
            expect($result)->toContain('<p>');
        });

        it('handles already clean content', function () {
            $clean = 'Normal text content';
            $result = $this->service->get($this->model, 'name', $clean, []);

            expect($result)->toBe($clean);
        });
    });

    describe('XSS prevention', function () {
        it('prevents script injection', function () {
            $xss = '<script>document.cookie</script>';
            $result = $this->service->set($this->model, 'name', $xss, []);

            expect($result)->not->toContain('<script>');
        });

        it('prevents event handler injection', function () {
            $xss = '<img onerror="alert(1)" src="x">';
            $result = $this->service->set($this->model, 'name', $xss, []);

            // clean() uses htmlspecialchars which encodes < and > making onerror harmless
            expect($result)->not->toContain('<img');
        });

        it('prevents javascript URI injection', function () {
            $xss = '<a href="javascript:alert(1)">Click</a>';
            $result = $this->service->set($this->model, 'name', $xss, []);

            // clean() uses htmlspecialchars which encodes < and > making the anchor tag harmless
            expect($result)->not->toContain('<a ');
        });

        it('handles unicode escape attempts', function () {
            $xss = '<script>\u0061lert(1)</script>';
            $result = $this->service->set($this->model, 'name', $xss, []);

            expect($result)->not->toContain('<script>');
        });
    });
});
