<?php

use App\Models\Plan;

it('muestra los planes activos con sus precios en pesos', function () {
    Plan::factory()->create();            // Básico  $9.990
    Plan::factory()->medium()->create();  // Medio   $14.990
    Plan::factory()->premium()->create(); // Premium $24.990

    $this->get(route('planes'))
        ->assertOk()
        ->assertSee('Básico')
        ->assertSee('Medio')
        ->assertSee('Premium')
        ->assertSee('9.990')
        ->assertSee('14.990')
        ->assertSee('24.990');
});

it('no muestra los planes inactivos', function () {
    Plan::factory()->create(['is_active' => false, 'name' => 'Plan Oculto', 'slug' => 'oculto']);

    $this->get(route('planes'))
        ->assertOk()
        ->assertDontSee('Plan Oculto');
});

it('la home responde correctamente', function () {
    $this->get(route('home'))->assertOk()->assertSee('Ver planes');
});
