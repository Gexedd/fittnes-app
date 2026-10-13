<?php

use App\Contracts\Video\VideoPlayback;
use App\Models\Category;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Video;
use Livewire\Volt\Volt;

it('muestra solo los videos publicados en el catálogo', function () {
    Video::factory()->create(['title' => 'Publicado visible']);
    Video::factory()->draft()->create(['title' => 'Borrador oculto']);

    $this->get(route('videos.index'))
        ->assertOk()
        ->assertSee('Publicado visible')
        ->assertDontSee('Borrador oculto');
});

it('filtra el catálogo por categoría', function () {
    $fuerza = Category::factory()->create(['name' => 'Fuerza', 'slug' => 'fuerza']);
    $cardio = Category::factory()->create(['name' => 'Cardio', 'slug' => 'cardio']);

    Video::factory()->create(['title' => 'Pesas libres', 'category_id' => $fuerza->id]);
    Video::factory()->create(['title' => 'Trote continuo', 'category_id' => $cardio->id]);

    Volt::test('videos.index')
        ->set('category', 'fuerza')
        ->assertSee('Pesas libres')
        ->assertDontSee('Trote continuo');
});

it('exige sesión para abrir la ficha de un video', function () {
    $video = Video::factory()->create();

    $this->get(route('videos.show', $video))->assertRedirect(route('login'));
});

it('oculta la URL de reproducción si el plan no alcanza el nivel', function () {
    $plan = Plan::factory()->create(); // nivel 1
    $user = User::factory()->create();
    Subscription::factory()->for($user)->for($plan)->create();

    $video = Video::factory()->create(['min_plan_level' => 3]);

    $this->actingAs($user)
        ->get(route('videos.show', $video))
        ->assertOk()
        ->assertSee('Contenido bloqueado')
        ->assertDontSee('mediadelivery.net')
        ->assertDontSee('playlist.m3u8');
});

it('genera la URL firmada cuando el plan alcanza el nivel', function () {
    config()->set('services.video.driver', 'bunny');
    config()->set('services.bunny.library_id', '12345');
    config()->set('services.bunny.token_key', 'clave-de-prueba');

    // El binding es singleton: hay que soltarlo para que tome la nueva config.
    app()->forgetInstance(VideoPlayback::class);

    $plan = Plan::factory()->premium()->create();
    $user = User::factory()->create();
    Subscription::factory()->for($user)->for($plan)->create();

    $video = Video::factory()->create(['min_plan_level' => 3]);

    $this->actingAs($user)
        ->get(route('videos.show', $video))
        ->assertOk()
        ->assertSee('mediadelivery.net')
        ->assertSee('token=');
});

it('la Policy autoriza según el nivel del plan', function () {
    $video = Video::factory()->create(['min_plan_level' => 3]);

    $premium = Plan::factory()->premium()->create();
    $withPremium = User::factory()->create();
    Subscription::factory()->for($withPremium)->for($premium)->create();

    $basic = Plan::factory()->create(); // nivel 1
    $withBasic = User::factory()->create();
    Subscription::factory()->for($withBasic)->for($basic)->create();

    expect($withPremium->can('view', $video))->toBeTrue()
        ->and($withBasic->can('view', $video))->toBeFalse();
});

it('no autoriza la reproducción de un video en borrador', function () {
    $plan = Plan::factory()->premium()->create();
    $user = User::factory()->create();
    Subscription::factory()->for($user)->for($plan)->create();

    $draft = Video::factory()->draft()->create(['min_plan_level' => 1]);

    expect($user->can('view', $draft))->toBeFalse();
});
