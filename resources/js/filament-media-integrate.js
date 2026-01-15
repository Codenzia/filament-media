/**
 * Lightweight placeholder for the media integration script.
 * Provides EditorService for compatibility and preserves globals.
 */
export class EditorService {
    static editorSelectFile() {
        // no-op placeholder
    }
}

(() => {
    window.rvMedia = window.rvMedia || {}
    window.FilamentMediaStandAlone = function () {
        return window.rvMedia
    }

    // jQuery plugin no-op to prevent errors if invoked
    if (typeof window.jQuery !== 'undefined') {
        window.jQuery.fn.rvMedia = function () {
            return this
        }
    }

    document.dispatchEvent(new CustomEvent('core-media-loaded'))
})()
