<?php

namespace App\Services\Subscriptions;

use App\Contracts\Payments\PaymentGateway;
use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\PaymentFailed;
use App\Notifications\SubscriptionActivated;
use App\Notifications\SubscriptionCancelled;
use Illuminate\Support\Facades\Log;
use Throwable;

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

        // Primero se crea la suscripción en el proveedor. Si la API falla,
        // la excepción sube y NO dejamos una suscripción huérfana en la base.
        $redirect = $this->gateway->startSubscription($user, $plan, $backUrl);

        Subscription::create([
            'user_id' => $user->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
            'mp_preapproval_id' => $redirect->preapprovalId,
        ]);

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

        $subscription->user->notify(new SubscriptionCancelled($subscription));
    }

    public function changePlan(Subscription $subscription, Plan $plan): void
    {
        $this->gateway->swapPlan($subscription, $plan);

        $subscription->update(['plan_id' => $plan->id]);
    }

    /**
     * Registra el cobro de un período y pausa la suscripción si se rechazó.
     *
     * Mercado Pago avisa con el webhook subscription_authorized_payment. Sin
     * esto, un cobro fallido del mes 2 pasaba desapercibido: el cliente seguía
     * con acceso sin pagar y la tabla payments quedaba vacía (por eso "ingresos
     * del mes" marcaba siempre 0).
     */
    public function recordAuthorizedPayment(string $providerPaymentId): void
    {
        $cobro = $this->gateway->retrieveAuthorizedPayment($providerPaymentId);

        $subscription = Subscription::query()
            ->where('mp_preapproval_id', $cobro->preapprovalId)
            ->first();

        // Un cobro de una suscripción que no es nuestra: no hacemos nada.
        if (! $subscription) {
            return;
        }

        Payment::updateOrCreate(
            ['mp_payment_id' => $cobro->id],
            [
                'user_id' => $subscription->user_id,
                'subscription_id' => $subscription->id,
                'amount_clp' => $cobro->amountClp,
                'status' => $cobro->approved ? 'approved' : 'rejected',
                'paid_at' => $cobro->approved ? ($cobro->paidAt ?? now()) : null,
            ],
        );

        if ($cobro->approved) {
            return;
        }

        // Cobro rechazado: se corta el acceso y se avisa al cliente (dunning).
        $this->applyStatus($subscription, SubscriptionStatus::Paused);
        $subscription->user->notify(new PaymentFailed($subscription));
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
        $wasActive = $subscription->status === SubscriptionStatus::Authorized;

        $subscription->update([
            'status' => $status,
            'started_at' => $subscription->started_at ?? ($isActive ? now() : null),
            'current_period_end' => $isActive ? now()->addMonth() : $subscription->current_period_end,
            'cancelled_at' => $status === SubscriptionStatus::Cancelled
                ? ($subscription->cancelled_at ?? now())
                : null,
        ]);

        // Solo se avisa en la transición a activa (no en cada webhook repetido).
        if (! $wasActive && $isActive) {
            $this->supersedeOtherSubscriptions($subscription);
            $subscription->user->notify(new SubscriptionActivated($subscription));
        }
    }

    /**
     * Al activarse una suscripción se cancelan las demás del usuario, también
     * en el proveedor. Sin esto, un cambio de plan deja dos preapprovals vivos
     * y Mercado Pago cobra los dos todos los meses.
     *
     * No se notifica al usuario: desde su punto de vista cambió de plan, no
     * perdió nada, y un email de "suscripción cancelada" solo asustaría.
     */
    private function supersedeOtherSubscriptions(Subscription $subscription): void
    {
        $others = Subscription::query()
            ->where('user_id', $subscription->user_id)
            ->whereKeyNot($subscription->getKey())
            ->where('status', SubscriptionStatus::Authorized)
            ->get();

        foreach ($others as $other) {
            try {
                $this->gateway->cancelSubscription($other);
            } catch (Throwable $e) {
                // Si el proveedor falla, la nueva suscripción igual queda activa.
                Log::warning('No se pudo cancelar la suscripción anterior', [
                    'subscription_id' => $other->id,
                    'error' => $e->getMessage(),
                ]);
            }

            $other->update([
                'status' => SubscriptionStatus::Cancelled,
                'cancelled_at' => now(),
            ]);
        }
    }
}
