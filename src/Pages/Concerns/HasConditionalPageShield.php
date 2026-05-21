<?php

namespace Codenzia\FilamentMedia\Pages\Concerns;

use BezhanSalleh\FilamentShield\Traits\HasPageShield;

/**
 * Conditionally applies FilamentShield's HasPageShield trait.
 *
 * When bezhanSalleh/filament-shield is installed, this delegates to
 * HasPageShield for permission enforcement. Otherwise, it's a no-op
 * so the package works without filament-shield as a dependency.
 */
if (trait_exists(HasPageShield::class)) {
    trait HasConditionalPageShield
    {
        use HasPageShield;
    }
} else {
    trait HasConditionalPageShield
    {
        // No-op: filament-shield is not installed
    }
}
