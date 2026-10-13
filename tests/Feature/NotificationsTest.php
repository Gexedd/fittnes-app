<?php

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\RenewalReminder;
use App\Notifications\SubscriptionActivated;
use App\Notifications\SubscriptionCancelled;
use App\Notifications\WelcomeNotification;
use Illuminate\Support\Facades\Notification;
use Livewire\Volt\Volt;

it('envía el correo de bienvenida al registrarse', function () {
    Notification::fake();

    Volt::test('pages.auth.register')
        ->set('name', 'Ana Pérez')
        ->set('email', 'ana@example.com')
        ->set('password', 'password123')
        ->set('password_confirmation', 'password123')
        ->call('register')
        ->assertHasNoErrors();

    Notification::assertSentTo(
        User::where('email', 'ana@example.com')->firstOrFail(),
        WelcomeNotification::class,
    );
});

it('avisa cuando la suscripción pasa a activa', function () {
    Notification::fake();

    $user = User::factory()->create();
    $plan = Plan::factory()->premium()->create();

    $this->actingAs($user)->post(route('subscriptions.store', $plan));

    $preapprovalId = Subscription::where('user_id', $user->id)->value('mp_preapproval_id');

    $this->actingAs($user)->get(route('subscriptions.callback', ['preapproval_id' => $preapprovalId]));

    Notification::assertSentTo($user, SubscriptionActivated::class);
});

it('no repite el aviso si el webhook vuelve a confirmar', function () {
    Notification::fake();

    $user = User::factory()->create();
    $plan = Plan::factory()->premium()->create();
    Subscription::factory()->for($user)->for($plan)->create([
        'status' => SubscriptionStatus::Authorized,
        'mp_preapproval_id' => 'fake_ya_activa',
    ]);

    $this->actingAs($user)->get(route('subscriptions.callback', ['preapproval_id' => 'fake_ya_activa']));

    Notification::assertNotSentTo($user, SubscriptionActivated::class);
});

it('avisa cuando se cancela la suscripción', function () {
    Notification::fake();

    $user = User::factory()->create();
    $plan = Plan::factory()->premium()->create();
    Subscription::factory()->for($user)->for($plan)->create(['mp_preapproval_id' => 'fake_activa']);

    $this->actingAs($user)->post(route('subscriptions.cancel'));

    Notification::assertSentTo($user, SubscriptionCancelled::class);
});

it('envía recordatorios solo a las suscripciones que vencen ese día', function () {
    Notification::fake();

    $plan = Plan::factory()->premium()->create();

    $dueSoon = User::factory()->create();
    Subscription::factory()->for($dueSoon)->for($plan)->create([
        'status' => SubscriptionStatus::Authorized,
        'current_period_end' => now()->addDays(3),
    ]);

    $later = User::factory()->create();
    Subscription::factory()->for($later)->for($plan)->create([
        'status' => SubscriptionStatus::Authorized,
        'current_period_end' => now()->addDays(20),
    ]);

    $this->artisan('subscriptions:renewal-reminders')->assertSuccessful();

    Notification::assertSentTo($dueSoon, RenewalReminder::class);
    Notification::assertNotSentTo($later, RenewalReminder::class);
});

it('renderiza los correos con la marca', function () {
    $user = User::factory()->create(['name' => 'Ana Pérez']);
    $plan = Plan::factory()->premium()->create();
    $subscription = Subscription::factory()->for($user)->for($plan)->create();

    $welcome = (new WelcomeNotification)->toMail($user)->render();
    expect($welcome)->toContain('Ana Pérez')->toContain('Fitnes');

    $activated = (new SubscriptionActivated($subscription))->toMail($user)->render();
    expect($activated)->toContain($plan->name)->toContain('24.990');

    $cancelled = (new SubscriptionCancelled($subscription))->toMail($user)->render();
    expect($cancelled)->toContain('cancelada');

    $reminder = (new RenewalReminder($subscription, 3))->toMail($user)->render();
    expect($reminder)->toContain('3 día');
});
