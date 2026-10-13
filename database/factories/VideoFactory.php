<?php

namespace Database\Factories;

use App\Enums\VideoStatus;
use App\Models\Category;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        $title = fake()->unique()->sentence(3);

        return [
            'category_id' => Category::factory(),
            'min_plan_level' => 1,
            'title' => $title,
            'slug' => Str::slug($title).'-'.Str::random(6),
            'description' => fake()->paragraph(),
            'bunny_video_id' => (string) Str::uuid(),
            'duration_seconds' => fake()->numberBetween(300, 3600),
            'thumbnail_url' => null,
            'status' => VideoStatus::Published,
            'published_at' => now(),
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => VideoStatus::Draft,
            'published_at' => null,
        ]);
    }
}
