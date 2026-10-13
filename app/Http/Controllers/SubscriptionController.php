<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

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
        $validated = $request->validate([
            'preapproval_id' => ['required', 'string'],
        ]);

        $subscription = Subscription::query()
            ->where('user_id', $request->user()->id)
            ->where('mp_preapproval_id', $validated['preapproval_id'])
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
