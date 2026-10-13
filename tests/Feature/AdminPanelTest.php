<?php

use App\Models\Category;
use App\Models\User;
use App\Models\Video;
use Livewire\Volt\Volt;

it('bloquea el panel de administración a un suscriptor', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.videos.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.categories.index'))->assertForbidden();
});

it('muestra el panel de videos al administrador', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.videos.index'))
        ->assertOk()
        ->assertSee('Nuevo video');
});

it('crea una categoría desde el panel', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Volt::test('admin.categories.index')
        ->set('name', 'Fuerza')
        ->set('description', 'Rutinas de fuerza')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::where('slug', 'fuerza')->exists())->toBeTrue();
});

it('genera slugs únicos al repetir el nombre de una categoría', function () {
    $admin = User::factory()->admin()->create();
    Category::factory()->create(['name' => 'Cardio', 'slug' => 'cardio']);

    $this->actingAs($admin);

    Volt::test('admin.categories.index')
        ->set('name', 'Cardio')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::where('slug', 'cardio-2')->exists())->toBeTrue();
});

it('valida el nombre al crear una categoría', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Volt::test('admin.categories.index')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors(['name' => 'required']);
});

it('crea un video con etiquetas, nivel y estado', function () {
    $admin = User::factory()->admin()->create();
    $category = Category::factory()->create();

    $this->actingAs($admin);

    Volt::test('admin.videos.index')
        ->set('title', 'Rutina de piernas')
        ->set('category_id', (string) $category->id)
        ->set('min_plan_level', '3')
        ->set('status', 'published')
        ->set('tags', 'fuerza, gluteo')
        ->call('save')
        ->assertHasNoErrors();

    $video = Video::where('slug', 'rutina-de-piernas')->first();

    expect($video)->not->toBeNull()
        ->and($video->min_plan_level)->toBe(3)
        ->and($video->status->value)->toBe('published')
        ->and($video->published_at)->not->toBeNull()
        ->and($video->bunny_video_id)->toStartWith('demo-')
        ->and($video->tags)->toHaveCount(2);
});

it('valida el título del video', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Volt::test('admin.videos.index')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title' => 'required']);
});

it('publica y despublica un video', function () {
    $admin = User::factory()->admin()->create();
    $video = Video::factory()->draft()->create();

    $this->actingAs($admin);

    Volt::test('admin.videos.index')->call('togglePublished', $video->id);

    expect($video->refresh()->status->value)->toBe('published');

    Volt::test('admin.videos.index')->call('togglePublished', $video->id);

    expect($video->refresh()->status->value)->toBe('draft');
});

it('elimina un video', function () {
    $admin = User::factory()->admin()->create();
    $video = Video::factory()->create();

    $this->actingAs($admin);

    Volt::test('admin.videos.index')->call('delete', $video->id);

    expect(Video::find($video->id))->toBeNull();
});
