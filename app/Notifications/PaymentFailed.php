<?php

namespace App\Notifications;

use App\Models\Subscription;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Aviso de cobro rechazado (dunning): el acceso queda pausado hasta que el
 * cliente regularice el pago.
 */
class PaymentFailed extends Notification implements ShouldQueue
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
            ->subject('No pudimos cobrar tu suscripción')
            ->view('mail.payment-failed', [
                'user' => $notifiable,
                'plan' => $this->subscription->plan,
            ]);
    }
}
