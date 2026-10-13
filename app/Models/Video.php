<?php

namespace App\Models;

use App\Enums\VideoStatus;
use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'category_id',
    'min_plan_level',
    'title',
    'slug',
    'description',
    'bunny_video_id',
    'duration_seconds',
    'thumbnail_url',
    'status',
    'published_at',
])]
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'min_plan_level' => 'integer',
            'duration_seconds' => 'integer',
            'status' => VideoStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }

    public function views(): HasMany
    {
        return $this->hasMany(VideoView::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(VideoProgress::class);
    }

    /** Solo videos publicados (visibles para el catálogo). */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', VideoStatus::Published);
    }

    /**
     * Videos que un plan de nivel $level puede ver
     * (nivel mínimo del video <= nivel del plan).
     */
    public function scopeVisibleForLevel(Builder $query, int $level): Builder
    {
        return $query->where('min_plan_level', '<=', $level);
    }

    public function isPublished(): bool
    {
        return $this->status === VideoStatus::Published;
    }

    /**
     * Duración en formato "m:ss" o "h:mm:ss".
     */
    public function durationFormatted(): string
    {
        $seconds = (int) $this->duration_seconds;
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $secs = $seconds % 60;

        return $hours > 0
            ? sprintf('%d:%02d:%02d', $hours, $minutes, $secs)
            : sprintf('%d:%02d', $minutes, $secs);
    }
}
