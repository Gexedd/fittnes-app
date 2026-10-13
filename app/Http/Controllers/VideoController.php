<?php

namespace App\Http\Controllers;

use App\Contracts\Video\VideoPlayback;
use App\Models\Plan;
use App\Models\Video;
use Illuminate\Http\Request;
use Illuminate\View\View;

class VideoController
{
    /**
     * Ficha de un video.
     *
     * La página SÍ es visible para cualquier suscriptor (título, descripción,
     * categoría...). Lo que se protege es la reproducción: la URL firmada solo
     * se genera si la Policy lo autoriza.
     */
    public function show(Request $request, Video $video, VideoPlayback $playback): View
    {
        abort_unless($video->isPublished(), 404);

        $video->load(['category', 'tags']);

        $canWatch = $request->user()->can('view', $video);

        return view('videos.show', [
            'video' => $video,
            'canWatch' => $canWatch,
            // Sin autorización NO se genera la URL: ese es el control real.
            'playbackUrl' => $canWatch ? $playback->signedUrl($video) : null,
            'requiredPlan' => Plan::query()
                ->where('level', $video->min_plan_level)
                ->first(),
        ]);
    }
}
