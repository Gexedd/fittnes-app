<?php

namespace App\Contracts\Video;

use App\Models\Video;

/**
 * Contrato de reproducción de video.
 *
 * Igual que PaymentGateway: la aplicación solo conoce esta interfaz. Hoy existe
 * un driver "null" (sin credenciales: la vista muestra un marcador) y el driver
 * real de Bunny Stream con URLs firmadas. Cambiar de proveedor = cambiar el
 * binding en AppServiceProvider.
 */
interface VideoPlayback
{
    public function provider(): string;

    /**
     * URL de reproducción firmada y con expiración.
     * Devuelve null si el proveedor no está configurado todavía.
     */
    public function signedUrl(Video $video, int $ttlMinutes = 240): ?string;
}
