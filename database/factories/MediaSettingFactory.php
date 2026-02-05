<?php

namespace Codenzia\FilamentMedia\Database\Factories;

use Codenzia\FilamentMedia\Models\MediaSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

class MediaSettingFactory extends Factory
{
    protected $model = MediaSetting::class;

    public function definition(): array
    {
        return [
            'key' => $this->faker->slug(2),
            'value' => $this->faker->randomElement([
                $this->faker->word(),
                $this->faker->numberBetween(1, 100),
                ['option1' => true, 'option2' => false],
            ]),
            'user_id' => null,
            'media_id' => null,
        ];
    }

    public function withUser(int $userId): static
    {
        return $this->state(fn (array $attributes) => [
            'user_id' => $userId,
        ]);
    }

    public function withMedia(int $mediaId): static
    {
        return $this->state(fn (array $attributes) => [
            'media_id' => $mediaId,
        ]);
    }

    public function withKey(string $key): static
    {
        return $this->state(fn (array $attributes) => [
            'key' => $key,
        ]);
    }
}
