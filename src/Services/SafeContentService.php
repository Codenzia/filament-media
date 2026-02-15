<?php

namespace Codenzia\FilamentMedia\Services;

use Codenzia\FilamentMedia\Helpers\BaseHelper;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;

/**
 * Eloquent cast that sanitizes HTML content on get and set to prevent XSS.
 */
class SafeContentService implements CastsAttributes
{
    public function set($model, string $key, $value, array $attributes)
    {
        return BaseHelper::clean($value);
    }

    public function get($model, string $key, $value, array $attributes)
    {
        if (! $value) {
            return $value;
        }

        return html_entity_decode(BaseHelper::clean($value));
    }
}
