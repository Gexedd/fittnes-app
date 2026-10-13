<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        // El entrenador (administrador).
        User::factory()->create([
            'name' => 'Entrenador',
            'email' => 'admin@fittnes.test',
            'role' => UserRole::Admin,
        ]);

        // Un suscriptor de ejemplo sin suscripción (para probar el paywall).
        User::factory()->create([
            'name' => 'Suscriptor Demo',
            'email' => 'demo@fittnes.test',
            'role' => UserRole::Subscriber,
        ]);
    }
}
