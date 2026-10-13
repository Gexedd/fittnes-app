<?php

namespace App\Http\Controllers;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SubscriptionController
{
    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    /**
     * Inicia el alta de la suscripción y redirige al checkout del proveedor.
     */
    public function store(Request $request, Plan $plan): RedirectResponse
    {
        $url = $this->subscriptions->start(
            user: $request->user(),
            plan: $plan,
            backUrl: route('subscriptions.callback', ['back' => route('dashboard')]),
        );

        return redirect()->away($url);
    }

    /**
     * Retorno del checkout: confirma el estado contra el proveedor y activa.
     */
    public function callback(Request $request): RedirectResponse
    {
        // Mercado Pago NO siempre añade preapproval_id a la back_url. Si se
        // exige con validate(), Laravel redirige "de vuelta" al referer, que es
        // el propio checkout de Mercado Pago: el usuario sale y vuelve sin que
        // se active nada. Por eso el parámetro es opcional y, si falta, se usa
        // la suscripción pendiente del usuario (es única: start() borra las
        // anteriores).
        $preapprovalId = (string) $request->query('preapproval_id', '');

        Log::info('Retorno de la pasarela', [
            'user_id' => $request->user()->id,
            'query' => $request->query(),
            'referer' => $request->headers->get('referer'),
        ]);

        $subscription = Subscription::query()
            ->where('user_id', $request->user()->id)
            ->when(
                $preapprovalId !== '',
                fn ($query) => $query->where('mp_preapproval_id', $preapprovalId),
                fn ($query) => $query->where('status', SubscriptionStatus::Pending)->latest('id'),
            )
            ->firstOrFail();

        $this->subscriptions->confirm($subscription);

        $message = $subscription->refresh()->isActive()
            ? 'subscription-active'
            : 'subscription-pending';

        return redirect()->route('dashboard')->with('status', $message);
    }

    /**
     * Cancela la suscripción activa del usuario.
     */
    public function cancel(Request $request): RedirectResponse
    {
        $subscription = $request->user()->activeSubscription;

        if ($subscription) {
            $this->subscriptions->cancel($subscription);
        }

        return redirect()->route('dashboard')->with('status', 'subscription-cancelled');
    }
}
