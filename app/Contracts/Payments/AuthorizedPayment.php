<?php

namespace App\Contracts\Payments;

use Carbon\CarbonInterface;

/**
 * Un cobro concreto generado por una suscripción: la "factura" del período.
 *
 * Mercado Pago lo llama authorized_payment. Se consulta por su id cuando llega
 * el webhook subscription_authorized_payment.
 */
final readonly class AuthorizedPayment
{
    public function __construct(
        public string $id,
        public string $preapprovalId,
        public int $amountClp,
        public bool $approved,
        public ?CarbonInterface $paidAt = null,
    ) {}
}
