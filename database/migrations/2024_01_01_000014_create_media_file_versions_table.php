<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_file_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('media_file_id')->constrained('media_files')->onDelete('cascade');
            $table->unsignedInteger('version_number');
            $table->string('url');
            $table->unsignedBigInteger('size');
            $table->string('mime_type', 120);
            $table->foreignId('user_id')->nullable()->index();
            $table->text('changelog')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['media_file_id', 'version_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_file_versions');
    }
};
