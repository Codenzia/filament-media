<?php

namespace Codenzia\FilamentMedia\Pages\Concerns;

/**
 * Conditionally applies FilamentShield's HasPageShield trait.
 *
 * When bezhanSalleh/filament-shield is installed, this delegates to
 * HasPageShield for permission enforcement. Otherwise, it's a no-op
 * so the package works without filament-shield as a dependency.
 */
if (trait_exists(\BezhanSalleh\FilamentShield\Traits\HasPageShield::class)) {
    trait HasConditionalPageShield
    {
        use \BezhanSalleh\FilamentShield\Traits\HasPageShield;
    }
} else {
    trait HasConditionalPageShield
    {
        // No-op: filament-shield is not installed
    }
}
