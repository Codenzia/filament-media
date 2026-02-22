<?php

/**
 * Performance Indexes for Media Tables at Scale
 *
 * Optimizes media queries when properties have many attached files.
 * With millions of properties, each having 5-20 images, the media_files
 * table can reach 10M+ rows. These indexes ensure fast:
 * - Polymorphic lookups (load all files for a model)
 * - Soft-delete aware queries
 * - MIME type filtering
 * - File name/description search
 * - Tag and metadata lookups
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─── media_files ────────────────────────────────────────────────

        Schema::table('media_files', function (Blueprint $table) {
            // Composite: polymorphic lookup with soft-delete awareness
            // This is THE hot query: $property->files loads all non-deleted files for a model
            // SELECT * FROM media_files WHERE fileable_type=? AND fileable_id=? AND deleted_at IS NULL
            $table->index(
                ['fileable_type', 'fileable_id', 'deleted_at'],
                'idx_media_files_fileable_alive'
            );

            // MIME type filter (image gallery vs documents vs videos)
            $table->index('mime_type', 'idx_media_files_mime');

            // Name search for media browser
            $table->index('name', 'idx_media_files_name');

            // Composite: folder browsing with soft-delete (admin media browser)
            // SELECT * FROM media_files WHERE folder_id=? AND deleted_at IS NULL ORDER BY created_at DESC
            $table->index(
                ['folder_id', 'deleted_at', 'created_at'],
                'idx_media_files_folder_browse'
            );
        });

        // ─── media_folders ──────────────────────────────────────────────

        Schema::table('media_folders', function (Blueprint $table) {
            // Composite: polymorphic lookup with soft-delete (load property's folders)
            $table->index(
                ['fileable_type', 'fileable_id', 'deleted_at'],
                'idx_media_folders_fileable_alive'
            );

            // Composite: tree traversal with soft-delete (list subfolders)
            $table->index(
                ['parent_id', 'deleted_at', 'created_at'],
                'idx_media_folders_tree_browse'
            );
        });

        // ─── media_file_tag (pivot — reverse lookup) ────────────────────

        Schema::table('media_file_tag', function (Blueprint $table) {
            // Reverse: "find all files with tag X"
            $table->index('media_tag_id', 'idx_file_tag_reverse');
        });

        // ─── media_folder_tag (pivot — reverse lookup) ──────────────────

        Schema::table('media_folder_tag', function (Blueprint $table) {
            // Reverse: "find all folders with tag X"
            $table->index('media_tag_id', 'idx_folder_tag_reverse');
        });

        // ─── media_file_metadata (pivot — reverse lookup) ───────────────

        Schema::table('media_file_metadata', function (Blueprint $table) {
            // Reverse: "find all files with metadata field X"
            $table->index('media_metadata_field_id', 'idx_file_meta_field');
        });

        // ─── media_file_versions ────────────────────────────────────────

        Schema::table('media_file_versions', function (Blueprint $table) {
            // User audit trail: "versions uploaded by user X"
            $table->index('user_id', 'idx_file_versions_user');
        });

        // ─── media_tags ─────────────────────────────────────────────────

        Schema::table('media_tags', function (Blueprint $table) {
            // Parent lookup for tree traversal
            $table->index('parent_id', 'idx_media_tags_parent');
        });
    }

    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropIndex('idx_media_files_fileable_alive');
            $table->dropIndex('idx_media_files_mime');
            $table->dropIndex('idx_media_files_name');
            $table->dropIndex('idx_media_files_folder_browse');
        });

        Schema::table('media_folders', function (Blueprint $table) {
            $table->dropIndex('idx_media_folders_fileable_alive');
            $table->dropIndex('idx_media_folders_tree_browse');
        });

        Schema::table('media_file_tag', function (Blueprint $table) {
            $table->dropIndex('idx_file_tag_reverse');
        });

        Schema::table('media_folder_tag', function (Blueprint $table) {
            $table->dropIndex('idx_folder_tag_reverse');
        });

        Schema::table('media_file_metadata', function (Blueprint $table) {
            $table->dropIndex('idx_file_meta_field');
        });

        Schema::table('media_file_versions', function (Blueprint $table) {
            $table->dropIndex('idx_file_versions_user');
        });

        Schema::table('media_tags', function (Blueprint $table) {
            $table->dropIndex('idx_media_tags_parent');
        });
    }
};
