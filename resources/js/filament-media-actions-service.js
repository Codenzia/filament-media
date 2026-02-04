import $ from 'jquery'
import Cropper from 'cropperjs'
import { Helpers } from './filament-media-helpers'
import { MessageService } from './filament-media-message-service'
import { $httpClient } from './filament-media-http-client'

export class ActionsService {
    static handleDropdown(open = false) {
        let selectedItems = Helpers.getSelectedItems()
        let selected = Helpers.size(selectedItems)

        ActionsService.renderActions()

        const $actions = $('.fm-dropdown-actions')
        const $button = $actions.find('button.fm-dropdown-actions, .dropdown-toggle')

        if (selected > 0) {
            $button.removeClass('disabled').prop('disabled', false)

            // Auto open dropdown only when one item is selected and it was just selected
            if (open && selected === 1) {
                $button.trigger('click')
            }
        } else {
            $button.addClass('disabled').prop('disabled', true)
        }
    }

    static handlePreview() {
        let selected = []
        Helpers.each(Helpers.getSelectedFiles(), (value) => {
            console.log(value);

            if (value.data.preview_url) {
                if (value.data.type === 'document') {
                    const iframe = document.createElement('iframe')
                    iframe.src = value.data.preview_url
                    iframe.allowFullscreen = true
                    iframe.style.width = '100vh'
                    iframe.style.height = '100vh'
                    selected.push(iframe)
                } else if (value.data.type === 'video') {
                    const video = document.createElement('video')
                    video.src = value.data.preview_url
                    video.controls = true
                    video.style.maxWidth = '100%'
                    video.style.maxHeight = '90vh'
                    selected.push(video)
                } else if (value.data.type === 'audio') {
                    const audio = document.createElement('audio')
                    audio.src = value.data.preview_url
                    audio.controls = true
                    audio.style.maxWidth = '100%'
                    audio.style.maxHeight = '90vh'
                    selected.push(audio)
                } else {
                    selected.push(value.data.preview_url)
                }

                // Add to recent items on the server
                Helpers.addToRecent(value.data.id)
            }
        })
        window.FilamentMedia.lightbox(selected)
    }


    static async handleCopyLink() {
        let links = ''

        Helpers.each(Helpers.getSelectedFiles(), (value) => {
            if (!Helpers.isEmpty(links)) {
                links += '\n'
            }
            links += value.full_url
        })

        await window.FilamentMedia.copyToClipboard(links)

        MessageService.showMessage(
            'success',
            Helpers.trans('clipboard.success'),
            Helpers.trans('message.success_header')
        )
    }

    static async handleCopyIndirectLink() {
        const selected = Helpers.getSelectedFiles()
        if (!Helpers.size(selected)) {
            return
        }

        const links = selected
            .map((value) => value.full_url || (value.data ? value.data.full_url : null))
            .filter((url) => !!url)
            .join('\n')

        if (!links) {
            MessageService.showMessage('error', Helpers.trans('clipboard.error'), Helpers.trans('message.error_header'))
            return
        }

        await window.FilamentMedia.copyToClipboard(links)

        MessageService.showMessage(
            'success',
            Helpers.trans('clipboard.success'),
            Helpers.trans('message.success_header')
        )
    }

    static handleGlobalAction(type, callback) {
        let selected = []
        Helpers.each(Helpers.getSelectedItems(), (value) => {
            selected.push(value)
        })
        console.log(selected);
        switch (type) {
            case 'rename':
                Livewire.dispatch('open-rename-modal', { items: selected })
                break
            case 'copy_link':
                ActionsService.handleCopyLink().then(() => {})
                break
            case 'copy_indirect_link':
                ActionsService.handleCopyIndirectLink().then(() => {})
                break
            case 'preview':
                ActionsService.handlePreview(selected)
                break
            case 'alt_text':
                Livewire.dispatch('open-alt-text-modal', { items: selected })
                break
            case 'trash':
                Livewire.dispatch('open-trash-modal', { items: selected })
                break
            case 'delete':
                Livewire.dispatch('open-delete-modal', { items: selected })
                break
            case 'empty_trash':
                Livewire.dispatch('open-empty-trash-modal')
                break
            case 'favorite':
                Livewire.dispatch('open-favorite-modal', { items: selected })
                break
            case 'remove_favorite':
                Livewire.dispatch('open-remove-favorite-modal', { items: selected })
                break
            case 'download':
                let files = []
                Helpers.each(Helpers.getSelectedItems(), (value) => {
                    if (!Helpers.inArray(Helpers.getConfigs().denied_download, value.mime_type)) {
                        files.push({
                            id: value.id,
                            is_folder: value.context === 'folder' ? true : false,
                        })
                    }
                })

                if (files.length) {
                    ActionsService.handleDownload(files)
                } else {
                    MessageService.showMessage(
                        'error',
                        Helpers.trans('download.error'),
                        Helpers.trans('message.error_header')
                    )
                }
                break
            case 'properties':
                Livewire.dispatch('open-properties-modal', { items: selected })
                break
            case 'create_folder':
                Livewire.dispatch('open-create-folder-modal')
                break
            default:
                ActionsService.processAction(
                    {
                        selected: selected,
                        action: type,
                    },
                    callback
                )
                break
        }
    }

