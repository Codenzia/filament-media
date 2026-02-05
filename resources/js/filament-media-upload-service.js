import $ from 'jquery'
import Dropzone from 'dropzone'
import { MediaService } from './filament-media-service'
import { Helpers } from './filament-media-helpers'

Dropzone.autoDiscover = false

export class UploadService {
    constructor() {
        this.$body = $('body')

        this.dropZone = null

        this.uploadUrl = null

        this.uploadProgressBox = $('.fm-upload-progress')

        this.uploadProgressContainer = $('.fm-upload-progress .fm-upload-progress-table')

        this.uploadProgressTemplate = $('#filament_media_upload_progress_item').html()

        this.totalQueued = 1

        this.MediaService = new MediaService()

        this.totalError = 0
    }

    init() {
        
        if (!document.querySelector('.fm-media-items')) {
            return
        }

        if (typeof FilamentMedia_URL !== 'undefined') {
            this.uploadUrl = FilamentMedia_URL.upload_file
        }

        if (typeof FilamentMediaConfig === 'undefined') {
            return
        }

        this.setupDropZone()
        this.handleEvents()
    }

    setupDropZone() {
        let _self = this
        let _dropZoneConfig = this.getDropZoneConfig()
        _self.filesUpload = 0

        const dropzoneElement = document.querySelector('.fm-media-items')
        
        if (!dropzoneElement) {
            return
        }

        if (dropzoneElement.dropzone) {
            dropzoneElement.dropzone.destroy()
        }

        if (_self.dropZone) {
            _self.dropZone.destroy()
        }

        _self.dropZone = new Dropzone(dropzoneElement, {
            ..._dropZoneConfig,
            thumbnailWidth: false,
            thumbnailHeight: false,
            parallelUploads: 1,
            autoQueue: true,
            clickable: '.js-dropzone-upload',
            previewsContainer: false,
            sending: function (file, xhr, formData) {
                formData.append('_token', $('meta[name="csrf-token"]').attr('content'))
                formData.append('folder_id', Helpers.getRequestParams().folder_id)
                formData.append('view_in', Helpers.getRequestParams().view_in)
                formData.append('path', file.fullPath)
            },
            chunksUploaded: (file, done) => {
                const $progressLine = _self.getProgressLine(file)
                if ($progressLine.length) {
                    $progressLine.find('.progress-percent').html(`- <span class="text-info">100%</span>`)
                }
                done()
            },
            accept: (file, done) => {
                _self.filesUpload++
                _self.totalError = 0
                done()
            },
            uploadprogress: (file, progress, bytesSent) => {
                const $progressLine = _self.getProgressLine(file)
                if (!$progressLine.length) {
                    return
                }

                let percent = (bytesSent / file.size) * 100
                if (file.upload.chunked && percent > 99) {
                    percent = percent - 1
                }

                const percentShow = (percent > 100 ? '100' : parseInt(percent)) + '%'
                $progressLine.find('.progress-percent').html(`- <span class="text-info">${percentShow}</span>`)
            },
        })

        _self.dropZone.on('addedfile', (file) => {
            file.index = _self.totalQueued
            _self.totalQueued++
        })

        _self.dropZone.on('sending', (file) => {
            
            _self.initProgress(file.name, file.size, file.index)
        })

        _self.dropZone.on('complete', (file) => {
            if (file.accepted) {
                _self.changeProgressStatus(file)
            }
            _self.filesUpload = 0
        })

        _self.dropZone.on('queuecomplete', () => {
            Helpers.resetPagination()
            _self.MediaService.getMedia(true)
            if (_self.totalError === 0) {
                setTimeout(() => {
                    $('.fm-upload-progress .close-pane').trigger('click')
                }, 1000)
            }
        })
    }

    handleEvents() {
        let _self = this
        /**
         * Close upload progress pane
         */
        _self.$body
            .off('click', '.fm-upload-progress .close-pane')
            .on('click', '.fm-upload-progress .close-pane', (event) => {
                event.preventDefault()
                $('.fm-upload-progress').addClass('hide-the-pane')
                _self.totalError = 0
                setTimeout(() => {
                    _self.uploadProgressContainer.children().remove()
                    _self.totalQueued = 1
                }, 300)
            })
    }

