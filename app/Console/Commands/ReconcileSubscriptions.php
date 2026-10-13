<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Red de seguridad de pagos.
 *
 * Los webhooks son "best effort": pueden perderse, llegar tarde o no estar
 * configurados. Este comando pregunta directamente a la pasarela por las
 * suscripciones que siguen pendientes, para que nadie termine pagando sin
 * recibir el servicio.
 */
class ReconcileSubscriptions extends Command
{
    protected $signature = 'subscriptions:reconcile {--minutes=120 : Antigüedad máxima de las pendientes a revisar}';

    protected $description = 'Sincroniza con la pasarela las suscripciones que quedaron pendientes';

    public function handle(SubscriptionService $subscriptions): int
    {
        // No tiene sentido revisar pendientes muy antiguas: son intentos de pago
        // abandonados, no clientes esperando su acceso.
        $pagoReciente = now()->subMinutes((int) $this->option('minutes'));

        $pendientes = Subscription::query()
            ->where('status', SubscriptionStatus::Pending)
            ->whereNotNull('mp_preapproval_id')
            ->where('created_at', '>=', $pagoReciente)
            ->with('user')
            ->get();

        if ($pendientes->isEmpty()) {
            $this->info('No hay suscripciones pendientes por revisar.');

            return self::SUCCESS;
        }

        $activadas = 0;

        foreach ($pendientes as $subscription) {
            try {
                $subscriptions->confirm($subscription);
            } catch (Throwable $e) {
                // Si la pasarela falla, la suscripción sigue pendiente y se
                // reintentará en la próxima pasada.
                Log::warning('No se pudo reconciliar la suscripción', [
                    'subscription_id' => $subscription->id,
                    'error' => $e->getMessage(),
                ]);

                $this->error("Sub {$subscription->id}: {$e->getMessage()}");

                continue;
            }

            $status = $subscription->refresh()->status;

            if ($status === SubscriptionStatus::Authorized) {
                $activadas++;
            }

            $this->line("Sub {$subscription->id} ({$subscription->user->email}): {$status->value}");
        }

        $this->info("Suscripciones activadas en esta pasada: {$activadas}");

        return self::SUCCESS;
    }
}
