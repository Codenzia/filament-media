<?php

namespace Codenzia\FilamentMedia\Commands;

use Codenzia\FilamentMedia\Facades\FilamentMedia;
use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SyncMediaCommand extends Command
{
    protected $signature = 'filament-media:sync';

    protected $description = 'Sync files and folders from storage to database';

    protected $disk;

    public function handle(): int
    {
        $this->info('Starting media synchronization...');

        $this->disk = FilamentMedia::getMediaDriver();
        $storage = Storage::disk($this->disk);
        
        // Start from root
        $this->processDirectory($storage, '', 0);

        $this->info('Media synchronization completed successfully.');

        return self::SUCCESS;
    }

    protected function processDirectory($storage, string $path, int|string $parentId)
    {
        // Get all directories in the current path
        $directories = $storage->directories($path);

        foreach ($directories as $directory) {
            $folderName = basename($directory);
            
            // Skip thumbnails or internal folders if any
            if ($folderName === 'thumbnails') {
                continue;
            }

            $this->info("Processing folder: {$directory}");

            // Check if folder exists in DB
            $folder = MediaFolder::firstOrCreate([
                'name' => $folderName,
                'parent_id' => $parentId,
            ], [
                'slug' => MediaFolder::createSlug($folderName, $parentId),
                'user_id' => 0, // System or default user
            ]);

            // Recursively process subdirectories
            $this->processDirectory($storage, $directory, $folder->id);
        }

        // Get all files in the current path
        $files = $storage->files($path);
        
        $sizes = FilamentMedia::getSizes();

        foreach ($files as $filePath) {
            $fileName = basename($filePath);

            // Skip hidden files or specific system files
            if (Str::startsWith($fileName, '.')) {
                continue;
            }

            // Check if it's a thumbnail
            foreach ($sizes as $size) {
                if (Str::endsWith($fileName, '-' . $size . '.' . pathinfo($fileName, PATHINFO_EXTENSION))) {
                    continue 2;
                }
            }

            $this->info("Processing file: {$filePath}");

            // Check if file exists in DB
            $existingFile = MediaFile::where('folder_id', $parentId)
                ->where('name', pathinfo($fileName, PATHINFO_FILENAME))
                ->first();

            if ($existingFile) {
                // Determine if we should check for extension too? 
                // MediaFile 'name' stores filename without extension usually, 
                // but checking the model, 'name' seems to be the name displayed.
                // The 'url' stores the full path or relative path.
                
                // Let's check by URL/path to be safer
                if (MediaFile::where('url', $filePath)->exists()) {
                     continue;
                }
            }

            $mimeType = $storage->mimeType($filePath);
            $size = $storage->size($filePath);
            
            $file = new MediaFile();
            $file->name = pathinfo($fileName, PATHINFO_FILENAME); // Or full name? ModelFactory uses logic.
            // In MediaFile model: createSlug uses name and extension.
            // Let's use the exact file name found.
            
            $file->folder_id = $parentId;
            $file->user_id = 0;
            $file->size = $size;
            $file->mime_type = $mimeType;
            $file->url = $filePath; // Stores relative path to storage root
            $file->visibility = 'public'; // Default
            
            $file->save();
        }
    }
}
