import './filament-media-jquery-doubletap'
import { MediaConfig } from './filament-media-config.js'
import { Helpers } from './filament-media-helpers.js'
import { MediaService } from './filament-media-service.js'
import { FolderService } from './filament-media-folder-service.js'
import { UploadService } from './filament-media-upload-service.js'
import { ActionsService } from './filament-media-actions-service.js'
import { DownloadService } from './filament-media-download-service.js'
import { EditorService } from './filament-media-integrate.js'

class MediaManagement {

    static noticesTimeout = {}
    static noticesTimeoutCount = 0

    constructor() {
        this.MediaService = new MediaService()
        this.UploadService = new UploadService()
        this.FolderService = new FolderService()
        this.DownloadService = new DownloadService()

        this.$body = $('body')
    }

    init() {
        Helpers.resetPagination()
        this.setupLayout()
        this.handleMediaList()
        this.changeViewType()
        this.changeFilter()
        this.MediaService.getMedia(true, false)
        this.search()
        this.handleActions()
        this.UploadService.init()
        this.scrollGetMore()

        const registerListener = () => {
            // Unregister if already registered to avoid duplicates
            if (this._cleanupFolderCreated) {
                this._cleanupFolderCreated()
            }

            this._cleanupFolderCreated = Livewire.on('media-folder-created', () => {
                Helpers.resetPagination()
                this.MediaService.getMedia(true)
            })
        }

        if (typeof Livewire !== 'undefined') {
            registerListener()
        } else {
            document.addEventListener('livewire:initialized', registerListener)
        }
    }

    setupLayout() {
        /**
         * Sidebar
         */
        const $currentFilter = $(
            `.js-rv-media-change-filter[data-type="filter"][data-value="${Helpers.getRequestParams().filter}"]`
        )

        $currentFilter
            .closest('button.dropdown-item')
            .addClass('active')
            .closest('.dropdown')
            .find('.js-rv-media-filter-current')
            .html(`(${$currentFilter.html()})`)

        const $currentViewIn = $(
            `.js-rv-media-change-filter[data-type="view_in"][data-value="${Helpers.getRequestParams().view_in}"]`
        )

        $currentViewIn
            .closest('button.dropdown-item')
            .addClass('active')
            .closest('.dropdown')
            .find('.js-rv-media-filter-current')
            .html(`(${$currentViewIn.html()})`)

        if (Helpers.isUseInModal()) {
            $('.rv-media-footer').removeClass('d-none')
        }

        /**
         * Sort
         */
        $(`.js-rv-media-change-filter[data-type="sort_by"][data-value="${Helpers.getRequestParams().sort_by}"]`)
            .closest('button.dropdown-item')
            .addClass('active')

        /**
         * Details pane
         */
        let $mediaDetailsCheckbox = $('#media_details_collapse')
        $mediaDetailsCheckbox.prop('checked', MediaConfig.hide_details_pane || false)
        setTimeout(() => {
            $('.rv-media-details').show()
        }, 300)

        $mediaDetailsCheckbox.on('change', (event) => {
            event.preventDefault()
            MediaConfig.hide_details_pane = $(event.currentTarget).is(':checked')
            Helpers.storeConfig()
        })
    }

