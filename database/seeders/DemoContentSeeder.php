<?php

namespace Database\Seeders;

use App\Enums\VideoStatus;
use App\Models\Category;
use App\Models\Tag;
use App\Models\Video;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Contenido de ejemplo para probar el catálogo y el paywall en local.
 * NO se ejecuta en producción (ver DatabaseSeeder).
 */
class DemoContentSeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'Fuerza', 'slug' => 'fuerza', 'description' => 'Pesas y fuerza general'],
            ['name' => 'Cardio', 'slug' => 'cardio', 'description' => 'Resistencia y quema de grasa'],
            ['name' => 'Movilidad', 'slug' => 'movilidad', 'description' => 'Estiramientos y flexibilidad'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(['slug' => $category['slug']], $category);
        }

        $videos = [
            ['title' => 'Full body para principiantes', 'category' => 'fuerza', 'level' => 1, 'duration' => 900, 'tags' => ['full body', 'principiante']],
            ['title' => 'HIIT quema grasa 20 minutos', 'category' => 'cardio', 'level' => 1, 'duration' => 1200, 'tags' => ['hiit', 'cardio']],
            ['title' => 'Rutina de piernas intermedia', 'category' => 'fuerza', 'level' => 2, 'duration' => 2100, 'tags' => ['piernas', 'fuerza']],
            ['title' => 'Movilidad de cadera', 'category' => 'movilidad', 'level' => 2, 'duration' => 780, 'tags' => ['cadera', 'movilidad']],
            ['title' => 'Hipertrofia: programa de 4 semanas', 'category' => 'fuerza', 'level' => 3, 'duration' => 3000, 'tags' => ['hipertrofia', 'premium']],
            ['title' => 'Core y abdominales avanzado', 'category' => 'fuerza', 'level' => 3, 'duration' => 1500, 'tags' => ['core', 'premium']],
        ];

        foreach ($videos as $data) {
            $video = Video::updateOrCreate(
                ['slug' => Str::slug($data['title'])],
                [
                    'category_id' => Category::query()->where('slug', $data['category'])->value('id'),
                    'min_plan_level' => $data['level'],
                    'title' => $data['title'],
                    'description' => 'Rutina guiada paso a paso, pensada para complementar tu semana de entrenamiento.',
                    'bunny_video_id' => 'demo-'.Str::slug($data['title']),
                    'duration_seconds' => $data['duration'],
                    'status' => VideoStatus::Published,
                    'published_at' => now()->subDays(random_int(1, 30)),
                ],
            );

            $video->tags()->sync(
                collect($data['tags'])
                    ->map(fn (string $tag) => Tag::firstOrCreate(
                        ['slug' => Str::slug($tag)],
                        ['name' => $tag],
                    )->id)
                    ->all(),
            );
        }
    }
}
