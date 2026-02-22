<?php

declare(strict_types=1);

namespace Codenzia\FilamentMedia\Support;

/**
 * Generates HMAC-SHA256 hashes for media file URL obfuscation.
 *
 * Used to create unpredictable URL tokens for private and indirect
 * file access routes, keyed to the application secret.
 */
class MediaHash
{
    public static function generate(string|int $id): string
    {
        return hash_hmac('sha256', (string) $id, config('app.key'));
    }

    public static function verify(string $hash, string|int $id): bool
    {
        return hash_equals(static::generate($id), $hash);
    }
}
