<?php

use App\Contracts\Payments\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Payments\MercadoPagoGateway;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

it('inicia la suscripción y redirige al checkout', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->premium()->create();

    $response = $this->actingAs($user)->post(route('subscriptions.store', $plan));

    $response->assertRedirect();

    $subscription = Subscription::where('user_id', $user->id)->first();

    expect($subscription)->not->toBeNull()
        ->and($subscription->status)->toBe(SubscriptionStatus::Pending)
        ->and($subscription->mp_preapproval_id)->toStartWith('fake_');
});

it('activa la suscripción al volver del checkout', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->premium()->create();

    $checkoutUrl = $this->actingAs($user)
        ->post(route('subscriptions.store', $plan))
        ->headers->get('Location');

    $this->actingAs($user)->get($checkoutUrl)->assertRedirect(route('dashboard'));

    $user->refresh();

    expect($user->hasActiveSubscription())->toBeTrue()
        ->and($user->planLevel())->toBe(3);
});

it('no activa la suscripción si el proveedor la rechaza', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $subscription = Subscription::factory()->for($user)->for($plan)->create([
        'status' => SubscriptionStatus::Pending,
        'mp_preapproval_id' => 'fake_cancel_123',
    ]);

    $this->actingAs($user)
        ->get(route('subscriptions.callback', ['preapproval_id' => 'fake_cancel_123']))
        ->assertRedirect(route('dashboard'));

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Cancelled)
        ->and($user->fresh()->hasActiveSubscription())->toBeFalse();
});

it('cancela la suscripción activa', function () {
    $user = User::factory()->create();
    $plan = Plan::factory()->premium()->create();

    Subscription::factory()->for($user)->for($plan)->create([
        'mp_preapproval_id' => 'fake_activa',
    ]);

    $this->actingAs($user)
        ->post(route('subscriptions.cancel'))
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasActiveSubscription())->toBeFalse()
        ->and($user->fresh()->planLevel())->toBe(0);
});

it('rechaza un webhook sin firma válida', function () {
    config()->set('services.mercadopago.webhook_secret', 'secreto-de-prueba');

    $this->postJson(route('webhooks.mercadopago'), [
        'id' => 'evt_sin_firma',
        'type' => 'subscription_preapproval',
        'data' => ['id' => 'mp_1'],
    ])->assertUnauthorized();
});

it('procesa un webhook firmado y sincroniza la suscripción', function () {
    config()->set('services.mercadopago.webhook_secret', 'secreto-de-prueba');

    $user = User::factory()->create();
    $plan = Plan::factory()->premium()->create();

    $subscription = Subscription::factory()->for($user)->for($plan)->create([
        'status' => SubscriptionStatus::Pending,
        'mp_preapproval_id' => 'mp_123',
    ]);

    $timestamp = '1704908010';
    $requestId = 'req-1';
    $manifest = "id:mp_123;request-id:{$requestId};ts:{$timestamp};";

    $this->postJson(route('webhooks.mercadopago'), [
        'id' => 'evt_1',
        'type' => 'subscription_preapproval',
        'data' => ['id' => 'mp_123'],
    ], [
        'x-signature' => 'ts='.$timestamp.',v1='.hash_hmac('sha256', $manifest, 'secreto-de-prueba'),
        'x-request-id' => $requestId,
    ])->assertNoContent();

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Authorized)
        ->and(WebhookEvent::where('event_id', 'evt_1')->whereNotNull('processed_at')->exists())->toBeTrue();
});

