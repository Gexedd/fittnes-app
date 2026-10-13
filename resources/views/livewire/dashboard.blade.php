<?php

use App\Services\Video\WatchTracker;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        $user = auth()->user();
        $tracker = app(WatchTracker::class);

        return [
            'subscription' => $user->activeSubscription,
            'summary' => $tracker->summary($user),
            'continueWatching' => $tracker->continueWatching($user),
            'history' => $tracker->history($user),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-6xl space-y-6 px-4 sm:px-6 lg:px-8">
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

        <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            {{-- Plan --}}
            <div class="card">
                <p class="eyebrow">Tu plan</p>

                @if ($subscription)
                    <div class="mt-3 flex flex-wrap items-end justify-between gap-4">
                        <div>
                            <h1 class="text-3xl font-extrabold uppercase text-white">
                                {{ $subscription->plan->name }}
                            </h1>
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
                    <h1 class="mt-3 text-2xl font-extrabold uppercase text-white">Sin suscripción</h1>
                    <p class="mt-1 text-sm text-slate-400">Elige un plan para acceder a los entrenamientos.</p>
                    <a href="{{ route('planes') }}" class="btn-primary mt-6">Ver planes</a>
                @endif
            </div>

            {{-- Progreso --}}
            <div class="card">
                <p class="eyebrow">Tu progreso</p>
                <p class="mt-3 font-display text-4xl font-extrabold text-white">
                    {{ $summary['percent'] }}%
                </p>
                <div class="mt-4 h-2 overflow-hidden rounded-full bg-ink">
                    <div class="h-full rounded-full bg-volt transition-all"
                         style="width: {{ $summary['percent'] }}%"></div>
                </div>

                <dl class="mt-5 grid grid-cols-3 gap-3 text-center">
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-slate-500">Vistos</dt>
                        <dd class="font-display text-2xl font-bold text-volt">{{ $summary['completed'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-slate-500">En curso</dt>
                        <dd class="font-display text-2xl font-bold text-white">{{ $summary['in_progress'] }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs uppercase tracking-wider text-slate-500">Disponibles</dt>
                        <dd class="font-display text-2xl font-bold text-slate-300">{{ $summary['available'] }}</dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Continuar viendo --}}
        @if ($continueWatching->isNotEmpty())
            <section>
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <h2 class="font-display text-2xl font-bold uppercase text-white">Continuar viendo</h2>
                    <a href="{{ route('videos.index') }}" wire:navigate
                       class="text-sm text-slate-400 transition hover:text-volt">
                        Ver el catálogo completo
                    </a>
                </div>

                <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($continueWatching as $progress)
                        <a href="{{ route('videos.show', $progress->video) }}"
                           wire:navigate
                           wire:key="continue-{{ $progress->id }}"
                           class="card transition hover:border-ember-500/60">
                            <p class="text-xs uppercase tracking-wider text-slate-500">
                                {{ $progress->video->category?->name ?? 'General' }}
                            </p>
                            <h3 class="mt-1 font-display text-lg font-bold uppercase text-white">
                                {{ $progress->video->title }}
                            </h3>
                            <p class="mt-2 text-xs text-slate-500">
                                Nivel {{ $progress->video->min_plan_level }}
                                @if ($progress->video->duration_seconds)
                                    · {{ $progress->video->durationFormatted() }}
                                @endif
                            </p>
                        </a>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Historial --}}
        <section class="card">
            <h2 class="font-display text-2xl font-bold uppercase text-white">Historial reciente</h2>

            @if ($history->isEmpty())
                <p class="mt-4 text-sm text-slate-400">
                    Todavía no has visto ningún video.
                    <a href="{{ route('videos.index') }}" wire:navigate class="text-volt hover:underline">Explora el catálogo</a>.
                </p>
            @else
                <ul class="mt-4 divide-y divide-ink-line">
                    @foreach ($history as $view)
                        <li class="flex items-center justify-between gap-4 py-3" wire:key="history-{{ $view->id }}">
                            <div class="min-w-0">
                                <a href="{{ route('videos.show', $view->video) }}"
                                   wire:navigate
                                   class="font-medium text-slate-200 transition hover:text-volt">
                                    {{ $view->video->title }}
                                </a>
                                <p class="text-xs text-slate-500">
                                    {{ $view->video->category?->name ?? 'General' }}
                                </p>
                            </div>
                            <span class="shrink-0 text-xs text-slate-500">
                                {{ $view->created_at->locale('es')->diffForHumans() }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            @endif
        </section>
    </div>
</div>
