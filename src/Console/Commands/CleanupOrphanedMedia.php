<?php

namespace Codenzia\FilamentMedia\Console\Commands;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupOrphanedMedia extends Command
{
    protected $signature = 'media:cleanup
        {--dry-run : Show what would be deleted without actually deleting}
        {--force : Skip confirmation prompt}';

    protected $description = 'Remove database entries for media files that no longer exist on disk';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        $this->info('Scanning for orphaned media entries...');

        $disk = Storage::disk(FilamentMedia::getConfig('disk', 'public'));
        $orphaned = collect();

        // Get all media files from database
        $mediaFiles = MediaFile::withTrashed()->get();
        $total = $mediaFiles->count();

        $this->output->progressStart($total);

        foreach ($mediaFiles as $file) {
            $this->output->progressAdvance();

            // Check if the file exists on disk
            if (!$disk->exists($file->url)) {
                $orphaned->push($file);
            }
        }

        $this->output->progressFinish();

        if ($orphaned->isEmpty()) {
            $this->info('No orphaned media entries found.');
            return self::SUCCESS;
        }

        $this->newLine();
        $this->warn("Found {$orphaned->count()} orphaned entries:");
        $this->newLine();

        // Show table of orphaned files
        $this->table(
            ['ID', 'Name', 'URL', 'Created At'],
            $orphaned->map(fn ($file) => [
                $file->id,
                $file->name,
                $file->url,
                $file->created_at->format('Y-m-d H:i'),
            ])->toArray()
        );

        if ($dryRun) {
            $this->info('Dry run complete. No changes made.');
            return self::SUCCESS;
        }

        if (!$force && !$this->confirm('Do you want to permanently delete these database entries?')) {
            $this->info('Operation cancelled.');
            return self::SUCCESS;
        }

        // Delete orphaned entries
        $deleted = 0;
        foreach ($orphaned as $file) {
            $file->forceDelete();
            $deleted++;
        }

        $this->info("Successfully deleted {$deleted} orphaned media entries.");

        return self::SUCCESS;
    }
}
