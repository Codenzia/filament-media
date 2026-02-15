<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_metadata_fields', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->enum('type', ['text', 'textarea', 'number', 'date', 'select', 'boolean', 'url'])->default('text');
            $table->json('options')->nullable();
            $table->boolean('is_required')->default(false);
            $table->boolean('is_searchable')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('media_file_metadata', function (Blueprint $table): void {
            $table->foreignId('media_file_id')->constrained('media_files')->onDelete('cascade');
            $table->foreignId('media_metadata_field_id')->constrained('media_metadata_fields')->onDelete('cascade');
            $table->text('value')->nullable();
            $table->timestamps();

            $table->primary(['media_file_id', 'media_metadata_field_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_file_metadata');
        Schema::dropIfExists('media_metadata_fields');
    }
};
