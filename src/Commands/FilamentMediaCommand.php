<?php

namespace Codenzia\FilamentMedia\Commands;

use Illuminate\Console\Command;

class FilamentMediaCommand extends Command
{
    public $signature = 'filament-media';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
