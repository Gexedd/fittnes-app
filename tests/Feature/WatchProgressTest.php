<?php

use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoProgress;
use App\Models\VideoView;
use App\Services\Video\WatchTracker;
use Livewire\Volt\Volt;

/**
 * Crea un usuario con un plan activo del nivel indicado.
 */
function subscribedUser(int $level = 3): User
{
    $plan = match ($level) {
        1 => Plan::factory()->create(),
        2 => Plan::factory()->medium()->create(),
        default => Plan::factory()->premium()->create(),
    };

    $user = User::factory()->create();
    Subscription::factory()->for($user)->for($plan)->create();

    return $user;
}

it('registra la vista al abrir un video autorizado', function () {
    $user = subscribedUser(3);
    $video = Video::factory()->create(['min_plan_level' => 1]);

    $this->actingAs($user)->get(route('videos.show', $video))->assertOk();

    expect(VideoView::where('user_id', $user->id)->where('video_id', $video->id)->exists())->toBeTrue()
        ->and(VideoProgress::where('user_id', $user->id)->where('video_id', $video->id)->exists())->toBeTrue();
});

it('no registra la vista si el plan no alcanza el nivel', function () {
    $user = subscribedUser(1);
    $video = Video::factory()->create(['min_plan_level' => 3]);

    $this->actingAs($user)->get(route('videos.show', $video))->assertOk();

    expect(VideoView::count())->toBe(0)
        ->and(VideoProgress::count())->toBe(0);
});

it('no duplica la vista al recargar dentro de la misma hora', function () {
    $user = subscribedUser(3);
    $video = Video::factory()->create(['min_plan_level' => 1]);

    $this->actingAs($user)->get(route('videos.show', $video));
    $this->actingAs($user)->get(route('videos.show', $video));

    expect(VideoView::count())->toBe(1);
});

it('permite marcar y desmarcar un video como visto', function () {
    $user = subscribedUser(3);
    $video = Video::factory()->create(['min_plan_level' => 1, 'duration_seconds' => 900]);

    $this->actingAs($user);

    Volt::test('watch-progress', ['video' => $video])
        ->assertSet('completed', false)
        ->call('toggle')
        ->assertSet('completed', true);

    expect(VideoProgress::first()->completed_at)->not->toBeNull()
        ->and(VideoProgress::first()->position_seconds)->toBe(900);

    Volt::test('watch-progress', ['video' => $video])
        ->assertSet('completed', true)
        ->call('toggle');

    expect(VideoProgress::first()->completed_at)->toBeNull();
});

it('muestra continuar viendo y el historial en el dashboard', function () {
    $user = subscribedUser(3);
    $video = Video::factory()->create(['title' => 'Piernas avanzadas', 'min_plan_level' => 1]);

    $this->actingAs($user)->get(route('videos.show', $video));

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Continuar viendo')
        ->assertSee('Piernas avanzadas')
        ->assertSee('Historial reciente');
});

it('marca los videos ya vistos en el catálogo', function () {
    $user = subscribedUser(3);
    $video = Video::factory()->create(['min_plan_level' => 1]);

    $this->actingAs($user)->get(route('videos.show', $video));
    $this->actingAs($user)->get(route('videos.index'))->assertOk()->assertSee('✓ Visto');
});

it('calcula el resumen de progreso del usuario', function () {
    $user = subscribedUser(3);
    app(WatchTracker::class)->markCompleted(
        $user,
        Video::factory()->create(['min_plan_level' => 1, 'duration_seconds' => 600]),
    );

    $summary = app(WatchTracker::class)->summary($user);

    expect($summary['completed'])->toBe(1)
        ->and($summary['in_progress'])->toBe(0)
        ->and($summary['available'])->toBe(1)
        ->and($summary['percent'])->toBe(100);
});
