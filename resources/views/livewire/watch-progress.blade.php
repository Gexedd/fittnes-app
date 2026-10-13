<?php

use App\Models\Video;
use App\Services\Video\WatchTracker;
use Livewire\Volt\Component;

new class extends Component
{
    public Video $video;

    public bool $completed = false;

    public function mount(): void
    {
        $this->completed = app(WatchTracker::class)->isCompleted(auth()->user(), $this->video);
    }

    public function toggle(): void
    {
        $tracker = app(WatchTracker::class);
        $user = auth()->user();

        $this->completed
            ? $tracker->markPending($user, $this->video)
            : $tracker->markCompleted($user, $this->video);

        $this->completed = ! $this->completed;

        $this->dispatch('progress-updated');
    }
}; ?>

<div>
    @if ($completed)
        <div class="rounded-xl border border-volt/40 bg-volt/10 px-4 py-3 text-center">
            <p class="text-sm font-semibold text-volt">✓ Ya viste este video</p>
            <button type="button" wire:click="toggle"
                    class="mt-2 text-xs text-slate-400 underline transition hover:text-slate-200">
                Marcar como pendiente
            </button>
        </div>
    @else
        <button type="button" wire:click="toggle" class="btn-primary w-full text-sm">
            Marcar como visto
        </button>
    @endif
</div>
