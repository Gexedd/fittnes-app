<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Recordatorio previo a la renovación automática.
 */
class RenewalReminder extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Subscription $subscription,
        public readonly int $days = 3,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(User $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Tu suscripción se renueva en {$this->days} día(s)")
            ->view('mail.renewal-reminder', [
                'user' => $notifiable,
                'plan' => $this->subscription->plan,
                'subscription' => $this->subscription,
                'days' => $this->days,
            ]);
    }
}
