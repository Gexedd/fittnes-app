<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Video;

it('da acceso premium a todos los niveles de video', function () {
    $premium = Plan::factory()->premium()->create();
    $user = User::factory()->create();
    Subscription::factory()->for($user)->for($premium)->create();

    expect($user->planLevel())->toBe(3)
        ->and($user->canAccessLevel(1))->toBeTrue()
        ->and($user->canAccessLevel(3))->toBeTrue();
});

it('niega el acceso a un video de nivel superior al plan', function () {
    $basic = Plan::factory()->create(); // nivel 1
    $user = User::factory()->create();
    Subscription::factory()->for($user)->for($basic)->create();

    expect($user->planLevel())->toBe(1)
        ->and($user->canAccessLevel(3))->toBeFalse();
});

it('no considera activa una suscripción cancelada', function () {
    $user = User::factory()->create();
    Subscription::factory()->for($user)->create([
        'status' => SubscriptionStatus::Cancelled,
        'cancelled_at' => now(),
    ]);

    expect($user->hasActiveSubscription())->toBeFalse()
        ->and($user->planLevel())->toBe(0);
});

it('deja al admin acceder a cualquier nivel sin suscripción', function () {
    $admin = User::factory()->admin()->create();

    expect($admin->canAccessLevel(3))->toBeTrue()
        ->and($admin->hasActiveSubscription())->toBeFalse();
});

it('filtra los videos visibles según el nivel del plan', function () {
    Video::factory()->create(['min_plan_level' => 1]);
    Video::factory()->create(['min_plan_level' => 3]);

    expect(Video::published()->visibleForLevel(1)->count())->toBe(1)
        ->and(Video::published()->visibleForLevel(3)->count())->toBe(2);
});
