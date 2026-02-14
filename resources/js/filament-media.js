/**
 * FilamentMedia - JavaScript Module
 *
 * This module provides vanilla JS services for file operations
 * that integrate with Livewire components via callbacks.
 *
 * Services:
 * - UploadService: File uploads with progress tracking
 * - DownloadService: File downloads and clipboard operations
 *
 * Usage with Alpine.js:
 * ```js
 * Alpine.data('myComponent', () => ({
 *     uploader: new FilamentMedia.UploadService({ uploadUrl: '/api/upload' }),
 *     handleUpload(file) {
 *         this.uploader.upload(file, folderId, {
 *             onProgress: (p) => this.$wire.updateProgress(p),
 *             onComplete: () => this.$wire.markComplete(),
 *             onError: (e) => this.$wire.markFailed(e)
 *         });
 *     }
 * }));
 * ```
 */

import { UploadService } from './services/UploadService.js';
import { DownloadService } from './services/DownloadService.js';

// Create global namespace
window.FilamentMedia = window.FilamentMedia || {};

// Export service classes for custom instantiation
window.FilamentMedia.UploadService = UploadService;
window.FilamentMedia.DownloadService = DownloadService;

// Create default instances for convenience
window.FilamentMedia.upload = new UploadService();
window.FilamentMedia.download = new DownloadService();

/**
 * Register Alpine components when Alpine initializes.
 *
 * This must be in the compiled JS bundle (not in @push('scripts'))
 * because Filament loads assets before Alpine initializes.
 */
document.addEventListener('alpine:init', () => {
    /**
     * FilamentMedia Uploader - Alpine.js Component
     *
     * This component integrates with the UploadService module
     * and Livewire for state management.
     */
    Alpine.data('filamentMediaUploader', (config) => ({
        open: false,
        isDragging: false,
        uploadUrl: config.uploadUrl,
        folderId: config.folderId,
        maxSize: config.maxSize,
        allowedTypes: config.allowedTypes,
        uploader: null,

        init() {
            // Initialize the upload service with configuration
            this.uploader = new window.FilamentMedia.UploadService({
                uploadUrl: this.uploadUrl,
                maxFileSize: this.maxSize,
                allowedTypes: this.allowedTypes
            });
        },

        /**
         * Handle files selected via input or drag-and-drop
         */
        handleFiles(files) {
            const $wire = this.$wire;

            Array.from(files).forEach((file, index) => {
                const key = `file-${Date.now()}-${index}`;

                // Add to Livewire queue
                $wire.addToQueue(key, file.name, file.size);

                // Upload using the service
                this.uploader.upload(file, this.folderId, {
                    onProgress: (percent) => {
                        $wire.updateProgress(key, percent);
                    },
                    onComplete: (response) => {
                        $wire.markComplete(key);
                    },
                    onError: (message) => {
                        $wire.markFailed(key, message);
                    }
                });
            });
        },

        /**
         * Handle drag-and-drop
         */
        handleDrop(event) {
            this.isDragging = false;
            const files = event.dataTransfer.files;
            if (files.length) {
                this.handleFiles(files);
            }
        },

        /**
         * Handle file input selection
         */
        handleFileSelect(event) {
            const files = event.target.files;
            if (files.length) {
                this.handleFiles(files);
            }
            event.target.value = ''; // Reset for re-selection
        }
    }));
});

// Export for ES module usage
export { UploadService, DownloadService };
export default {
    UploadService,
    DownloadService
};
