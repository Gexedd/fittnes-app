<?php

use App\Enums\SubscriptionStatus;
use App\Models\Payment;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use App\Models\Video;
use App\Models\VideoView;
use App\Services\Metrics\MetricsService;

it('bloquea las métricas a un suscriptor', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.metrics.index'))->assertForbidden();
});

it('muestra el panel de métricas al administrador', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.metrics.index'))
        ->assertOk()
        ->assertSee('MRR')
        ->assertSee('Suscriptores por plan')
        ->assertSee('Videos más vistos');
});

it('suma el MRR solo con las suscripciones activas', function () {
    $basic = Plan::factory()->create();               // $9.990
    $premium = Plan::factory()->premium()->create();  // $24.990

    Subscription::factory()->create(['plan_id' => $basic->id]);
    Subscription::factory()->create(['plan_id' => $premium->id]);
    Subscription::factory()->create([
        'plan_id' => $premium->id,
        'status' => SubscriptionStatus::Cancelled,
    ]);

    expect(app(MetricsService::class)->mrr())->toBe(9990 + 24990)
        ->and(app(MetricsService::class)->activeSubscribers())->toBe(2);
});

it('agrupa los suscriptores activos por plan', function () {
    $basic = Plan::factory()->create();
    $premium = Plan::factory()->premium()->create();

    Subscription::factory()->count(3)->create(['plan_id' => $basic->id]);
    Subscription::factory()->create(['plan_id' => $premium->id]);

    $byPlan = app(MetricsService::class)->subscribersByPlan()->keyBy('slug');

    expect($byPlan['basico']->active_count)->toBe(3)
        ->and($byPlan['premium']->active_count)->toBe(1);
});

it('cuenta altas y bajas del mes', function () {
    $plan = Plan::factory()->create();

    Subscription::factory()->count(2)->create(['plan_id' => $plan->id, 'started_at' => now()]);
    Subscription::factory()->create(['plan_id' => $plan->id, 'started_at' => now()->subMonths(2)]);
    Subscription::factory()->create([
        'plan_id' => $plan->id,
        'status' => SubscriptionStatus::Cancelled,
        'cancelled_at' => now(),
    ]);

    $metrics = app(MetricsService::class);

    expect($metrics->newSubscriptionsThisMonth())->toBe(2)
        ->and($metrics->cancellationsThisMonth())->toBe(1);
});

it('suma los ingresos del mes desde los pagos', function () {
    $user = User::factory()->create();

    Payment::create([
        'user_id' => $user->id,
        'mp_payment_id' => 'pay-1',
        'amount_clp' => 14990,
        'status' => 'approved',
        'paid_at' => now(),
    ]);

    Payment::create([
        'user_id' => $user->id,
        'mp_payment_id' => 'pay-2',
        'amount_clp' => 9990,
        'status' => 'approved',
        'paid_at' => now()->subMonths(2),
    ]);

    expect(app(MetricsService::class)->revenueThisMonth())->toBe(14990);
});

it('ordena los videos más vistos', function () {
    $popular = Video::factory()->create(['title' => 'El más visto']);
    $other = Video::factory()->create(['title' => 'Menos visto']);

    foreach (range(1, 3) as $i) {
        VideoView::create([
            'user_id' => User::factory()->create()->id,
            'video_id' => $popular->id,
            'watched_seconds' => 120,
        ]);
    }

    VideoView::create([
        'user_id' => User::factory()->create()->id,
        'video_id' => $other->id,
        'watched_seconds' => 60,
    ]);

    $top = app(MetricsService::class)->topVideos();

    expect($top->first()->id)->toBe($popular->id)
        ->and($top->first()->views_count)->toBe(3)
        ->and(app(MetricsService::class)->totalViews())->toBe(4);
});
