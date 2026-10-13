<?php

namespace App\Services\Payments;

use App\Contracts\Payments\CheckoutRedirect;
use App\Contracts\Payments\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Integración real con la API de Suscripciones (preapproval) de Mercado Pago.
 *
 * Se usa el cliente HTTP de Laravel en vez de un SDK: son 4 llamadas HTTP y así
 * evitamos depender de una librería de terceros.
 *
 * Docs: https://www.mercadopago.cl/developers/es/docs/subscriptions/overview
 */
class MercadoPagoGateway implements PaymentGateway
{
    public function __construct(
        private readonly string $accessToken,
        private readonly string $baseUrl = 'https://api.mercadopago.com',
        private readonly bool $sandbox = false,
        private readonly ?string $testPayerEmail = null,
    ) {
        if ($this->accessToken === '') {
            throw new RuntimeException('Falta MERCADOPAGO_ACCESS_TOKEN para usar la pasarela real.');
        }
    }

    public function provider(): string
    {
        return 'mercadopago';
    }

    public function startSubscription(User $user, Plan $plan, string $backUrl): CheckoutRedirect
    {
        $preapproval = $this->client()
            ->post("{$this->baseUrl}/preapproval", [
                'reason' => "{$plan->name} - ".config('app.name'),
                'external_reference' => 'user_'.$user->id,
                'payer_email' => $this->payerEmail($user),
                'back_url' => $backUrl,
                'status' => 'pending',
                'auto_recurring' => [
                    'frequency' => 1,
                    'frequency_type' => 'months',
                    'transaction_amount' => $plan->price_clp,
                    'currency_id' => 'CLP',
                ],
            ])
            ->throw()
            ->json();

        return new CheckoutRedirect(
            preapprovalId: (string) $preapproval['id'],
            url: $this->checkoutUrl($preapproval),
        );
    }

    /**
     * En sandbox el pagador tiene que ser un usuario de prueba de Mercado Pago;
     * si configuraste uno, se usa su email en lugar del email real del usuario.
     */
    private function payerEmail(User $user): string
    {
        return $this->sandbox && $this->testPayerEmail
            ? $this->testPayerEmail
            : $user->email;
    }

    /**
     * Con credenciales de prueba hay que mandar al usuario al checkout de
     * sandbox; con credenciales de producción, al checkout real.
     */
    private function checkoutUrl(array $preapproval): string
    {
        $production = (string) ($preapproval['init_point'] ?? '');
        $sandboxUrl = (string) ($preapproval['sandbox_init_point'] ?? '');

        if ($this->sandbox && $sandboxUrl !== '') {
            return $sandboxUrl;
        }

        return $production !== '' ? $production : $sandboxUrl;
    }

    public function retrieveStatus(string $providerSubscriptionId): SubscriptionStatus
    {
        $status = $this->client()
            ->get("{$this->baseUrl}/preapproval/{$providerSubscriptionId}")
            ->throw()
            ->json('status');

        // Los estados de Mercado Pago coinciden con nuestro enum.
        return SubscriptionStatus::tryFrom((string) $status) ?? SubscriptionStatus::Pending;
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        $this->client()
            ->put("{$this->baseUrl}/preapproval/{$subscription->mp_preapproval_id}", [
                'status' => 'cancelled',
            ])
            ->throw();
    }

    public function swapPlan(Subscription $subscription, Plan $plan): void
    {
        $this->client()
            ->put("{$this->baseUrl}/preapproval/{$subscription->mp_preapproval_id}", [
                'reason' => "{$plan->name} - ".config('app.name'),
                'auto_recurring' => [
                    'transaction_amount' => $plan->price_clp,
                    'currency_id' => 'CLP',
                ],
            ])
            ->throw();
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->accessToken)
            ->acceptJson()
            ->asJson()
            ->timeout(15);
    }
}
