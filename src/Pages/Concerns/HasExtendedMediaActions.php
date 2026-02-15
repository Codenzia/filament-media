<?php

namespace Codenzia\FilamentMedia\Pages\Concerns;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Codenzia\FilamentMedia\Models\MediaTag;
use Codenzia\FilamentMedia\Services\ExportImportService;
use Codenzia\FilamentMedia\Services\FavoriteService;
use Codenzia\FilamentMedia\Services\MetadataService;
use Codenzia\FilamentMedia\Services\TagService;
use Codenzia\FilamentMedia\Services\VersionService;
use Filament\Actions\Action;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\ColorPicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Infolists\Components\TextEntry;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * Provides extended Filament modal actions for the media manager.
 *
 * Includes actions for favorites, collections, folder properties, alt text,
 * tags, file versioning, custom metadata, bulk export, and parent model details.
 */
trait HasExtendedMediaActions
{
    public function favoriteAction(): Action
    {
        return Action::make('favorite')
            ->label(trans('filament-media::media.javascript.actions_list.user.favorite'))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];

                app(FavoriteService::class)->addToFavorites($items, Auth::guard()->id());

                $this->refresh();

                $this->notifySuccess('favorite_success');
            });
    }

    public function remove_favoriteAction(): Action
    {
        return Action::make('remove_favorite')
            ->label(trans('filament-media::media.javascript.actions_list.user.remove_favorite'))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];

                app(FavoriteService::class)->removeFromFavorites($items, Auth::guard()->id());

                $this->refresh();

                $this->notifySuccess('remove_favorite_success');
            });
    }

    public function remove_from_collectionAction(): Action
    {
        return Action::make('remove_from_collection')
            ->label(trans('filament-media::media.remove_from_collection'))
            ->requiresConfirmation()
            ->modalHeading(trans('filament-media::media.remove_from_collection'))
            ->modalDescription(trans('filament-media::media.remove_from_collection_confirm'))
            ->action(function (array $arguments) {
                $items = $arguments['items'] ?? [];

                $fileIds = collect($items)
                    ->filter(fn($item) => ! ($item['is_folder'] ?? false))
                    ->pluck('id')
                    ->toArray();

                if (! empty($fileIds) && $this->collectionId > 0) {
                    app(TagService::class)->removeFromCollection($this->collectionId, $fileIds);
                }

                $this->refresh();

                $this->notifySuccess('removed_from_collection');
            });
    }

    public function propertiesAction(): Action
    {
        return Action::make('properties')
            ->label(trans('filament-media::media.properties.name'))
            ->fillForm(function (array $arguments): array {
                $items = $arguments['items'] ?? [];
                if (count($items) === 1) {
                    $folder = MediaFolder::find($items[0]['id']);
                    if ($folder) {
                        return ['color' => $folder->color];
                    }
                }

                return [];
            })
            ->schema([
                ColorPicker::make('color')
                    ->label(trans('filament-media::media.properties.color_label'))
                    ->required(),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                foreach ($items as $item) {
                    if ($item['is_folder'] ?? false) {
                        MediaFolder::where('id', $item['id'])->update(['color' => $data['color']]);
                    }
                }
                $this->refresh();
                $this->notifySuccess('update_properties_success');
            });
    }

    public function alt_textAction(): Action
    {
        return Action::make('alt_text')
            ->label(trans('filament-media::media.alt_text'))
            ->fillForm(function (array $arguments): array {
                $items = $arguments['items'] ?? [];
                if (count($items) === 1) {
                    $file = MediaFile::find($items[0]['id']);
                    if ($file) {
                        return ['alt' => $file->alt];
                    }
                }

                return [];
            })
            ->schema([
                TextInput::make('alt')
                    ->label(trans('filament-media::media.alt_text'))
                    ->maxLength(255),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                foreach ($items as $item) {
                    if (! ($item['is_folder'] ?? false)) {
                        MediaFile::where('id', $item['id'])->update(['alt' => $data['alt']]);
                    }
                }
                $this->refresh();
                $this->notifySuccess('update_alt_text_success');
            });
    }

    public function tagAction(): Action
    {
        return Action::make('tag')
            ->label(trans('filament-media::media.tags'))
            ->icon('heroicon-o-tag')
            ->fillForm(function (array $arguments): array {
                $items = $arguments['items'] ?? [];
                if (count($items) === 1 && ! ($items[0]['is_folder'] ?? false)) {
                    $file = MediaFile::find($items[0]['id']);
                    if ($file) {
                        return ['tags' => $file->tags->pluck('name')->toArray()];
                    }
                }

                return ['tags' => []];
            })
            ->schema([
                TagsInput::make('tags')
                    ->label(trans('filament-media::media.tags'))
                    ->suggestions(
                        MediaTag::tags()->pluck('name')->toArray()
                    ),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                $tagService = app(TagService::class);

                foreach ($items as $item) {
                    if (! ($item['is_folder'] ?? false)) {
                        $file = MediaFile::find($item['id']);
                        if ($file) {
                            $tagService->syncTags($file, $data['tags'] ?? []);
                        }
                    } else {
                        $folder = MediaFolder::find($item['id']);
                        if ($folder) {
                            $tagService->syncTags($folder, $data['tags'] ?? []);
                        }
                    }
                }

                $this->refresh();

                $this->notifySuccess('tags_updated');
            });
    }

    public function add_to_collectionAction(): Action
    {
        return Action::make('add_to_collection')
            ->label(trans('filament-media::media.add_to_collection'))
            ->icon('heroicon-o-rectangle-stack')
            ->schema([
                Select::make('collection_id')
                    ->label(trans('filament-media::media.collection'))
                    ->options(
                        MediaTag::collections()->pluck('name', 'id')->toArray()
                    )
                    ->required()
                    ->searchable()
                    ->suffixAction(
                        Action::make('createCollection')
                            ->icon('heroicon-m-plus')
                            ->schema([
                                TextInput::make('collection_name')
                                    ->label(trans('filament-media::media.collection_name'))
                                    ->required()
                                    ->maxLength(255),
                            ])
                            ->action(function (array $data, Select $component) {
                                $collection = app(TagService::class)->createCollection($data['collection_name']);
                                $component->state($collection->id);
                            })
                    ),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                $tagService = app(TagService::class);
                $fileIds = collect($items)
                    ->filter(fn($item) => ! ($item['is_folder'] ?? false))
                    ->pluck('id')
                    ->toArray();

                if (! empty($fileIds)) {
                    $tagService->addToCollection($data['collection_id'], $fileIds);
                }

                $this->notifySuccess('added_to_collection');
            });
    }

    public function upload_new_versionAction(): Action
    {
        return Action::make('upload_new_version')
            ->label(trans('filament-media::media.upload_new_version'))
            ->icon('heroicon-o-arrow-path')
            ->schema([
                FileUpload::make('new_file')
                    ->label(trans('filament-media::media.new_version_file'))
                    ->required(),
                TextInput::make('changelog')
                    ->label(trans('filament-media::media.version_changelog'))
                    ->maxLength(500),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                if (empty($items) || ($items[0]['is_folder'] ?? false)) {
                    return;
                }

                $file = MediaFile::find($items[0]['id']);
                if (! $file) {
                    return;
                }

                $versionService = app(VersionService::class);
                $versionService->createVersion(
                    $file,
                    $data['new_file'],
                    $data['changelog'] ?? null
                );

                $this->refresh();

                $this->notifySuccess('version_created');
            });
    }

    public function edit_metadataAction(): Action
    {
        $hasFields = app(MetadataService::class)->getFields()->isNotEmpty();

        return Action::make('edit_metadata')
            ->label(trans('filament-media::media.edit_metadata'))
            ->icon('heroicon-o-document-text')
            ->modalSubmitAction(fn ($action) => $hasFields ? $action : false)
            ->fillForm(function (array $arguments): array {
                $items = $arguments['items'] ?? [];
                if (empty($items) || ($items[0]['is_folder'] ?? false)) {
                    return [];
                }

                $file = MediaFile::find($items[0]['id']);
                if (! $file) {
                    return [];
                }

                $metadataService = app(MetadataService::class);
                $metadata = $metadataService->getMetadata($file);
                $values = [];

                foreach ($metadata as $field) {
                    $values['metadata_' . $field->id] = $field->pivot->value;
                }

                return $values;
            })
            ->schema(function (): array {
                $metadataService = app(MetadataService::class);
                $fields = $metadataService->getFields();

                if ($fields->isEmpty()) {
                    return [
                        TextEntry::make('no_fields')
                            ->label('')
                            ->state(trans('filament-media::media.no_metadata_fields')),
                    ];
                }

                $schema = [];

                foreach ($fields as $field) {
                    $schema[] = match ($field->type) {
                        'textarea' => Textarea::make('metadata_' . $field->id)
                            ->label($field->name)
                            ->required($field->is_required),
                        'number' => TextInput::make('metadata_' . $field->id)
                            ->label($field->name)
                            ->numeric()
                            ->required($field->is_required),
                        'select' => Select::make('metadata_' . $field->id)
                            ->label($field->name)
                            ->options($field->options ?? [])
                            ->required($field->is_required),
                        'boolean' => Checkbox::make('metadata_' . $field->id)
                            ->label($field->name),
                        default => TextInput::make('metadata_' . $field->id)
                            ->label($field->name)
                            ->required($field->is_required),
                    };
                }

                return $schema;
            })
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? [];
                if (empty($items) || ($items[0]['is_folder'] ?? false)) {
                    return;
                }

                $file = MediaFile::find($items[0]['id']);
                if (! $file) {
                    return;
                }

                $metadataService = app(MetadataService::class);
                $fieldValues = [];

                foreach ($data as $key => $value) {
                    if (str_starts_with($key, 'metadata_')) {
                        $fieldId = (int) str_replace('metadata_', '', $key);
                        $fieldValues[$fieldId] = $value;
                    }
                }

                $metadataService->setMetadata($file, $fieldValues);

                $this->notifySuccess('metadata_updated');
            });
    }

    public function export_filesAction(): Action
    {
        return Action::make('export_files')
            ->label(trans('filament-media::media.export'))
            ->icon('heroicon-o-arrow-down-tray')
            ->requiresConfirmation()
            ->modalDescription(trans('filament-media::media.export_description'))
            ->schema([
                Checkbox::make('include_metadata')
                    ->label(trans('filament-media::media.include_metadata'))
                    ->default(true),
            ])
            ->action(function (array $data, array $arguments) {
                $items = $arguments['items'] ?? $this->selectedItems;
                $exportService = app(ExportImportService::class);

                $fileIds = collect($items)
                    ->filter(fn($item) => ! ($item['is_folder'] ?? false))
                    ->pluck('id')
                    ->toArray();

                if (empty($fileIds)) {
                    $this->notifyWarning('no_files_selected');

                    return;
                }

                $includeMetadata = $data['include_metadata'] ?? false;

                return $includeMetadata
                    ? $exportService->exportWithMetadata($fileIds)
                    : $exportService->exportFiles($fileIds);
            });
    }

    public function view_parent_detailsAction(): Action
    {
        return Action::make('view_parent_details')
            ->label(trans('filament-media::media.view_parent_details'))
            ->icon('heroicon-o-link')
            ->slideOver()
            ->modalHeading(function (array $arguments): string {
                $items = $arguments['items'] ?? [];

                if (empty($items) || ($items[0]['is_folder'] ?? false)) {
                    return trans('filament-media::media.parent_details');
                }

                $file = MediaFile::find($items[0]['id']);
                $info = $file?->getLinkedModelInfo();

                return $info
                    ? trans('filament-media::media.parent_details') . ': ' . $info['label']
                    : trans('filament-media::media.parent_details');
            })
            ->schema(function (array $arguments): array {
                $items = $arguments['items'] ?? [];

                if (empty($items) || ($items[0]['is_folder'] ?? false)) {
                    return [
                        TextEntry::make('no_parent')
                            ->label('')
                            ->state(trans('filament-media::media.not_linked')),
                    ];
                }

                $file = MediaFile::find($items[0]['id']);
                $info = $file?->getLinkedModelInfo();

                if (! $info) {
                    return [
                        TextEntry::make('no_parent')
                            ->label('')
                            ->state(trans('filament-media::media.not_linked')),
                    ];
                }

                $schema = [
                    TextInput::make('_model_type')
                        ->label(trans('filament-media::media.parent_model_type'))
                        ->default($info['type'])
                        ->disabled(),
                    TextInput::make('_model_id')
                        ->label(trans('filament-media::media.parent_model_id'))
                        ->default((string) $info['id'])
                        ->disabled(),
                ];

                foreach ($info['attributes'] as $key => $value) {
                    if ($key === 'id') {
                        continue;
                    }

                    $label = Str::of($key)->replace('_', ' ')->title()->toString();

                    if (is_string($value) && Str::length($value) > 200) {
                        $schema[] = Textarea::make("_attr_{$key}")
                            ->label($label)
                            ->default($value)
                            ->disabled()
                            ->rows(3);
                    } else {
                        $schema[] = TextInput::make("_attr_{$key}")
                            ->label($label)
                            ->default($value === null ? '—' : (string) $value)
                            ->disabled();
                    }
                }

                return $schema;
            })
            ->modalSubmitAction(false)
            ->modalCancelActionLabel(trans('filament-media::media.close'));
    }
}
