<?php

namespace Codenzia\FilamentMedia\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Codenzia\FilamentMedia\FilamentMedia
 */
class FilamentMedia extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \Codenzia\FilamentMedia\FilamentMedia::class;
    }
}