it('ignora un webhook duplicado', function () {
    config()->set('services.mercadopago.webhook_secret', 'secreto-de-prueba');

    WebhookEvent::create([
        'provider' => 'mercadopago',
        'event_id' => 'evt_dup',
        'payload' => ['id' => 'evt_dup'],
        'processed_at' => now(),
    ]);

    $timestamp = '1704908010';
    $requestId = 'req-2';
    $manifest = "id:mp_999;request-id:{$requestId};ts:{$timestamp};";

    $this->postJson(route('webhooks.mercadopago'), [
        'id' => 'evt_dup',
        'type' => 'subscription_preapproval',
        'data' => ['id' => 'mp_999'],
    ], [
        'x-signature' => 'ts='.$timestamp.',v1='.hash_hmac('sha256', $manifest, 'secreto-de-prueba'),
        'x-request-id' => $requestId,
    ])->assertNoContent();

    expect(Subscription::where('mp_preapproval_id', 'mp_999')->exists())->toBeFalse();
});

it('el gateway de Mercado Pago crea el preapproval y consulta su estado', function () {
    Http::fake([
        'api.mercadopago.com/preapproval' => Http::response([
            'id' => 'mp_new_1',
            'init_point' => 'https://www.mercadopago.cl/subscriptions/checkout?preapproval_id=mp_new_1',
        ], 201),
        'api.mercadopago.com/preapproval/mp_new_1' => Http::response([
            'id' => 'mp_new_1',
            'status' => 'authorized',
        ], 200),
    ]);

    $this->app->instance(PaymentGateway::class, new MercadoPagoGateway('token-de-prueba'));

    $user = User::factory()->create();
    $plan = Plan::factory()->premium()->create();

    $checkoutUrl = $this->actingAs($user)
        ->post(route('subscriptions.store', $plan))
        ->headers->get('Location');

    expect($checkoutUrl)->toContain('preapproval_id=mp_new_1');

    Http::assertSent(fn ($request) => str_contains($request->url(), '/preapproval')
        && $request['auto_recurring']['currency_id'] === 'CLP'
        && $request['auto_recurring']['transaction_amount'] === $plan->price_clp);

    // Al volver del checkout, el estado se consulta al proveedor y se activa.
    // En producción el usuario regresa DESDE Mercado Pago con ?preapproval_id=...,
    // así que aquí simulamos ese retorno a nuestra propia ruta de callback.
    $this->actingAs($user)
        ->get(route('subscriptions.callback', ['preapproval_id' => 'mp_new_1']))
        ->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasActiveSubscription())->toBeTrue()
        ->and($user->fresh()->planLevel())->toBe(3);
});

it('no deja una suscripción pendiente si la pasarela falla', function () {
    Http::fake([
        'api.mercadopago.com/preapproval' => Http::response(['message' => 'Invalid value for back_url'], 400),
    ]);

    $this->app->instance(PaymentGateway::class, new MercadoPagoGateway(accessToken: 'token-de-prueba'));

    $user = User::factory()->create();
    $plan = Plan::factory()->create();

    $this->withoutExceptionHandling();

    expect(fn () => $this->actingAs($user)->post(route('subscriptions.store', $plan)))
        ->toThrow(RequestException::class);

    expect(Subscription::where('user_id', $user->id)->count())->toBe(0);
});

it('en sandbox usa el checkout de prueba y el pagador de prueba', function () {
    Http::fake([
        'api.mercadopago.com/preapproval' => Http::response([
            'id' => 'mp_sandbox_1',
            'init_point' => 'https://www.mercadopago.cl/subscriptions/checkout',
            'sandbox_init_point' => 'https://sandbox.mercadopago.cl/subscriptions/checkout',
        ], 201),
    ]);

    $this->app->instance(PaymentGateway::class, new MercadoPagoGateway(
        accessToken: 'token-de-prueba',
        sandbox: true,
        testPayerEmail: 'test_user_123@testuser.com',
    ));

    $user = User::factory()->create(['email' => 'cuenta-real@fittnes.test']);
    $plan = Plan::factory()->create();

    $this->actingAs($user)
        ->post(route('subscriptions.store', $plan))
        ->assertRedirect('https://sandbox.mercadopago.cl/subscriptions/checkout');

    Http::assertSent(fn ($request) => $request['payer_email'] === 'test_user_123@testuser.com');
});
