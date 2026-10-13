<?php

namespace Database\Factories;

use App\Enums\SubscriptionStatus;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Subscription>
 */
class SubscriptionFactory extends Factory
{
    protected $model = Subscription::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'status' => SubscriptionStatus::Authorized,
            'mp_preapproval_id' => null,
            'started_at' => now(),
            'current_period_end' => now()->addMonth(),
            'trial_ends_at' => null,
            'cancelled_at' => null,
        ];
    }
}
