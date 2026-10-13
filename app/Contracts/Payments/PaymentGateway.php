<?php

namespace App\Contracts\Payments;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

/**
 * Contrato que abstrae la pasarela de pagos.
 *
 * Hoy existe una implementación real (Mercado Pago) y una falsa (FakeGateway)
 * para desarrollo y tests. Gracias a esta interfaz, la aplicación no depende
 * de ningún proveedor concreto: cambiarlo es cambiar el binding en
 * AppServiceProvider.
 */
interface PaymentGateway
{
    /**
     * Identificador del proveedor. Se guarda en webhook_events.provider.
     */
    public function provider(): string;

    /**
     * Inicia el alta de una suscripción recurrente.
     * Devuelve el ID en el proveedor y la URL a la que redirigir al usuario.
     */
    public function startSubscription(User $user, Plan $plan, string $backUrl): CheckoutRedirect;

    /**
     * Consulta el estado actual de la suscripción en el proveedor.
     * Se usa al volver del checkout y para reconciliar tras un webhook.
     */
    public function retrieveStatus(string $providerSubscriptionId): SubscriptionStatus;

    /**
     * Cancela la suscripción en el proveedor.
     */
    public function cancelSubscription(Subscription $subscription): void;

    /**
     * Cambia de plan (upgrade/downgrade) una suscripción existente.
     */
    public function swapPlan(Subscription $subscription, Plan $plan): void;

    /**
     * Consulta un cobro concreto de una suscripción: la factura del período.
     * Se usa al recibir el webhook subscription_authorized_payment.
     */
    public function retrieveAuthorizedPayment(string $providerPaymentId): AuthorizedPayment;
}
