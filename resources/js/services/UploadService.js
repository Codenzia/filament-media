/**
 * FilamentMedia Upload Service
 *
 * Vanilla JS service for handling file uploads with progress tracking.
 * Designed to work with Livewire components via callback integration.
 *
 * @example
 * const uploader = new UploadService({ uploadUrl: '/media/files/upload' });
 * uploader.upload(file, folderId, {
 *     onProgress: (percent) => console.log(`${percent}% uploaded`),
 *     onComplete: (response) => console.log('Done!', response),
 *     onError: (message) => console.error('Failed:', message)
 * });
 */
export class UploadService {
    /**
     * Create an upload service instance.
     *
     * @param {Object} options - Configuration options
     * @param {string} options.uploadUrl - The upload endpoint URL
     * @param {number} options.maxFileSize - Maximum file size in bytes (default: 10MB)
     * @param {string} options.allowedTypes - Comma-separated list of allowed extensions
     */
    constructor(options = {}) {
        this.uploadUrl = options.uploadUrl || '/media/files/upload';
        this.maxFileSize = options.maxFileSize || 10 * 1024 * 1024; // 10MB default
        this.allowedTypes = options.allowedTypes || '*';
        this.csrfToken = null;
        this.activeUploads = new Map();
    }

    /**
     * Get CSRF token from meta tag.
     *
     * @returns {string|null}
     */
    getCsrfToken() {
        if (!this.csrfToken) {
            this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || null;
        }
        return this.csrfToken;
    }

    /**
     * Validate a file before upload.
     *
     * @param {File} file - The file to validate
     * @returns {Object} - { valid: boolean, error?: string }
     */
    validateFile(file) {
        // Check file size
        if (file.size > this.maxFileSize) {
            const maxSizeMB = (this.maxFileSize / 1024 / 1024).toFixed(2);
            return {
                valid: false,
                error: `File too large. Maximum size: ${maxSizeMB} MB`
            };
        }

        // Check file type if restrictions are set
        if (this.allowedTypes !== '*') {
            const extension = file.name.split('.').pop()?.toLowerCase();
            const allowedList = this.allowedTypes.split(',').map(t => t.trim().toLowerCase());

            if (!allowedList.includes(extension)) {
                return {
                    valid: false,
                    error: `File type not allowed. Allowed types: ${this.allowedTypes}`
                };
            }
        }

        return { valid: true };
    }

    /**
     * Upload a single file with progress tracking.
     *
     * @param {File} file - The file to upload
     * @param {number|string} folderId - The target folder ID
     * @param {Object} callbacks - Callback functions
     * @param {Function} callbacks.onProgress - Called with progress percentage (0-100)
     * @param {Function} callbacks.onComplete - Called with server response on success
     * @param {Function} callbacks.onError - Called with error message on failure
     * @returns {XMLHttpRequest} - The XHR object (can be used to abort)
     */
    upload(file, folderId = 0, callbacks = {}) {
        const { onProgress, onComplete, onError } = callbacks;

        // Validate file first
        const validation = this.validateFile(file);
        if (!validation.valid) {
            onError?.(validation.error);
            return null;
        }

        // Check CSRF token
        const csrfToken = this.getCsrfToken();
        if (!csrfToken) {
            onError?.('CSRF token not found. Please refresh the page.');
            return null;
        }

        // Create FormData
        const formData = new FormData();
        formData.append('file[]', file);
        formData.append('folder_id', folderId);

        // Create XHR request
        const xhr = new XMLHttpRequest();
        const uploadId = `upload-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

        this.activeUploads.set(uploadId, xhr);

        xhr.open('POST', this.uploadUrl);
        xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
        xhr.setRequestHeader('Accept', 'application/json');

        // Track upload progress
        xhr.upload.onprogress = (e) => {
            if (e.lengthComputable && onProgress) {
                const percent = Math.round((e.loaded / e.total) * 100);
                onProgress(percent);
            }
        };

        // Handle response
        xhr.onload = () => {
            this.activeUploads.delete(uploadId);

            try {
                const response = JSON.parse(xhr.responseText);

                if (xhr.status === 200 && !response.error) {
                    onComplete?.(response);
                } else {
                    onError?.(response.message || 'Upload failed');
                }
            } catch (e) {
                onError?.('Invalid server response');
            }
        };

        // Handle network errors
        xhr.onerror = () => {
            this.activeUploads.delete(uploadId);
            onError?.('Network error. Please check your connection.');
        };

        // Handle abort
        xhr.onabort = () => {
            this.activeUploads.delete(uploadId);
            onError?.('Upload cancelled');
        };

        // Send the request
        xhr.send(formData);

        return xhr;
    }

    /**
     * Upload multiple files.
     *
     * @param {FileList|File[]} files - Array of files to upload
     * @param {number|string} folderId - The target folder ID
     * @param {Object} callbacks - Callback functions for each file
     * @returns {XMLHttpRequest[]} - Array of XHR objects
     */
    uploadMultiple(files, folderId = 0, callbacks = {}) {
        return Array.from(files).map(file => this.upload(file, folderId, callbacks));
    }

    /**
     * Abort all active uploads.
     */
    abortAll() {
        this.activeUploads.forEach((xhr) => {
            xhr.abort();
        });
        this.activeUploads.clear();
    }

    /**
     * Get the number of active uploads.
     *
     * @returns {number}
     */
    getActiveCount() {
        return this.activeUploads.size;
    }

    /**
     * Format file size for display.
     *
     * @param {number} bytes - Size in bytes
     * @returns {string} - Formatted size string
     */
    static formatSize(bytes) {
        const units = ['B', 'KB', 'MB', 'GB', 'TB'];
        let size = bytes;
        let unitIndex = 0;

        while (size >= 1024 && unitIndex < units.length - 1) {
            size /= 1024;
            unitIndex++;
        }

        return `${size.toFixed(unitIndex > 0 ? 2 : 0)} ${units[unitIndex]}`;
    }
}

export default UploadService;
