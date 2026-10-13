<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Video;

/**
 * Autorización de videos. Laravel descubre esta Policy automáticamente
 * por la convención App\Models\Video -> App\Policies\VideoPolicy.
 */
class VideoPolicy
{
    /**
     * ¿Puede el usuario reproducir este video?
     * Requiere que esté publicado y que su plan alcance el nivel mínimo.
     */
    public function view(User $user, Video $video): bool
    {
        return $video->isPublished() && $user->canAccessLevel($video->min_plan_level);
    }

    /**
     * El entrenador (admin) puede ver cualquier video, publicado o no.
     */
    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Video $video): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Video $video): bool
    {
        return $user->isAdmin();
    }
}
