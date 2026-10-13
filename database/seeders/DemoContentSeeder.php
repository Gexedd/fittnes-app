<?php

namespace Database\Seeders;

use App\Enums\SubscriptionStatus;
use App\Enums\VideoStatus;
use App\Models\Category;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Tag;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
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

        $this->seedSubscribersAndActivity();
    }

    /**
     * Suscriptores de ejemplo con plan activo, pagos y visualizaciones,
     * para que el panel de métricas muestre datos reales.
     */
    private function seedSubscribersAndActivity(): void
    {
        $plans = Plan::query()->get()->keyBy('level');

        if ($plans->isEmpty()) {
            return;
        }

        $subscribers = [
            ['name' => 'Camila Rojas', 'email' => 'camila@fittnes.test', 'level' => 1],
            ['name' => 'Diego Fuentes', 'email' => 'diego@fittnes.test', 'level' => 2],
            ['name' => 'Valentina Soto', 'email' => 'valentina@fittnes.test', 'level' => 3],
            ['name' => 'Matías Herrera', 'email' => 'matias@fittnes.test', 'level' => 2],
        ];

        $publishedVideos = Video::query()->published()->get();

        foreach ($subscribers as $index => $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => 'password',
                    'email_verified_at' => now(),
                ],
            );

            $subscription = Subscription::updateOrCreate(
                ['mp_preapproval_id' => 'demo-'.$user->id],
                [
                    'user_id' => $user->id,
                    'plan_id' => $plans[$data['level']]->id,
                    'status' => SubscriptionStatus::Authorized,
                    'started_at' => now()->subDays($index * 3),
                    'current_period_end' => now()->addDays(4 + $index * 5),
                ],
            );

            Payment::updateOrCreate(
                ['mp_payment_id' => 'demo-pay-'.$subscription->id],
                [
                    'user_id' => $user->id,
                    'subscription_id' => $subscription->id,
                    'amount_clp' => $plans[$data['level']]->price_clp,
                    'status' => 'approved',
                    'paid_at' => now()->subDays($index),
                ],
            );

            if ($publishedVideos->isNotEmpty()) {
                foreach ($publishedVideos->random(min(4, $publishedVideos->count())) as $video) {
                    VideoView::updateOrCreate(
                        ['user_id' => $user->id, 'video_id' => $video->id],
                        [
                            'watched_seconds' => random_int(60, max(61, (int) $video->duration_seconds)),
                            'ip' => '127.0.0.1',
                        ],
                    );
                }
            }
        }

        // Un cliente que se dio de baja este mes (para que las bajas no estén en cero).
        $churned = User::firstOrCreate(
            ['email' => 'jose@fittnes.test'],
            [
                'name' => 'José Bravo',
                'password' => 'password',
                'email_verified_at' => now(),
            ],
        );

        Subscription::updateOrCreate(
            ['mp_preapproval_id' => 'demo-cancelled-'.$churned->id],
            [
                'user_id' => $churned->id,
                'plan_id' => $plans[1]->id,
                'status' => SubscriptionStatus::Cancelled,
                'started_at' => now()->subDays(45),
                'current_period_end' => now()->addDays(2),
                'cancelled_at' => now()->subDays(2),
            ],
        );
    }
}
