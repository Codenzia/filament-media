import { Helpers } from '../../filament-media-helpers'

export class MediaDetails {
    constructor() {
        this.$detailsWrapper = $('.fm-media-main .fm-media-details')

        this.descriptionItemTemplate = `<div class="mb-3 fm-media-name">
            <label class="form-label">__title__</label>
            __url__
        </div>`

        this.onlyFields = [
            'name',
            'alt',
            'full_url',
            'size',
            'mime_type',
            'created_at',
            'updated_at',
            'nothing_selected',
        ]
    }

    renderData(data) {
        const _self = this
        const thumb = data.type === 'image' && data.full_url ? `<img src="${data.full_url}" alt="${data.name}">` : data.icon
        let description = ''
        Helpers.forEach(data, (val, index) => {
            if (Helpers.inArray(_self.onlyFields, index) && val) {
                if (!Helpers.inArray(['mime_type'], index)) {

                    description += _self.descriptionItemTemplate
                        .replace(/__title__/gi, Helpers.trans(index))
                        .replace(/__url__/gi,
                        val
                            ? index === 'full_url'
                                ? `<div class="flex items-center gap-2 pe-1">
                                        <input
                                            type="text"
                                            id="file_details_url"
                                            class="block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm text-gray-900 shadow-sm focus:border-primary-500 focus:ring-2 focus:ring-primary-500/40 dark:border-white/10 dark:bg-gray-900 dark:text-white"
                                            value="${val}"
                                            readonly
                                        />
                                        <button
                                            class="inline-flex items-center justify-center rounded-lg border border-gray-300 bg-gray-50 px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary-500/40 dark:border-white/10 dark:bg-gray-800 dark:text-gray-200 dark:hover:bg-gray-700 js-btn-copy-to-clipboard"
                                            type="button"
                                            data-bb-toggle="clipboard"
                                            data-clipboard-action="copy"
                                            data-clipboard-message="Copied"
                                            data-clipboard-target="#file_details_url"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-clipboard mr-0 h-5 w-5" data-clipboard-icon="true" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                                               <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                               <path d="M9 5h-2a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-12a2 2 0 0 0 -2 -2h-2"></path>
                                               <path d="M9 3m0 2a2 2 0 0 1 2 -2h2a2 2 0 0 1 2 2v0a2 2 0 0 1 -2 2h-2a2 2 0 0 1 -2 -2z"></path>
                                            </svg>
                                            <svg class="icon mr-0 h-5 w-5 text-success hidden" data-clipboard-success-icon="true" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                              <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                                              <path d="M5 12l5 5l10 -10"></path>
                                            </svg>
                                        </button>
                                    </div>`
                                : `<span title="${val}">${val}</span>`
                            : ''
                    )
                }
            }
        })

        _self.$detailsWrapper.find('.fm-media-thumbnail').html(thumb)
        _self.$detailsWrapper.find('.fm-media-thumbnail').css('color', data.color)
        _self.$detailsWrapper.find('.fm-media-description').html(description)

        const $copyButton = _self.$detailsWrapper.find('.js-btn-copy-to-clipboard')
        $copyButton.off('click.media-copy').on('click.media-copy', function (event) {
            event.preventDefault()
            const $btn = $(this)
            const target = $btn.data('clipboard-target')
            const $target = target ? $(target) : null
            const value = $target?.val?.() || $target?.text?.() || ''

            if (!value) {
                return
            }

            window.FilamentMedia.copyToClipboard(value, _self.$detailsWrapper[0])

            const $icon = $btn.find('[data-clipboard-icon]')
            const $successIcon = $btn.find('[data-clipboard-success-icon]')

            $icon.addClass('hidden')
            $successIcon.removeClass('hidden')

            setTimeout(() => {
                $successIcon.addClass('hidden')
                $icon.removeClass('hidden')
            }, 1200)
        })

        let dimensions = ''

        if (data.mime_type && data.mime_type.indexOf('image') !== -1) {
            const image = new Image()
            image.src = data.full_url

            image.onload = () => {
                dimensions += this.descriptionItemTemplate
                    .replace(/__title__/gi, Helpers.trans('width'))
                    .replace(/__url__/gi, `<span title="${image.width}">${image.width}px</span>`)

                dimensions += this.descriptionItemTemplate
                    .replace(/__title__/gi, Helpers.trans('height'))
                    .replace(/__url__/gi, `<span title="${image.height}">${image.height}px</span>`)

                _self.$detailsWrapper.find('.fm-media-description').append(dimensions)
            }
        }
    }
}
