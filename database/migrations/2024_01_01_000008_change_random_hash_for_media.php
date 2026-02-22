<?php

use Codenzia\FilamentMedia\Models\MediaSetting;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('media_settings')) {
            return;
        }

        MediaSetting::setSystemSetting('media_random_hash', md5((string) time()));
    }
};
