<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            if (!Schema::hasColumn('media_files', 'fileable_type')) {
                $table->nullableMorphs('fileable');
            }
            if (!Schema::hasColumn('media_files', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable();
            }
            if (!Schema::hasColumn('media_files', 'updated_by_user_id')) {
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
            }
        });

        Schema::table('media_folders', function (Blueprint $table) {
            if (!Schema::hasColumn('media_folders', 'fileable_type')) {
                $table->nullableMorphs('fileable');
            }
            if (!Schema::hasColumn('media_folders', 'created_by_user_id')) {
                $table->unsignedBigInteger('created_by_user_id')->nullable();
            }
            if (!Schema::hasColumn('media_folders', 'updated_by_user_id')) {
                $table->unsignedBigInteger('updated_by_user_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('media_files', function (Blueprint $table) {
            if (Schema::hasColumn('media_files', 'fileable_type')) {
                $table->dropMorphs('fileable');
            }
            if (Schema::hasColumn('media_files', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
            if (Schema::hasColumn('media_files', 'updated_by_user_id')) {
                $table->dropColumn('updated_by_user_id');
            }
        });

        Schema::table('media_folders', function (Blueprint $table) {
            if (Schema::hasColumn('media_folders', 'fileable_type')) {
                $table->dropMorphs('fileable');
            }
            if (Schema::hasColumn('media_folders', 'created_by_user_id')) {
                $table->dropColumn('created_by_user_id');
            }
            if (Schema::hasColumn('media_folders', 'updated_by_user_id')) {
                $table->dropColumn('updated_by_user_id');
            }
        });
    }
};
