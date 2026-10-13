<?php

namespace App\Enums;

/**
 * Ciclo de vida de un video.
 *
 * - Draft: creado pero aún no visible.
 * - Processing: subido a Bunny, esperando transcodificación/metadatos.
 * - Published: visible para los suscriptores cuyo plan alcanza el nivel mínimo.
 */
enum VideoStatus: string
{
    case Draft = 'draft';
    case Processing = 'processing';
    case Published = 'published';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Processing => 'Procesando',
            self::Published => 'Publicado',
        };
    }
}
