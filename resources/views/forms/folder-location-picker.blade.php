@php
    $statePath = $getStatePath();
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="folderLocationPicker({
            statePath: '{{ $statePath }}',
            folderTree: @js($folderTree),
            initialBreadcrumbs: @js($initialBreadcrumbs),
            initialFolderId: @js($initialFolderId),
        })"
        class="fm-location-picker rounded-lg bg-white shadow-sm ring-1 ring-gray-950/10 dark:bg-white/5 dark:ring-white/20 overflow-hidden"
    >
        {{-- Breadcrumb bar --}}
        <div class="fm-location-breadcrumbs">
            <svg class="w-4 h-4 flex-shrink-0 text-gray-400 dark:text-gray-500" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z" clip-rule="evenodd" />
            </svg>
            <template x-for="(crumb, index) in breadcrumbs" :key="crumb.id">
                <span class="flex items-center gap-1 whitespace-nowrap">
                    <svg x-show="index > 0" class="w-3 h-3 flex-shrink-0 text-gray-300 dark:text-gray-600" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                    </svg>
                    <button
                        type="button"
                        x-on:click="selectFolder(crumb.id)"
                        x-text="crumb.name"
                        class="rounded px-1 py-0.5 transition-colors hover:bg-gray-950/5 dark:hover:bg-white/5"
                        :class="index === breadcrumbs.length - 1
                            ? 'font-medium text-gray-950 dark:text-white'
                            : 'text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300'"
                    ></button>
                </span>
            </template>
        </div>

        {{-- Folder tree --}}
        <div class="fm-folder-tree">
            {{-- Root: All Media --}}
            <div
                x-on:click="selectFolder(0)"
                class="fm-folder-tree-item"
                :class="{ 'selected': selectedFolderId === 0 }"
            >
                {{-- Closed folder icon --}}
                <svg x-show="selectedFolderId !== 0" class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" style="color: var(--fm-icon-color)">
                    <path d="M3.75 3A1.75 1.75 0 0 0 2 4.75v3.26a3.235 3.235 0 0 1 1.75-.51h12.5c.644 0 1.245.188 1.75.51V6.75A1.75 1.75 0 0 0 16.25 5h-4.836a.25.25 0 0 1-.177-.073L9.823 3.513A1.75 1.75 0 0 0 8.586 3H3.75Z" />
                    <path d="M3.75 9A1.75 1.75 0 0 0 2 10.75v4.5c0 .966.784 1.75 1.75 1.75h12.5A1.75 1.75 0 0 0 18 15.25v-4.5A1.75 1.75 0 0 0 16.25 9H3.75Z" />
                </svg>
                {{-- Open folder icon (selected — inherits primary color from parent) --}}
                <svg x-show="selectedFolderId === 0" x-cloak class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                    <path d="M4.75 3A1.75 1.75 0 0 0 3 4.75v2.752l.104-.002h13.792c.035 0 .07 0 .104.002V6.75A1.75 1.75 0 0 0 15.25 5h-3.836a.25.25 0 0 1-.177-.073L9.823 3.513A1.75 1.75 0 0 0 8.586 3H4.75ZM3.104 9a1.75 1.75 0 0 0-1.673 2.265l1.385 4.5A1.75 1.75 0 0 0 4.488 17h11.023a1.75 1.75 0 0 0 1.673-1.235l1.384-4.5A1.75 1.75 0 0 0 16.896 9H3.104Z" />
                </svg>
                <span class="text-sm">{{ trans('filament-media::media.all_media') }}</span>
            </div>

            {{-- Folder nodes --}}
            <template x-for="folder in flatFolders" :key="folder.id">
                <div
                    x-show="isVisible(folder)"
                    x-on:click="selectFolder(folder.id)"
                    class="fm-folder-tree-item"
                    :class="{ 'selected': selectedFolderId === folder.id }"
                    :style="{ paddingLeft: ((folder.depth + 1) * 1.25 + 0.75) + 'rem' }"
                >
                    {{-- Expand/collapse toggle --}}
                    <button
                        x-show="folder.hasChildren"
                        type="button"
                        x-on:click.stop="toggleExpand(folder.id)"
                        class="fm-folder-tree-toggle"
                        :class="{ 'expanded': expandedNodes[folder.id] }"
                    >
                        <svg class="w-3.5 h-3.5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd" />
                        </svg>
                    </button>
                    <span x-show="!folder.hasChildren" class="w-5 h-5 flex-shrink-0"></span>

                    {{-- Closed folder icon --}}
                    <svg x-show="selectedFolderId !== folder.id" class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" :style="folder.color ? { color: folder.color } : {}" style="color: var(--fm-icon-color)">
                        <path d="M3.75 3A1.75 1.75 0 0 0 2 4.75v3.26a3.235 3.235 0 0 1 1.75-.51h12.5c.644 0 1.245.188 1.75.51V6.75A1.75 1.75 0 0 0 16.25 5h-4.836a.25.25 0 0 1-.177-.073L9.823 3.513A1.75 1.75 0 0 0 8.586 3H3.75Z" />
                        <path d="M3.75 9A1.75 1.75 0 0 0 2 10.75v4.5c0 .966.784 1.75 1.75 1.75h12.5A1.75 1.75 0 0 0 18 15.25v-4.5A1.75 1.75 0 0 0 16.25 9H3.75Z" />
                    </svg>
                    {{-- Open folder icon (selected — inherits primary color from parent) --}}
                    <svg x-show="selectedFolderId === folder.id" x-cloak class="w-4 h-4 flex-shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M4.75 3A1.75 1.75 0 0 0 3 4.75v2.752l.104-.002h13.792c.035 0 .07 0 .104.002V6.75A1.75 1.75 0 0 0 15.25 5h-3.836a.25.25 0 0 1-.177-.073L9.823 3.513A1.75 1.75 0 0 0 8.586 3H4.75ZM3.104 9a1.75 1.75 0 0 0-1.673 2.265l1.385 4.5A1.75 1.75 0 0 0 4.488 17h11.023a1.75 1.75 0 0 0 1.673-1.235l1.384-4.5A1.75 1.75 0 0 0 16.896 9H3.104Z" />
                    </svg>

                    {{-- Folder name --}}
                    <span x-text="folder.name" class="truncate text-sm"></span>
                </div>
            </template>
        </div>
    </div>
</x-dynamic-component>
