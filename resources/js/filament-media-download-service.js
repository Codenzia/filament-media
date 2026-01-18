import { MediaService } from './filament-media-service'
import { MessageService } from './filament-media-message-service'
import { Helpers } from './filament-media-helpers'
import { $httpClient } from './filament-media-http-client'

export class DownloadService {
    constructor() {
        this.MediaService = new MediaService()

        $(document).on('shown.bs.modal', '#modal_download_url', (event) => {
            $(event.currentTarget).find('.form-download-url input[type=text]').focus()
        })
    }

    async download(urls, onProgress, onCompleted) {
        let _self = this

        urls = $.trim(urls).split(/\r?\n/)

        let index = 0
        let hasError = false
        for (const url of urls) {
            let filename = ''
            try {
                filename = new URL(url).pathname.split('/').pop()
            } catch (e) {
                filename = url
            }
            let ok = onProgress(`${index} / ${urls.length}`, filename, url)
            await new Promise((resolve, reject) => {
                $httpClient
                    .make()
                    .post(FilamentMedia_URL.download_url, {
                        folderId: Helpers.getRequestParams().folder_id,
                        url,
                    })
                    .then(({ data }) => {
                        ok(true, data.message || data.data?.message)

                        resolve()
                    })
                    .finally(() => onCompleted())
                    .catch((error) => reject(error))
            })

            index += 1
        }

        Helpers.resetPagination()
        _self.MediaService.getMedia(true)
        if (!hasError) {
            DownloadService.closeModal()
            MessageService.showMessage('success', Helpers.trans('message.success_header'))
        }
    }

    static closeModal() {
        $(document).find('#modal_download_url').modal('hide')
    }
}
