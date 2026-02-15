<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_tags', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('color', 7)->nullable();
            $table->enum('type', ['tag', 'collection'])->default('tag');
            $table->text('description')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('media_tags')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->index();
            $table->timestamps();

            $table->index(['type', 'name']);
        });

        Schema::create('media_file_tag', function (Blueprint $table): void {
            $table->foreignId('media_file_id')->constrained('media_files')->onDelete('cascade');
            $table->foreignId('media_tag_id')->constrained('media_tags')->onDelete('cascade');

            $table->primary(['media_file_id', 'media_tag_id']);
        });

        Schema::create('media_folder_tag', function (Blueprint $table): void {
            $table->foreignId('media_folder_id')->constrained('media_folders')->onDelete('cascade');
            $table->foreignId('media_tag_id')->constrained('media_tags')->onDelete('cascade');

            $table->primary(['media_folder_id', 'media_tag_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_folder_tag');
        Schema::dropIfExists('media_file_tag');
        Schema::dropIfExists('media_tags');
    }
};
