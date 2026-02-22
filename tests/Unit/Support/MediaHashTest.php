<?php

use Codenzia\FilamentMedia\Support\MediaHash;

describe('MediaHash', function () {
    it('generates consistent hash for the same ID', function () {
        $hash1 = MediaHash::generate(42);
        $hash2 = MediaHash::generate(42);

        expect($hash1)->toBe($hash2);
    });

    it('generates different hashes for different IDs', function () {
        $hash1 = MediaHash::generate(1);
        $hash2 = MediaHash::generate(2);

        expect($hash1)->not->toBe($hash2);
    });

    it('generates hash that is not plain sha1', function () {
        $id = 123;
        $hmacHash = MediaHash::generate($id);
        $sha1Hash = sha1($id);

        expect($hmacHash)->not->toBe($sha1Hash);
    });

    it('generates a 64-character hex string (SHA-256)', function () {
        $hash = MediaHash::generate(1);

        expect(strlen($hash))->toBe(64)
            ->and(ctype_xdigit($hash))->toBeTrue();
    });

    it('accepts string IDs', function () {
        $hash = MediaHash::generate('abc-123');

        expect($hash)->toBeString()
            ->and(strlen($hash))->toBe(64);
    });

    it('accepts integer IDs', function () {
        $hash = MediaHash::generate(999);

        expect($hash)->toBeString()
            ->and(strlen($hash))->toBe(64);
    });

    it('produces different hash when app key changes', function () {
        $hash1 = MediaHash::generate(1);

        config(['app.key' => 'base64:' . base64_encode(str_repeat('b', 32))]);

        $hash2 = MediaHash::generate(1);

        expect($hash1)->not->toBe($hash2);
    });

    it('verifies matching hash correctly', function () {
        $id = 42;
        $hash = MediaHash::generate($id);

        expect(MediaHash::verify($hash, $id))->toBeTrue();
    });

    it('rejects non-matching hash', function () {
        expect(MediaHash::verify('invalid-hash', 42))->toBeFalse();
    });

    it('rejects hash from different ID', function () {
        $hash = MediaHash::generate(1);

        expect(MediaHash::verify($hash, 2))->toBeFalse();
    });
});
