<x-app-layout>
    <x-slot name="header">
        <h2 class="font-display text-2xl font-extrabold uppercase tracking-wide text-white">
            Mi panel
        </h2>
    </x-slot>

    @php($subscription = auth()->user()->activeSubscription)

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-xl border border-volt/40 bg-volt/10 px-4 py-3 text-sm font-medium text-volt">
                    {{ match (session('status')) {
                        'subscription-active' => '¡Listo! Tu suscripción está activa.',
                        'subscription-pending' => 'Tu pago quedó pendiente de confirmación.',
                        'subscription-cancelled' => 'Tu suscripción fue cancelada.',
                        default => 'Operación realizada.',
                    } }}
                </div>
            @endif

            <div class="card">
                <p class="eyebrow">Tu plan</p>

                @if ($subscription)
                    <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h3 class="text-3xl font-extrabold uppercase text-white">
                                {{ $subscription->plan->name }}
                            </h3>
                            <p class="mt-1 text-sm text-slate-400">
                                {{ $subscription->plan->priceFormatted() }} CLP / mes
                                @if ($subscription->current_period_end)
                                    · activo hasta {{ $subscription->current_period_end->format('d/m/Y') }}
                                @endif
                            </p>
                        </div>

                        <form method="POST" action="{{ route('subscriptions.cancel') }}">
                            @csrf
                            <button type="submit" class="btn-ghost text-sm">Cancelar suscripción</button>
                        </form>
                    </div>
                @else
                    <h3 class="mt-3 text-2xl font-extrabold uppercase text-white">Sin suscripción</h3>
                    <p class="mt-1 text-sm text-slate-400">
                        Elige un plan para acceder a los entrenamientos.
                    </p>
                    <a href="{{ route('planes') }}" class="btn-primary mt-6">Ver planes</a>
                @endif
            </div>

            <p class="text-sm text-slate-500">
                Hola {{ auth()->user()->name }}. Aquí verás tu progreso e historial de entrenamientos.
            </p>
        </div>
    </div>
</x-app-layout>
