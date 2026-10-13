<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de cancelación de la suscripción.
 */
class SubscriptionCancelled extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Subscription $subscription,
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
            ->subject('Tu suscripción fue cancelada')
            ->view('mail.subscription-cancelled', [
                'user' => $notifiable,
                'plan' => $this->subscription->plan,
            ]);
    }
}
