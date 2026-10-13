<?php

namespace App\Services\Video;

use App\Contracts\Video\VideoPlayback;
use App\Models\Video;

/**
 * Driver por defecto mientras no hay credenciales de Bunny: no genera URL y
 * la vista muestra un marcador ("reproductor listo").
 */
class NullPlayback implements VideoPlayback
{
    public function provider(): string
    {
        return 'null';
    }

    public function signedUrl(Video $video, int $ttlMinutes = 240): ?string
    {
        return null;
    }
}
