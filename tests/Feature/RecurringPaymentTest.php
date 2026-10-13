<?php

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentFailed;
use App\Services\Payments\MercadoPagoGateway;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;

it('registra el cobro del período cuando la pasarela lo aprueba', function () {
    $user = User::factory()->create();
    $subscription = Subscription::factory()->for($user)->for(Plan::factory()->create())->create([
        'status' => SubscriptionStatus::Authorized,
        'mp_preapproval_id' => 'fake_1',
    ]);

    app(SubscriptionService::class)->recordAuthorizedPayment('fake_invoice_fake_1');

    $payment = Payment::sole();

    expect($payment->status)->toBe('approved')
        ->and($payment->amount_clp)->toBe(9_990)
        ->and($payment->paid_at)->not->toBeNull()
        ->and($payment->subscription_id)->toBe($subscription->id)
        ->and($subscription->refresh()->status)->toBe(SubscriptionStatus::Authorized);
});

it('pausa la suscripción y avisa al cliente cuando el cobro se rechaza', function () {
    // Dunning: antes, un cobro fallido pasaba desapercibido y el cliente seguía
    // con acceso sin pagar.
    Notification::fake();

    $user = User::factory()->create();
    $subscription = Subscription::factory()->for($user)->for(Plan::factory()->create())->create([
        'status' => SubscriptionStatus::Authorized,
        'mp_preapproval_id' => 'fake_2',
    ]);

    app(SubscriptionService::class)->recordAuthorizedPayment('fake_invoice_fake_2_rejected');

    expect($subscription->refresh()->status)->toBe(SubscriptionStatus::Paused)
        ->and($user->fresh()->planLevel())->toBe(0);

    $payment = Payment::sole();

    expect($payment->status)->toBe('rejected')
        ->and($payment->paid_at)->toBeNull();

    Notification::assertSentTo($user, PaymentFailed::class);
});

it('no registra cobros de suscripciones que no existen', function () {
    app(SubscriptionService::class)->recordAuthorizedPayment('fake_invoice_desconocida');

    expect(Payment::count())->toBe(0);
});

it('procesa el webhook de cobro y registra el pago', function () {
    config()->set('services.mercadopago.webhook_secret', 'secreto-de-prueba');

    $user = User::factory()->create();
    Subscription::factory()->for($user)->for(Plan::factory()->create())->create([
        'mp_preapproval_id' => 'fake_3',
    ]);

    $timestamp = '1704908010';
    $requestId = 'req-cobro';
    $manifest = "id:fake_invoice_fake_3;request-id:{$requestId};ts:{$timestamp};";

    $this->postJson(route('webhooks.mercadopago'), [
        'id' => 'evt_cobro_1',
        'type' => 'subscription_authorized_payment',
        'data' => ['id' => 'fake_invoice_fake_3'],
    ], [
        'x-signature' => 'ts='.$timestamp.',v1='.hash_hmac('sha256', $manifest, 'secreto-de-prueba'),
        'x-request-id' => $requestId,
    ])->assertOk();

    expect(Payment::where('mp_payment_id', 'fake_invoice_fake_3')->exists())->toBeTrue();
});

it('el gateway de Mercado Pago interpreta la factura del período', function () {
    Http::fake([
        'api.mercadopago.com/authorized_payments/*' => Http::response([
            'id' => 12345,
            'preapproval_id' => '2c938084726fca480172750000000000',
            'status' => 'processed',
            'transaction_amount' => 14990,
            'payment' => [
                'status' => 'approved',
                'date_approved' => '2026-11-13T10:00:00.000-03:00',
            ],
        ]),
    ]);

    $cobro = (new MercadoPagoGateway(accessToken: 'token-de-prueba'))
        ->retrieveAuthorizedPayment('12345');

    expect($cobro->id)->toBe('12345')
        ->and($cobro->preapprovalId)->toBe('2c938084726fca480172750000000000')
        ->and($cobro->amountClp)->toBe(14990)
        ->and($cobro->approved)->toBeTrue()
        ->and($cobro->paidAt)->not->toBeNull();
});

it('el gateway marca como no aprobado un cobro rechazado', function () {
    Http::fake([
        'api.mercadopago.com/authorized_payments/*' => Http::response([
            'id' => 999,
            'preapproval_id' => '2c9380',
            'status' => 'rejected',
            'transaction_amount' => 9990,
            'payment' => ['status' => 'rejected'],
        ]),
    ]);

    $cobro = (new MercadoPagoGateway(accessToken: 'token-de-prueba'))
        ->retrieveAuthorizedPayment('999');

    expect($cobro->approved)->toBeFalse()
        ->and($cobro->amountClp)->toBe(9990);
});
