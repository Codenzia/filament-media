<script>
    "use strict";

    var RV_MEDIA_URL = {{ Js::from(FilamentMedia::getUrls()) }};

    var RV_MEDIA_CONFIG = {{ Js::from([
        'permissions' => FilamentMedia::getPermissions(),
        'translations' => trans('core/media::media.javascript'),
        'pagination' => [
            'paged' => FilamentMedia::getConfig('pagination.paged'),
            'posts_per_page' => FilamentMedia::getConfig('pagination.per_page'),
            'in_process_get_media' => false,
            'has_more' => true,
        ],
        'chunk' => FilamentMedia::getConfig('chunk'),
        'random_hash' => null,
        'default_image' => FilamentMedia::getDefaultImage(),
    ]) }};

    RV_MEDIA_CONFIG.translations.actions_list.other.properties = '{{ trans('core/media::media.javascript.actions_list.other.properties') }}';
</script>
