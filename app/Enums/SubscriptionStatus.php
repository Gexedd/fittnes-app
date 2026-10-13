<?php

namespace App\Enums;

/**
 * Estados de una suscripción, alineados con los estados de
 * Mercado Pago (preapproval): pending -> authorized -> paused/cancelled.
 */
enum SubscriptionStatus: string
{
    case Pending = 'pending';
    case Authorized = 'authorized';
    case Paused = 'paused';
    case Cancelled = 'cancelled';

    /**
     * ¿La suscripción da acceso al catálogo?
     */
    public function isActive(): bool
    {
        return $this === self::Authorized;
    }

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pendiente',
            self::Authorized => 'Activa',
            self::Paused => 'Pausada',
            self::Cancelled => 'Cancelada',
        };
    }
}
