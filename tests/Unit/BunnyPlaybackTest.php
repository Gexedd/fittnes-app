<?php

use App\Models\Video;
use App\Services\Video\BunnyPlayback;
use Tests\TestCase;

// Se necesita el contenedor de Laravel (helper now(), casts de Eloquent...).
uses(TestCase::class);

it('genera una URL firmada de Bunny con expiración', function () {
    $playback = new BunnyPlayback('12345', 'clave-secreta');
    $video = new Video(['bunny_video_id' => 'abc-123']);

    $url = $playback->signedUrl($video, ttlMinutes: 60);

    expect($url)
        ->toContain('iframe.mediadelivery.net/embed/12345/abc-123')
        ->toContain('token=')
        ->toContain('expires=');
});

it('el token depende del path y de la expiración', function () {
    $playback = new BunnyPlayback('1', 'clave');

    expect($playback->token('/a/', 100))->not->toBe($playback->token('/b/', 100))
        ->and($playback->token('/a/', 100))->not->toBe($playback->token('/a/', 200));
});

it('usa el HLS del CDN cuando hay hostname configurado', function () {
    $playback = new BunnyPlayback('1', 'clave', 'mi-zona.b-cdn.net');
    $video = new Video(['bunny_video_id' => 'xyz']);

    expect($playback->signedUrl($video))
        ->toContain('mi-zona.b-cdn.net/xyz/playlist.m3u8?token=');
});

it('devuelve null cuando falta la configuración', function () {
    $playback = new BunnyPlayback('', '');
    $video = new Video(['bunny_video_id' => 'abc']);

    expect($playback->signedUrl($video))->toBeNull();
});
