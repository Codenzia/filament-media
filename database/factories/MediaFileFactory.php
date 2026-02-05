<?php

namespace Codenzia\FilamentMedia\Database\Factories;

use Codenzia\FilamentMedia\Models\MediaFile;
use Codenzia\FilamentMedia\Models\MediaFolder;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaFileFactory extends Factory
{
    protected $model = MediaFile::class;

    public function definition(): array
    {
        $types = ['image', 'document', 'video', 'audio', 'zip'];
        $type = $this->faker->randomElement($types);

        $mimeTypes = [
            'image' => ['image/jpeg', 'image/png', 'image/gif'],
            'document' => ['application/pdf', 'application/msword', 'text/plain'],
            'video' => ['video/mp4', 'video/quicktime'],
            'audio' => ['audio/mpeg', 'audio/wav'],
            'zip' => ['application/zip'],
        ];

        $extensions = [
            'image' => ['jpg', 'png', 'gif'],
            'document' => ['pdf', 'doc', 'txt'],
            'video' => ['mp4', 'mov'],
            'audio' => ['mp3', 'wav'],
            'zip' => ['zip'],
        ];

        $extension = $this->faker->randomElement($extensions[$type]);
        $fileName = $this->faker->slug(3) . '.' . $extension;

        return [
            'name' => $this->faker->words(3, true),
            'mime_type' => $this->faker->randomElement($mimeTypes[$type]),
            'size' => $this->faker->numberBetween(1024, 10485760), // 1KB to 10MB
            'url' => $fileName,
            'options' => null,
            'folder_id' => null,
            'user_id' => null,
            'alt' => $this->faker->sentence(4),
            'visibility' => 'public',
        ];
    }

    public function image(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement(['image/jpeg', 'image/png', 'image/gif']),
            'url' => $this->faker->slug(2) . '.' . $this->faker->randomElement(['jpg', 'png', 'gif']),
        ]);
    }

    public function document(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => $this->faker->randomElement(['application/pdf', 'application/msword']),
            'url' => $this->faker->slug(2) . '.' . $this->faker->randomElement(['pdf', 'doc']),
        ]);
    }

    public function video(): static
    {
        return $this->state(fn (array $attributes) => [
            'mime_type' => 'video/mp4',
            'url' => $this->faker->slug(2) . '.mp4',
        ]);
    }

    public function inFolder(MediaFolder $folder): static
    {
        return $this->state(fn (array $attributes) => [
            'folder_id' => $folder->id,
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'visibility' => 'private',
        ]);
    }

    public function withUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }
}
