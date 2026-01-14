<?php

namespace Codenzia\FilamentMedia\Pages;

use Filament\Pages\Page;

class Media extends Page
{

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected string $view = 'filament-media::pages.media';
}
