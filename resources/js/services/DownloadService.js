/**
 * FilamentMedia Download Service
 *
 * Vanilla JS service for handling file downloads.
 *
 * @example
 * const downloader = new DownloadService();
 * downloader.download('/media/files/image.jpg', 'my-image.jpg');
 */
export class DownloadService {
    /**
     * Create a download service instance.
     *
     * @param {Object} options - Configuration options
     * @param {string} options.downloadUrl - Base URL for downloads (optional)
     */
    constructor(options = {}) {
        this.downloadUrl = options.downloadUrl || null;
        this.csrfToken = null;
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
     * Download a file using a hidden link.
     *
     * @param {string} url - The file URL
     * @param {string} filename - Optional filename for the download
     */
    download(url, filename = '') {
        const link = document.createElement('a');
        link.href = url;
        link.download = filename;
        link.style.display = 'none';

        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }

    /**
     * Download a file via POST request (for files requiring authentication).
     *
     * @param {string} url - The download endpoint URL
     * @param {Object} data - POST data (e.g., { id: 123 })
     * @param {string} filename - Optional filename for the download
     * @returns {Promise<void>}
     */
    async downloadViaPost(url, data = {}, filename = '') {
        const csrfToken = this.getCsrfToken();

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/octet-stream'
                },
                body: JSON.stringify(data)
            });

            if (!response.ok) {
                throw new Error('Download failed');
            }

            const blob = await response.blob();
            const objectUrl = URL.createObjectURL(blob);

            this.download(objectUrl, filename);

            // Clean up the object URL after a delay
            setTimeout(() => URL.revokeObjectURL(objectUrl), 100);
        } catch (error) {
            console.error('Download error:', error);
            throw error;
        }
    }

    /**
     * Download multiple files with a delay between each.
     *
     * @param {Array<{url: string, filename?: string}>} files - Array of file objects
     * @param {number} delay - Delay between downloads in ms (default: 200)
     */
    downloadMultiple(files, delay = 200) {
        files.forEach((file, index) => {
            setTimeout(() => {
                this.download(file.url, file.filename || file.name || '');
            }, index * delay);
        });
    }

    /**
     * Download files as a ZIP archive via POST request.
     *
     * @param {string} url - The ZIP download endpoint URL
     * @param {Array<number|string>} fileIds - Array of file IDs to include
     * @param {string} archiveName - Name for the ZIP file
     * @returns {Promise<void>}
     */
    async downloadAsZip(url, fileIds, archiveName = 'download.zip') {
        return this.downloadViaPost(url, { ids: fileIds }, archiveName);
    }

    /**
     * Open a file in a new tab/window.
     *
     * @param {string} url - The file URL
     */
    openInNewTab(url) {
        window.open(url, '_blank');
    }

    /**
     * Copy a URL to clipboard.
     *
     * @param {string} url - The URL to copy
     * @returns {Promise<boolean>} - Whether the copy was successful
     */
    async copyToClipboard(url) {
        try {
            await navigator.clipboard.writeText(url);
            return true;
        } catch (error) {
            // Fallback for older browsers
            const textarea = document.createElement('textarea');
            textarea.value = url;
            textarea.style.position = 'fixed';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                document.execCommand('copy');
                return true;
            } catch (e) {
                console.error('Copy failed:', e);
                return false;
            } finally {
                document.body.removeChild(textarea);
            }
        }
    }
}

export default DownloadService;
