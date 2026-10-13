<?php

namespace App\Services\Payments;

use App\Contracts\Payments\AuthorizedPayment;
use App\Contracts\Payments\CheckoutRedirect;
use App\Contracts\Payments\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Support\Str;

/**
 * Driver para desarrollo y tests: NO llama a ninguna API externa.
 * Simula un pago exitoso redirigiendo a una ruta interna.
 *
 * Es el driver por defecto (PAYMENT_DRIVER=fake), así se puede probar todo el
 * flujo de suscripción sin credenciales de Mercado Pago.
 */
class FakeGateway implements PaymentGateway
{
    public function provider(): string
    {
        return 'fake';
    }

    public function startSubscription(User $user, Plan $plan, string $backUrl): CheckoutRedirect
    {
        $preapprovalId = 'fake_'.Str::random(16);

        return new CheckoutRedirect(
            preapprovalId: $preapprovalId,
            url: route('subscriptions.callback', [
                'preapproval_id' => $preapprovalId,
                'back' => $backUrl,
            ]),
        );
    }

    /**
     * Siempre "autorizada": en local no hay un pago real que rechazar.
     * Si el ID empieza con "fake_cancel" simula un pago rechazado (para tests).
     */
    public function retrieveStatus(string $providerSubscriptionId): SubscriptionStatus
    {
        return str_starts_with($providerSubscriptionId, 'fake_cancel')
            ? SubscriptionStatus::Cancelled
            : SubscriptionStatus::Authorized;
    }

    /**
     * Cobro falso para tests. El id de la factura sigue el patrón
     * "fake_invoice_<preapproval_id>" y, si termina en "_rejected", simula un
     * cobro rechazado. Así los tests controlan el resultado sin tocar la API.
     */
    public function retrieveAuthorizedPayment(string $providerPaymentId): AuthorizedPayment
    {
        $rejected = str_ends_with($providerPaymentId, '_rejected');
        $invoiceId = $rejected ? Str::beforeLast($providerPaymentId, '_rejected') : $providerPaymentId;

        return new AuthorizedPayment(
            id: $providerPaymentId,
            preapprovalId: Str::after($invoiceId, 'fake_invoice_'),
            amountClp: 9_990,
            approved: ! $rejected,
            paidAt: now(),
        );
    }

    public function cancelSubscription(Subscription $subscription): void
    {
        // No hay proveedor real: nada que cancelar.
    }

    public function swapPlan(Subscription $subscription, Plan $plan): void
    {
        // No hay proveedor real: nada que actualizar.
    }
}
