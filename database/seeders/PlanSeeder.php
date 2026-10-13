<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    /**
     * Crea (o actualiza) los tres niveles de suscripción.
     * Los precios son de ejemplo: se ajustan con el cliente.
     */
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Básico',
                'slug' => 'basico',
                'level' => 1,
                'price_clp' => 9_990,
                'description' => 'Acceso al catálogo inicial de entrenamientos.',
                'features' => ['Catálogo básico', 'Nuevos videos cada mes'],
            ],
            [
                'name' => 'Medio',
                'slug' => 'medio',
                'level' => 2,
                'price_clp' => 14_990,
                'description' => 'Catálogo básico más los videos nuevos exclusivos.',
                'features' => ['Todo lo del plan Básico', 'Videos nuevos exclusivos'],
            ],
            [
                'name' => 'Premium',
                'slug' => 'premium',
                'level' => 3,
                'price_clp' => 24_990,
                'description' => 'Acceso total, incluido el contenido premium exclusivo.',
                'features' => [
                    'Todo lo del plan Medio',
                    'Contenido premium exclusivo',
                    'Rutinas personalizadas',
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::updateOrCreate(['slug' => $plan['slug']], $plan);
        }
    }
}
