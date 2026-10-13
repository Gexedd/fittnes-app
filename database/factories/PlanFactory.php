<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        return [
            'name' => 'Básico',
            'slug' => 'basico',
            'level' => 1,
            'price_clp' => 9_990,
            'mp_plan_id' => null,
            'description' => fake()->sentence(),
            'features' => ['Acceso al catálogo inicial'],
            'is_active' => true,
        ];
    }

    public function medium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Medio',
            'slug' => 'medio',
            'level' => 2,
            'price_clp' => 14_990,
            'features' => ['Catálogo inicial', 'Videos nuevos exclusivos'],
        ]);
    }

    public function premium(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Premium',
            'slug' => 'premium',
            'level' => 3,
            'price_clp' => 24_990,
            'features' => ['Catálogo completo', 'Contenido premium exclusivo'],
        ]);
    }
}
