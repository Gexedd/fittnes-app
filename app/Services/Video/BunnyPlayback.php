<?php

namespace App\Services\Video;

use App\Contracts\Video\VideoPlayback;
use App\Models\Video;

/**
 * Reproducción protegida con Bunny Stream (token authentication).
 *
 * Bunny exige un token derivado de la clave secreta de la biblioteca:
 *   token = base64url( sha256( token_key + path + expires ) )   [binario]
 * El path es /{videoId}/ y expires es un timestamp UNIX.
 * El enlace caduca: si alguien lo comparte, deja de funcionar.
 *
 * Docs: https://docs.bunny.net/docs/stream-token-authentication
 */
class BunnyPlayback implements VideoPlayback
{
    public function __construct(
        private readonly string $libraryId,
        private readonly string $tokenKey,
        private readonly string $cdnHostname = '',
    ) {}

    public function provider(): string
    {
        return 'bunny';
    }

    public function signedUrl(Video $video, int $ttlMinutes = 240): ?string
    {
        if ($this->libraryId === '' || $this->tokenKey === '') {
            return null;
        }

        $path = '/'.$video->bunny_video_id.'/';
        $expires = now()->addMinutes($ttlMinutes)->timestamp;
        $token = $this->token($path, $expires);

        // Si hay hostname de CDN, se usa el HLS directo; si no, el reproductor
        // embebido de Bunny (que también soporta token authentication).
        if ($this->cdnHostname !== '') {
            return sprintf(
                'https://%s/%s/playlist.m3u8?token=%s&expires=%d',
                $this->cdnHostname,
                $video->bunny_video_id,
                $token,
                $expires,
            );
        }

        return sprintf(
            'https://iframe.mediadelivery.net/embed/%s/%s?token=%s&expires=%d&autoplay=false',
            $this->libraryId,
            $video->bunny_video_id,
            $token,
            $expires,
        );
    }

    /**
     * Token base64url de Bunny (sin relleno).
     */
    public function token(string $path, int $expires): string
    {
        $hash = hash('sha256', $this->tokenKey.$path.$expires, binary: true);

        return rtrim(strtr(base64_encode($hash), '+/', '-_'), '=');
    }
}
