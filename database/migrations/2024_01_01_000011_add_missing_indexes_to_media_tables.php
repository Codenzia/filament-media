<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            // Index for soft delete queries
            if (!$this->indexExists('media_files', 'media_files_deleted_at_index')) {
                $table->index('deleted_at', 'media_files_deleted_at_index');
            }
        });

        // Add type index if column exists
        if (Schema::hasColumn('media_files', 'type')) {
            Schema::table('media_files', function (Blueprint $table) {
                if (!$this->indexExists('media_files', 'media_files_type_index')) {
                    $table->index('type', 'media_files_type_index');
                }
            });
        }

        // Add visibility index if column exists
        if (Schema::hasColumn('media_files', 'visibility')) {
            Schema::table('media_files', function (Blueprint $table) {
                if (!$this->indexExists('media_files', 'media_files_visibility_index')) {
                    $table->index('visibility', 'media_files_visibility_index');
                }
            });
        }

        Schema::table('media_folders', function (Blueprint $table) {
            // Index for soft delete queries
            if (!$this->indexExists('media_folders', 'media_folders_deleted_at_index')) {
                $table->index('deleted_at', 'media_folders_deleted_at_index');
            }

            // Index for slug lookups
            if (!$this->indexExists('media_folders', 'media_folders_slug_index')) {
                $table->index('slug', 'media_folders_slug_index');
            }
        });

        Schema::table('media_settings', function (Blueprint $table) {
            // Index for key lookups
            if (!$this->indexExists('media_settings', 'media_settings_key_index')) {
                $table->index('key', 'media_settings_key_index');
            }

            // Index for user_id lookups
            if (!$this->indexExists('media_settings', 'media_settings_user_id_index')) {
                $table->index('user_id', 'media_settings_user_id_index');
            }

            // Composite index for common queries
            if (!$this->indexExists('media_settings', 'media_settings_key_user_id_index')) {
                $table->index(['key', 'user_id'], 'media_settings_key_user_id_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            $table->dropIndex('media_files_deleted_at_index');
        });

        if (Schema::hasColumn('media_files', 'type')) {
            Schema::table('media_files', function (Blueprint $table) {
                $table->dropIndex('media_files_type_index');
            });
        }

        if (Schema::hasColumn('media_files', 'visibility')) {
            Schema::table('media_files', function (Blueprint $table) {
                $table->dropIndex('media_files_visibility_index');
            });
        }

        Schema::table('media_folders', function (Blueprint $table) {
            $table->dropIndex('media_folders_deleted_at_index');
            $table->dropIndex('media_folders_slug_index');
        });

        Schema::table('media_settings', function (Blueprint $table) {
            $table->dropIndex('media_settings_key_index');
            $table->dropIndex('media_settings_user_id_index');
            $table->dropIndex('media_settings_key_user_id_index');
        });
    }

    /**
     * Check if an index exists on a table (Laravel 11+ compatible).
     */
    protected function indexExists(string $table, string $indexName): bool
    {
        $connection = Schema::getConnection();
        $driverName = $connection->getDriverName();

        try {
            if ($driverName === 'sqlite') {
                $indexes = $connection->select("PRAGMA index_list('{$table}')");
                foreach ($indexes as $index) {
                    if ($index->name === $indexName) {
                        return true;
                    }
                }
                return false;
            }

            if ($driverName === 'mysql') {
                $indexes = $connection->select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);
                return count($indexes) > 0;
            }

            if ($driverName === 'pgsql') {
                $indexes = $connection->select(
                    "SELECT indexname FROM pg_indexes WHERE tablename = ? AND indexname = ?",
                    [$table, $indexName]
                );
                return count($indexes) > 0;
            }

            // For other drivers, assume index doesn't exist and let it fail gracefully
            return false;
        } catch (\Exception $e) {
            // If we can't check, assume it doesn't exist
            return false;
        }
    }
};
