<?php

namespace Codenzia\FilamentMedia\Pages;

use Filament\Pages\Page;
use Codenzia\FilamentMedia\FilamentMedia;

class Media extends Page
{

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-photo';

    protected string $view = 'filament-media::pages.media';
    protected array $sorts = [];

    public function mount(): void
    {
        $this->sorts = FilamentMedia::getSorts();
    }

}
