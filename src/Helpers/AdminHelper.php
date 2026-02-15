<?php

namespace Codenzia\FilamentMedia\Helpers;

/**
 * Helper for determining whether the current request is within the admin panel.
 */
class AdminHelper
{
    public static function isInAdmin(bool $checkGuest = false): bool
    {
        return true;
    }
}
