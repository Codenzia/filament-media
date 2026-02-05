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
        // this.UploadService = new UploadService()
        this.FolderService = new FolderService()
        this.DownloadService = new DownloadService()
        this.UploadService = new UploadService()

        this.$body = $('body')
        this._cleanupFolderCreated = null
        this.keys = { ctrl: false, meta: false, shift: false }
    }

    init() {
        if (!document.querySelector('.fm-media-container')) return

        console.debug('Init: resetPagination');
        Helpers.resetPagination()
        this.setupLayout()
        this.handleMediaList()
        this.changeViewType()
        this.changeFilter()
        
        console.debug('Init: getMedia');
        this.MediaService.getMedia(true, false)
        this.search()
        this.handleActions()
        
        if (document.querySelector('.fm-media-items')) {
            this.UploadService.init()
        }
        
        this.scrollGetMore()
        this.setupLivewireListeners()
    }

    setupLivewireListeners() {
        const registerListener = () => {
            if (typeof this._cleanupFolderCreated === 'function') {
                this._cleanupFolderCreated()
                this._cleanupFolderCreated = null
            }

            if (typeof window.Livewire !== 'undefined') {
                console.debug('Livewire: Registering listener');
                this._cleanupFolderCreated = Livewire.on('media-folder-created', (data) => {
                    console.debug('Livewire: media-folder-created triggered', data);
                    Helpers.resetPagination()
                    this.MediaService.getMedia(true)
                })
            }
        }

        if (typeof Livewire !== 'undefined') {
            registerListener()
        } else {
            document.removeEventListener('livewire:initialized', registerListener)
            document.addEventListener('livewire:initialized', registerListener)
        }
    }

    setupLayout() {
        /**
         * Sidebar
         */
        const params = Helpers.getRequestParams()
        
        const updateActiveState = (type, value) => {
            const $item = $(`.js-fm-media-change-filter[data-type="${type}"][data-value="${value}"]`)
            if ($item.length) {
                $item.closest('button.dropdown-item').addClass('active')
                    .closest('.dropdown').find('.js-fm-media-filter-current').html(`(${$item.text().trim()})`)
            }
        }

        updateActiveState('filter', params.filter)
        updateActiveState('view_in', params.view_in)
        updateActiveState('sort_by', params.sort_by)

        if (Helpers.isUseInModal()) {
            $('.fm-media-footer').removeClass('d-none')
        }

        /**
         * Details pane
         */
        let $mediaDetailsCheckbox = $('#media_details_collapse')
        if ($mediaDetailsCheckbox.length) {
            $mediaDetailsCheckbox.prop('checked', MediaConfig.hide_details_pane || false)
            
            setTimeout(() => {
                $('.fm-media-details').show()
            }, 300)

            $mediaDetailsCheckbox.off('change').on('change', (event) => {
                MediaConfig.hide_details_pane = $(event.currentTarget).is(':checked')
                Helpers.storeConfig()
            })
        }
    }

    handleMediaList() {
        const _self = this

        /*Ctrl key in Windows, Command key in MAC, Shift key*/
        $(document).off('keyup keydown').on('keyup keydown', (e) => {
            if (!e) return
            _self.keys.ctrl = e.ctrlKey || false
            _self.keys.meta = e.metaKey || false
            _self.keys.shift = e.shiftKey || false
        })

        _self.$body
            .off('click', '.js-media-list-title')
            .on('click', '.js-media-list-title', (event) => {
                if (event) event.preventDefault()
                const $current = $(event.currentTarget)
                const $items = $('.fm-media-items li')

                if (_self.keys.shift) {
                    const selected = Helpers.getSelectedItems()
                    const firstItem = Helpers.arrayFirst(selected)
                    
                    if (firstItem && typeof firstItem.index_key !== 'undefined') {
                        const firstIndex = firstItem.index_key
                        const currentIndex = $current.index()
                        const start = Math.min(firstIndex, currentIndex)
                        const end = Math.max(firstIndex, currentIndex)

                        $items.each((index, el) => {
                            if (index >= start && index <= end) {
                                $(el).find('input[type=checkbox]').prop('checked', true)
                            }
                        })
                    }
                } else if (!_self.keys.ctrl && !_self.keys.meta) {
                    $items.find('input[type=checkbox]').prop('checked', false)
                }

                let $lineCheckBox = $current.find('input[type=checkbox]')
                let wasChecked = $lineCheckBox.prop('checked')
                $lineCheckBox.prop('checked', true)
                
                console.debug('MediaList: handleDropdown');
                ActionsService.handleDropdown(!wasChecked)
                _self.MediaService.getFileDetails($current.data() || {})

                // Add to recent items when a file is clicked
                if ($current.data('is_folder') !== true) {
                    Helpers.addToRecent($current.data('id'))
                }
            })
            .on('dblclick doubletap', '.js-media-list-title', (event) => {
                if (event) event.preventDefault()
                const data = $(event.currentTarget).data()
                if (data && data.is_folder === true) {
                    Helpers.resetPagination()
                    _self.FolderService.changeFolderAndAddToRecent(data.id)
                } else {
                    ActionsService.handlePreview()
                }
                return false
            })
            .on('click', '.js-up-one-level', (event) => {
                if (event) event.preventDefault()
                let count = $('.fm-media-breadcrumb .breadcrumb li').length
                if (count > 1) {
                    $(`.fm-media-breadcrumb .breadcrumb li:nth-child(${count - 1}) a`).trigger('click')
                }
            })
            .on('contextmenu', '.js-context-menu', (event) => {
                if (!$(event.currentTarget).find('input[type=checkbox]').is(':checked')) {
                    $(event.currentTarget).trigger('click')
                }
            })
            .on('click contextmenu', '.fm-media-items', (e) => {
                if (e && !$(e.target).closest('.js-context-menu').length) {
                    $('.fm-media-items input[type="checkbox"]').prop('checked', false)
                    ActionsService.handleDropdown()
                    _self.MediaService.getFileDetails({
                        icon: `<svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"></path><path d="M15 8h.01"></path><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"></path><path d="M3 16l5 -5c.928 -.893 2.072 -.893 3 0l5 5"></path><path d="M14 14l1 -1c.928 -.893 2.072 -.893 3 0l3 3"></path></svg>`,
                        nothing_selected: '',
                    })
                }
            })
    }

    changeViewType() {
        const _self = this
        _self.$body
            .off('click', '.js-fm-media-change-view-type button')
            .on('click', '.js-fm-media-change-view-type button', (event) => {
                if (event) event.preventDefault()
                const $current = $(event.currentTarget)
                if ($current.hasClass('active')) return

                $current.closest('.js-fm-media-change-view-type').find('button').removeClass('active')
                $current.addClass('active')

                MediaConfig.request_params.view_type = $current.data('type')
                $('.js-insert-to-editor').prop('disabled', $current.data('type') === 'trash')

                Helpers.storeConfig()
                if (MediaConfig.pagination) MediaConfig.pagination.paged = 1
                
                _self.MediaService.getMedia(true, false)
            })

        const currentParams = Helpers.getRequestParams()
        $(`.js-fm-media-change-view-type .btn[data-type="${currentParams.view_type}"]`).trigger('click')
        this.bindIntegrateModalEvents()
    }

    changeFilter() {
        const _self = this
        _self.$body.off('click', '.js-fm-media-change-filter').on('click', '.js-fm-media-change-filter', (event) => {
            if (event) event.preventDefault()
            if (Helpers.isOnAjaxLoading()) return

            const $current = $(event.currentTarget)
            const data = $current.data() || {}

            MediaConfig.request_params[data.type] = data.value

            if (window.FilamentMedia.options && data.type === 'view_in') {
                window.FilamentMedia.options.view_in = data.value
            }

            if (data.type === 'view_in') {
                MediaConfig.request_params.folder_id = 0
                $('.js-insert-to-editor').prop('disabled', data.value === 'trash')
            }

            // Update Labels
            $current.closest('.dropdown').find('.js-fm-media-filter-current').html(`(${$current.text().trim()})`)
            $current.addClass('active').siblings().removeClass('active')

            Helpers.storeConfig()
            
            if (MediaService.refreshFilter) {
                MediaService.refreshFilter()
            }
            
            Helpers.resetPagination()
            _self.MediaService.getMedia(true)
        })
    }

    search() {
        const _self = this
        const currentSearch = Helpers.getRequestParams().search || ''
        const $searchForm = $('.fm-media-search form')
        const $searchInput = $('.fm-media-search input[name="search"]')
        
        $searchInput.val(currentSearch)
        
        _self.$body.off('submit', '.fm-media-search form').on('submit', '.fm-media-search form', (event) => {
            if (event) event.preventDefault()
            MediaConfig.request_params.search = $(event.currentTarget).find('input[name="search"]').val()
            Helpers.storeConfig()
            Helpers.resetPagination()
            _self.MediaService.getMedia(true)
        })
    }

    handleActions() {
        const _self = this
        _self.$body
            .off('click', '.fm-media-actions .js-change-action[data-type="refresh"]')
            .on('click', '.fm-media-actions .js-change-action[data-type="refresh"]', (event) => {
                if (event) event.preventDefault()
                Helpers.resetPagination()
                const fmData = window.FilamentMedia?.$el?.data('fm-media')
                const hasSelectedFile = fmData?.[0]?.selected_file_id !== undefined
                _self.MediaService.getMedia(true, hasSelectedFile)
            })
            .off('click', '.fm-media-items li.no-items')
            .on('click', '.fm-media-items li.no-items', (e) => {
                if (e) e.preventDefault()
                $('.fm-media-header .fm-media-top-header .fm-media-actions .js-dropzone-upload').first().trigger('click')
            })
            .off('submit', '.form-add-folder')
            .on('submit', '.form-add-folder', (event) => {
                if (event) event.preventDefault()
                const $input = $(event.currentTarget).find('input[name="name"]')
                _self.FolderService.create($input.val())
                $input.val('')
            })
            .off('click', '.js-change-folder')
            .on('click', '.js-change-folder', (event) => {
                if (event) event.preventDefault()
                Helpers.resetPagination()
                _self.FolderService.changeFolderAndAddToRecent($(event.currentTarget).data('folder'))
            })
            .off('click', '.js-files-action')
            .on('click', '.js-files-action', (event) => {
                if (event) event.preventDefault()
                ActionsService.handleGlobalAction($(event.currentTarget).data('action'), () => {
                    Helpers.resetPagination()
                    _self.MediaService.getMedia(true)
                })
            })
            .off('submit', '.form-download-url')
            .on('submit', '.form-download-url', async (event) => {
                if (event) event.preventDefault()
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

                await _self.DownloadService.download(url, (progress, item, currentUrl) => {
                    let $noticeItem = $(`<div class="p-2 text-primary"><span>${item}</span></div>`)
                    $notice.append($noticeItem).scrollTop($notice[0].scrollHeight)
                    $header.html(`${$header.data('downloading')} (${progress})`)

                    return (success, message = '') => {
                        if (!success) remainUrls.push(currentUrl)
                        $noticeItem.find('span').text(`${item}: ${message}`)
                        $noticeItem.attr('class', `py-2 text-${success ? 'success' : 'danger'}`)
                    }
                }, () => {
                    $wrapper.slideDown()
                    $input.val(remainUrls.join('\n')).prop('disabled', false)
                    $header.html($header.data('text'))
                    MediaManagement.hideButtonLoading($button)
                })
            })
    }

    checkFileTypeSelect(selectedFiles) {
        if (window.FilamentMedia && window.FilamentMedia.$el) {
            const fmData = window.FilamentMedia.$el.data('fm-media')
            const ele_options = fmData ? fmData[0] : null
            const firstItem = Helpers.arrayFirst(selectedFiles)
            
            if (ele_options && firstItem && firstItem.type) {
                if (ele_options.file_type && !ele_options.file_type.match(firstItem.type)) return false
                if (ele_options.ext_allowed && Array.isArray(ele_options.ext_allowed)) {
                    if ($.inArray(firstItem.mime_type, ele_options.ext_allowed) === -1) return false
                }
            }
        }
        return true
    }

    bindIntegrateModalEvents() {
        const $mainModal = $('#filament_media_modal')
        const _self = this

        $mainModal.off('click', '.js-insert-to-editor').on('click', '.js-insert-to-editor', (e) => {
            if (e) e.preventDefault()
            const selectedFiles = Helpers.getSelectedFiles()
            if (Helpers.size(selectedFiles) > 0) {
                if (window.FilamentMedia.options && typeof window.FilamentMedia.options.onSelectFiles === 'function') {
                    window.FilamentMedia.options.onSelectFiles(selectedFiles, window.FilamentMedia.$el)
                }
                if (_self.checkFileTypeSelect(selectedFiles)) {
                    $mainModal.find('.btn-close').trigger('click')
                }
            }
        })

        $mainModal.off('dblclick doubletap', '.js-media-list-title[data-context="file"]')
            .on('dblclick doubletap', '.js-media-list-title[data-context="file"]', (e) => {
                if (e) e.preventDefault()
                const configs = Helpers.getConfigs()
                if (configs && configs.request_params && configs.request_params.view_in !== 'trash') {
                    const selectedFiles = Helpers.getSelectedFiles()
                    if (Helpers.size(selectedFiles) > 0) {
                        if (window.FilamentMedia.options && typeof window.FilamentMedia.options.onSelectFiles === 'function') {
                            window.FilamentMedia.options.onSelectFiles(selectedFiles, window.FilamentMedia.$el)
                        }
                        if (_self.checkFileTypeSelect(selectedFiles)) {
                            $mainModal.find('.btn-close').trigger('click')
                        }
                    }
                } else {
                    ActionsService.handlePreview()
                }
            })
    }

    scrollGetMore() {
        const _self = this
        const $mediaList = $('.fm-media-main .fm-media-items')

        $mediaList.off('wheel scroll').on('wheel scroll', function (e) {
            const $target = $(e.currentTarget)
            if (!$target.length) return
            const threshold = $target.closest('.media-modal').length > 0 ? 450 : 150
            const loadMore = $target.scrollTop() + $target.innerHeight() >= $target[0].scrollHeight - threshold

            if (loadMore && MediaConfig.pagination && MediaConfig.pagination.has_more) {
                _self.MediaService.getMedia(false, false, true)
            }
        })
    }

    static lightbox(items) {
        if (!items || !items.length) return
        let currentIndex = 0
        const $overlay = $('<div id="filament-media-lightbox"></div>').css({
            position: 'fixed', top: 0, left: 0, width: '100%', height: '100%',
            backgroundColor: 'rgba(0, 0, 0, 0.9)', zIndex: 99999, display: 'flex',
            alignItems: 'center', justifyContent: 'center', flexDirection: 'column',
        })
        const $close = $('<button type="button">&times;</button>').css({
            position: 'absolute', top: '20px', right: '30px', background: 'transparent',
            border: 'none', color: '#fff', fontSize: '30px', cursor: 'pointer'
        })
        const $content = $('<div></div>').css({ maxWidth: '90%', maxHeight: '90%' })
        const $prev = $('<button type="button">&lt;</button>').css({ position: 'absolute', left: '5%', color: '#fff', fontSize: '40px', background: 'none', border: 'none' })
        const $next = $('<button type="button">&gt;</button>').css({ position: 'absolute', right: '5%', color: '#fff', fontSize: '40px', background: 'none', border: 'none' })

        const showItem = (index) => {
            $content.empty()
            const item = items[index]
            if (item instanceof HTMLElement) {
                $content.append(item)
            } else {
                $content.append($('<img>').attr('src', item).css({ maxWidth: '100%', maxHeight: '90vh' }))
            }
            $prev.toggle(index > 0)
            $next.toggle(index < items.length - 1)
        }

        $overlay.append($close, $content, $prev, $next).appendTo('body')
        showItem(currentIndex)

        const close = () => { $overlay.remove(); $(document).off('keydown.lightbox') }
        $close.on('click', close)
        $overlay.on('click', (e) => { if (e.target === $overlay[0]) close() })
        $prev.on('click', (e) => { if (e) e.stopPropagation(); showItem(--currentIndex) })
        $next.on('click', (e) => { if (e) e.stopPropagation(); showItem(++currentIndex) })
        $(document).on('keydown.lightbox', (e) => {
            if (e.key === 'Escape') close()
            if (e.key === 'ArrowLeft' && currentIndex > 0) showItem(--currentIndex)
            if (e.key === 'ArrowRight' && currentIndex < items.length - 1) showItem(++currentIndex)
        })
    }

    static showButtonLoading(element) {
        if (!element) return
        $(element).addClass('btn-loading').attr('disabled', true)
            .prepend('<span class="spinner-border spinner-border-sm me-2"></span>')
            .find('svg').addClass('d-none')
    }

    static hideButtonLoading(element) {
        if (!element) return
        $(element).removeClass('btn-loading').removeAttr('disabled')
            .find('.spinner-border').remove()
        $(element).find('svg').removeClass('d-none')
    }

    static showLoading(element = document.querySelector('.page-wrapper')) {
        const $el = $(element)
        if ($el.length && !$el.find('.loading-spinner').length) {
            $el.addClass('position-relative').append('<div class="loading-spinner"></div>')
        }
    }

    static hideLoading(element = document.querySelector('.page-wrapper')) {
        $(element).removeClass('position-relative').find('.loading-spinner').remove()
    }

    static async copyToClipboard(text, target = document.body) {
        try {
            if (navigator.clipboard && navigator.clipboard.writeText && window.isSecureContext) {
                await navigator.clipboard.writeText(text)
            } else {
                const area = document.createElement('textarea')
                area.value = text
                area.style.position = 'fixed'
                area.style.left = '-9999px'
                target.appendChild(area)
                area.select()
                document.execCommand('copy')
                area.remove()
            }
        } catch (err) { console.error('Copy failed', err) }
    }

    static showNotice(type, message, header = '') {
        const key = `${type}.${message}`
        if (this.noticesTimeout[key]) clearTimeout(this.noticesTimeout[key])
        this.noticesTimeout[key] = setTimeout(() => {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: {
                    status: type === 'error' ? 'danger' : 'success',
                    message: header ? `${header}: ${message}` : message,
                    duration: 5000
                }
            }))
        }, 200)
    }
}

const initMediaManagement = () => {
    // 1. Check if the container exists
    if (!document.querySelector('.fm-media-container')) return
    
    // 2. Safely check for jQuery before using $
    const $ = window.jQuery || window.$;
    if (!$) {
        console.warn('FilamentMedia: Waiting for jQuery...');
        setTimeout(initMediaManagement, 100);
        return;
    }

    window.FilamentMedia = window.FilamentMedia || {}
    Object.assign(window.FilamentMedia, {
        copyToClipboard: MediaManagement.copyToClipboard,
        showButtonLoading: MediaManagement.showButtonLoading,
        hideButtonLoading: MediaManagement.hideButtonLoading,
        showLoading: MediaManagement.showLoading,
        hideLoading: MediaManagement.hideLoading,
        showNotice: MediaManagement.showNotice,
        lightbox: MediaManagement.lightbox
    })

    // Now it's safe to use $
    $(() => {
        new MediaManagement().init();
    });
}

// Start the check
initMediaManagement();

// Keep the Livewire listener for SPA navigation
document.addEventListener('livewire:navigated', initMediaManagement);