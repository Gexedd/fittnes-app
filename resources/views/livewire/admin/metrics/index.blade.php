<?php

use App\Services\Metrics\MetricsService;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('layouts.app')] class extends Component
{
    public function with(): array
    {
        $metrics = app(MetricsService::class);

        return [
            'mrr' => $metrics->mrr(),
            'activeSubscribers' => $metrics->activeSubscribers(),
            'subscribersByPlan' => $metrics->subscribersByPlan(),
            'newThisMonth' => $metrics->newSubscriptionsThisMonth(),
            'cancellationsThisMonth' => $metrics->cancellationsThisMonth(),
            'revenueThisMonth' => $metrics->revenueThisMonth(),
            'topVideos' => $metrics->topVideos(5),
            'totalViews' => $metrics->totalViews(),
            'publishedVideos' => $metrics->publishedVideos(),
        ];
    }
}; ?>

<div class="py-10">
    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8">
        <header>
            <p class="eyebrow">Panel de administración</p>
            <h1 class="mt-2 text-3xl font-extrabold uppercase text-white">Métricas</h1>
            <p class="mt-2 text-sm text-slate-500">Resumen en vivo de tu plataforma.</p>
        </header>

        {{-- KPIs --}}
        <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="card">
                <p class="text-xs uppercase tracking-wider text-slate-500">MRR</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-ember-500">
                    ${{ number_format($mrr, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-slate-500">Ingreso recurrente mensual (CLP)</p>
            </div>

            <div class="card">
                <p class="text-xs uppercase tracking-wider text-slate-500">Suscriptores activos</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-white">{{ $activeSubscribers }}</p>
                <p class="mt-1 text-xs text-slate-500">
                    +{{ $newThisMonth }} este mes · −{{ $cancellationsThisMonth }} bajas
                </p>
            </div>

            <div class="card">
                <p class="text-xs uppercase tracking-wider text-slate-500">Ingresos del mes</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-volt">
                    ${{ number_format($revenueThisMonth, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-slate-500">Pagos confirmados</p>
            </div>

            <div class="card">
                <p class="text-xs uppercase tracking-wider text-slate-500">Vistas totales</p>
                <p class="mt-2 font-display text-3xl font-extrabold text-white">
                    {{ number_format($totalViews, 0, ',', '.') }}
                </p>
                <p class="mt-1 text-xs text-slate-500">{{ $publishedVideos }} videos publicados</p>
            </div>
        </div>

        <div class="mt-6 grid gap-6 lg:grid-cols-2">
            {{-- Suscriptores por plan --}}
            <div class="card">
                <h2 class="font-display text-xl font-bold uppercase text-white">Suscriptores por plan</h2>

                <table class="mt-4 w-full text-left text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wider text-slate-500">
                            <th scope="col" class="py-2 font-semibold">Plan</th>
                            <th scope="col" class="py-2 font-semibold">Precio</th>
                            <th scope="col" class="py-2 text-right font-semibold">Activos</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-line">
                        @forelse ($subscribersByPlan as $plan)
                            <tr>
                                <td class="py-3 text-slate-200">{{ $plan->name }}</td>
                                <td class="py-3 text-slate-400">{{ $plan->priceFormatted() }}</td>
                                <td class="py-3 text-right font-semibold text-volt">{{ $plan->active_count }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="py-3 text-slate-400">Todavía no hay planes.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            {{-- Videos más vistos --}}
            <div class="card">
                <h2 class="font-display text-xl font-bold uppercase text-white">Videos más vistos</h2>

                @if ($topVideos->isEmpty())
                    <p class="mt-4 text-sm text-slate-400">Sin datos todavía.</p>
                @else
                    @php($maxViews = max(1, (int) $topVideos->max('views_count')))

                    <ul class="mt-4 space-y-4">
                        @foreach ($topVideos as $video)
                            <li>
                                <div class="flex items-baseline justify-between gap-4">
                                    <span class="truncate text-sm text-slate-200">{{ $video->title }}</span>
                                    <span class="shrink-0 text-xs font-semibold text-slate-400">
                                        {{ $video->views_count }} vistas
                                    </span>
                                </div>
                                <div class="mt-2 h-2 overflow-hidden rounded-full bg-ink">
                                    <div class="h-full rounded-full bg-ember-500"
                                         style="width: {{ round($video->views_count / $maxViews * 100) }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>
    </div>
</div>