    static processAction(data, callback = null) {
        Helpers.showAjaxLoading()

        $httpClient
            .make()
            .post(FilamentMedia_URL.global_actions, data)
            .then(({ data }) => {
                Helpers.resetPagination()

                MessageService.showMessage('success', data.message, Helpers.trans('message.success_header'))

                callback && callback(data)
            })
            .catch(({ response }) => callback && callback(response.data))
            .finally(() => Helpers.hideAjaxLoading())
    }

    static renderActions() {
        let selectedItems = Helpers.getSelectedItems()
        let hasFolderSelected = Helpers.getSelectedFolder().length > 0

        let ACTION_TEMPLATE = $('#filament_action_item').html() ?? ''
        let initializedItem = 0
        let $dropdownActions = $('.fm-dropdown-actions-list')

        if ($dropdownActions.length === 0) {
            $dropdownActions = $('.fm-dropdown-actions').find('.dropdown-menu, .fi-dropdown-list, .fi-dropdown-panel')
        }

        if ($dropdownActions.length === 0) {
            return
        }

        $dropdownActions.empty()

        let actionsList = $.extend({}, true, Helpers.getConfigs().actions_list)

        Helpers.each(actionsList, (group, key) => {
            if (!Helpers.isArray(group)) {
                actionsList[key] = []
            }
            // Remove share action
            actionsList[key] = Helpers.arrayReject(actionsList[key], (item) => item.action === 'share')
        })
        console.log(selectedItems.length);
        if (selectedItems.length > 1) {
            Helpers.each(actionsList, (group, key) => {
                console.log(key);
                actionsList[key] = Helpers.arrayReject(group, (item) => item.action === 'rename' || item.action === 'copy_indirect_link' || item.action === 'copy_link')
            })
        }

        if (hasFolderSelected) {
            const ignoreActions = ['preview', 'crop', 'alt_text', 'copy_link']

            Helpers.each(actionsList, (group, key) => {
                actionsList[key] = Helpers.arrayReject(group, (item) => ignoreActions.includes(item.action))
            })
            if (!Helpers.hasPermission('folders.create')) {
                actionsList.file = Helpers.arrayReject(actionsList.file, (item) => {
                    return item.action === 'make_copy'
                })
            }

            if (!Helpers.hasPermission('folders.edit')) {
                actionsList.file = Helpers.arrayReject(actionsList.file, (item) => {
                    return Helpers.inArray(['rename'], item.action)
                })

                actionsList.user = Helpers.arrayReject(actionsList.user, (item) => {
                    return Helpers.inArray(['rename'], item.action)
                })
            }

            if (!Helpers.hasPermission('folders.trash')) {
                actionsList.other = Helpers.arrayReject(actionsList.other, (item) => {
                    return Helpers.inArray(['trash', 'restore'], item.action)
                })
            }

            if (!Helpers.hasPermission('folders.destroy')) {
                actionsList.other = Helpers.arrayReject(actionsList.other, (item) => {
                    return Helpers.inArray(['delete'], item.action)
                })
            }

            if (!Helpers.hasPermission('folders.favorite')) {
                actionsList.other = Helpers.arrayReject(actionsList.other, (item) => {
                    return Helpers.inArray(['favorite', 'remove_favorite'], item.action)
                })
            }
        }

        let selectedFiles = Helpers.getSelectedFiles()

        let fileIsImage = Helpers.arrayFilter(selectedFiles, function (value) {
            return value.type === 'image'
        }).length

        if (!fileIsImage) {
            actionsList.basic = Helpers.arrayReject(actionsList.basic, (item) => {
                return item.action === 'crop'
            })

            actionsList.file = Helpers.arrayReject(actionsList.file, (item) => {
                return item.action === 'alt_text'
            })
        }

        if (selectedFiles.length > 0) {
            if (!Helpers.hasPermission('files.create')) {
                actionsList.file = Helpers.arrayReject(actionsList.file, (item) => {
                    return item.action === 'make_copy'
                })
            }

            if (!Helpers.hasPermission('files.edit')) {
                actionsList.file = Helpers.arrayReject(actionsList.file, (item) => {
                    return Helpers.inArray(['rename'], item.action)
                })
            }

            if (!Helpers.hasPermission('files.trash')) {
                actionsList.other = Helpers.arrayReject(actionsList.other, (item) => {
                    return Helpers.inArray(['trash', 'restore'], item.action)
                })
            }

            if (!Helpers.hasPermission('files.destroy')) {
                actionsList.other = Helpers.arrayReject(actionsList.other, (item) => {
                    return Helpers.inArray(['delete'], item.action)
                })
            }

            if (!Helpers.hasPermission('files.favorite')) {
                actionsList.other = Helpers.arrayReject(actionsList.other, (item) => {
                    return Helpers.inArray(['favorite', 'remove_favorite'], item.action)
                })
            }

            if (selectedFiles.length > 1) {
                actionsList.basic = Helpers.arrayReject(actionsList.basic, (item) => {
                    return item.action === 'crop'
                })
            }
        }

        if (!Helpers.hasPermission('folders.edit') || selectedFiles.length > 0) {
            actionsList.other = Helpers.arrayReject(actionsList.other, (item) => {
                return Helpers.inArray(['properties'], item.action)
            })
        }

        Helpers.each(actionsList, (action, key) => {
            // Sort actions by order
            action.sort((a, b) => (a.order || 0) - (b.order || 0))

            Helpers.each(action, (item, index) => {
                let is_break = false
                switch (Helpers.getRequestParams().view_in) {
                    case 'all_media':
                        if (Helpers.inArray(['remove_favorite', 'delete', 'restore'], item.action)) {
                            is_break = true
                        }
                        break
                    case 'recent':
                        if (Helpers.inArray(['remove_favorite', 'delete', 'restore', 'make_copy'], item.action)) {
                            is_break = true
                        }
                        break
                    case 'favorites':
                        if (Helpers.inArray(['favorite', 'delete', 'restore', 'make_copy'], item.action)) {
                            is_break = true
                        }
                        break
                    case 'trash':
                        if (!Helpers.inArray(['preview', 'delete', 'restore', 'rename', 'download'], item.action)) {
                            is_break = true
                        }
                        break
                }
                if (!is_break) {
                    const baseTemplate = typeof ACTION_TEMPLATE === 'string' ? ACTION_TEMPLATE : ''
                    let template = baseTemplate
                        .replace(/__action__/gi, item.action || '')
                        .replace('__icon__', item.icon || '')
                        .replace(/__name__/gi, Helpers.trans(`actions_list.${key}.${item.action}`) || item.name)

                    if (!index && initializedItem) {
                        template = `<li role="separator" class="divider"></li>${template}`
                    }

                    $dropdownActions.append(template)
                }
            })

            if (action.length > 0) {
                initializedItem++
            }
        })
    }

