<?php

namespace App\Services\Subscriptions;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;

/**
 * Orquesta el ciclo de vida de una suscripción. Es la única clase que
 * conoce las reglas de negocio; delega todo lo específico del proveedor
 * en PaymentGateway.
 */
class SubscriptionService
{
    public function __construct(
        private readonly PaymentGateway $gateway,
    ) {}

    /**
     * Crea la suscripción (pendiente) y devuelve la URL de pago del proveedor.
     */
    public function start(User $user, Plan $plan, string $backUrl): string
    {
        // Limpia intentos de pago abandonados para no acumular basura.
        Subscription::query()
            ->where('user_id', $user->id)
            ->where('status', SubscriptionStatus::Pending)
            ->delete();

        $subscription = Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
        ]);

        $redirect = $this->gateway->startSubscription($user, $plan, $backUrl);

        $subscription->update(['mp_preapproval_id' => $redirect->preapprovalId]);

        return $redirect->url;
    }

    /**
     * Consulta el estado real al proveedor y lo aplica (se usa al volver del checkout).
     */
    public function confirm(Subscription $subscription): Subscription
    {
        if (! $subscription->mp_preapproval_id) {
            return $subscription;
        }

        $this->applyStatus(
            $subscription,
            $this->statusFromProvider($subscription->mp_preapproval_id),
        );

        return $subscription->refresh();
    }

    public function cancel(Subscription $subscription): void
    {
        $this->gateway->cancelSubscription($subscription);

        $subscription->update([
            'status' => SubscriptionStatus::Cancelled,
            'cancelled_at' => now(),
        ]);
    }

    public function changePlan(Subscription $subscription, Plan $plan): void
    {
        $this->gateway->swapPlan($subscription, $plan);

        $subscription->update(['plan_id' => $plan->id]);
    }

    public function statusFromProvider(string $providerSubscriptionId): SubscriptionStatus
    {
        return $this->gateway->retrieveStatus($providerSubscriptionId);
    }

    /**
     * Aplica un estado del proveedor a la suscripción local.
     * Idempotente: llamarlo dos veces con el mismo estado no cambia el resultado.
     */
    public function applyStatus(Subscription $subscription, SubscriptionStatus $status): void
    {
        $isActive = $status->isActive();

        $subscription->update([
            'status' => $status,
            'started_at' => $subscription->started_at ?? ($isActive ? now() : null),
            'current_period_end' => $isActive ? now()->addMonth() : $subscription->current_period_end,
            'cancelled_at' => $status === SubscriptionStatus::Cancelled
                ? ($subscription->cancelled_at ?? now())
                : null,
        ]);
    }
}
