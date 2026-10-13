<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

it('activa las suscripciones pendientes que la pasarela ya autorizó', function () {
    // El caso real que motiva la reconciliación: el cliente pagó en Mercado Pago
    // pero el webhook nunca llegó (o llegó mal), así que seguía pendiente.
    $user = User::factory()->create();

    $subscription = Subscription::factory()->for($user)->for(Plan::factory()->create())->create([
        'status' => SubscriptionStatus::Pending,
        'mp_preapproval_id' => 'fake_pagada',
    ]);

    $this->artisan('subscriptions:reconcile')->assertSuccessful();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Authorized)
        ->and($user->fresh()->hasActiveSubscription())->toBeTrue();
});

it('deja intactas las pendientes fuera de la ventana de tiempo', function () {
    // Un intento de pago abandonado hace días no es un cliente esperando acceso.
    $user = User::factory()->create();

    $abandonada = Subscription::factory()->for($user)->for(Plan::factory()->create())->create([
        'status' => SubscriptionStatus::Pending,
        'mp_preapproval_id' => 'fake_abandonada',
        'created_at' => now()->subDays(3),
    ]);

    $this->artisan('subscriptions:reconcile')->assertSuccessful();

    expect($abandonada->refresh()->status)->toBe(SubscriptionStatus::Pending);
});

it('no falla si no hay nada pendiente', function () {
    $this->artisan('subscriptions:reconcile')->assertSuccessful();
});
