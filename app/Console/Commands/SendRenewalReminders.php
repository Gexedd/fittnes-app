<?php

namespace App\Console\Commands;

use App\Enums\SubscriptionStatus;
use App\Models\Subscription;
use App\Notifications\RenewalReminder;
use Illuminate\Console\Command;

class SendRenewalReminders extends Command
{
    protected $signature = 'subscriptions:renewal-reminders {--days=3 : Con cuántos días de anticipación avisar}';

    protected $description = 'Envía un recordatorio a los suscriptores cuyo período vence en N días';

    public function handle(): int
    {
        $days = (int) $this->option('days');
        $targetDate = now()->addDays($days)->toDateString();

        $sent = 0;

        Subscription::query()
            ->where('status', SubscriptionStatus::Authorized)
            ->whereDate('current_period_end', $targetDate)
            ->with(['user', 'plan'])
            ->each(function (Subscription $subscription) use ($days, &$sent): void {
                $subscription->user->notify(new RenewalReminder($subscription, $days));
                $sent++;
            });

        $this->info("Recordatorios de renovación enviados: {$sent}");

        return self::SUCCESS;
    }
}