    initProgress($fileName, $fileSize, fileIndex) {
        let template = this.uploadProgressTemplate
            .replace(/__fileName__/gi, $fileName)
            .replace(/__fileSize__/gi, UploadService.formatFileSize($fileSize))
            .replace(/__status__/gi, 'warning')
            .replace(/__message__/gi, 'Uploading...')

        if (this.checkUploadTotalProgress() && this.uploadProgressContainer.children().length >= 1) {
            return
        }

        const $row = $(template)
        $row.attr('data-file-index', fileIndex)

        this.uploadProgressContainer.append($row)
        this.uploadProgressBox.removeClass('hide-the-pane')
        this.uploadProgressContainer.animate({ scrollTop: this.uploadProgressContainer.height() }, 150)
    }

    changeProgressStatus(file) {
        const _self = this

        const $progressLine = _self.getProgressLine(file)

        if (!$progressLine.length) {
            return
        }

        const $label = $progressLine.find('.file-status')

        const response = Helpers.jsonDecode(file.xhr.responseText || '', {})
        const isError = response.error === true || file.status === 'error'
        

        _self.totalError = _self.totalError + (isError ? 1 : 0)

        $label.removeClass('text-success text-danger text-warning')
        $label.addClass(isError ? 'text-danger' : 'text-success')
        $label.html(isError ? 'Error' : 'Uploaded')

        if (isError) {
            $progressLine.find('.progress-percent').html('');
        }

        if (file.status === 'error') {
            if (file.xhr.status === 422) {
                const $errorContainer = $progressLine.find('.file-error').empty();
                $.each(response.errors, (key, item) => {
                    $('<span>').addClass('text-danger').text(item).appendTo($errorContainer);
                    $('<br>').appendTo($errorContainer);
                })
                console.error('error', response.errors);
            } else if (file.xhr.status === 500) {
                $progressLine.find('.file-error').empty().append(
                    $('<span>').addClass('text-danger').text(file.xhr.statusText)
                );
            }

            $progressLine.find('.progress-percent').html('');
        } else if (response.error) {
            console.error('response error message', response.message);
            $progressLine.find('.file-error').empty().append(
                $('<span>').addClass('text-danger').text(response.message)
            );
            $progressLine.find('.progress-percent').html('');
        } else {
            Helpers.addToRecent(response.data.id)
            Helpers.setSelectedFile(response.data.id)
        }
    }

    getProgressLine(file) {
        if (!file?.index) {
            return $()
        }

        return this.uploadProgressContainer.find(`[data-file-index="${file.index}"]`)
    }

    static formatFileSize(bytes, si = false) {
        let thresh = si ? 1000 : 1024
        if (Math.abs(bytes) < thresh) {
            return bytes + ' B'
        }
        let units = ['KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB']
        let u = -1
        do {
            bytes /= thresh
            ++u
        } while (Math.abs(bytes) >= thresh && u < units.length - 1)

        return bytes.toFixed(1) + ' ' + units[u]
    }

    getDropZoneConfig() {
        
        return {
            url: this.uploadUrl,
            uploadMultiple: !FilamentMediaConfig.chunk.enabled,
            chunking: FilamentMediaConfig.chunk.enabled,
            forceChunking: true, // forces chunking when file.size < chunkSize
            parallelChunkUploads: false, // allows chunks to be uploaded in parallel (this is independent of the parallelUploads option)
            chunkSize: FilamentMediaConfig.chunk.chunk_size, // chunk size 1,000,000 bytes (~1MB)
            retryChunks: true, // retry chunks on failure
            retryChunksLimit: 3, // retry maximum of 3 times (default is 3)
            timeout: 0, // MB,
            maxFilesize: FilamentMediaConfig.chunk.max_file_size, // MB
            maxFiles: null, // max files upload,
        }
    }

    checkUploadTotalProgress() {
        return this.filesUpload === 1
    }
}
