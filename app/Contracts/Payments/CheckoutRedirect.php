<?php

namespace App\Contracts\Payments;

/**
 * Resultado de iniciar una suscripción: a dónde mandar al usuario y
 * con qué identificador quedó registrada en el proveedor.
 */
final readonly class CheckoutRedirect
{
    public function __construct(
        public string $preapprovalId,
        public string $url,
    ) {}
}