    static handleDownload(files) {
        const html = $('.media-download-popup')
        let downloadTimeout = null

        html.show()

        $httpClient
            .make()
            .withResponseType('blob')
            .post(FilamentMedia_URL.download, { selected: files })
            .then((response) => {
                let fileName = 'download'
                const disposition = response.headers['content-disposition']

                if (disposition && disposition.indexOf('filename=') !== -1) {
                    const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition)
                    if (matches != null && matches[1]) {
                        fileName = matches[1].replace(/['"]/g, '')
                    }
                }

                if (response.data instanceof Blob && response.data.type === 'application/json') {
                    // Convert blob to json to show error
                    const reader = new FileReader();
                    reader.onload = () => {
                        const result = JSON.parse(reader.result);
                        if (result.error) {
                             MessageService.showMessage('error', result.message, Helpers.trans('message.error_header'))
                        }
                    };
                    reader.readAsText(response.data);
                    return;
                }

                const objectUrl = URL.createObjectURL(response.data)
                const a = document.createElement('a')

                a.href = objectUrl
                a.download = fileName
                document.body.appendChild(a)
                a.click()
                a.remove()

                URL.revokeObjectURL(objectUrl)
            })
            .catch(({ response }) => {
                 // ...
            })
            .finally(() => {
                html.hide()
                clearTimeout(downloadTimeout)
            })
    }
}
