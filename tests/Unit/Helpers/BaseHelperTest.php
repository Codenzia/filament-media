<?php

use Codenzia\FilamentMedia\Helpers\BaseHelper;

describe('BaseHelper', function () {
    beforeEach(function () {
        $this->helper = new BaseHelper();
    });

    describe('stringify method', function () {
        it('returns null for empty content', function () {
            expect($this->helper->stringify(''))->toBeNull()
                ->and($this->helper->stringify(null))->toBeNull()
                ->and($this->helper->stringify([]))->toBeNull();
        });

        it('returns "0" for zero string', function () {
            expect($this->helper->stringify('0'))->toBe('0');
        });

        it('returns "0" for zero integer', function () {
            expect($this->helper->stringify(0))->toBe('0');
        });

        it('returns "1" for true boolean', function () {
            expect($this->helper->stringify(true))->toBe('1');
        });

        it('returns "0" for false boolean', function () {
            expect($this->helper->stringify(false))->toBe('0');
        });

        it('escapes HTML in strings for XSS prevention', function () {
            $malicious = '<script>alert("xss")</script>';
            $result = $this->helper->stringify($malicious);

            expect($result)->not->toContain('<script>')
                ->and($result)->toContain('&lt;script&gt;');
        });

        it('escapes quotes in strings', function () {
            $input = 'Test "quoted" & \'apostrophe\'';
            $result = $this->helper->stringify($input);

            // ENT_HTML5 escapes single quotes to &apos;
            expect($result)->toContain('&quot;')
                ->and($result)->toContain('&apos;')
                ->and($result)->toContain('&amp;');
        });

        it('converts numbers to escaped strings', function () {
            expect($this->helper->stringify(123))->toBe('123')
                ->and($this->helper->stringify(45.67))->toBe('45.67');
        });

        it('converts arrays to escaped JSON', function () {
            $array = ['key' => 'value'];
            $result = $this->helper->stringify($array);

            expect($result)->toContain('key')
                ->and($result)->toContain('value');
        });

        it('returns null for objects', function () {
            $object = new stdClass();
            $object->property = 'value';

            expect($this->helper->stringify($object))->toBeNull();
        });
    });

    describe('humanFilesize method', function () {
        it('formats bytes correctly', function () {
            expect(BaseHelper::humanFilesize(500))->toBe('500 B');
        });

        it('formats kilobytes correctly', function () {
            // Uses lowercase 'kB', and exactly 1024 stays as 1024 B (not > 1024)
            // Need > 1024 to trigger division
            expect(BaseHelper::humanFilesize(1025))->toBe('1 kB');
        });

        it('formats megabytes correctly', function () {
            // Need > 1048576 to trigger division to MB
            expect(BaseHelper::humanFilesize(1048577))->toBe('1 MB');
        });

        it('formats gigabytes correctly', function () {
            // Need > 1073741824 to trigger division to GB
            expect(BaseHelper::humanFilesize(1073741825))->toBe('1 GB');
        });

        it('handles zero bytes', function () {
            expect(BaseHelper::humanFilesize(0))->toBe('0 B');
        });

        it('formats with custom precision', function () {
            $result = BaseHelper::humanFilesize(1536, 1);
            expect($result)->toBe('1.5 kB');
        });
    });

    describe('formatDate method', function () {
        it('formats date with default format', function () {
            $date = new DateTime('2024-06-15 14:30:45');
            $result = BaseHelper::formatDate($date);

            expect($result)->toBe('2024-06-15 14:30:45');
        });

        it('formats date with custom format', function () {
            $date = new DateTime('2024-06-15 14:30:45');
            $result = BaseHelper::formatDate($date, 'd/m/Y');

            expect($result)->toBe('15/06/2024');
        });
    });
});

describe('Helper Functions', function () {
    describe('setting function', function () {
        beforeEach(function () {
            // Set up test config - setting() looks in media.{key} not media.test_key directly
            config(['media.test_key' => 'test_value']);
            config(['media.nested.key' => 'nested_value']);
        });

        it('returns config value for key', function () {
            // setting('test_key') looks in config('media.test_key')
            $value = setting('test_key');

            expect($value)->toBe('test_value');
        });

        it('returns default when key not found', function () {
            $value = setting('nonexistent_key', 'default');

            expect($value)->toBe('default');
        });

        it('returns null when key not found and no default', function () {
            $value = setting('nonexistent_key');

            expect($value)->toBeNull();
        });
    });

    describe('clean function', function () {
        it('sanitizes HTML content', function () {
            $dirty = '<script>alert("xss")</script><p>Clean text</p>';
            $result = clean($dirty);

            expect($result)->not->toContain('<script>');
        });

        it('returns null for null input', function () {
            // clean() returns null for null input per implementation
            expect(clean(null))->toBeNull();
        });

        it('returns empty string for empty input', function () {
            expect(clean(''))->toBe('');
        });
    });
});
