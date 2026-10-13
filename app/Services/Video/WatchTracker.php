<?php

namespace App\Services\Video;

use App\Models\User;
use App\Models\Video;
use App\Models\VideoProgress;
use App\Models\VideoView;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Registra y consulta lo que cada usuario ha visto.
 *
 * Cuando se conecte Bunny Stream, el avance real (minuto a minuto) llegará desde
 * el reproductor; hasta entonces el progreso se marca desde la ficha del video.
 */
class WatchTracker
{
    /**
     * Registra una visualización al abrir un video autorizado.
     * Se limita a una por hora y usuario/video para no inflar el contador
     * cuando alguien recarga la página.
     */
    public function recordView(User $user, Video $video, ?string $ip = null): void
    {
        $alreadyCounted = VideoView::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->where('created_at', '>=', now()->subHour())
            ->exists();

        if (! $alreadyCounted) {
            VideoView::create([
                'user_id' => $user->id,
                'video_id' => $video->id,
                'watched_seconds' => 0,
                'ip' => $ip,
            ]);
        }

        // Empezar a ver = aparece en "continuar viendo".
        VideoProgress::firstOrCreate(
            ['user_id' => $user->id, 'video_id' => $video->id],
            ['position_seconds' => 0],
        );
    }

    public function markCompleted(User $user, Video $video): void
    {
        VideoProgress::updateOrCreate(
            ['user_id' => $user->id, 'video_id' => $video->id],
            [
                'position_seconds' => (int) ($video->duration_seconds ?? 0),
                'completed_at' => now(),
            ],
        );
    }

    public function markPending(User $user, Video $video): void
    {
        VideoProgress::updateOrCreate(
            ['user_id' => $user->id, 'video_id' => $video->id],
            ['position_seconds' => 0, 'completed_at' => null],
        );
    }

    public function isCompleted(User $user, Video $video): bool
    {
        return VideoProgress::query()
            ->where('user_id', $user->id)
            ->where('video_id', $video->id)
            ->whereNotNull('completed_at')
            ->exists();
    }

    /**
     * Videos empezados y no terminados, del más reciente al más antiguo.
     *
     * @return Collection<int, VideoProgress>
     */
    public function continueWatching(User $user, int $limit = 4): Collection
    {
        return VideoProgress::query()
            ->with('video.category')
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            // Sin esto el dashboard ofrecía "continuar" un video que el plan
            // actual ya no abre: al hacer clic saltaba el paywall.
            ->whereHas('video', fn (Builder $query) => $this->restrictToPlan($query, $user))
            ->latest('updated_at')
            ->limit($limit)
            ->get()
            ->filter(fn (VideoProgress $progress) => $progress->video !== null)
            ->values();
    }

    /**
     * Historial de reproducciones.
     *
     * @return Collection<int, VideoView>
     */
    public function history(User $user, int $limit = 8): Collection
    {
        return VideoView::query()
            ->with('video')
            ->where('user_id', $user->id)
            ->latest('created_at')
            ->limit($limit)
            ->get()
            ->filter(fn (VideoView $view) => $view->video !== null)
            ->values();
    }

    /**
     * Ids de videos que el usuario ya abrió (para marcar en el catálogo).
     *
     * @return array<int, int>
     */
    public function watchedVideoIds(User $user): array
    {
        return VideoProgress::query()
            ->where('user_id', $user->id)
            ->pluck('video_id')
            ->all();
    }

    /**
     * Resumen de progreso para el dashboard.
     *
     * @return array{completed: int, in_progress: int, available: int, percent: int}
     */
    public function summary(User $user): array
    {
        $level = $user->planLevel();

        $available = $level > 0
            ? Video::query()->published()->visibleForLevel($level)->count()
            : 0;

        $completed = VideoProgress::query()
            ->where('user_id', $user->id)
            ->whereNotNull('completed_at')
            ->count();

        $inProgress = VideoProgress::query()
            ->where('user_id', $user->id)
            ->whereNull('completed_at')
            ->whereHas('video', fn (Builder $query) => $this->restrictToPlan($query, $user))
            ->count();

        return [
            'completed' => $completed,
            'in_progress' => $inProgress,
            'available' => max($available, $completed),
            'percent' => $available > 0 ? (int) round($completed / $available * 100) : 0,
        ];
    }

    /**
     * Limita una consulta de videos al contenido que el plan del usuario abre.
     *
     * Los admins ven todo lo publicado (no dependen del nivel, igual que en
     * User::canAccessLevel), así su panel no se queda vacío.
     */
    private function restrictToPlan(Builder $query, User $user): void
    {
        $query->published();

        if (! $user->isAdmin()) {
            $query->visibleForLevel($user->planLevel());
        }
    }
}
