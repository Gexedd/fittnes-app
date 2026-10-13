<?php

namespace App\Enums;

/**
 * Roles disponibles en la plataforma.
 *
 * - Admin: el entrenador. Gestiona videos, planes y usuarios.
 * - Subscriber: cliente que paga una suscripción para ver videos.
 */
enum UserRole: string
{
    case Admin = 'admin';
    case Subscriber = 'subscriber';

    /**
     * Etiqueta legible para mostrar en la interfaz.
     */
    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Administrador',
            self::Subscriber => 'Suscriptor',
        };
    }
}
