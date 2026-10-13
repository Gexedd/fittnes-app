<?php

namespace App\Services\Metrics;

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Video;
use App\Models\VideoView;
use Illuminate\Support\Collection;

/**
 * Centraliza las consultas de métricas del negocio.
 * Así el componente de la vista no sabe nada de SQL y las cifras son testeables.
 */
class MetricsService
{
    /**
     * MRR: ingreso recurrente mensual (CLP).
     * Suma el precio de los planes de las suscripciones activas.
     */
    public function mrr(): int
    {
        return (int) Subscription::query()
            ->active()
            ->join('plans', 'plans.id', '=', 'subscriptions.plan_id')
            ->sum('plans.price_clp');
    }

    public function activeSubscribers(): int
    {
        return Subscription::query()->active()->count();
    }

    /**
     * Suscriptores activos agrupados por plan.
     *
     * @return Collection<int, Plan>
     */
    public function subscribersByPlan(): Collection
    {
        return Plan::query()
            ->withCount(['subscriptions as active_count' => fn ($query) => $query->where('status', SubscriptionStatus::Authorized)])
            ->orderBy('level')
            ->get();
    }

    public function newSubscriptionsThisMonth(): int
    {
        return Subscription::query()
            ->where('status', SubscriptionStatus::Authorized)
            ->where('started_at', '>=', now()->startOfMonth())
            ->count();
    }

    public function cancellationsThisMonth(): int
    {
        return Subscription::query()
            ->where('cancelled_at', '>=', now()->startOfMonth())
            ->count();
    }

    /**
     * Ingresos cobrados en el mes actual (desde la tabla de pagos).
     */
    public function revenueThisMonth(): int
    {
        return (int) Payment::query()
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('amount_clp');
    }

    /**
     * Videos más vistos.
     *
     * @return Collection<int, Video>
     */
    public function topVideos(int $limit = 5): Collection
    {
        return Video::query()
            ->published()
            ->withCount('views')
            ->orderByDesc('views_count')
            ->limit($limit)
            ->get();
    }

    public function totalViews(): int
    {
        return VideoView::query()->count();
    }

    public function publishedVideos(): int
    {
        return Video::query()->published()->count();
    }
}
