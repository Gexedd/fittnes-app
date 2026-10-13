<?php

namespace App\Http\Controllers;

use App\Models\Subscription;
use App\Models\WebhookEvent;
use App\Services\Subscriptions\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Recibe las notificaciones (webhooks) de Mercado Pago.
 *
 * Dos garantías importantes:
 *  1. Firma: se valida x-signature (HMAC SHA256) para descartar peticiones falsas.
 *  2. Idempotencia: Mercado Pago puede reenviar el mismo evento; se registra
 *     cada uno en webhook_events y se ignora si ya fue procesado.
 */
class PaymentWebhookController
{
    private const PROVIDER = 'mercadopago';

    public function __construct(
        private readonly SubscriptionService $subscriptions,
    ) {}

    public function handle(Request $request): Response
    {
        if (! $this->hasValidSignature($request)) {
            // Se registra para poder distinguir "no me llegó" de "me llegó y la
            // rechacé": sin esto, un 401 es invisible al depurar.
            Log::warning('Webhook con firma invalida', [
                'ip' => $request->ip(),
                'type' => $request->input('type'),
            ]);

            abort(401);
        }

        $eventId = (string) ($request->input('id') ?? $request->header('x-request-id') ?? '');

        if ($eventId === '') {
            return $this->acknowledge();
        }

        $event = WebhookEvent::firstOrCreate(
            ['provider' => self::PROVIDER, 'event_id' => $eventId],
            ['payload' => $request->all()],
        );

        // Ya procesado antes: no se vuelve a aplicar.
        if ($event->isProcessed()) {
            return $this->acknowledge();
        }

        $type = (string) $request->input('type', '');
        $preapprovalId = (string) $request->input('data.id', '');

        if ($preapprovalId !== '' && in_array($type, ['subscription_preapproval', 'preapproval'], true)) {
            $this->syncPreapproval($preapprovalId);
        }

        $event->update(['processed_at' => now()]);

        return $this->acknowledge();
    }

    /**
     * Respuesta al webhook. Mercado Pago documenta como válidos 200 o 201, y
     * reintenta cada 15 minutos si no la recibe en 22 segundos.
     *
     * Se devuelve 200 sin cuerpo en lugar de 204 para ajustarse a lo
     * documentado, aunque el simulador acepte las dos cosas.
     */
    private function acknowledge(): Response
    {
        return response()->noContent(200);
    }

    /**
     * Reconsulta el estado real al proveedor (no se confía en el payload) y lo aplica.
     */
    private function syncPreapproval(string $preapprovalId): void
    {
        $subscription = Subscription::query()
            ->where('mp_preapproval_id', $preapprovalId)
            ->first();

        if (! $subscription) {
            return;
        }

        $this->subscriptions->applyStatus(
            $subscription,
            $this->subscriptions->statusFromProvider($preapprovalId),
        );
    }

    /**
     * Valida el encabezado x-signature de Mercado Pago.
     *
     * Formato:  x-signature: ts=1704908010,v1=<hmac>
     * Manifiesto: id:<data.id>;request-id:<x-request-id>;ts:<ts>;
     * HMAC-SHA256 en hexadecimal usando el secreto del webhook.
     */
    private function hasValidSignature(Request $request): bool
    {
        $secret = (string) config('services.mercadopago.webhook_secret');
        $signature = (string) $request->header('x-signature', '');
        $requestId = (string) $request->header('x-request-id', '');

        // Sin secreto configurado solo se permite en local/testing.
        if ($secret === '') {
            return app()->environment(['local', 'testing']);
        }

        if ($signature === '') {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signature) as $chunk) {
            [$key, $value] = array_pad(explode('=', trim($chunk), 2), 2, null);
            $parts[$key] = $value;
        }

        $timestamp = $parts['ts'] ?? null;
        $hash = $parts['v1'] ?? null;

        if ($timestamp === null || $hash === null) {
            return false;
        }

        // Mercado Pago exige el id en minúsculas dentro del manifiesto.
        $dataId = strtolower((string) $request->input('data.id', ''));

        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $expected = hash_hmac('sha256', $manifest, $secret);

        return hash_equals($expected, $hash);
    }
}