    handleMediaList() {
        let _self = this

        /*Ctrl key in Windows*/
        let ctrl_key = false

        /*Command key in MAC*/
        let meta_key = false

        /*Shift key*/
        let shift_key = false

        $(document).on('keyup keydown', (e) => {
            /*User hold ctrl key*/
            ctrl_key = e.ctrlKey
            /*User hold command key*/
            meta_key = e.metaKey
            /*User hold shift key*/
            shift_key = e.shiftKey
        })

        _self.$body
            .off('click', '.js-media-list-title')
            .on('click', '.js-media-list-title', (event) => {
                event.preventDefault()
                let $current = $(event.currentTarget)

                if (shift_key) {
                    let firstItem = Helpers.arrayFirst(Helpers.getSelectedItems())
                    if (firstItem) {
                        let firstIndex = firstItem.index_key
                        let currentIndex = $current.index()
                        $('.rv-media-items li').each((index, el) => {
                            if (index > firstIndex && index <= currentIndex) {
                                $(el).find('input[type=checkbox]').prop('checked', true)
                            }
                        })
                    }
                } else if (!ctrl_key && !meta_key) {
                    $current.closest('.rv-media-items').find('input[type=checkbox]').prop('checked', false)
                }

                let $lineCheckBox = $current.find('input[type=checkbox]')
                let wasChecked = $lineCheckBox.prop('checked')
                $lineCheckBox.prop('checked', true)
                ActionsService.handleDropdown(!wasChecked)

                _self.MediaService.getFileDetails($current.data())

                // Add to recent items when a file is clicked
                if (!$current.data('is_folder')) {
                    Helpers.addToRecent($current.data('id'))
                }
            })
            .on('dblclick doubletap', '.js-media-list-title', (event) => {
                event.preventDefault()
                let data = $(event.currentTarget).data()
                if (data.is_folder === true) {
                    Helpers.resetPagination()
                    _self.FolderService.changeFolderAndAddToRecent(data.id)
                } else {
                    ActionsService.handlePreview()
                }

                return false
            })
            .on('click', '.js-up-one-level', (event) => {
                event.preventDefault()
                let count = $('.rv-media-breadcrumb .breadcrumb li').length
                $(`.rv-media-breadcrumb .breadcrumb li:nth-child(${count - 1}) a`).trigger('click')
            })
            .on('contextmenu', '.js-context-menu', (event) => {
                if (!$(event.currentTarget).find('input[type=checkbox]').is(':checked')) {
                    $(event.currentTarget).trigger('click')
                }
            })
            .on('click contextmenu', '.rv-media-items', (e) => {
                if (!Helpers.size(e.target.closest('.js-context-menu'))) {
                    $('.rv-media-items input[type="checkbox"]').prop('checked', false)

                    ActionsService.handleDropdown()

                    _self.MediaService.getFileDetails({
                        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M15 8h.01"></path>
                            <path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"></path>
                            <path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"></path>
                            <path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"></path>
                        </svg>`,
                        nothing_selected: '',
                    })
                }
            })
    }

    changeViewType() {
        let _self = this
        _self.$body
            .off('click', '.js-rv-media-change-view-type button')
            .on('click', '.js-rv-media-change-view-type button', (event) => {
                event.preventDefault()

                let $current = $(event.currentTarget)

                if ($current.hasClass('active')) {
                    return
                }

                $current.closest('.js-rv-media-change-view-type').find('button').removeClass('active')
                $current.addClass('active')

                MediaConfig.request_params.view_type = $current.data('type')

                if ($current.data('type') === 'trash') {
                    $(document).find('.js-insert-to-editor').prop('disabled', true)
                } else {
                    $(document).find('.js-insert-to-editor').prop('disabled', false)
                }

                Helpers.storeConfig()

                if (typeof MediaConfig.pagination != 'undefined') {
                    if (typeof MediaConfig.pagination.paged != 'undefined') {
                        MediaConfig.pagination.paged = 1
                    }
                }
                console.log('changeViewType', MediaConfig.pagination);
                _self.MediaService.getMedia(true, false)
            })

        $(`.js-rv-media-change-view-type .btn[data-type="${Helpers.getRequestParams().view_type}"]`).trigger('click')

        this.bindIntegrateModalEvents()
    }

    changeFilter() {
        let _self = this
        _self.$body.off('click', '.js-rv-media-change-filter').on('click', '.js-rv-media-change-filter', (event) => {
            event.preventDefault()

            if (!Helpers.isOnAjaxLoading()) {
                let $current = $(event.currentTarget)
                let data = $current.data()

                MediaConfig.request_params[data.type] = data.value

                if (window.FilamentMedia.options && data.type === 'view_in') {
                    window.FilamentMedia.options.view_in = data.value
                }

                if (data.type === 'view_in') {
                    MediaConfig.request_params.folder_id = 0
                    if (data.value === 'trash') {
                        $(document).find('.js-insert-to-editor').prop('disabled', true)
                    } else {
                        $(document).find('.js-insert-to-editor').prop('disabled', false)
                    }
                }

                $current.closest('.dropdown').find('.js-rv-media-filter-current').html(`(${$current.html()})`)

                Helpers.storeConfig()
                MediaService.refreshFilter()

                Helpers.resetPagination()
                _self.MediaService.getMedia(true)

                $current.addClass('active')
                $current.siblings().removeClass('active')
            }
        })
    }

    search() {
        let _self = this
        $('.input-search-wrapper input[type="text"]').val(Helpers.getRequestParams().search || '')
        _self.$body.off('submit', '.input-search-wrapper').on('submit', '.input-search-wrapper', (event) => {
            event.preventDefault()
            MediaConfig.request_params.search = $(event.currentTarget).find('input[name="search"]').val()

            Helpers.storeConfig()
            Helpers.resetPagination()
            _self.MediaService.getMedia(true)
        })
    }

    handleActions() {
        let _self = this

        _self.$body
            .off('click', '.rv-media-actions .js-change-action[data-type="refresh"]')
            .on('click', '.rv-media-actions .js-change-action[data-type="refresh"]', (event) => {
                event.preventDefault()

                Helpers.resetPagination()

                let ele_options =
                    typeof window.FilamentMedia.$el !== 'undefined' ? window.FilamentMedia.$el.data('rv-media') : undefined
                if (
                    typeof ele_options !== 'undefined' &&
                    ele_options.length > 0 &&
                    typeof ele_options[0].selected_file_id !== 'undefined'
                ) {
                    _self.MediaService.getMedia(true, true)
                } else {
                    _self.MediaService.getMedia(true, false)
                }
            })
            .off('click', '.rv-media-items li.no-items')
            .on('click', '.rv-media-items li.no-items', (event) => {
                event.preventDefault()
                $('.rv-media-header .rv-media-top-header .rv-media-actions .js-dropzone-upload').trigger('click')
            })
            .off('submit', '.form-add-folder')
            .on('submit', '.form-add-folder', (event) => {
                event.preventDefault()
                const $input = $(event.currentTarget).find('input[name="name"]')
                const folderName = $input.val()
                _self.FolderService.create(folderName)
                $input.val('')
                return false
            })
            .off('click', '.js-change-folder')
            .on('click', '.js-change-folder', (event) => {
                event.preventDefault()
                let folderId = $(event.currentTarget).data('folder')
                Helpers.resetPagination()
                _self.FolderService.changeFolderAndAddToRecent(folderId)
            })
            .off('click', '.js-files-action')
            .on('click', '.js-files-action', (event) => {
                event.preventDefault()
                ActionsService.handleGlobalAction($(event.currentTarget).data('action'), () => {
                    Helpers.resetPagination()
                    _self.MediaService.getMedia(true)
                })
            })
            .off('submit', '.form-download-url')
            .on('submit', '.form-download-url', async (event) => {
                event.preventDefault()

                const $el = $('#modal_download_url')
                const $wrapper = $el.find('#download-form-wrapper')
                const $notice = $el.find('#modal-notice').empty()
                const $header = $el.find('.modal-title')
                const $input = $el.find('textarea[name="urls"]').prop('disabled', true)
                const $button = $el.find('[type="submit"]')
                const url = $input.val()
                const remainUrls = []

            window.FilamentMedia.showButtonLoading?.($button)

                $wrapper.slideUp()

                // start to download
                await _self.DownloadService.download(
                    url,
                    (progress, item, url) => {
                        let $noticeItem = $(`
                        <div class="p-2 text-primary">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                <path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"></path>
                                <path d="M12 9h.01"></path>
                                <path d="M11 12h1v4h1"></path>
                            </svg>
                            <span>${item}</span>
                        </div>
                    `)
                        $notice.append($noticeItem).scrollTop($notice[0].scrollHeight)
                        $header.html(
                            `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                            <path d="M7 11l5 5l5 -5"></path>
                            <path d="M12 4l0 12"></path>
                        </svg>
                        ${$header.data('downloading')} (${progress})`
                        )
                        return (success, message = '') => {
                            if (!success) {
                                remainUrls.push(url)
                            }
                            $noticeItem.find('span').text(`${item}: ${message}`)
                            $noticeItem
                                .attr('class', `py-2 text-${success ? 'success' : 'danger'}`)
                                .find('i')
                                .attr('class', success ? 'icon heroicon-m-check-circle' : 'icon heroicon-m-x-circle')
                        }
                    },
                    () => {
                        $wrapper.slideDown()
                        $input.val(remainUrls.join('\n')).prop('disabled', false)
                        $header.html(`<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                            <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                            <path d="M7 11l5 5l5 -5"></path>
                            <path d="M12 4l0 12"></path>
                        </svg>
                        ${$header.data('text')}
                    `)
                        FilamentMedia.hideButtonLoading($button)
                    }
                )
                return false
            })
    }

    checkFileTypeSelect(selectedFiles) {
        if (typeof window.FilamentMedia.$el !== 'undefined') {
            let firstItem = Helpers.arrayFirst(selectedFiles)
            let ele_options = window.FilamentMedia.$el.data('rv-media')
            if (
                typeof ele_options !== 'undefined' &&
                typeof ele_options[0] !== 'undefined' &&
                typeof ele_options[0].file_type !== 'undefined' &&
                firstItem !== 'undefined' &&
                firstItem.type !== 'undefined'
            ) {
                if (!ele_options[0].file_type.match(firstItem.type)) {
                    return false
                } else {
                    if (typeof ele_options[0].ext_allowed !== 'undefined' && $.isArray(ele_options[0].ext_allowed)) {
                        if ($.inArray(firstItem.mime_type, ele_options[0].ext_allowed) === -1) {
                            return false
                        }
                    }
                }
            }
        }
        return true
    }

    bindIntegrateModalEvents() {
        let $mainModal = $('#rv_media_modal')
        let _self = this
        $mainModal.off('click', '.js-insert-to-editor').on('click', '.js-insert-to-editor', (event) => {
            event.preventDefault()
            let selectedFiles = Helpers.getSelectedFiles()
            if (Helpers.size(selectedFiles) > 0) {
                window.FilamentMedia.options.onSelectFiles(selectedFiles, window.FilamentMedia.$el)
                if (_self.checkFileTypeSelect(selectedFiles)) {
                    $mainModal.find('.btn-close').trigger('click')
                }
            }
        })

        $mainModal
            .off('dblclick doubletap', '.js-media-list-title[data-context="file"]')
            .on('dblclick doubletap', '.js-media-list-title[data-context="file"]', (event) => {
                event.preventDefault()
                if (Helpers.getConfigs().request_params.view_in !== 'trash') {
                    let selectedFiles = Helpers.getSelectedFiles()
                    if (Helpers.size(selectedFiles) > 0) {
                        window.FilamentMedia.options.onSelectFiles(selectedFiles, window.FilamentMedia.$el)
                        if (_self.checkFileTypeSelect(selectedFiles)) {
                            $mainModal.find('.btn-close').trigger('click')
                        }
                    }
                } else {
                    ActionsService.handlePreview()
                }
            })
    }

    // Scroll get more media
    scrollGetMore() {
        let _self = this
        let $mediaList = $('.rv-media-main .rv-media-items')

        // Handle both mouse wheel and touch scroll events
        $mediaList.on('wheel scroll', function (e) {
            let $target = $(e.currentTarget)
            let scrollHeight = $target[0].scrollHeight
            let scrollTop = $target.scrollTop()
            let innerHeight = $target.innerHeight()

            let threshold = $target.closest('.media-modal').length > 0 ? 450 : 150
            let loadMore = scrollTop + innerHeight >= scrollHeight - threshold

            if (loadMore && FilamentMediaConfig.pagination?.has_more) {
                _self.MediaService.getMedia(false, false, true)
            }
        })
    }

    static lightbox(items) {
        console.log(items);
        if (!items || !items.length) {
            return
        }

        let currentIndex = 0
        const $body = $('body')
        const $overlay = $('<div id="filament-media-lightbox"></div>').css({
            position: 'fixed',
            top: 0,
            left: 0,
            width: '100%',
            height: '100%',
            backgroundColor: 'rgba(0, 0, 0, 0.9)',
            zIndex: 99999,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            flexDirection: 'column',
        })
        const $close = $('<button type="button">&times;</button>').css({
            position: 'absolute',
            top: '20px',
            right: '30px',
            background: 'transparent',
            border: 'none',
            color: '#fff',
            fontSize: '30px',
            cursor: 'pointer',
            zIndex: 100000,
        })
        const $content = $('<div></div>').css({
            maxWidth: '90%',
            maxHeight: '90%',
            display: 'flex',
            justifyContent: 'center',
            alignItems: 'center',
        })
        const $prev = $('<button type="button">&lt;</button>').css({
            position: 'absolute',
            left: '20px',
            top: '50%',
            transform: 'translateY(-50%)',
            background: 'transparent',
            border: 'none',
            color: '#fff',
            fontSize: '40px',
            cursor: 'pointer',
            display: 'none',
        })
        const $next = $('<button type="button">&gt;</button>').css({
            position: 'absolute',
            right: '20px',
            top: '50%',
            transform: 'translateY(-50%)',
            background: 'transparent',
            border: 'none',
            color: '#fff',
            fontSize: '40px',
            cursor: 'pointer',
            display: 'none',
        })

        const showItem = (index) => {
            $content.empty()
            const item = items[index]

            if (item instanceof HTMLElement) {
                $content.append(item)
            } else if (typeof item === 'string') {
                const $img = $('<img>').attr('src', item).css({
                    maxWidth: '100%',
                    maxHeight: '90vh',
                    objectFit: 'contain',
                })
                $content.append($img)
            }

            if (items.length > 1) {
                if (index > 0) $prev.show()
                else $prev.hide()
                if (index < items.length - 1) $next.show()
                else $next.hide()
            }
        }

        $overlay.append($close).append($content)
        if (items.length > 1) {
            $overlay.append($prev).append($next)
        }

        $body.append($overlay)
        showItem(currentIndex)

        const closeLightbox = () => {
            $overlay.remove()
            $(document).off('keydown.lightbox')
        }

        $close.on('click', closeLightbox)
        $overlay.on('click', (e) => {
            if (e.target === $overlay[0]) closeLightbox()
        })

        $prev.on('click', (e) => {
            e.stopPropagation()
            if (currentIndex > 0) showItem(--currentIndex)
        })
        $next.on('click', (e) => {
            e.stopPropagation()
            if (currentIndex < items.length - 1) showItem(++currentIndex)
        })

        $(document).on('keydown.lightbox', (e) => {
            if (e.key === 'Escape') closeLightbox()
            if (e.key === 'ArrowLeft' && currentIndex > 0) showItem(--currentIndex)
            if (e.key === 'ArrowRight' && currentIndex < items.length - 1) showItem(++currentIndex)
        })
    }

    static showButtonLoading(element, overlay = true, position = 'start') {
        if (overlay && element) {
            $(element).addClass('btn-loading').attr('disabled', true)

            return
        }

        const loading = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>'
        const icon = $(element).find('svg')

        if (icon.length) {
            icon.addClass('d-none')
        }

        if (position === 'start') {
            $(element).prepend(loading)
        } else if (position === 'end') {
            $(element).append(loading)
        }
    }

    static hideButtonLoading(element) {
        if (!element) {
            return
        }

        if ($(element).hasClass('btn-loading')) {
            $(element).removeClass('btn-loading').removeAttr('disabled')

            return
        }

        $(element).find('.spinner-border').remove()
        $(element).find('svg').removeClass('d-none')
    }

    /**
     * @param {HTMLElement} element
     */
    static showLoading(element = null) {
        if (!element) {
            element = document.querySelector('.page-wrapper')
        }

        if ($(element).find('.loading-spinner').length) {
            return
        }

        $(element).addClass('position-relative')
        $(element).append('<div class="loading-spinner"></div>')
    }

    static hideLoading(element = null) {
        if (!element) {
            element = document.querySelector('.page-wrapper')
        }

        $(element).removeClass('position-relative')
        $(element).find('.loading-spinner').remove()
    }

    static async copyToClipboard(textToCopy, parentTarget) {
        console.log('copyToClipboard', textToCopy, parentTarget);
        if (navigator.clipboard && window.isSecureContext) {
            await navigator.clipboard.writeText(textToCopy)
        } else {
            this.unsecuredCopyToClipboard(textToCopy, parentTarget)
        }
    }

    static unsecuredCopyToClipboard(textToCopy, parentTarget) {
        parentTarget = parentTarget || document.body
        const textArea = document.createElement('textarea')
        textArea.value = textToCopy
        textArea.style.position = 'absolute'
        textArea.style.left = '-999999px'
        parentTarget.append(textArea)
        textArea.select()

        try {
            document.execCommand('copy')
        } catch (error) {
            console.error('Unable to copy to clipboard', error)
        }

        parentTarget.removeChild(textArea)
    }

    static showNotice(messageType, message, messageHeader = '') {
        // Ensure per-class storage even if called via window.FilamentMedia.showNotice
        MediaManagement.noticesTimeout = MediaManagement.noticesTimeout || {}

        const key = `notices_msg.${messageType}.${message}`

        if (MediaManagement.noticesTimeout[key]) {
            clearTimeout(MediaManagement.noticesTimeout[key])
        }

        MediaManagement.noticesTimeout[key] = setTimeout(() => {
            // Filament listens for `notify` events on window
            window.dispatchEvent(
                new CustomEvent('notify', {
                    detail: {
                        status: messageType === 'error' ? 'danger' : 'success',
                        message: messageHeader ? `${messageHeader}: ${message}` : message,
                        duration: 5000,
                    },
                })
            )
        }, 200)
    }

}

const initMediaManagement = () => {
    window.FilamentMedia = window.FilamentMedia || {}

    // Expose helpers for other modules
    window.FilamentMedia.copyToClipboard = MediaManagement.copyToClipboard
    window.FilamentMedia.showButtonLoading = MediaManagement.showButtonLoading
    window.FilamentMedia.hideButtonLoading = MediaManagement.hideButtonLoading
    window.FilamentMedia.showLoading = MediaManagement.showLoading
    window.FilamentMedia.hideLoading = MediaManagement.hideLoading
    window.FilamentMedia.showNotice = MediaManagement.showNotice
    window.FilamentMedia.lightbox = MediaManagement.lightbox

    new MediaManagement().init()
}

$(initMediaManagement)
document.addEventListener('livewire:navigated', initMediaManagement)
