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
    /**
     * Folder Location Picker - Alpine.js Component
     *
     * Renders a hierarchical folder tree for selecting
     * a destination folder. Used in the Create Folder modal.
     */
    Alpine.data('folderLocationPicker', (config) => ({
        state: null,
        selectedFolderId: config.initialFolderId,
        flatFolders: [],
        folderMap: {},
        expandedNodes: {},
        breadcrumbs: config.initialBreadcrumbs,

        init() {
            this.state = this.$wire.$entangle(config.statePath);
            this.flattenTree(config.folderTree, 0);
            this.buildFolderMap();
            this.expandPathTo(this.selectedFolderId);

            this.$watch('selectedFolderId', (value) => {
                this.state = value;
                this.breadcrumbs = this.computeBreadcrumbs(value);
            });
        },

        flattenTree(nodes, depth) {
            for (const node of nodes) {
                this.flatFolders.push({
                    id: node.id,
                    name: node.name,
                    color: node.color,
                    parentId: node.parent_id ?? 0,
                    depth: depth,
                    hasChildren: node.children && node.children.length > 0,
                });
                if (node.children && node.children.length > 0) {
                    this.flattenTree(node.children, depth + 1);
                }
            }
        },

        buildFolderMap() {
            this.folderMap = { 0: { id: 0, name: config.initialBreadcrumbs[0]?.name || 'All Media', parentId: null } };
            for (const folder of this.flatFolders) {
                this.folderMap[folder.id] = folder;
            }
        },

        expandPathTo(folderId) {
            let currentId = folderId;
            while (currentId && this.folderMap[currentId]) {
                const folder = this.folderMap[currentId];
                if (folder.parentId !== null && folder.parentId !== undefined) {
                    this.expandedNodes[folder.parentId] = true;
                }
                currentId = folder.parentId;
            }
        },

        computeBreadcrumbs(folderId) {
            const crumbs = [];
            let currentId = folderId;

            while (currentId !== null && currentId !== undefined && this.folderMap[currentId]) {
                crumbs.unshift({
                    id: this.folderMap[currentId].id,
                    name: this.folderMap[currentId].name,
                });
                currentId = this.folderMap[currentId].parentId;
            }

            if (crumbs.length === 0 || crumbs[0].id !== 0) {
                crumbs.unshift({
                    id: 0,
                    name: config.initialBreadcrumbs[0]?.name || 'All Media',
                });
            }

            return crumbs;
        },

        selectFolder(id) {
            this.selectedFolderId = id;
            this.expandPathTo(id);
        },

        toggleExpand(id) {
            this.expandedNodes[id] = !this.expandedNodes[id];
        },

        isVisible(folder) {
            let currentId = folder.parentId;
            while (currentId !== null && currentId !== undefined && currentId !== 0) {
                if (!this.expandedNodes[currentId]) {
                    return false;
                }
                const parent = this.folderMap[currentId];
                if (!parent) return false;
                currentId = parent.parentId;
            }
            // Root-level items (parentId = 0) are always visible
            if (folder.parentId === 0 || folder.parentId === null) {
                return true;
            }
            // Direct children of root need root to be "expanded" (always true)
            return !!this.expandedNodes[folder.parentId];
        },
    }));

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
