<?php

use App\Models\Category;
use App\Models\Tag;
use App\Models\Video;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('layouts.public')] class extends Component
{
    use WithPagination;

    // Los filtros quedan en la URL (?q=...&categoria=...), así el enlace se comparte.
    #[Url(as: 'q', history: true)]
    public string $search = '';

    #[Url(as: 'categoria', history: true)]
    public string $category = '';

    #[Url(as: 'etiqueta', history: true)]
    public string $tag = '';

    /**
     * Al cambiar cualquier filtro se vuelve a la primera página.
     */
    public function updating(string $property, mixed $value = null): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'category', 'tag');
        $this->resetPage();
    }

    public function with(): array
    {
        return [
            'videos' => Video::query()
                ->published()
                ->with(['category', 'tags'])
                ->when($this->search !== '', fn ($q) => $q->where('title', 'like', '%'.$this->search.'%'))
                ->when($this->category !== '', fn ($q) => $q->whereHas(
                    'category',
                    fn ($c) => $c->where('slug', $this->category),
                ))
                ->when($this->tag !== '', fn ($q) => $q->whereHas(
                    'tags',
                    fn ($t) => $t->where('slug', $this->tag),
                ))
                ->latest('published_at')
                ->paginate(9),
            'categories' => Category::query()->orderBy('name')->get(),
            'tags' => Tag::query()->orderBy('name')->get(),
        ];
    }
}; ?>

<div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
    <header>
        <p class="eyebrow">Catálogo</p>
        <h1 class="mt-3 text-4xl font-extrabold uppercase tracking-tight text-white sm:text-5xl">
            Entrenamientos
        </h1>
        <p class="mt-4 max-w-2xl text-lg text-slate-400">
            Explora los videos disponibles. Lo que incluye tu plan se reproduce al instante;
            el resto se desbloquea al mejorar la suscripción.
        </p>
    </header>

    {{-- Filtros --}}
    <div class="mt-10 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        <input type="search" wire:model.live.debounce.300ms="search" placeholder="Buscar por título..."
               class="w-full rounded-lg border-ink-line bg-ink-soft text-slate-100 placeholder-slate-500 focus:border-ember-500 focus:ring-ember-500" />

        <select wire:model.live="category"
                class="w-full rounded-lg border-ink-line bg-ink-soft text-slate-100 focus:border-ember-500 focus:ring-ember-500">
            <option value="">Todas las categorías</option>
            @foreach ($categories as $item)
                <option value="{{ $item->slug }}">{{ $item->name }}</option>
            @endforeach
        </select>

        <select wire:model.live="tag"
                class="w-full rounded-lg border-ink-line bg-ink-soft text-slate-100 focus:border-ember-500 focus:ring-ember-500">
            <option value="">Todas las etiquetas</option>
            @foreach ($tags as $item)
                <option value="{{ $item->slug }}">{{ $item->name }}</option>
            @endforeach
        </select>

        <button type="button" wire:click="clearFilters" class="btn-ghost">
            Limpiar filtros
        </button>
    </div>

    {{-- Grilla --}}
    @if ($videos->isEmpty())
        <div class="mt-12 card text-center">
            <p class="font-display text-2xl font-bold uppercase text-slate-300">Sin resultados</p>
            <p class="mt-2 text-sm text-slate-500">Prueba con otros filtros o vuelve más tarde.</p>
        </div>
    @else
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($videos as $video)
                @php($unlocked = auth()->check() && auth()->user()->canAccessLevel($video->min_plan_level))

                <a href="{{ route('videos.show', $video) }}"
                   wire:navigate
                   wire:key="video-{{ $video->id }}"
                   class="group card overflow-hidden p-0 transition hover:border-ember-500/60">
                    <div class="relative aspect-video bg-gradient-to-br from-ink-soft to-ink">
                        @if ($video->thumbnail_url)
                            <img src="{{ $video->thumbnail_url }}" alt="" class="h-full w-full object-cover" />
                        @else
                            <div class="grid h-full w-full place-items-center">
                                <span class="font-display text-4xl font-extrabold uppercase text-ink-line">Fitnes</span>
                            </div>
                        @endif

                        <span class="absolute left-3 top-3 rounded-md bg-ink/85 px-2 py-1 text-xs font-bold text-volt">
                            Nivel {{ $video->min_plan_level }}
                        </span>

                        @unless ($unlocked)
                            <span class="absolute right-3 top-3 rounded-md bg-ember-500 px-2 py-1 text-xs font-bold text-white">
                                🔒 Bloqueado
                            </span>
                        @endunless

                        @if ($video->duration_seconds)
                            <span class="absolute bottom-3 right-3 rounded bg-ink/85 px-2 py-1 text-xs text-slate-300">
                                {{ $video->durationFormatted() }}
                            </span>
                        @endif
                    </div>

                    <div class="p-5">
                        <p class="text-xs uppercase tracking-wider text-slate-500">
                            {{ $video->category?->name ?? 'General' }}
                        </p>
                        <h2 class="mt-1 font-display text-xl font-bold uppercase text-white transition group-hover:text-volt">
                            {{ $video->title }}
                        </h2>
                    </div>
                </a>
            @endforeach
        </div>

        @if ($videos->hasPages())
            <nav class="mt-10 flex items-center justify-center gap-3" aria-label="Paginación del catálogo">
                <button type="button" wire:click="previousPage" @disabled($videos->onFirstPage())
                        class="btn-ghost !px-4 !py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40">
                    Anterior
                </button>
                <span class="text-sm text-slate-500">
                    Página {{ $videos->currentPage() }} de {{ $videos->lastPage() }}
                </span>
                <button type="button" wire:click="nextPage" @disabled(! $videos->hasMorePages())
                        class="btn-ghost !px-4 !py-2 text-sm disabled:cursor-not-allowed disabled:opacity-40">
                    Siguiente
                </button>
            </nav>
        @endif
    @endif
</div>
